<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { Users } from 'lucide-vue-next';
import { ref } from 'vue';
import CameraController from '@/actions/App/Http/Controllers/CameraController';
import CameraStatusDot from '@/components/CameraStatusDot.vue';
import Heading from '@/components/Heading.vue';
import MapboxMap from '@/components/MapboxMap.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { useAppearance } from '@/composables/useAppearance';
import { index, show, edit } from '@/routes/cameras';
import type { Camera, CameraStatusPayload } from '@/types';

type Props = {
    camera: Camera;
    mapboxToken: string;
    mapboxDarkStyle: string;
    mapboxLightStyle: string;
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Cameras', href: index() },
        { title: props.camera.name, href: show(props.camera) },
    ],
});

const { resolvedAppearance } = useAppearance();

// Local reactive copy for real-time updates
const camera = ref<Camera>({ ...props.camera });

// Real-time status updates (D-10)
useEcho(
    'fras.alerts',
    '.CameraStatusChanged',
    (payload: CameraStatusPayload) => {
        if (payload.camera_id === camera.value.id) {
            camera.value.is_online = payload.is_online;
            camera.value.last_seen_at = payload.last_seen_at;
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
    <Head :title="camera.name" />

    <div class="space-y-6">
        <Heading :title="camera.name" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <!-- Camera Information (left, 3/5 width) per D-13 -->
            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Camera Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <p class="text-sm text-muted-foreground">Name</p>
                        <p class="text-sm text-foreground">
                            {{ camera.name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Device ID</p>
                        <p class="font-mono text-sm text-foreground">
                            {{ camera.device_id }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Location</p>
                        <p class="text-sm text-foreground">
                            {{ camera.location_label }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Status</p>
                        <CameraStatusDot :is-online="camera.is_online" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Last Seen</p>
                        <p class="text-sm text-foreground">
                            {{ formatRelativeTime(camera.last_seen_at) }}
                        </p>
                    </div>

                    <Separator />

                    <!-- Mapbox map preview (D-14, read-only) -->
                    <div
                        class="h-48 overflow-hidden rounded-lg border border-border"
                    >
                        <MapboxMap
                            :latitude="Number(camera.latitude)"
                            :longitude="Number(camera.longitude)"
                            :interactive="false"
                            :access-token="props.mapboxToken"
                            :style-url="
                                resolvedAppearance === 'dark'
                                    ? props.mapboxDarkStyle
                                    : props.mapboxLightStyle
                            "
                        />
                    </div>

                    <Separator />

                    <!-- Action buttons -->
                    <div class="flex items-center gap-3">
                        <Button variant="outline" as-child>
                            <Link :href="edit(camera)">Edit</Link>
                        </Button>

                        <!-- Delete confirmation dialog (D-15) -->
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="destructive">Delete</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete camera?</DialogTitle>
                                    <DialogDescription>
                                        This will permanently remove
                                        {{ camera.name }} and all associated
                                        enrollment records. This action cannot
                                        be undone.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Keep Camera
                                        </Button>
                                    </DialogClose>
                                    <Form
                                        v-bind="
                                            CameraController.destroy.form(
                                                camera,
                                            )
                                        "
                                        v-slot="{ processing }"
                                    >
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            Delete camera
                                        </Button>
                                    </Form>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </CardContent>
            </Card>

            <!-- Enrolled Personnel (right, 2/5 width) per D-13, D-16 -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Enrolled Personnel</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex flex-col items-center justify-center py-12 text-center"
                    >
                        <Users class="size-10 text-muted-foreground/40" />
                        <h3 class="mt-3 text-sm font-semibold">
                            No personnel enrolled
                        </h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Personnel will appear here once enrollment is
                            configured in a future update.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
