<script setup lang="ts">
import { computed, ref } from 'vue';

type Props = {
    src: string;
    targetBbox: number[] | null;
    alt?: string;
};

const props = withDefaults(defineProps<Props>(), {
    alt: 'Scene image',
});

const imgEl = ref<HTMLImageElement | null>(null);
const naturalSize = ref<{ width: number; height: number } | null>(null);

function onImageLoad(): void {
    if (imgEl.value) {
        naturalSize.value = {
            width: imgEl.value.naturalWidth,
            height: imgEl.value.naturalHeight,
        };
    }
}

// target_bbox is [x1, y1, x2, y2] in scene image pixel coordinates
// CSS overlay scales with image via percentage positioning
const bboxStyle = computed(() => {
    if (!props.targetBbox || !naturalSize.value) {
        return null;
    }

    const [x1, y1, x2, y2] = props.targetBbox;
    const { width, height } = naturalSize.value;

    if (width === 0 || height === 0) {
        return null;
    }

    return {
        left: `${(x1 / width) * 100}%`,
        top: `${(y1 / height) * 100}%`,
        width: `${((x2 - x1) / width) * 100}%`,
        height: `${((y2 - y1) / height) * 100}%`,
    };
});
</script>

<template>
    <div class="relative inline-block">
        <img
            ref="imgEl"
            :src="src"
            :alt="alt"
            class="max-w-full rounded"
            @load="onImageLoad"
        />
        <div
            v-if="bboxStyle"
            class="pointer-events-none absolute rounded-sm border-2 border-yellow-400"
            :style="bboxStyle"
        />
    </div>
</template>
