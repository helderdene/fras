<script setup lang="ts">
import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { Camera as CameraIcon } from 'lucide-vue-next';
import { ref } from 'vue';

import {
    retry,
    resyncAll,
} from '@/actions/App/Http/Controllers/EnrollmentController';
import PersonnelController from '@/actions/App/Http/Controllers/PersonnelController';
import Heading from '@/components/Heading.vue';
import SyncStatusDot from '@/components/SyncStatusDot.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
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
import { Spinner } from '@/components/ui/spinner';
import { getInitials } from '@/composables/useInitials';
import { index, show, edit } from '@/routes/personnel';
import type {
    CameraWithEnrollment,
    EnrollmentStatusPayload,
    Personnel,
} from '@/types';

type Props = {
    personnel: Personnel;
    cameras: CameraWithEnrollment[];
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Personnel', href: index() },
        { title: props.personnel.name, href: show(props.personnel) },
    ],
});

// Local reactive copy for real-time updates — normalize 'enrolled' -> 'synced'
const cameras = ref<CameraWithEnrollment[]>(
    props.cameras.map((cam) => ({
        ...cam,
        enrollment: cam.enrollment
            ? {
                  ...cam.enrollment,
                  status:
                      cam.enrollment.status === 'enrolled'
                          ? 'synced'
                          : cam.enrollment.status,
              }
            : null,
    })),
);

const enrollmentLabels = { synced: 'Enrolled', 'not-synced': 'Not synced' };

// Real-time enrollment updates (D-09)
useEcho(
    'fras.alerts',
    '.EnrollmentStatusChanged',
    (payload: EnrollmentStatusPayload) => {
        if (payload.personnel_id === props.personnel.id) {
            const cam = cameras.value.find((c) => c.id === payload.camera_id);

            if (cam) {
                cam.enrollment = {
                    status:
                        payload.status === 'enrolled'
                            ? 'synced'
                            : payload.status,
                    enrolled_at: payload.enrolled_at,
                    last_error: payload.last_error,
                };
            }
        }
    },
);

const retryProcessing = ref<number | null>(null);
const resyncProcessing = ref(false);

function retryEnrollment(cameraId: number) {
    retryProcessing.value = cameraId;

    router.post(
        retry.url({ personnel: props.personnel, camera: { id: cameraId } }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                retryProcessing.value = null;
            },
        },
    );
}

function resyncAllCameras() {
    resyncProcessing.value = true;

    router.post(
        resyncAll.url(props.personnel),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                resyncProcessing.value = false;
            },
            onSuccess: () => {
                // Optimistically set all to pending
                cameras.value.forEach((cam) => {
                    cam.enrollment = {
                        status: 'pending',
                        enrolled_at: null,
                        last_error: null,
                    };
                });
            },
        },
    );
}

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

function formatGender(gender: number | null): string {
    if (gender === 0) {
        return 'Male';
    }

    if (gender === 1) {
        return 'Female';
    }

    return '-';
}

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '-';
    }

    return new Date(dateString).toLocaleDateString();
}
</script>

