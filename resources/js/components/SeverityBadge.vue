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
            return 'bg-red-500 text-white';
        case 'warning':
            return 'bg-amber-500 text-white';
        case 'info':
            return 'bg-emerald-500 text-white';
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
        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
        :aria-label="`Severity: ${label}`"
    >
        {{ label }}
    </span>
</template>
