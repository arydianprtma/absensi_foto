<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Camera,
    Calendar,
    Clock,
    FileText,
    LayoutGrid,
    Users,
    BookOpen,
    Bookmark,
    UserCheck,
} from '@lucide/vue';
import { computed } from 'vue';
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

const page = usePage();
const userRole = computed(
    () => (page.props.auth as any)?.user?.role || 'admin',
);

const allNavItems: NavItem[] = [
    {
        title: 'Kamera Absensi AI',
        href: '/absensi',
        icon: Camera,
    },
    {
        title: 'Absensi Mapel Kelas',
        href: '/absensi-mapel',
        icon: BookOpen,
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
        title: 'Kelola Akun Guru',
        href: '/teachers',
        icon: UserCheck,
    },
    {
        title: 'Jadwal Pelajaran',
        href: '/schedules',
        icon: Bookmark,
    },
    {
        title: 'Kelola Jam Absensi',
        href: '/attendance-settings',
        icon: Clock,
    },
    {
        title: 'Kalender Hari Libur',
        href: '/holidays',
        icon: Calendar,
    },
    {
        title: 'Laporan Absensi',
        href: '/reports',
        icon: FileText,
    },
];

const navItems = computed(() => {
    if (userRole.value === 'teacher') {
        return allNavItems.filter((item) =>
            ['/absensi', '/absensi-mapel', '/schedules'].includes(item.href),
        );
    }
    return allNavItems;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="border-b border-sidebar-border pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="hover:bg-transparent"
                    >
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="py-2">
            <NavMain :items="navItems" />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border pt-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
