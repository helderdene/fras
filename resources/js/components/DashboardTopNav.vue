<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    BellRing,
    Moon,
    PanelLeft,
    PanelRight,
    Settings,
    Sun,
} from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type Props = {
    leftRailOpen: boolean;
    rightFeedOpen: boolean;
    soundEnabled: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'toggle-left-rail': [];
    'toggle-right-feed': [];
    'toggle-sound': [];
}>();

const page = usePage<{ auth: Auth }>();
const user = page.props.auth.user;

const { resolvedAppearance, updateAppearance } = useAppearance();
const { isCurrentOrParentUrl } = useCurrentUrl();

const navItems = [
    { label: 'Cameras', url: '/cameras' },
    { label: 'Personnel', url: '/personnel' },
    { label: 'Users', url: '/users' },
    { label: 'Alerts', url: '/alerts' },
    { label: 'Events', url: '/events' },
];

function toggleTheme() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="relative z-10 flex h-14 shrink-0 items-center justify-between border-b border-border bg-card px-4 text-foreground shadow-sm"
    >
        <div class="flex items-center gap-2">
            <Link :href="dashboard()" class="flex items-center gap-2">
                <div
                    class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <AppLogoIcon class="text-sm" />
                </div>
                <span class="text-sm font-semibold tracking-wide">FRAS</span>
            </Link>
            <Button
                variant="ghost"
                size="icon-sm"
                :aria-expanded="leftRailOpen"
                aria-label="Toggle camera rail"
                @click="emit('toggle-left-rail')"
            >
                <PanelLeft class="size-5 text-foreground" />
            </Button>
        </div>

        <nav class="absolute left-1/2 flex -translate-x-1/2 items-center gap-1">
            <Link
                v-for="item in navItems"
                :key="item.label"
                :href="item.url"
                class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                :class="
                    isCurrentOrParentUrl(item.url)
                        ? 'bg-primary/10 text-primary'
                        : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                "
            >
                {{ item.label }}
            </Link>
        </nav>
        <div class="flex items-center gap-1">
            <Button
                variant="ghost"
                size="icon-sm"
                :aria-expanded="rightFeedOpen"
                aria-label="Toggle alert feed"
                @click="emit('toggle-right-feed')"
            >
                <PanelRight class="size-5 text-foreground" />
            </Button>
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="
                                resolvedAppearance === 'dark'
                                    ? 'Switch to light mode'
                                    : 'Switch to dark mode'
                            "
                            @click="toggleTheme"
                        >
                            <Sun
                                v-if="resolvedAppearance === 'dark'"
                                class="size-5 text-foreground"
                            />
                            <Moon v-else class="size-5 text-foreground" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{
                            resolvedAppearance === 'dark'
                                ? 'Switch to light mode'
                                : 'Switch to dark mode'
                        }}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="
                                soundEnabled
                                    ? 'Mute alert sounds'
                                    : 'Enable alert sounds'
                            "
                            @click="emit('toggle-sound')"
                        >
                            <BellRing
                                v-if="soundEnabled"
                                class="size-5 text-foreground"
                            />
                            <Bell v-else class="size-5 text-foreground" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{
                            soundEnabled
                                ? 'Mute alert sounds'
                                : 'Enable alert sounds'
                        }}
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <Button variant="ghost" size="icon-sm" :as-child="true">
                <Link :href="edit()" aria-label="Settings" prefetch>
                    <Settings class="size-5 text-foreground" />
                </Link>
            </Button>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="sm" class="gap-2">
                        <span class="text-sm text-foreground">{{
                            user.name
                        }}</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
