<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Camera,
    Eye,
    Lock,
    MonitorDot,
    Radio,
    ShieldCheck,
} from 'lucide-vue-next';
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
    <Head title="FRAS — Face Recognition Alert System" />
    <div class="relative flex min-h-svh flex-col bg-background text-foreground">
        <!-- Ambient background glow -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute -left-32 -top-32 size-96 rounded-full bg-primary/5 blur-[120px]"
            />
            <div
                class="absolute -bottom-48 -right-48 size-[500px] rounded-full bg-primary/3 blur-[150px]"
            />
        </div>

        <!-- Top nav bar -->
        <header
            class="relative z-10 flex h-14 items-center justify-between border-b border-border/30 px-6"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <AppLogoIcon class="text-sm" />
                </div>
                <span class="text-sm font-semibold tracking-wide">FRAS</span>
            </div>
            <nav class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    View Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Get Started
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero section -->
        <main class="relative z-10 flex flex-1 flex-col items-center justify-center px-6">
            <div class="flex max-w-3xl flex-col items-center gap-8 text-center">
                <!-- Badge -->
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-border/50 bg-card/50 px-4 py-1.5 text-xs text-muted-foreground backdrop-blur-sm"
                >
                    <Radio class="size-3 text-emerald-500" />
                    <span>Real-Time Monitoring System</span>
                </div>

                <!-- Headline -->
                <h1
                    class="text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl"
                >
                    Face Recognition
                    <br />
                    <span class="text-primary">Alert System</span>
                </h1>

                <p
                    class="max-w-xl text-lg leading-relaxed text-muted-foreground"
                >
                    AI-powered surveillance monitoring with real-time face
                    recognition, severity-classified alerts, and map-based
                    command center operations.
                </p>

                <!-- CTA buttons -->
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex h-11 items-center gap-2 rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:bg-primary/90 hover:shadow-primary/30"
                    >
                        <MonitorDot class="size-4" />
                        Open Command Center
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex h-11 items-center gap-2 rounded-md bg-primary px-6 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:bg-primary/90 hover:shadow-primary/30"
                        >
                            <Lock class="size-4" />
                            Operator Login
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="inline-flex h-11 items-center gap-2 rounded-md border border-border bg-card/50 px-6 text-sm font-semibold backdrop-blur-sm transition-colors hover:bg-accent"
                        >
                            Register Account
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Feature cards -->
            <div class="mt-20 grid w-full max-w-4xl grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    class="group rounded-xl border border-border/40 bg-card/30 p-6 backdrop-blur-sm transition-all hover:border-border/60 hover:bg-card/50"
                >
                    <div
                        class="mb-4 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Eye class="size-5" />
                    </div>
                    <h3 class="text-sm font-semibold">
                        Real-Time Recognition
                    </h3>
                    <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                        AI cameras detect and match faces in milliseconds.
                        Block-list matches trigger instant critical alerts.
                    </p>
                </div>
                <div
                    class="group rounded-xl border border-border/40 bg-card/30 p-6 backdrop-blur-sm transition-all hover:border-border/60 hover:bg-card/50"
                >
                    <div
                        class="mb-4 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Camera class="size-5" />
                    </div>
                    <h3 class="text-sm font-semibold">
                        Multi-Camera Command
                    </h3>
                    <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                        Map-based dashboard with live camera markers, pulse
                        animations, and three-panel ops layout.
                    </p>
                </div>
                <div
                    class="group rounded-xl border border-border/40 bg-card/30 p-6 backdrop-blur-sm transition-all hover:border-border/60 hover:bg-card/50"
                >
                    <div
                        class="mb-4 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <ShieldCheck class="size-5" />
                    </div>
                    <h3 class="text-sm font-semibold">
                        Severity Classification
                    </h3>
                    <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                        Events classified as critical, warning, or info.
                        Audible alerts ensure block-list matches are never
                        missed.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="relative z-10 flex h-12 items-center justify-center border-t border-border/30 text-xs text-muted-foreground"
        >
            <span>FRAS &mdash; HyperDrive System</span>
        </footer>
    </div>
</template>
