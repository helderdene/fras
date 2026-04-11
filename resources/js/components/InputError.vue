<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p
            class="text-sm text-red-600 dark:text-red-500 dark:drop-shadow-[0_0_4px_rgba(239,68,68,0.2)]"
        >
            {{ message }}
        </p>
    </div>
</template>
