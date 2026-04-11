<script setup lang="ts">
import { computed } from 'vue';

import type { AlertSeverity } from '@/types';

type Props = {
    severity: AlertSeverity;
};

const props = defineProps<Props>();

const classes = computed(() => {
    switch (props.severity) {
        case 'critical':
            return 'bg-red-500/90 text-white dark:shadow-[0_0_12px_rgba(239,68,68,0.4)]';
        case 'warning':
            return 'bg-amber-500/90 text-white dark:shadow-[0_0_10px_rgba(245,158,11,0.35)]';
        case 'info':
            return 'bg-emerald-500/90 text-white dark:shadow-[0_0_8px_rgba(16,185,129,0.3)]';
        default:
            return 'bg-muted text-muted-foreground';
    }
});

const label = computed(() => {
    switch (props.severity) {
        case 'critical':
            return 'Critical';
        case 'warning':
            return 'Warning';
        case 'info':
            return 'Info';
        default:
            return props.severity;
    }
});
</script>

<template>
    <span
        :class="classes"
        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold"
        :aria-label="`Severity: ${label}`"
    >
        {{ label }}
    </span>
</template>
