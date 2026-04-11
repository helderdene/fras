<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Moon, PanelLeft, PanelRight, Settings, Sun } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type Props = {
    leftRailOpen: boolean;
    rightFeedOpen: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'toggle-left-rail': [];
    'toggle-right-feed': [];
}>();

const page = usePage<{ auth: Auth }>();
const user = page.props.auth.user;

const { resolvedAppearance, updateAppearance } = useAppearance();

function toggleTheme() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="flex h-12 shrink-0 items-center justify-between border-b border-border bg-muted px-3"
    >
        <div class="flex items-center gap-2">
            <Link :href="dashboard()" class="flex items-center gap-2">
                <AppLogoIcon class="size-5 text-foreground" />
                <span
                    class="text-sm font-semibold tracking-wide text-foreground"
                    >FRAS</span
                >
            </Link>
            <Button
                variant="ghost"
                size="icon-sm"
                :aria-expanded="leftRailOpen"
                aria-label="Toggle camera rail"
                @click="emit('toggle-left-rail')"
            >
                <PanelLeft class="size-4" />
            </Button>
        </div>
        <div class="flex items-center gap-1">
            <Button
                variant="ghost"
                size="icon-sm"
                :aria-expanded="rightFeedOpen"
                aria-label="Toggle alert feed"
                @click="emit('toggle-right-feed')"
            >
                <PanelRight class="size-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon-sm"
                aria-label="Toggle theme"
                @click="toggleTheme"
            >
                <Sun v-if="resolvedAppearance === 'dark'" class="size-4" />
                <Moon v-else class="size-4" />
            </Button>
            <Button variant="ghost" size="icon-sm" :as-child="true">
                <Link :href="edit()" aria-label="Settings" prefetch>
                    <Settings class="size-4" />
                </Link>
            </Button>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="sm" class="gap-2">
                        <span class="text-xs">{{ user.name }}</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
