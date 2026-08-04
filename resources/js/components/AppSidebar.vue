<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Camera, Clock, FileText, LayoutGrid, Users } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
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
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Kamera Absensi AI',
        href: '/absensi',
        icon: Camera,
    },
    {
        title: 'Dashboard Analitik',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Data Siswa & Wajah',
        href: '/students',
        icon: Users,
    },
    {
        title: 'Kelola Jam Absensi',
        href: '/attendance-settings',
        icon: Clock,
    },
    {
        title: 'Laporan Absensi',
        href: '/reports',
        icon: FileText,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-slate-200 dark:border-slate-800 bg-slate-900 text-slate-100">
        <SidebarHeader class="border-b border-slate-200/50 dark:border-slate-800/80 pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-transparent">
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="py-2">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="border-t border-slate-200/50 dark:border-slate-800/80 pt-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
