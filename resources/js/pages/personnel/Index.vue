<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import EnrollmentSummaryPanel from '@/components/EnrollmentSummaryPanel.vue';
import Heading from '@/components/Heading.vue';
import SyncStatusDot from '@/components/SyncStatusDot.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import { index, create, show } from '@/routes/personnel';
import type { CameraEnrollmentSummary, PersonnelWithSync } from '@/types';

type Props = {
    personnel: PersonnelWithSync[];
    cameraSummary: CameraEnrollmentSummary[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Personnel', href: index() }],
    },
});

const search = ref('');

const filtered = computed(() => {
    if (!search.value) {
        return props.personnel;
    }

    const term = search.value.toLowerCase();

    return props.personnel.filter(
        (p) =>
            p.name.toLowerCase().includes(term) ||
            p.custom_id.toLowerCase().includes(term),
    );
});
</script>

<template>
    <Head title="Personnel" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading title="Personnel" />
            <Button as-child>
                <Link :href="create()">
                    <Plus class="size-4" />
                    Add Personnel
                </Link>
            </Button>
        </div>

        <!-- Enrollment Summary Panel (D-10) -->
        <EnrollmentSummaryPanel
            v-if="props.cameraSummary.length > 0 && props.personnel.length > 0"
            :cameras="props.cameraSummary"
        />

        <!-- Empty state -->
        <Card v-if="props.personnel.length === 0" class="p-12">
            <CardContent
                class="flex flex-col items-center justify-center text-center"
            >
                <Users class="size-12 text-muted-foreground/50" />
                <h3 class="mt-4 text-lg font-semibold">
                    No personnel registered
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add your first person to start building the enrollment
                    roster.
                </p>
                <Button class="mt-4" as-child>
                    <Link :href="create()">Add Personnel</Link>
                </Button>
            </CardContent>
        </Card>

        <!-- Personnel table -->
        <template v-else>
            <Input
                v-model="search"
                placeholder="Search by name or ID..."
                class="max-w-sm"
            />

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-border bg-muted/50">
                            <th
                                scope="col"
                                class="w-12 px-2 py-1 text-left"
                            ></th>
                            <th
                                scope="col"
                                class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Name
                            </th>
                            <th
                                scope="col"
                                class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Custom ID
                            </th>
                            <th
                                scope="col"
                                class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Sync Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="filtered.length === 0 && search !== ''">
                            <td
                                colspan="5"
                                class="py-12 text-center text-sm text-muted-foreground"
                            >
                                No personnel match your search.
                            </td>
                        </tr>
                        <tr
                            v-for="p in filtered"
                            v-else
                            :key="p.id"
                            class="border-b border-border/50 transition-colors duration-150 hover:bg-accent/50"
                        >
                            <td class="w-12 px-2 py-1">
                                <Avatar class="size-7">
                                    <AvatarImage
                                        v-if="p.photo_url"
                                        :src="p.photo_url"
                                        :alt="p.name"
                                    />
                                    <AvatarFallback>{{
                                        getInitials(p.name)
                                    }}</AvatarFallback>
                                </Avatar>
                            </td>
                            <td class="px-2 py-1">
                                <Link
                                    :href="show(p)"
                                    class="font-semibold text-foreground hover:underline"
                                >
                                    {{ p.name }}
                                </Link>
                            </td>
                            <td
                                class="px-2 py-1 font-mono text-muted-foreground"
                            >
                                {{ p.custom_id }}
                            </td>
                            <td class="px-2 py-1">
                                <Badge
                                    :variant="
                                        p.person_type === 1
                                            ? 'destructive'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        p.person_type === 1 ? 'Block' : 'Allow'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-2 py-1">
                                <SyncStatusDot
                                    :status="p.sync_status"
                                    :labels="{ synced: 'Enrolled' }"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
