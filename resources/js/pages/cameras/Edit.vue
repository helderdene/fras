<script setup lang="ts">
defineProps<{
    camera: {
        id: number;
        device_id: string;
        name: string;
        location_label: string;
        latitude: string;
        longitude: string;
        is_online: boolean;
        last_seen_at: string | null;
    };
    mapboxToken: string;
    mapboxDarkStyle: string;
    mapboxLightStyle: string;
}>();
</script>

<template>
    <div>
        <h1>Edit Camera</h1>
    </div>
</template>
