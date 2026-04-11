<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { Camera as CameraIcon, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import CameraStatusDot from '@/components/CameraStatusDot.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index, create, show } from '@/routes/cameras';
import type { Camera, CameraStatusPayload } from '@/types';

type Props = {
    cameras: Camera[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cameras', href: index() }],
    },
});

// Local reactive copy for real-time updates (D-10)
const cameras = ref<Camera[]>([...props.cameras]);

// Real-time status updates via Echo (D-10, D-06)
useEcho(
    'fras.alerts',
    '.CameraStatusChanged',
    (payload: CameraStatusPayload) => {
        const camera = cameras.value.find((c) => c.id === payload.camera_id);

        if (camera) {
            camera.is_online = payload.is_online;
            camera.last_seen_at = payload.last_seen_at;
        }
    },
);

function formatRelativeTime(dateString: string | null): string {
    if (!dateString) {
        return 'Never';
    }

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
</script>

<template>
    <Head title="Cameras" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading title="Cameras" />
            <Button as-child>
                <Link :href="create()">
                    <Plus class="size-4" />
                    Add Camera
                </Link>
            </Button>
        </div>

        <!-- Empty state (D-11: max 8 cameras, no filtering) -->
        <Card v-if="cameras.length === 0" class="p-12">
            <CardContent
                class="flex flex-col items-center justify-center text-center"
            >
                <CameraIcon class="size-12 text-muted-foreground/50" />
                <h3 class="mt-4 text-lg font-semibold">
                    No cameras registered
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add your first camera to start monitoring.
                </p>
                <Button class="mt-4" as-child>
                    <Link :href="create()">Add Camera</Link>
                </Button>
            </CardContent>
        </Card>

        <!-- Camera table (D-09) -->
        <div v-else class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-border bg-muted/50">
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
                            Device ID
                        </th>
                        <th
                            scope="col"
                            class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Location
                        </th>
                        <th
                            scope="col"
                            class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Status
                        </th>
                        <th
                            scope="col"
                            class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Last Seen
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="camera in cameras"
                        :key="camera.id"
                        class="border-b border-border/50 transition-colors duration-150 hover:bg-accent/50"
                    >
                        <td class="px-2 py-1">
                            <Link
                                :href="show(camera)"
                                class="font-semibold text-foreground hover:underline"
                            >
                                {{ camera.name }}
                            </Link>
                        </td>
                        <td class="px-2 py-1 font-mono text-muted-foreground">
                            {{ camera.device_id }}
                        </td>
                        <td class="max-w-[200px] truncate px-2 py-1">
                            {{ camera.location_label }}
                        </td>
                        <td class="px-2 py-1">
                            <CameraStatusDot :is-online="camera.is_online" />
                        </td>
                        <td class="px-2 py-1 font-mono text-muted-foreground">
                            {{ formatRelativeTime(camera.last_seen_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
