<script setup lang="ts">
import { Head, Link, useHttp, usePage } from '@inertiajs/vue3';
import { useConnectionStatus, useEcho } from '@laravel/echo-vue';
import { Camera as CameraIcon } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import CameraRail from '@/components/CameraRail.vue';
import ConnectionBanner from '@/components/ConnectionBanner.vue';
import DashboardAlertFeed from '@/components/DashboardAlertFeed.vue';
import DashboardMap from '@/components/DashboardMap.vue';
import DashboardTopNav from '@/components/DashboardTopNav.vue';
import StatusBar from '@/components/StatusBar.vue';
import { Button } from '@/components/ui/button';
import { useAlertSound } from '@/composables/useAlertSound';
import { useAppearance } from '@/composables/useAppearance';
import type { DashboardCamera } from '@/composables/useDashboardMap';
import {
    acknowledge as acknowledgeRoute,
    dismiss as dismissRoute,
} from '@/routes/alerts';
import { create as camerasCreate } from '@/routes/cameras';
import type {
    CameraStatusPayload,
    RecognitionAlertPayload,
    RecognitionEvent,
} from '@/types';

interface TodayStats {
    recognitions: number;
    critical: number;
    warnings: number;
    enrolled: number;
}

interface MapboxConfig {
    token: string;
    darkStyle: string;
    lightStyle: string;
}

type Props = {
    cameras: DashboardCamera[];
    todayStats: TodayStats;
    recentEvents: RecognitionEvent[];
    mapbox: MapboxConfig;
};

const props = defineProps<Props>();

const page = usePage();

const leftRailOpen = ref(true);
const rightFeedOpen = ref(true);
const queueDepth = ref(0);
const selectedCameraId = ref<number | null>(null);

// Local reactive copies for real-time updates
const cameras = ref<DashboardCamera[]>([...props.cameras]);
const alerts = ref<RecognitionEvent[]>([...props.recentEvents]);
const todayStats = ref<TodayStats>({ ...props.todayStats });

// Highlight animation tracking
const highlightedAlertId = ref<number | null>(null);

// Connection status
const connectionStatus = useConnectionStatus();
const isReverbConnected = computed(
    () => connectionStatus.value === 'connected',
);
const isMqttConnected = isReverbConnected;

// Map ref
const mapRef = ref<InstanceType<typeof DashboardMap> | null>(null);

// Theme -> map style
const { resolvedAppearance } = useAppearance();
const currentMapStyle = computed(() =>
    resolvedAppearance.value === 'dark'
        ? props.mapbox.darkStyle
        : props.mapbox.lightStyle,
);

watch(currentMapStyle, (newStyle) => {
    mapRef.value?.switchStyle(newStyle);
});

// Panel toggle -> map resize
watch([leftRailOpen, rightFeedOpen], () => {
    setTimeout(() => mapRef.value?.resizeMap(), 250);
});

// Alert sound
const {
    isEnabled: soundEnabled,
    enable: enableSound,
    disable: disableSound,
    play: playAlertSound,
} = useAlertSound();

function toggleSound(): void {
    if (soundEnabled.value) {
        disableSound();
    } else {
        enableSound();
    }
}

// Map broadcast payload to RecognitionEvent shape
function mapPayloadToEvent(payload: RecognitionAlertPayload): RecognitionEvent {
    return {
        id: payload.id,
        camera_id: payload.camera_id,
        personnel_id: payload.personnel_id,
        severity: payload.severity,
        similarity: payload.similarity,
        person_type: payload.person_type,
        face_image_url: payload.face_image_url,
        scene_image_url: payload.scene_image_url,
        target_bbox: payload.target_bbox,
        captured_at: payload.captured_at,
        created_at: payload.created_at,
        custom_id: payload.custom_id ?? null,
        camera_person_id: null,
        record_id: 0,
        verify_status: 0,
        is_real_time: true,
        name_from_camera: payload.person_name,
        updated_at: payload.created_at,
        acknowledged_by: null,
        acknowledged_at: null,
        dismissed_at: null,
        camera: {
            id: payload.camera_id,
            name: payload.camera_name,
        },
        personnel: payload.personnel_id
            ? {
                  id: payload.personnel_id,
                  name: payload.person_name ?? 'Unknown',
                  custom_id: payload.custom_id ?? '',
                  person_type: payload.person_type,
                  photo_url: null,
              }
            : null,
    };
}

// Echo listener for RecognitionAlert
useEcho(
    'fras.alerts',
    '.RecognitionAlert',
    (payload: RecognitionAlertPayload) => {
        // Map payload to event and prepend to alerts
        const event = mapPayloadToEvent(payload);
        alerts.value.unshift(event);

        if (alerts.value.length > 50) {
            alerts.value = alerts.value.slice(0, 50);
        }

        // Update today stats
        todayStats.value.recognitions++;

        if (payload.severity === 'critical') {
            todayStats.value.critical++;
        }

        if (payload.severity === 'warning') {
            todayStats.value.warnings++;
        }

        // Trigger pulse ring on camera marker
        mapRef.value?.triggerPulse(payload.camera_id);

        // Update camera recognition count in local state
        const cam = cameras.value.find((c) => c.id === payload.camera_id);

        if (cam) {
            cam.today_recognition_count++;
        }

        // Play sound for critical events
        if (payload.severity === 'critical') {
            playAlertSound();
        }

        // Highlight flash
        highlightedAlertId.value = event.id;
        setTimeout(() => {
            highlightedAlertId.value = null;
        }, 300);
    },
);

