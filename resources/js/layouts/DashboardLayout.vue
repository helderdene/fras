<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="flex h-screen w-screen flex-col overflow-hidden">
        <slot />
        <Toaster />
    </div>
</template>
