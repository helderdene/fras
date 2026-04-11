<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="flex w-full max-w-[400px] flex-col gap-6">
            <Link :href="home()" class="flex items-center gap-2 self-center">
                <div class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                    <AppLogoIcon class="text-lg" />
                </div>
                <span class="text-lg font-semibold">FRAS</span>
            </Link>

            <div class="flex flex-col gap-6">
                <Card
                    class="rounded-xl border-border/50 dark:bg-card/75 dark:backdrop-blur-lg"
                >
                    <CardHeader class="px-8 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-8 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
