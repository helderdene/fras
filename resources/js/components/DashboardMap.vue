<script setup lang="ts">
import { ref } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import { useDashboardMap } from '@/composables/useDashboardMap';
import type { DashboardCamera } from '@/composables/useDashboardMap';

type Props = {
    cameras: DashboardCamera[];
    accessToken: string;
    styleUrl: string;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'camera-click': [cameraId: number];
}>();

const mapContainer = ref<HTMLElement | null>(null);

const {
    isLoaded,
    hasError,
    triggerPulse,
    updateMarkerStatus,
    flyTo,
    switchStyle,
    resizeMap,
} = useDashboardMap({
    container: mapContainer,
    accessToken: props.accessToken,
    styleUrl: props.styleUrl,
    cameras: props.cameras,
    onCameraClick: (cameraId: number) => {
        emit('camera-click', cameraId);
    },
});

defineExpose({
    triggerPulse,
    updateMarkerStatus,
    flyTo,
    switchStyle,
    resizeMap,
});
</script>

<template>
    <div class="relative h-full w-full">
        <Skeleton v-if="!isLoaded && !hasError" class="absolute inset-0" />
        <div
            v-if="hasError"
            class="flex h-full items-center justify-center bg-muted text-sm text-muted-foreground"
        >
            Map unavailable
        </div>
        <div
            ref="mapContainer"
            class="h-full w-full"
            role="application"
            aria-label="Camera locations map"
        />
    </div>
</template>
