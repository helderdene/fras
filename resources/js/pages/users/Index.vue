<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, UserCog } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index, create, edit } from '@/routes/users';
import type { User } from '@/types';

type Props = {
    users: User[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: index() }],
    },
});
</script>

<template>
    <Head title="Users" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading title="Users" />
            <Button as-child>
                <Link :href="create()">
                    <Plus class="size-4" />
                    Add User
                </Link>
            </Button>
        </div>

        <!-- Empty state -->
        <Card v-if="users.length === 0" class="p-12">
            <CardContent
                class="flex flex-col items-center justify-center text-center"
            >
                <UserCog class="size-12 text-muted-foreground/50" />
                <h3 class="mt-4 text-lg font-semibold">No users found</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create your first user to get started.
                </p>
                <Button class="mt-4" as-child>
                    <Link :href="create()">Add User</Link>
                </Button>
            </CardContent>
        </Card>

        <!-- Users table -->
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
                            Email
                        </th>
                        <th
                            scope="col"
                            class="px-2 py-1 text-left font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Created
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-b border-border/50 transition-colors duration-150 hover:bg-accent/50"
                    >
                        <td class="px-2 py-1">
                            <Link
                                :href="edit(user)"
                                class="font-semibold text-foreground hover:underline"
                            >
                                {{ user.name }}
                            </Link>
                        </td>
                        <td class="px-2 py-1 font-mono text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-2 py-1 font-mono text-muted-foreground">
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
