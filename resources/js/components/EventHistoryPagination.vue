<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';

type Props = {
    currentPage: number;
    lastPage: number;
    from: number;
    to: number;
    total: number;
};

defineProps<Props>();

const emit = defineEmits<{
    'page-change': [page: number];
}>();
</script>

<template>
    <div class="flex items-center justify-between">
        <p class="text-xs text-muted-foreground">
            Showing {{ from }}-{{ to }} of {{ total }} events
        </p>
        <Pagination
            v-slot="{ page }"
            :total="total"
            :items-per-page="25"
            :default-page="currentPage"
            :sibling-count="1"
            @update:page="emit('page-change', $event)"
        >
            <PaginationContent
                v-slot="{ items }"
                class="flex items-center gap-1"
            >
                <PaginationPrevious />
                <template v-for="(item, idx) in items">
                    <PaginationItem
                        v-if="item.type === 'page'"
                        :key="idx"
                        :value="item.value"
                        as-child
                    >
                        <Button
                            class="size-9 p-0"
                            :variant="
                                item.value === page ? 'default' : 'outline'
                            "
                        >
                            {{ item.value }}
                        </Button>
                    </PaginationItem>
                    <PaginationEllipsis v-else :key="item.type" :index="idx" />
                </template>
                <PaginationNext />
            </PaginationContent>
        </Pagination>
    </div>
</template>
