<script setup lang="ts">
import { Head, useHttp, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { Bell, BellRing, ShieldAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AlertDetailModal from '@/components/AlertDetailModal.vue';
import AlertFeedItem from '@/components/AlertFeedItem.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAlertSound } from '@/composables/useAlertSound';
import {
    acknowledge as acknowledgeRoute,
    dismiss as dismissRoute,
    index,
} from '@/routes/alerts';
import type {
    AlertSeverity,
    RecognitionAlertPayload,
    RecognitionEvent,
} from '@/types';

type Props = {
    events: RecognitionEvent[];
};

const props = defineProps<Props>();

const page = usePage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Live Alerts', href: index() }],
    },
});

// Local reactive copy for real-time updates
const alerts = ref<RecognitionEvent[]>([...props.events]);

// Filter state
const activeFilter = ref<'all' | AlertSeverity>('all');

// Filtered alerts
const filteredAlerts = computed(() => {
    if (activeFilter.value === 'all') {
        return alerts.value;
    }

    return alerts.value.filter((a) => a.severity === activeFilter.value);
});

// Filter counts
const filterCounts = computed(() => ({
    all: alerts.value.length,
    critical: alerts.value.filter((a) => a.severity === 'critical').length,
    warning: alerts.value.filter((a) => a.severity === 'warning').length,
    info: alerts.value.filter((a) => a.severity === 'info').length,
}));

// Alert sound composable
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

// Highlight animation tracking
const highlightedId = ref<number | null>(null);

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
        // Fields not in payload
        custom_id: payload.custom_id ?? null,
        camera_person_id: null,
        record_id: 0,
        verify_status: 0,
        is_real_time: true,
        name_from_camera: payload.person_name,
        updated_at: payload.created_at,
        // New events are never acknowledged or dismissed
        acknowledged_by: null,
        acknowledged_at: null,
        acknowledger_name: null,
        dismissed_at: null,
        // Map flat fields into nested relationship objects
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

// Echo real-time listener
useEcho(
    'fras.alerts',
    '.RecognitionAlert',
    (payload: RecognitionAlertPayload) => {
        const event = mapPayloadToEvent(payload);

        // Prepend to alerts array (D-02)
        alerts.value.unshift(event);

        // Cap at 50 items (D-03)
        if (alerts.value.length > 50) {
            alerts.value = alerts.value.slice(0, 50);
        }

        // Play sound for critical events (D-08)
        if (event.severity === 'critical') {
            playAlertSound();
        }

        // Highlight animation
        highlightedId.value = event.id;
        setTimeout(() => {
            highlightedId.value = null;
        }, 300);
    },
);

// Acknowledge/Dismiss via useHttp
const http = useHttp();

function handleAcknowledge(event: RecognitionEvent): void {
    http.submit(acknowledgeRoute.post(event), {
        onSuccess: () => {
            const alert = alerts.value.find((a) => a.id === event.id);

            if (alert) {
                alert.acknowledged_at = new Date().toISOString();
                alert.acknowledged_by = page.props.auth.user.id;
                alert.acknowledger_name = page.props.auth.user.name;
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

// Modal state
const selectedEvent = ref<RecognitionEvent | null>(null);
const modalOpen = ref(false);

function handleSelect(event: RecognitionEvent): void {
    selectedEvent.value = event;
    modalOpen.value = true;
}

// Filter pill definitions
const filters: { key: 'all' | AlertSeverity; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'critical', label: 'Critical' },
    { key: 'warning', label: 'Warning' },
    { key: 'info', label: 'Info' },
];
</script>

<template>
    <Head title="Live Alerts" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading title="Live Alerts" />
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            :variant="soundEnabled ? 'default' : 'outline'"
                            size="icon"
                            @click="toggleSound"
                        >
                            <BellRing v-if="soundEnabled" class="size-4" />
                            <Bell v-else class="size-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{
                            soundEnabled
                                ? 'Mute alert sounds'
                                : 'Enable alert sounds'
                        }}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>

        <!-- Filter pills (D-04) -->
        <div class="flex items-center gap-2">
            <Button
                v-for="filter in filters"
                :key="filter.key"
                :variant="activeFilter === filter.key ? 'default' : 'outline'"
                size="sm"
                @click="activeFilter = filter.key"
            >
                {{ filter.label }}
                ({{ filterCounts[filter.key] }})
            </Button>
        </div>

        <!-- aria-live region for critical alert announcements -->
        <div aria-live="polite" class="sr-only">
            <template v-if="highlightedId !== null">
                New alert received
            </template>
        </div>

        <!-- Empty state -->
        <Card v-if="alerts.length === 0" class="p-12">
            <CardContent
                class="flex flex-col items-center justify-center text-center"
            >
                <ShieldAlert class="size-16 text-muted-foreground/50" />
                <h3 class="mt-4 text-lg font-semibold">No alerts yet</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Recognition alerts will appear here in real time as cameras
                    detect known personnel.
                </p>
            </CardContent>
        </Card>

        <!-- Alert feed -->
        <div
            v-else
            class="divide-y divide-border overflow-hidden rounded-lg border"
        >
            <TransitionGroup name="alert-list">
                <AlertFeedItem
                    v-for="alert in filteredAlerts"
                    :key="alert.id"
                    :event="alert"
                    :highlighted="highlightedId === alert.id"
                    @select="handleSelect"
                    @acknowledge="handleAcknowledge"
                    @dismiss="handleDismiss"
                />
            </TransitionGroup>
        </div>
        <!-- Detail modal -->
        <AlertDetailModal
            v-model:open="modalOpen"
            :event="selectedEvent"
            @acknowledge="handleAcknowledge"
            @dismiss="handleDismiss"
        />
    </div>
</template>

<style scoped>
.alert-list-enter-active {
    transition: all 0.2s ease-out;
}

.alert-list-enter-from {
    opacity: 0;
    max-height: 0;
}

.alert-list-enter-to {
    opacity: 1;
    max-height: 80px;
}

.alert-list-move {
    transition: transform 0.2s ease-out;
}
</style>