<template>
    <Head :title="props.personnel.name" />

    <div class="space-y-6">
        <Heading :title="props.personnel.name" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <!-- Personnel Information (left, 3/5 width) -->
            <Card class="lg:col-span-3 dark:border-border/50">
                <CardContent class="space-y-4 pt-6">
                    <!-- Large photo -->
                    <div class="flex justify-center">
                        <Avatar class="size-[200px] rounded-lg">
                            <AvatarImage
                                v-if="props.personnel.photo_url"
                                :src="props.personnel.photo_url"
                                :alt="props.personnel.name"
                                class="object-cover"
                            />
                            <AvatarFallback class="rounded-lg text-4xl">
                                {{ getInitials(props.personnel.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    <Separator />

                    <!-- Identity section -->
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Name
                        </p>
                        <p class="text-sm text-foreground">
                            {{ props.personnel.name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Custom ID
                        </p>
                        <p class="font-mono text-xs text-foreground">
                            {{ props.personnel.custom_id }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Person Type
                        </p>
                        <Badge
                            :variant="
                                props.personnel.person_type === 1
                                    ? 'destructive'
                                    : 'secondary'
                            "
                        >
                            {{
                                props.personnel.person_type === 1
                                    ? 'Block'
                                    : 'Allow'
                            }}
                        </Badge>
                    </div>

                    <Separator />

                    <!-- Details section -->
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Gender
                        </p>
                        <p class="text-sm text-foreground">
                            {{ formatGender(props.personnel.gender) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Birthday
                        </p>
                        <p class="font-mono text-xs text-foreground">
                            {{ formatDate(props.personnel.birthday) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            ID Card
                        </p>
                        <p class="font-mono text-xs text-foreground">
                            {{ props.personnel.id_card || '-' }}
                        </p>
                    </div>

                    <Separator />

                    <!-- Contact section -->
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Phone
                        </p>
                        <p class="font-mono text-xs text-foreground">
                            {{ props.personnel.phone || '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Address
                        </p>
                        <p class="text-sm text-foreground">
                            {{ props.personnel.address || '-' }}
                        </p>
                    </div>

                    <Separator />

                    <!-- Action buttons -->
                    <div class="flex items-center gap-3">
                        <Button variant="outline" as-child>
                            <Link :href="edit(props.personnel)">Edit</Link>
                        </Button>

                        <!-- Delete confirmation dialog -->
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="destructive">Delete</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete personnel?</DialogTitle>
                                    <DialogDescription>
                                        This will permanently remove
                                        {{ props.personnel.name }} and all
                                        associated enrollment records. This
                                        action cannot be undone. <br /><br />
                                        <span class="font-semibold">
                                            This person will also be removed
                                            from all enrolled cameras.
                                        </span>
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Keep Personnel
                                        </Button>
                                    </DialogClose>
                                    <Form
                                        v-bind="
                                            PersonnelController.destroy.form(
                                                props.personnel,
                                            )
                                        "
                                        v-slot="{ processing }"
                                    >
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            Delete Personnel
                                        </Button>
                                    </Form>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </CardContent>
            </Card>

            <!-- Enrollment sidebar (right, 2/5 width) -->
            <Card class="lg:col-span-2 dark:border-border/50">
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0"
                >
                    <CardTitle>Enrollment Status</CardTitle>
                    <Button
                        v-if="cameras.length > 0"
                        variant="default"
                        size="sm"
                        :disabled="resyncProcessing"
                        @click="resyncAllCameras"
                    >
                        <Spinner v-if="resyncProcessing" class="size-3" />
                        Re-sync All
                    </Button>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="cameras.length === 0"
                        class="flex flex-col items-center justify-center py-12 text-center"
                    >
                        <CameraIcon class="size-10 text-muted-foreground/40" />
                        <h3 class="mt-3 text-sm font-semibold">
                            No cameras registered
                        </h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Register cameras to enable enrollment.
                        </p>
                    </div>
                    <div v-else>
                        <div
                            v-for="cam in cameras"
                            :key="cam.id"
                            class="space-y-1 border-b border-border py-3 last:border-0"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="text-sm text-foreground">
                                    {{ cam.name }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <SyncStatusDot
                                        :status="
                                            cam.enrollment?.status ??
                                            'not-synced'
                                        "
                                        :labels="enrollmentLabels"
                                    />
                                    <Button
                                        v-if="
                                            cam.enrollment?.status === 'failed'
                                        "
                                        variant="outline"
                                        size="sm"
                                        :disabled="retryProcessing === cam.id"
                                        :aria-label="`Retry enrollment to ${cam.name}`"
                                        @click="retryEnrollment(cam.id)"
                                    >
                                        <Spinner
                                            v-if="retryProcessing === cam.id"
                                            class="size-3"
                                        />
                                        Retry Enrollment
                                    </Button>
                                </div>
                            </div>
                            <p
                                v-if="
                                    cam.enrollment?.status === 'synced' &&
                                    cam.enrollment?.enrolled_at
                                "
                                class="text-sm text-muted-foreground"
                            >
                                <time :datetime="cam.enrollment.enrolled_at">
                                    Enrolled
                                    {{
                                        formatRelativeTime(
                                            cam.enrollment.enrolled_at,
                                        )
                                    }}
                                </time>
                            </p>
                            <p
                                v-else-if="
                                    cam.enrollment?.status === 'failed' &&
                                    cam.enrollment?.last_error
                                "
                                class="text-sm text-red-600 dark:text-red-400"
                            >
                                {{ cam.enrollment.last_error }}
                            </p>
                            <p
                                v-else-if="cam.enrollment?.status === 'pending'"
                                class="text-sm text-muted-foreground"
                            >
                                <Spinner class="inline size-3" />
                                Syncing...
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
