<script setup lang="ts">
type StatusLabels = Partial<
    Record<'synced' | 'pending' | 'failed' | 'not-synced', string>
>;

const props = withDefaults(
    defineProps<{
        status: 'synced' | 'pending' | 'failed' | 'not-synced';
        labels?: StatusLabels;
    }>(),
    {
        labels: () => ({}),
    },
);

const defaultLabels: Record<string, string> = {
    synced: 'Synced',
    pending: 'Pending',
    failed: 'Failed',
    'not-synced': 'Not synced',
};

function getLabel(status: string): string {
    return (
        props.labels?.[status as keyof StatusLabels] ??
        defaultLabels[status] ??
        status
    );
}
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span
            class="size-1.5 rounded-full"
            :class="{
                'bg-emerald-500': status === 'synced',
                'bg-amber-500': status === 'pending',
                'bg-red-500': status === 'failed',
                'bg-neutral-400 dark:bg-neutral-500': status === 'not-synced',
            }"
            :aria-label="`Sync status: ${getLabel(status)}`"
        />
        <span
            class="text-sm"
            :class="{
                'text-emerald-700 dark:text-emerald-400': status === 'synced',
                'text-amber-700 dark:text-amber-400': status === 'pending',
                'text-red-700 dark:text-red-400': status === 'failed',
                'text-muted-foreground': status === 'not-synced',
            }"
        >
            {{ getLabel(status) }}
        </span>
    </span>
</template>
