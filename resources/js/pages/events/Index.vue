<script setup lang="ts">
import { Head, router, useHttp, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AlertDetailModal from '@/components/AlertDetailModal.vue';
import EventHistoryFilters from '@/components/EventHistoryFilters.vue';
import EventHistoryPagination from '@/components/EventHistoryPagination.vue';
import EventHistoryTable from '@/components/EventHistoryTable.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import {
    acknowledge as acknowledgeRoute,
    dismiss as dismissRoute,
} from '@/routes/alerts';
import { index } from '@/routes/events';
import type { RecognitionEvent } from '@/types';

type PaginatedEvents = {
    data: RecognitionEvent[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
};

type Props = {
    events: PaginatedEvents;
    cameras: Array<{ id: number; name: string }>;
    filters: {
        date_from: string;
        date_to: string;
        camera_id: number | null;
        search: string | null;
        severity: string | null;
        sort: string;
        direction: string;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Event History', href: index() }],
    },
});

// Modal state
const selectedEvent = ref<RecognitionEvent | null>(null);
const modalOpen = ref(false);

function handleSelect(event: RecognitionEvent): void {
    selectedEvent.value = event;
    modalOpen.value = true;
}

// Active filters detection
const hasActiveFilters = computed(
    () =>
        !!props.filters.camera_id ||
        !!props.filters.search ||
        !!props.filters.severity,
);

// Filter change handler
function handleFilterChange(newFilters: Record<string, unknown>): void {
    router.get(
        index.url(),
        {
            ...newFilters,
            sort: props.filters.sort,
            direction: props.filters.direction,
            page: 1,
        },
        { preserveState: true, replace: true },
    );
}

// Sort change handler
function handleSort(payload: { sort: string; direction: string }): void {
    router.get(
        index.url(),
        { ...props.filters, ...payload, page: 1 },
        { preserveState: true, replace: true },
    );
}

// Page change handler
function handlePageChange(page: number): void {
    router.get(
        index.url(),
        { ...props.filters, page },
        { preserveState: true, replace: true },
    );
}

// Acknowledge/dismiss handlers
const http = useHttp();
const page = usePage();

function handleAcknowledge(event: RecognitionEvent): void {
    http.submit(acknowledgeRoute.post(event), {
        onSuccess: () => {
            const found = props.events.data.find((e) => e.id === event.id);

            if (found) {
                found.acknowledged_at = new Date().toISOString();
                found.acknowledged_by = page.props.auth.user.id;
            }

            if (selectedEvent.value?.id === event.id) {
                selectedEvent.value = {
                    ...selectedEvent.value,
                    acknowledged_at: new Date().toISOString(),
                    acknowledged_by: page.props.auth.user.id,
                };
            }
        },
    });
}

function handleDismiss(event: RecognitionEvent): void {
    http.submit(dismissRoute.post(event), {
        onSuccess: () => {
            const found = props.events.data.find((e) => e.id === event.id);

            if (found) {
                found.dismissed_at = new Date().toISOString();
            }

            if (selectedEvent.value?.id === event.id) {
                selectedEvent.value = {
                    ...selectedEvent.value,
                    dismissed_at: new Date().toISOString(),
                };
            }
        },
    });
}
</script>

<template>
    <Head title="Event History" />

    <div class="space-y-6">
        <Heading title="Event History" />

        <EventHistoryFilters
            :filters="filters"
            :cameras="cameras"
            @filter-change="handleFilterChange"
        />

        <!-- Empty state when no events -->
        <Card v-if="events.data.length === 0" class="py-12">
            <CardContent
                class="flex flex-col items-center justify-center text-center"
            >
                <Search class="size-16 text-muted-foreground/50" />
                <h3 class="mt-4 text-lg font-semibold">
                    {{
                        hasActiveFilters
                            ? 'No matching events'
                            : 'No recognition events yet'
                    }}
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        hasActiveFilters
                            ? 'Try adjusting your filters or expanding the date range.'
                            : 'Events will appear here as cameras detect personnel. Check that cameras are online and enrolled.'
                    }}
                </p>
            </CardContent>
        </Card>

        <template v-else>
            <EventHistoryTable
                :events="events.data"
                :sort="filters.sort"
                :direction="filters.direction"
                @select="handleSelect"
                @sort="handleSort"
            />

            <EventHistoryPagination
                :current-page="events.current_page"
                :last-page="events.last_page"
                :from="events.from ?? 0"
                :to="events.to ?? 0"
                :total="events.total"
                @page-change="handlePageChange"
            />
        </template>

        <AlertDetailModal
            v-model:open="modalOpen"
            :event="selectedEvent"
            @acknowledge="handleAcknowledge"
            @dismiss="handleDismiss"
        />
    </div>
</template>