// Echo listener for CameraStatusChanged
useEcho(
    'fras.alerts',
    '.CameraStatusChanged',
    (payload: CameraStatusPayload) => {
        const cam = cameras.value.find((c) => c.id === payload.camera_id);

        if (cam) {
            cam.is_online = payload.is_online;
            cam.last_seen_at = payload.last_seen_at;
        }

        mapRef.value?.updateMarkerStatus(
            payload.camera_id,
            payload.is_online,
            payload.last_seen_at,
        );
    },
);

// Camera selection handler
function handleCameraSelect(cameraId: number | null): void {
    selectedCameraId.value = cameraId;

    if (cameraId !== null) {
        mapRef.value?.flyTo(cameraId);
    }
}

// Selected camera name (computed)
const selectedCameraName = computed(() => {
    if (!selectedCameraId.value) {
        return null;
    }

    return (
        cameras.value.find((c) => c.id === selectedCameraId.value)?.name ?? null
    );
});

// Camera click from map marker
function handleCameraClick(cameraId: number): void {
    selectedCameraId.value = cameraId;
}

// Acknowledge/Dismiss via useHttp
const http = useHttp();

function handleAcknowledge(event: RecognitionEvent): void {
    http.submit(acknowledgeRoute.post(event), {
        onSuccess: () => {
            const alert = alerts.value.find((a) => a.id === event.id);

            if (alert) {
                alert.acknowledged_at = new Date().toISOString();
                alert.acknowledged_by = page.props.auth.user.id;
            }
        },
    });
}

function handleDismiss(event: RecognitionEvent): void {
    http.submit(dismissRoute.post(event), {
        onSuccess: () => {
            const alert = alerts.value.find((a) => a.id === event.id);

            if (alert) {
                alert.dismissed_at = new Date().toISOString();
            }
        },
    });
}

// Queue depth polling
let queuePollInterval: ReturnType<typeof setInterval> | null = null;

async function fetchQueueDepth() {
    try {
        const res = await fetch('/api/queue-depth');
        const data = await res.json();
        queueDepth.value = data.depth;
    } catch {
        /* silently ignore */
    }
}

onMounted(() => {
    fetchQueueDepth();
    queuePollInterval = setInterval(fetchQueueDepth, 30000);
});

onUnmounted(() => {
    if (queuePollInterval) {
        clearInterval(queuePollInterval);
    }
});
</script>

<template>
    <Head title="Command Center" />
    <DashboardTopNav
        :left-rail-open="leftRailOpen"
        :right-feed-open="rightFeedOpen"
        :sound-enabled="soundEnabled"
        @toggle-left-rail="leftRailOpen = !leftRailOpen"
        @toggle-right-feed="rightFeedOpen = !rightFeedOpen"
        @toggle-sound="toggleSound"
    />
    <ConnectionBanner :visible="!isReverbConnected" />
    <div class="flex flex-1 overflow-hidden">
        <!-- Left rail: camera list + today stats -->
        <aside
            v-show="leftRailOpen"
            class="w-[280px] shrink-0 overflow-y-auto border-r border-border bg-muted/50 transition-all duration-200"
        >
            <CameraRail
                :cameras="cameras"
                :selected-camera-id="selectedCameraId"
                :today-stats="todayStats"
                @camera-select="handleCameraSelect"
            />
        </aside>
        <!-- Center map or empty state -->
        <main class="flex-1 overflow-hidden">
            <div
                v-if="cameras.length === 0"
                class="flex h-full items-center justify-center"
            >
                <div class="text-center">
                    <CameraIcon
                        class="mx-auto size-16 text-muted-foreground/50"
                    />
                    <h3 class="mt-4 text-lg font-semibold">
                        No cameras registered
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Add your first camera to start monitoring.
                    </p>
                    <Button as-child class="mt-4">
                        <Link :href="camerasCreate()">Add Camera</Link>
                    </Button>
                </div>
            </div>
            <DashboardMap
                v-else
                ref="mapRef"
                :cameras="cameras"
                :access-token="props.mapbox.token"
                :style-url="currentMapStyle"
                @camera-click="handleCameraClick"
            />
        </main>
        <!-- Right alert feed -->
        <aside
            v-show="rightFeedOpen"
            class="w-[360px] shrink-0 overflow-hidden border-l border-border transition-all duration-200"
        >
            <DashboardAlertFeed
                :events="alerts"
                :selected-camera-id="selectedCameraId"
                :selected-camera-name="selectedCameraName"
                :highlighted-alert-id="highlightedAlertId"
                @acknowledge="handleAcknowledge"
                @dismiss="handleDismiss"
                @camera-select="handleCameraSelect"
            />
        </aside>
    </div>
    <StatusBar
        :mqtt-connected="isMqttConnected"
        :reverb-connected="isReverbConnected"
        :queue-depth="queueDepth"
    />
</template>
