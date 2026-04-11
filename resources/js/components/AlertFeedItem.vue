<script setup lang="ts">
import { Check, X } from 'lucide-vue-next';
import { computed } from 'vue';

import SeverityBadge from '@/components/SeverityBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { faceImage } from '@/routes/alerts';
import type { RecognitionEvent } from '@/types';

type Props = {
    event: RecognitionEvent;
    highlighted?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    highlighted: false,
});

const emit = defineEmits<{
    select: [event: RecognitionEvent];
    acknowledge: [event: RecognitionEvent];
    dismiss: [event: RecognitionEvent];
}>();

const personName = computed(() => {
    return (
        props.event.personnel?.name ?? props.event.name_from_camera ?? 'Unknown'
    );
});

const severityBorderClass = computed(() => {
    switch (props.event.severity) {
        case 'critical':
            return 'border-l-red-500';
        case 'warning':
            return 'border-l-amber-500';
        case 'info':
            return 'border-l-emerald-500';
        default:
            return 'border-l-muted';
    }
});

const severityBgClass = computed(() => {
    switch (props.event.severity) {
        case 'critical':
            return 'bg-red-50 dark:bg-red-950/30';
        case 'warning':
            return 'bg-amber-50 dark:bg-amber-950/30';
        case 'info':
            return 'bg-emerald-50 dark:bg-emerald-950/30';
        default:
            return '';
    }
});

const isDismissed = computed(() => props.event.dismissed_at !== null);
const isAcknowledged = computed(() => props.event.acknowledged_at !== null);

function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffSeconds < 60) {
        return 'Just now';
    }

    if (diffSeconds < 3600) {
        const mins = Math.floor(diffSeconds / 60);

        return `${mins} min ago`;
    }

    if (diffSeconds < 86400) {
        const hours = Math.floor(diffSeconds / 3600);

        return `${hours} hr ago`;
    }

    const days = Math.floor(diffSeconds / 86400);

    return `${days} day${days > 1 ? 's' : ''} ago`;
}

function formatAbsoluteTime(dateString: string): string {
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(dateString));
}

function handleSelect(): void {
    emit('select', props.event);
}

function handleKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter') {
        emit('select', props.event);
    }
}

function handleAcknowledge(e: Event): void {
    e.stopPropagation();
    emit('acknowledge', props.event);
}

function handleDismiss(e: Event): void {
    e.stopPropagation();
    emit('dismiss', props.event);
}
</script>

<template>
    <div
        class="group flex cursor-pointer items-center gap-3 border-l-4 px-4 py-3 transition-colors duration-300 hover:bg-muted/50"
        :class="[
            severityBorderClass,
            severityBgClass,
            isDismissed ? 'opacity-50' : '',
            highlighted ? 'bg-primary/10' : '',
        ]"
        tabindex="0"
        role="button"
        @click="handleSelect"
        @keydown="handleKeydown"
    >
        <!-- Face crop avatar -->
        <Avatar class="size-8 shrink-0">
            <AvatarImage
                v-if="event.face_image_url"
                :src="faceImage.url(event)"
                :alt="personName"
            />
            <AvatarFallback>{{ getInitials(personName) }}</AvatarFallback>
        </Avatar>

        <!-- Person name and camera -->
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <span class="truncate text-sm font-medium">
                    {{ personName }}
                </span>
                <span
                    v-if="event.camera?.name"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{ event.camera.name }}
                </span>
            </div>
            <div v-if="isAcknowledged" class="text-xs text-muted-foreground">
                Acknowledged at
                {{ formatAbsoluteTime(event.acknowledged_at!) }}
            </div>
        </div>

        <!-- Severity badge -->
        <SeverityBadge :severity="event.severity" class="shrink-0" />

        <!-- Similarity score -->
        <span class="shrink-0 font-mono text-xs text-muted-foreground">
            {{ (event.similarity * 100).toFixed(1) }}%
        </span>

        <!-- Relative timestamp -->
        <span
            class="shrink-0 text-xs text-muted-foreground"
            :title="formatAbsoluteTime(event.captured_at)"
        >
            {{ formatRelativeTime(event.captured_at) }}
        </span>

        <!-- Ack/Dismiss hover buttons -->
        <div
            class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity duration-150 group-hover:opacity-100"
        >
            <Button
                v-if="!isAcknowledged"
                variant="ghost"
                size="icon-sm"
                aria-label="Acknowledge alert"
                @click="handleAcknowledge"
            >
                <Check class="size-4" />
            </Button>
            <Button
                v-if="!isDismissed"
                variant="ghost"
                size="icon-sm"
                aria-label="Dismiss alert"
                @click="handleDismiss"
            >
                <X class="size-4" />
            </Button>
        </div>
    </div>
</template>
