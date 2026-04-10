<script setup lang="ts">
defineProps<{
    isOnline: boolean;
}>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5"
        :aria-label="isOnline ? 'Camera is online' : 'Camera is offline'"
    >
        <span
            class="size-1.5 rounded-full"
            :class="
                isOnline
                    ? 'bg-emerald-500'
                    : 'bg-neutral-400 dark:bg-neutral-500'
            "
        />
        <span
            class="text-sm"
            :class="
                isOnline
                    ? 'text-emerald-700 dark:text-emerald-400'
                    : 'text-muted-foreground'
            "
        >
            {{ isOnline ? 'Online' : 'Offline' }}
        </span>
    </span>
</template>
