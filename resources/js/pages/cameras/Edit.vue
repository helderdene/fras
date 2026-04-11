<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';
import CameraController from '@/actions/App/Http/Controllers/CameraController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MapboxMap from '@/components/MapboxMap.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppearance } from '@/composables/useAppearance';
import { index, show, edit } from '@/routes/cameras';
import type { Camera } from '@/types';

type Props = {
    camera: Camera;
    mapboxToken: string;
    mapboxDarkStyle: string;
    mapboxLightStyle: string;
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Cameras', href: index() },
        { title: props.camera.name, href: show(props.camera) },
        { title: 'Edit', href: edit(props.camera) },
    ],
});

const { resolvedAppearance } = useAppearance();

// Pre-populate from existing camera data
const latitude = ref(String(props.camera.latitude));
const longitude = ref(String(props.camera.longitude));

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const mapLatitude = ref(Number(props.camera.latitude));
const mapLongitude = ref(Number(props.camera.longitude));

watch([latitude, longitude], ([newLat, newLng]) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        const lat = parseFloat(newLat);
        const lng = parseFloat(newLng);

        if (
            !isNaN(lat) &&
            !isNaN(lng) &&
            lat >= -90 &&
            lat <= 90 &&
            lng >= -180 &&
            lng <= 180
        ) {
            mapLatitude.value = lat;
            mapLongitude.value = lng;
        }
    }, 300);
});

onUnmounted(() => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
});

function onMapCoordinatesUpdate(lat: number, lng: number): void {
    latitude.value = lat.toFixed(7);
    longitude.value = lng.toFixed(7);
    mapLatitude.value = lat;
    mapLongitude.value = lng;
}
</script>

<template>
    <Head title="Edit Camera" />

    <div class="max-w-2xl space-y-6">
        <Heading
            title="Edit Camera"
            description="Update camera details and location"
        />

        <Form
            v-bind="CameraController.update.form(props.camera)"
            class="grid gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-6">
                <div class="grid gap-2">
                    <Label for="device_id">Device ID</Label>
                    <Input
                        id="device_id"
                        name="device_id"
                        placeholder="e.g. 1026700"
                        required
                        :default-value="props.camera.device_id"
                    />
                    <InputError :message="errors.device_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="e.g. Main Entrance"
                        required
                        :default-value="props.camera.name"
                    />
                    <InputError :message="errors.name" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="location_label">Location</Label>
                <Input
                    id="location_label"
                    name="location_label"
                    placeholder="e.g. Building A, Ground Floor"
                    required
                    :default-value="props.camera.location_label"
                />
                <InputError :message="errors.location_label" />
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="grid gap-2">
                    <Label for="latitude">Latitude</Label>
                    <Input
                        id="latitude"
                        name="latitude"
                        type="text"
                        placeholder="e.g. 8.9475785"
                        required
                        v-model="latitude"
                    />
                    <InputError :message="errors.latitude" />
                </div>
                <div class="grid gap-2">
                    <Label for="longitude">Longitude</Label>
                    <Input
                        id="longitude"
                        name="longitude"
                        type="text"
                        placeholder="e.g. 125.5406434"
                        required
                        v-model="longitude"
                    />
                    <InputError :message="errors.longitude" />
                </div>
            </div>

            <p class="text-xs text-muted-foreground">
                Click the map below or type coordinates manually. Coordinates
                sync both ways.
            </p>

            <div class="h-64 overflow-hidden rounded-lg border border-border">
                <MapboxMap
                    :latitude="mapLatitude"
                    :longitude="mapLongitude"
                    :interactive="true"
                    :access-token="props.mapboxToken"
                    :style-url="
                        resolvedAppearance === 'dark'
                            ? props.mapboxDarkStyle
                            : props.mapboxLightStyle
                    "
                    @update:coordinates="onMapCoordinatesUpdate"
                />
            </div>

            <Button :disabled="processing">Update Camera</Button>
        </Form>
    </div>
</template>
