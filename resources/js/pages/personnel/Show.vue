<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Camera as CameraIcon } from 'lucide-vue-next';

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
import { getInitials } from '@/composables/useInitials';
import { index, show, edit } from '@/routes/personnel';
import type { Personnel } from '@/types';

type Props = {
    personnel: Personnel;
    cameras: { id: number; name: string }[];
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Personnel', href: index() },
        { title: props.personnel.name, href: show(props.personnel) },
    ],
});

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
            <Card class="lg:col-span-3">
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
                        <p class="text-sm text-muted-foreground">Name</p>
                        <p class="text-sm text-foreground">
                            {{ props.personnel.name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Custom ID</p>
                        <p class="font-mono text-sm text-foreground">
                            {{ props.personnel.custom_id }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Person Type</p>
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
                        <p class="text-sm text-muted-foreground">Gender</p>
                        <p class="text-sm text-foreground">
                            {{ formatGender(props.personnel.gender) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Birthday</p>
                        <p class="text-sm text-foreground">
                            {{ formatDate(props.personnel.birthday) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">ID Card</p>
                        <p class="text-sm text-foreground">
                            {{ props.personnel.id_card || '-' }}
                        </p>
                    </div>

                    <Separator />

                    <!-- Contact section -->
                    <div>
                        <p class="text-sm text-muted-foreground">Phone</p>
                        <p class="text-sm text-foreground">
                            {{ props.personnel.phone || '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Address</p>
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
                                        action cannot be undone.
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
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Enrollment Status</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.cameras.length === 0"
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
                            v-for="camera in props.cameras"
                            :key="camera.id"
                            class="flex items-center justify-between border-b border-border py-2 last:border-0"
                        >
                            <span class="text-sm text-foreground">
                                {{ camera.name }}
                            </span>
                            <SyncStatusDot status="not-synced" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
