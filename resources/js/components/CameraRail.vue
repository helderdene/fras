<script setup lang="ts">
import CameraRailItem from '@/components/CameraRailItem.vue';
import TodayStats from '@/components/TodayStats.vue';
import type { DashboardCamera } from '@/composables/useDashboardMap';

type Props = {
    cameras: DashboardCamera[];
    selectedCameraId: number | null;
    todayStats: {
        recognitions: number;
        critical: number;
        warnings: number;
        enrolled: number;
    };
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'camera-select': [cameraId: number | null];
}>();

function handleCameraSelect(cameraId: number): void {
    if (cameraId === props.selectedCameraId) {
        emit('camera-select', null);
    } else {
        emit('camera-select', cameraId);
    }
}
</script>

<template>
    <div class="flex h-full flex-col">
        <TodayStats v-bind="todayStats" />
        <div class="flex-1 overflow-y-auto">
            <div
                class="cursor-pointer px-4 py-2 text-xs font-medium text-muted-foreground hover:text-foreground"
                @click="$emit('camera-select', null)"
            >
                All Cameras
            </div>
            <div role="listbox" aria-label="Camera list">
                <CameraRailItem
                    v-for="camera in cameras"
                    :key="camera.id"
                    :name="camera.name"
                    :is-online="camera.is_online"
                    :recognition-count="camera.today_recognition_count"
                    :is-selected="selectedCameraId === camera.id"
                    @select="handleCameraSelect(camera.id)"
                />
            </div>
        </div>
    </div>
</template>
