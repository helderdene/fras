<script setup lang="ts">
import { ShieldAlert, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AlertDetailModal from '@/components/AlertDetailModal.vue';
import AlertFeedItem from '@/components/AlertFeedItem.vue';
import { Button } from '@/components/ui/button';
import type { AlertSeverity, RecognitionEvent } from '@/types';

type Props = {
    events: RecognitionEvent[];
    selectedCameraId: number | null;
    selectedCameraName: string | null;
    highlightedAlertId?: number | null;
};

const props = withDefaults(defineProps<Props>(), {
    highlightedAlertId: null,
});

const emit = defineEmits<{
    acknowledge: [event: RecognitionEvent];
    dismiss: [event: RecognitionEvent];
    'camera-select': [cameraId: number | null];
}>();

// Filter state
const activeFilter = ref<'all' | AlertSeverity>('all');

// Modal state
const selectedEvent = ref<RecognitionEvent | null>(null);
const modalOpen = ref(false);

// Filter definitions
const filters: { key: 'all' | AlertSeverity; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'critical', label: 'Critical' },
    { key: 'warning', label: 'Warning' },
    { key: 'info', label: 'Info' },
];

// Events filtered by camera selection
const cameraFilteredEvents = computed(() => {
    if (!props.selectedCameraId) {
        return props.events;
    }

    return props.events.filter((e) => e.camera_id === props.selectedCameraId);
});

// Events filtered by both camera and severity
const filteredEvents = computed(() => {
    if (activeFilter.value === 'all') {
        return cameraFilteredEvents.value;
    }

    return cameraFilteredEvents.value.filter(
        (e) => e.severity === activeFilter.value,
    );
});

// Filter counts (from camera-filtered events)
const filterCounts = computed(() => ({
    all: cameraFilteredEvents.value.length,
    critical: cameraFilteredEvents.value.filter(
        (a) => a.severity === 'critical',
    ).length,
    warning: cameraFilteredEvents.value.filter((a) => a.severity === 'warning')
        .length,
    info: cameraFilteredEvents.value.filter((a) => a.severity === 'info')
        .length,
}));

function handleSelect(event: RecognitionEvent): void {
    selectedEvent.value = event;
    modalOpen.value = true;
}
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Header with filter info -->
        <div class="border-b border-border px-4 py-2">
            <h3 class="text-base font-semibold">Live Alerts</h3>
            <!-- Camera filter chip -->
            <div
                v-if="selectedCameraName"
                class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
            >
                Showing: {{ selectedCameraName }}
                <button
                    class="ml-1 text-foreground hover:text-destructive"
                    aria-label="Clear camera filter"
                    @click="emit('camera-select', null)"
                >
                    <X class="size-3" />
                </button>
            </div>
        </div>

        <!-- Filter pills -->
        <div class="flex items-center gap-2 border-b border-border px-4 py-2">
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

        <!-- Feed list -->
        <div class="flex-1 overflow-y-auto" aria-live="polite">
            <!-- Empty state -->
            <div
                v-if="filteredEvents.length === 0"
                class="flex h-full items-center justify-center p-4"
            >
                <div class="text-center">
                    <ShieldAlert
                        class="mx-auto size-12 text-muted-foreground/50"
                    />
                    <p
                        v-if="selectedCameraName && activeFilter !== 'all'"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        No {{ activeFilter }} alerts from
                        {{ selectedCameraName }}.
                    </p>
                    <p
                        v-else-if="selectedCameraName"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        No alerts from {{ selectedCameraName }}.
                    </p>
                    <p v-else class="mt-2 text-sm text-muted-foreground">
                        No alerts yet
                    </p>
                    <p
                        v-if="!selectedCameraName"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Recognition alerts will appear here in real time as
                        cameras detect known personnel.
                    </p>
                </div>
            </div>

            <!-- Alert items -->
            <div v-else class="divide-y divide-border">
                <AlertFeedItem
                    v-for="event in filteredEvents"
                    :key="event.id"
                    :event="event"
                    :highlighted="event.id === highlightedAlertId"
                    @select="handleSelect"
                    @acknowledge="emit('acknowledge', $event)"
                    @dismiss="emit('dismiss', $event)"
                />
            </div>
        </div>

        <!-- Detail modal -->
        <AlertDetailModal
            v-model:open="modalOpen"
            :event="selectedEvent"
            @acknowledge="emit('acknowledge', $event)"
            @dismiss="emit('dismiss', $event)"
        />
    </div>
</template>
