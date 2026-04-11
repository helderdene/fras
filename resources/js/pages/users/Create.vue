<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, create } from '@/routes/users';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'Add User', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Add User" />

    <div class="max-w-2xl space-y-6">
        <Heading title="Add User" description="Create a new operator account" />

        <Form
            v-bind="UserController.store.form()"
            class="grid gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" type="text" required />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" name="email" type="email" required />
                    <InputError :message="errors.email" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm Password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button :disabled="processing">Create User</Button>
        </Form>
    </div>
</template>
