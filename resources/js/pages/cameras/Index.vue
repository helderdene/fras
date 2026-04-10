<script setup lang="ts">
defineProps<{
    cameras: Array<{
        id: number;
        device_id: string;
        name: string;
        location_label: string;
        is_online: boolean;
        last_seen_at: string | null;
    }>;
}>();
</script>

<template>
    <div>
        <h1>Cameras</h1>
    </div>
</template>
