<script setup lang="ts">
type Props = {
    recognitions: number;
    critical: number;
    warnings: number;
    enrolled: number;
};

defineProps<Props>();
</script>

<template>
    <div class="border-b border-border p-4">
        <h3 class="mb-3 text-base font-semibold">Today</h3>
        <div class="grid grid-cols-2 gap-2">
            <div class="rounded-md bg-muted/50 p-2">
                <div
                    class="text-[28px] leading-tight font-semibold text-primary"
                >
                    {{ recognitions }}
                </div>
                <div class="text-xs text-muted-foreground">Recognitions</div>
            </div>
            <div class="rounded-md bg-muted/50 p-2">
                <div
                    class="text-[28px] leading-tight font-semibold text-red-500"
                >
                    {{ critical }}
                </div>
                <div class="text-xs text-muted-foreground">Critical</div>
            </div>
            <div class="rounded-md bg-muted/50 p-2">
                <div
                    class="text-[28px] leading-tight font-semibold text-amber-500"
                >
                    {{ warnings }}
                </div>
                <div class="text-xs text-muted-foreground">Warnings</div>
            </div>
            <div class="rounded-md bg-muted/50 p-2">
                <div
                    class="text-[28px] leading-tight font-semibold text-primary"
                >
                    {{ enrolled }}
                </div>
                <div class="text-xs text-muted-foreground">Enrolled</div>
            </div>
        </div>
    </div>
</template>
