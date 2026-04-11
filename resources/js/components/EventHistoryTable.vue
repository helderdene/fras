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
    <div class="overflow-auto rounded-lg border">
        <Table>
            <TableHeader class="sticky top-0 z-10 bg-card">
                <TableRow>
                    <TableHead
                        class="w-[40px] px-2 py-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    />
                    <TableHead
                        class="min-w-[140px] px-2 py-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Person</TableHead
                    >
                    <TableHead
                        class="w-[140px] px-2 py-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Camera</TableHead
                    >
                    <TableHead
                        class="w-[100px] px-2 py-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        <button
                            class="inline-flex items-center gap-1"
                            :class="{ 'text-primary': sort === 'severity' }"
                            @click="toggleSort('severity')"
                        >
                            Severity
                            <ChevronUp
                                v-if="
                                    sort === 'severity' && direction === 'asc'
                                "
                                class="size-3"
                            />
                            <ChevronDown
                                v-else-if="
                                    sort === 'severity' && direction === 'desc'
                                "
                                class="size-3"
                            />
                        </button>
                    </TableHead>
                    <TableHead
                        class="w-[90px] px-2 py-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        <button
                            class="inline-flex items-center gap-1"
                            :class="{ 'text-primary': sort === 'similarity' }"
                            @click="toggleSort('similarity')"
                        >
                            Similarity
                            <ChevronUp
                                v-if="
                                    sort === 'similarity' && direction === 'asc'
                                "
                                class="size-3"
                            />
                            <ChevronDown
                                v-else-if="
                                    sort === 'similarity' &&
                                    direction === 'desc'
                                "
                                class="size-3"
                            />
                        </button>
                    </TableHead>
                    <TableHead
                        class="w-[160px] px-2 py-1 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        <button
                            class="inline-flex items-center gap-1"
                            :class="{ 'text-primary': sort === 'captured_at' }"
                            @click="toggleSort('captured_at')"
                        >
                            Time
                            <ChevronUp
                                v-if="
                                    sort === 'captured_at' &&
                                    direction === 'asc'
                                "
                                class="size-3"
                            />
                            <ChevronDown
                                v-else-if="
                                    sort === 'captured_at' &&
                                    direction === 'desc'
                                "
                                class="size-3"
                            />
                        </button>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="event in events"
                    :key="event.id"
                    class="cursor-pointer border-b border-border/50 hover:bg-accent/50"
                    :class="{ 'opacity-60': !event.is_real_time }"
                    tabindex="0"
                    @click="emit('select', event)"
                    @keydown.enter="emit('select', event)"
                >
                    <TableCell class="w-[40px] px-2 py-1">
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
                    <TableCell class="min-w-[140px] px-2 py-1 text-xs">
                        <div>{{ personName(event) }}</div>
                        <div
                            v-if="personCustomId(event)"
                            class="font-mono text-muted-foreground"
                        >
                            {{ personCustomId(event) }}
                        </div>
                    </TableCell>
                    <TableCell class="w-[140px] px-2 py-1 text-xs">
                        {{ event.camera?.name ?? 'Unknown' }}
                    </TableCell>
                    <TableCell class="w-[100px] px-2 py-1">
                        <div class="flex items-center gap-1">
                            <SeverityBadge :severity="event.severity" />
                            <span
                                v-if="!event.is_real_time"
                                class="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground"
                            >
                                Replay
                            </span>
                        </div>
                    </TableCell>
                    <TableCell
                        class="w-[90px] px-2 py-1 text-right font-mono text-xs"
                    >
                        {{ (event.similarity ?? 0).toFixed(1) }}%
                    </TableCell>
                    <TableCell class="w-[160px] px-2 py-1 font-mono text-xs">
                        {{ formatTimestamp(event.captured_at) }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
