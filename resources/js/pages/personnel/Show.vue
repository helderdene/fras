<script setup lang="ts">
import type { Personnel } from '@/types';

defineProps<{
    personnel: Personnel;
    cameras: { id: number; name: string }[];
}>();
</script>

<template>
    <div>
        <h1>Personnel Detail</h1>
    </div>
</template>
