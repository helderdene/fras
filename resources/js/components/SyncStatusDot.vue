<script setup lang="ts">
defineProps<{
    status: 'synced' | 'pending' | 'failed' | 'not-synced';
}>();
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
            :aria-label="`Sync status: ${status === 'synced' ? 'Synced' : status === 'pending' ? 'Pending' : status === 'failed' ? 'Failed' : 'Not synced'}`"
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
            {{
                status === 'synced'
                    ? 'Synced'
                    : status === 'pending'
                      ? 'Pending'
                      : status === 'failed'
                        ? 'Failed'
                        : 'Not synced'
            }}
        </span>
    </span>
</template>
