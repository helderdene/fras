<script setup lang="ts">
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';

type Props = {
    latitude?: number;
    longitude?: number;
    interactive?: boolean;
    accessToken: string;
    styleUrl: string;
};

const props = withDefaults(defineProps<Props>(), {
    latitude: 8.9475,
    longitude: 125.5406,
    interactive: false,
});

const emit = defineEmits<{
    'update:coordinates': [lat: number, lng: number];
}>();

const mapContainer = ref<HTMLElement | null>(null);
const isLoaded = ref(false);
const hasError = ref(false);

// CRITICAL: Do NOT use ref() for map instance -- Vue 3 Proxy breaks mapbox-gl internals
let map: mapboxgl.Map | null = null;
let marker: mapboxgl.Marker | null = null;

function initializeMap(): void {
    if (!mapContainer.value || !props.accessToken) {
        hasError.value = !props.accessToken;

        return;
    }

    mapboxgl.accessToken = props.accessToken;

    map = new mapboxgl.Map({
        container: mapContainer.value,
        style: props.styleUrl,
        center: [props.longitude, props.latitude],
        zoom: 15,
        interactive: props.interactive,
        attributionControl: false,
    });

    // Show marker if coordinates provided (not default Butuan center on create)
    const hasCoordinates =
        props.latitude !== 8.9475 || props.longitude !== 125.5406;

    if (hasCoordinates) {
        marker = new mapboxgl.Marker()
            .setLngLat([props.longitude, props.latitude])
            .addTo(map);
    }

    map.on('load', () => {
        isLoaded.value = true;
    });

    if (props.interactive) {
        map.getCanvas().style.cursor = 'crosshair';

        map.on('click', (e) => {
            const { lat, lng } = e.lngLat;

            if (!marker && map) {
                marker = new mapboxgl.Marker().setLngLat([lng, lat]).addTo(map);
            } else if (marker) {
                marker.setLngLat([lng, lat]);
            }

            emit('update:coordinates', lat, lng);
        });
    }
}

// Watch for external coordinate changes (from input fields)
watch(
    () => [props.latitude, props.longitude],
    ([newLat, newLng]) => {
        if (!map || !newLat || !newLng) {
            return;
        }

        const lat = Number(newLat);
        const lng = Number(newLng);

        if (isNaN(lat) || isNaN(lng)) {
            return;
        }

        map.setCenter([lng, lat]);

        if (!marker && map) {
            marker = new mapboxgl.Marker().setLngLat([lng, lat]).addTo(map);
        } else if (marker) {
            marker.setLngLat([lng, lat]);
        }
    },
    { flush: 'post' },
);

onMounted(() => {
    initializeMap();
});

onUnmounted(() => {
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div class="relative h-full w-full">
        <Skeleton v-if="!isLoaded && !hasError" class="absolute inset-0" />
        <div
            v-if="hasError"
            class="flex items-center justify-center bg-muted text-sm text-muted-foreground"
            style="height: 100%"
        >
            Map unavailable
        </div>
        <div
            ref="mapContainer"
            class="h-full w-full"
            role="application"
            aria-label="Camera location map"
        />
    </div>
</template>
