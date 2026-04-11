<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <span
        :class="className"
        class="font-semibold leading-none select-none"
        v-bind="$attrs"
    >F</span>
</template>
