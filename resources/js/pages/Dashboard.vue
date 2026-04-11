<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useConnectionStatus, useEcho } from '@laravel/echo-vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ConnectionBanner from '@/components/ConnectionBanner.vue';
import DashboardMap from '@/components/DashboardMap.vue';
import DashboardTopNav from '@/components/DashboardTopNav.vue';
import StatusBar from '@/components/StatusBar.vue';
import { useAlertSound } from '@/composables/useAlertSound';
import { useAppearance } from '@/composables/useAppearance';
import type { DashboardCamera } from '@/composables/useDashboardMap';
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

const leftRailOpen = ref(true);
const rightFeedOpen = ref(true);
const queueDepth = ref(0);
const selectedCameraId = ref<number | null>(null);

// Local reactive copy for real-time updates
const cameras = ref<DashboardCamera[]>([...props.cameras]);

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

// Echo listener for RecognitionAlert
useEcho(
    'fras.alerts',
    '.RecognitionAlert',
    (payload: RecognitionAlertPayload) => {
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

// Camera click handler
function handleCameraClick(cameraId: number): void {
    selectedCameraId.value = cameraId;
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
        <!-- Left rail placeholder (Plan 03 fills this) -->
        <aside
            v-show="leftRailOpen"
            class="w-[280px] shrink-0 overflow-y-auto border-r border-border bg-muted/50 transition-all duration-200"
        >
            <!-- CameraRail will go here in Plan 03 -->
        </aside>
        <!-- Center map -->
        <main class="flex-1 overflow-hidden">
            <DashboardMap
                ref="mapRef"
                :cameras="cameras"
                :access-token="props.mapbox.token"
                :style-url="currentMapStyle"
                @camera-click="handleCameraClick"
            />
        </main>
        <!-- Right alert feed placeholder (Plan 03 fills this) -->
        <aside
            v-show="rightFeedOpen"
            class="w-[360px] shrink-0 overflow-y-auto border-l border-border transition-all duration-200"
        >
            <!-- DashboardAlertFeed will go here in Plan 03 -->
        </aside>
    </div>
    <StatusBar
        :mqtt-connected="isMqttConnected"
        :reverb-connected="isReverbConnected"
        :queue-depth="queueDepth"
    />
</template>
