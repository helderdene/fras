<script setup lang="ts">
import { Form, Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, edit } from '@/routes/users';
import type { Auth, User } from '@/types';

type Props = {
    user: User;
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Users', href: index() },
        { title: props.user.name, href: edit(props.user) },
    ],
});

const page = usePage<{ auth: Auth }>();
const isOwnAccount = page.props.auth.user.id === props.user.id;
</script>

<template>
    <Head title="Edit User" />

    <div class="max-w-2xl space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                title="Edit User"
                description="Update user details or set a new password"
            />

            <Dialog v-if="!isOwnAccount">
                <DialogTrigger as-child>
                    <Button variant="destructive">Delete</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete user?</DialogTitle>
                        <DialogDescription>
                            This will permanently remove
                            {{ props.user.name }}'s account. This action cannot
                            be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Keep User</Button>
                        </DialogClose>
                        <Form
                            v-bind="UserController.destroy.form(props.user)"
                            v-slot="{ processing: deleting }"
                        >
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="deleting"
                            >
                                Delete user
                            </Button>
                        </Form>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <Form
            v-bind="UserController.update.form(props.user)"
            class="grid gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-2 gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        type="text"
                        required
                        :default-value="props.user.name"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        required
                        :default-value="props.user.email"
                    />
                    <InputError :message="errors.email" />
                </div>
            </div>

            <div class="border-t border-border pt-4">
                <p class="text-xs text-muted-foreground">
                    Leave password fields empty to keep the current password.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="New password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation"> Confirm Password </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Confirm new password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button :disabled="processing">Update User</Button>
        </Form>
    </div>
</template>
