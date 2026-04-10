<script setup lang="ts">
import type { Personnel } from '@/types';

defineProps<{
    personnel: Personnel[];
}>();
</script>

<template>
    <div>
        <h1>Personnel</h1>
    </div>
</template>
