<script setup lang="ts">
type Props = {
    name: string;
    isOnline: boolean;
    recognitionCount: number;
    isSelected: boolean;
};

defineProps<Props>();

defineEmits<{
    select: [];
}>();
</script>

<template>
    <div
        class="flex h-10 cursor-pointer items-center gap-2 px-2 py-1 transition-colors duration-150 hover:bg-accent/50"
        :class="isSelected ? 'bg-accent text-accent-foreground' : ''"
        role="option"
        :aria-selected="isSelected"
        @click="$emit('select')"
    >
        <span
            class="size-1.5 shrink-0 rounded-full"
            :class="
                isOnline
                    ? 'bg-emerald-500'
                    : 'bg-neutral-400 dark:bg-neutral-500'
            "
        />
        <span class="min-w-0 flex-1 truncate text-sm">{{ name }}</span>
        <span
            v-if="recognitionCount > 0"
            class="shrink-0 rounded-full bg-muted px-2 py-0.5 font-mono text-xs"
        >
            {{ recognitionCount }}
        </span>
    </div>
</template>
