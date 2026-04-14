<script setup lang="ts">
import { computed } from 'vue';

import SceneImageOverlay from '@/components/SceneImageOverlay.vue';
import SeverityBadge from '@/components/SeverityBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { getInitials } from '@/composables/useInitials';
import { faceImage, sceneImage } from '@/routes/alerts';
import type { RecognitionEvent } from '@/types';

type Props = {
    event: RecognitionEvent | null;
    open: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    acknowledge: [event: RecognitionEvent];
    dismiss: [event: RecognitionEvent];
}>();

const personName = computed(() => {
    if (!props.event) {
        return 'Unknown';
    }

    return (
        props.event.personnel?.name ?? props.event.name_from_camera ?? 'Unknown'
    );
});

const personTypeLabel = computed(() => {
    if (!props.event) {
        return 'Unknown';
    }

    return props.event.person_type === 1 ? 'Block' : 'Allow';
});

const personTypeVariant = computed(() => {
    if (!props.event) {
        return 'secondary' as const;
    }

    return props.event.person_type === 1
        ? ('destructive' as const)
        : ('secondary' as const);
});

const isAcknowledged = computed(() => !!props.event?.acknowledged_at);
const isDismissed = computed(() => !!props.event?.dismissed_at);

function formatAbsoluteTime(dateString: string): string {
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(dateString));
}

function handleAcknowledge(): void {
    if (props.event) {
        emit('acknowledge', props.event);
    }
}

function handleDismiss(): void {
    if (props.event) {
        emit('dismiss', props.event);
    }
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="(value: boolean) => emit('update:open', value)"
    >
        <DialogContent
            class="max-w-2xl"
            :class="{
                'border-t-2 border-t-red-500': event?.severity === 'critical',
                'border-t-2 border-t-amber-500': event?.severity === 'warning',
                'border-t-2 border-t-emerald-500': event?.severity === 'info',
            }"
        >
            <DialogHeader>
                <DialogTitle>Alert Detail</DialogTitle>
                <DialogDescription class="sr-only">
                    Detailed view of a recognition alert event
                </DialogDescription>
            </DialogHeader>

            <template v-if="event">
                <!-- Top section: side-by-side images -->
                <div class="flex gap-4">
                    <!-- Face crop -->
                    <Avatar class="size-[150px] shrink-0 rounded-lg">
                        <AvatarImage
                            v-if="event.face_image_url"
                            :src="faceImage.url(event)"
                            :alt="personName"
                            class="object-cover"
                        />
                        <AvatarFallback class="rounded-lg text-2xl">
                            {{ getInitials(personName) }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- Scene image with bbox overlay -->
                    <div
                        class="flex min-w-0 flex-1 items-center justify-center"
                    >
                        <SceneImageOverlay
                            v-if="event.scene_image_url"
                            :src="sceneImage.url(event)"
                            :target-bbox="event.target_bbox"
                            alt="Scene image with face detection"
                        />
                        <div
                            v-else
                            class="flex h-[150px] w-full items-center justify-center rounded bg-muted"
                        >
                            <span class="text-sm text-muted-foreground">
                                Scene image not available
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Metadata grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-muted-foreground">Person</p>
                        <p class="text-sm">{{ personName }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Custom ID</p>
                        <p class="font-mono text-xs">
                            {{
                                event.personnel?.custom_id ||
                                event.custom_id ||
                                '-'
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Camera</p>
                        <p class="text-sm">
                            {{ event.camera?.name ?? 'Unknown' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Similarity</p>
                        <p class="font-mono text-xs">
                            {{ (event.similarity ?? 0).toFixed(1) }}%
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Person Type</p>
                        <Badge :variant="personTypeVariant">
                            {{ personTypeLabel }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Captured</p>
                        <p class="font-mono text-xs">
                            {{ formatAbsoluteTime(event.captured_at) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Severity</p>
                        <SeverityBadge :severity="event.severity" />
                    </div>
                </div>

                <!-- Footer -->
                <DialogFooter class="gap-2 sm:gap-0">
                    <template v-if="isDismissed">
                        <p class="text-sm text-muted-foreground">Dismissed</p>
                    </template>
                    <template v-else>
                        <template v-if="isAcknowledged">
                            <p class="text-sm text-muted-foreground">
                                Acknowledged
                                <template v-if="event.acknowledger_name">
                                    by {{ event.acknowledger_name }}
                                </template>
                                at
                                {{ formatAbsoluteTime(event.acknowledged_at!) }}
                            </p>
                        </template>
                        <Button
                            v-if="!isAcknowledged"
                            variant="default"
                            @click="handleAcknowledge"
                        >
                            Acknowledge
                        </Button>
                        <Button variant="outline" @click="handleDismiss">
                            Dismiss
                        </Button>
                    </template>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
</template>
