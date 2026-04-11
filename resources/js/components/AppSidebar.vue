<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Camera,
    History,
    LayoutGrid,
    ShieldAlert,
    UserCog,
    Users,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as alertsIndex } from '@/routes/alerts';
import { index as camerasIndex } from '@/routes/cameras';
import { index as eventsIndex } from '@/routes/events';
import { index as personnelIndex } from '@/routes/personnel';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Cameras',
        href: camerasIndex(),
        icon: Camera,
    },
    {
        title: 'Personnel',
        href: personnelIndex(),
        icon: Users,
    },
    {
        title: 'Users',
        href: usersIndex(),
        icon: UserCog,
    },
    {
        title: 'Live Alerts',
        href: alertsIndex(),
        icon: ShieldAlert,
    },
    {
        title: 'Event History',
        href: eventsIndex(),
        icon: History,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="dark:bg-sidebar/80 dark:backdrop-blur-xl"
    >
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
