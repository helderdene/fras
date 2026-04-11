<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome" />
    <div
        class="flex min-h-svh flex-col items-center justify-center bg-background p-6"
    >
        <div class="flex w-full max-w-sm flex-col items-center gap-8">
            <!-- Logo -->
            <div class="flex flex-col items-center gap-3">
                <AppLogoIcon class="size-12 fill-current text-primary" />
                <h1
                    class="text-[28px] font-semibold tracking-tight text-foreground"
                >
                    FRAS
                </h1>
                <p class="text-center text-sm text-muted-foreground">
                    Face Recognition Alert System
                </p>
            </div>

            <!-- Navigation -->
            <div class="flex w-full flex-col gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    View Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-flex h-10 w-full items-center justify-center rounded-md border border-border text-sm font-semibold text-foreground transition-colors hover:bg-accent"
                    >
                        Register
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>
