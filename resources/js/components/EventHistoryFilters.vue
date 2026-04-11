<script setup lang="ts">
import { watchDebounced } from '@vueuse/core';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    filters: {
        date_from: string;
        date_to: string;
        camera_id: number | null;
        search: string | null;
        severity: string | null;
        sort: string;
        direction: string;
    };
    cameras: Array<{ id: number; name: string }>;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'filter-change': [filters: Record<string, unknown>];
}>();

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const cameraId = ref<string>(props.filters.camera_id?.toString() ?? '');
const search = ref(props.filters.search ?? '');
const activeSeverity = ref<string>(props.filters.severity ?? '');

const severityFilters = [
    { key: '', label: 'All' },
    { key: 'critical', label: 'Critical' },
    { key: 'warning', label: 'Warning' },
    { key: 'info', label: 'Info' },
];

function emitFilters(): void {
    emit('filter-change', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
        camera_id: cameraId.value || undefined,
        search: search.value || undefined,
        severity: activeSeverity.value || undefined,
    });
}

// Debounce search input at 300ms
watchDebounced(search, () => emitFilters(), { debounce: 300 });

// Immediate emit for non-search filters
watch([dateFrom, dateTo, cameraId, activeSeverity], () => emitFilters());
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1.5">
            <label
                for="filter-date-from"
                class="text-xs font-semibold text-muted-foreground"
            >
                From
            </label>
            <input
                id="filter-date-from"
                v-model="dateFrom"
                type="date"
                class="flex h-9 w-[160px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            />
        </div>

        <div class="flex items-center gap-1.5">
            <label
                for="filter-date-to"
                class="text-xs font-semibold text-muted-foreground"
            >
                To
            </label>
            <input
                id="filter-date-to"
                v-model="dateTo"
                type="date"
                class="flex h-9 w-[160px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            />
        </div>

        <Select v-model="cameraId">
            <SelectTrigger class="w-[180px]">
                <SelectValue placeholder="All cameras" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="">All cameras</SelectItem>
                <SelectItem
                    v-for="camera in cameras"
                    :key="camera.id"
                    :value="camera.id.toString()"
                >
                    {{ camera.name }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Input
            v-model="search"
            placeholder="Search person..."
            class="w-[200px]"
        />

        <div class="flex items-center gap-1">
            <Button
                v-for="filter in severityFilters"
                :key="filter.key"
                :variant="activeSeverity === filter.key ? 'default' : 'outline'"
                size="sm"
                @click="activeSeverity = filter.key"
            >
                {{ filter.label }}
            </Button>
        </div>
    </div>
</template>
