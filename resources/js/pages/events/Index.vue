<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    events: object;
    cameras: Array<{ id: number; name: string }>;
    filters: object;
}>();
</script>

<template>
    <Head title="Event History" />
    <div>
        <h1>Event History</h1>
    </div>
</template>
