<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CameraStatusDot from '@/components/CameraStatusDot.vue';
import { Card } from '@/components/ui/card';
import { show } from '@/routes/cameras';
import type { CameraEnrollmentSummary } from '@/types';

defineProps<{
    cameras: CameraEnrollmentSummary[];
}>();
</script>

<template>
    <div class="flex gap-4 overflow-x-auto pb-2">
        <Link
            v-for="cam in cameras"
            :key="cam.id"
            :href="show(cam)"
            class="block w-56 shrink-0"
        >
            <Card
                class="cursor-pointer p-4 transition-colors hover:bg-muted/50 dark:border-border/50"
            >
                <div class="flex items-center justify-between gap-2">
                    <span
                        class="truncate text-sm font-semibold text-foreground"
                    >
                        {{ cam.name }}
                    </span>
                    <CameraStatusDot :is-online="cam.is_online" />
                </div>
                <div class="mt-2 space-y-1">
                    <div class="flex items-baseline gap-1">
                        <span
                            class="text-xl font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            {{ cam.enrolled_count }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            / {{ cam.total_count }} enrolled
                        </span>
                    </div>
                    <div
                        v-if="cam.failed_count > 0"
                        class="flex items-baseline gap-1"
                    >
                        <span
                            class="text-sm font-semibold text-red-600 dark:text-red-400"
                        >
                            {{ cam.failed_count }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            failed
                        </span>
                    </div>
                    <div
                        v-if="cam.pending_count > 0"
                        class="flex items-baseline gap-1"
                    >
                        <span
                            class="text-sm font-semibold text-amber-600 dark:text-amber-400"
                        >
                            {{ cam.pending_count }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            pending
                        </span>
                    </div>
                </div>
            </Card>
        </Link>
    </div>
</template>
