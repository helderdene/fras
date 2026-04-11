<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import PersonnelController from '@/actions/App/Http/Controllers/PersonnelController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PhotoDropzone from '@/components/PhotoDropzone.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { index, show, edit } from '@/routes/personnel';
import type { Personnel } from '@/types';

const props = defineProps<{
    personnel: Personnel;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Personnel', href: index() },
        { title: props.personnel.name, href: show(props.personnel) },
        { title: 'Edit', href: edit(props.personnel) },
    ],
});
</script>

<template>
    <Head title="Edit Personnel" />

    <div class="max-w-2xl space-y-6">
        <Heading
            title="Edit Personnel"
            description="Update personnel details and photo"
        />

        <Form
            v-bind="PersonnelController.update.form(props.personnel)"
            class="grid gap-6"
            v-slot="{ errors, processing }"
        >
            <!-- Section 1: Photo -->
            <div class="grid gap-2">
                <PhotoDropzone
                    name="photo"
                    :current-photo-url="props.personnel.photo_url ?? undefined"
                />
                <InputError :message="errors.photo" />
            </div>

            <Separator class="my-2" />

            <!-- Section 2: Identity -->
            <div class="space-y-4">
                <h3 class="text-base font-semibold text-foreground">
                    Identity
                </h3>
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="Full name"
                        required
                        :default-value="props.personnel.name"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="custom_id">Custom ID</Label>
                        <Input
                            id="custom_id"
                            name="custom_id"
                            placeholder="e.g. EMP-0001"
                            required
                            :default-value="props.personnel.custom_id"
                        />
                        <InputError :message="errors.custom_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="person_type">Person Type</Label>
                        <Select
                            name="person_type"
                            :default-value="String(props.personnel.person_type)"
                        >
                            <SelectTrigger id="person_type">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">Allow</SelectItem>
                                <SelectItem value="1">Block</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.person_type" />
                    </div>
                </div>
            </div>

            <Separator class="my-2" />

            <!-- Section 3: Details -->
            <div class="space-y-4">
                <h3 class="text-base font-semibold text-foreground">Details</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="gender">Gender</Label>
                        <Select
                            name="gender"
                            :default-value="
                                props.personnel.gender !== null
                                    ? String(props.personnel.gender)
                                    : undefined
                            "
                        >
                            <SelectTrigger id="gender">
                                <SelectValue placeholder="Select gender" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">Male</SelectItem>
                                <SelectItem value="1">Female</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.gender" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="birthday">Birthday</Label>
                        <Input
                            id="birthday"
                            name="birthday"
                            type="date"
                            :default-value="
                                props.personnel.birthday ?? undefined
                            "
                        />
                        <InputError :message="errors.birthday" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="id_card">ID Card</Label>
                    <Input
                        id="id_card"
                        name="id_card"
                        placeholder="ID card number"
                        :default-value="props.personnel.id_card ?? undefined"
                    />
                    <InputError :message="errors.id_card" />
                </div>
            </div>

            <Separator class="my-2" />

            <!-- Section 4: Contact -->
            <div class="space-y-4">
                <h3 class="text-base font-semibold text-foreground">Contact</h3>
                <div class="grid gap-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone"
                        name="phone"
                        placeholder="Phone number"
                        :default-value="props.personnel.phone ?? undefined"
                    />
                    <InputError :message="errors.phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="address">Address</Label>
                    <Input
                        id="address"
                        name="address"
                        placeholder="Full address"
                        :default-value="props.personnel.address ?? undefined"
                    />
                    <InputError :message="errors.address" />
                </div>
            </div>

            <Button :disabled="processing">Update Personnel</Button>
        </Form>
    </div>
</template>
