<script setup lang="ts">
type Props = {
    visible: boolean;
};

defineProps<Props>();
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="-translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-full opacity-0"
    >
        <div
            v-if="visible"
            class="border-b border-amber-500/20 bg-amber-500/10 px-4 py-1.5"
        >
            <p class="text-center text-xs text-amber-700 dark:text-amber-400">
                Real-time connection lost. Alerts may be delayed.
            </p>
        </div>
    </Transition>
</template>
