<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';
import CameraController from '@/actions/App/Http/Controllers/CameraController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MapboxMap from '@/components/MapboxMap.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppearance } from '@/composables/useAppearance';
import { index, create } from '@/routes/cameras';

type Props = {
    mapboxToken: string;
    mapboxDarkStyle: string;
    mapboxLightStyle: string;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cameras', href: index() },
            { title: 'Add Camera', href: create() },
        ],
    },
});

const { resolvedAppearance } = useAppearance();

const latitude = ref('');
const longitude = ref('');

// Debounce timer for coordinate input -> map sync
let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const mapLatitude = ref(8.9475);
const mapLongitude = ref(125.5406);

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
    <Head title="Add Camera" />

    <div class="max-w-2xl space-y-6">
        <Heading
            title="Add Camera"
            description="Register a new camera for monitoring"
        />

        <Form
            v-bind="CameraController.store.form()"
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

            <Button :disabled="processing">Create Camera</Button>
        </Form>
    </div>
</template>
