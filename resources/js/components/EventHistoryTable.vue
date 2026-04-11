<script setup lang="ts">
import { ChevronDown, ChevronUp } from 'lucide-vue-next';

import SeverityBadge from '@/components/SeverityBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { getInitials } from '@/composables/useInitials';
import { faceImage } from '@/routes/alerts';
import type { RecognitionEvent } from '@/types';

type Props = {
    events: RecognitionEvent[];
    sort: string;
    direction: string;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    select: [event: RecognitionEvent];
    sort: [payload: { sort: string; direction: string }];
}>();

function toggleSort(column: string): void {
    const newDirection =
        props.sort === column && props.direction === 'asc' ? 'desc' : 'asc';
    emit('sort', { sort: column, direction: newDirection });
}

function personName(event: RecognitionEvent): string {
    return event.personnel?.name ?? event.name_from_camera ?? 'Unknown';
}

function personCustomId(event: RecognitionEvent): string {
    return event.personnel?.custom_id ?? event.custom_id ?? '';
}

function formatTimestamp(dateString: string): string {
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(dateString));
}
</script>

<template>
    <div class="overflow-hidden rounded-lg border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-[48px]" />
                    <TableHead class="min-w-[140px]">Person</TableHead>
                    <TableHead class="w-[140px]">Camera</TableHead>
                    <TableHead class="w-[100px]">
                        <button
                            class="inline-flex items-center gap-1"
                            @click="toggleSort('severity')"
                        >
                            Severity
                            <ChevronUp
                                v-if="
                                    sort === 'severity' && direction === 'asc'
                                "
                                class="size-3 text-primary"
                            />
                            <ChevronDown
                                v-else-if="
                                    sort === 'severity' && direction === 'desc'
                                "
                                class="size-3 text-primary"
                            />
                        </button>
                    </TableHead>
                    <TableHead class="w-[90px]">
                        <button
                            class="inline-flex items-center gap-1"
                            @click="toggleSort('similarity')"
                        >
                            Similarity
                            <ChevronUp
                                v-if="
                                    sort === 'similarity' && direction === 'asc'
                                "
                                class="size-3 text-primary"
                            />
                            <ChevronDown
                                v-else-if="
                                    sort === 'similarity' &&
                                    direction === 'desc'
                                "
                                class="size-3 text-primary"
                            />
                        </button>
                    </TableHead>
                    <TableHead class="w-[160px]">
                        <button
                            class="inline-flex items-center gap-1"
                            @click="toggleSort('captured_at')"
                        >
                            Time
                            <ChevronUp
                                v-if="
                                    sort === 'captured_at' &&
                                    direction === 'asc'
                                "
                                class="size-3 text-primary"
                            />
                            <ChevronDown
                                v-else-if="
                                    sort === 'captured_at' &&
                                    direction === 'desc'
                                "
                                class="size-3 text-primary"
                            />
                        </button>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="event in events"
                    :key="event.id"
                    class="cursor-pointer hover:bg-muted/50"
                    :class="{ 'opacity-60': !event.is_real_time }"
                    tabindex="0"
                    @click="emit('select', event)"
                    @keydown.enter="emit('select', event)"
                >
                    <TableCell class="w-[48px] py-2">
                        <Avatar class="size-8">
                            <AvatarImage
                                v-if="event.face_image_url"
                                :src="faceImage.url(event)"
                                :alt="personName(event)"
                            />
                            <AvatarFallback>
                                {{ getInitials(personName(event)) }}
                            </AvatarFallback>
                        </Avatar>
                    </TableCell>
                    <TableCell class="min-w-[140px]">
                        <div class="text-sm">{{ personName(event) }}</div>
                        <div
                            v-if="personCustomId(event)"
                            class="text-xs text-muted-foreground"
                        >
                            {{ personCustomId(event) }}
                        </div>
                    </TableCell>
                    <TableCell class="w-[140px] text-sm">
                        {{ event.camera?.name ?? 'Unknown' }}
                    </TableCell>
                    <TableCell class="w-[100px]">
                        <div class="flex items-center gap-1">
                            <SeverityBadge :severity="event.severity" />
                            <span
                                v-if="!event.is_real_time"
                                class="rounded bg-muted px-1.5 py-0.5 text-[12px] text-muted-foreground"
                            >
                                Replay
                            </span>
                        </div>
                    </TableCell>
                    <TableCell class="w-[90px] font-mono text-xs">
                        {{ event.similarity.toFixed(1) }}%
                    </TableCell>
                    <TableCell class="w-[160px] text-sm">
                        {{ formatTimestamp(event.captured_at) }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
