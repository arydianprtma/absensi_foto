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
import NavMain, { type NavGroup } from '@/components/NavMain.vue';
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

const page = usePage();
const userRole = computed(
    () => (page.props.auth as any)?.user?.role || 'admin',
);

const adminNavGroups: NavGroup[] = [
    {
        title: 'Dashboard & Absensi',
        items: [
            {
                title: 'Dashboard Analitik',
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Kamera Absensi AI Gerbang',
                href: '/absensi',
                icon: Camera,
            },
            {
                title: 'Absensi Mapel Kelas',
                href: '/absensi-mapel',
                icon: BookOpen,
            },
        ],
    },
    {
        title: 'Manajemen Data',
        items: [
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
        ],
    },
    {
        title: 'Pengaturan & Laporan',
        items: [
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
        ],
    },
];

const teacherNavGroups: NavGroup[] = [
    {
        title: 'Utama',
        items: [
            {
                title: 'Dashboard Guru',
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Absensi Mapel Kelas',
                href: '/absensi-mapel',
                icon: BookOpen,
            },
        ],
    },
    {
        title: 'Akademik',
        items: [
            {
                title: 'Jadwal Pelajaran',
                href: '/schedules',
                icon: Bookmark,
            },
        ],
    },
];

const navGroups = computed(() => {
    if (userRole.value === 'teacher') {
        return teacherNavGroups;
    }
    return adminNavGroups;
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
            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border pt-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
