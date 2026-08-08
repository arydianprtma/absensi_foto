<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Clock,
    Users,
    CheckCircle2,
    Calendar,
    Sparkles,
    ArrowRight,
} from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface SubjectItem {
    id: number;
    code: string;
    name: string;
}

interface ScheduleItem {
    id: number;
    class_name: string;
    subject_id: number;
    teacher_name: string;
    day_of_week: string;
    start_time: string;
    end_time: string;
    subject?: SubjectItem;
}

defineProps<{
    user: {
        name: string;
        email: string;
        role: string;
        nip?: string;
    };
    schedules: ScheduleItem[];
    todayDate: string;
    todayDayName: string;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard Guru', href: '/dashboard' }]">
        <Head title="Dashboard Guru Pengampu" />

        <div class="mx-auto max-w-7xl space-y-8 p-6 font-sans md:p-8">
            <!-- Top Hero Banner -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 to-indigo-800 p-8 text-white shadow-xl"
            >
                <div
                    class="pointer-events-none absolute -right-10 -bottom-10 opacity-10"
                >
                    <BookOpen class="h-80 w-80 text-white" />
                </div>

                <div class="relative z-10 max-w-2xl space-y-3">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-extrabold backdrop-blur"
                    >
                        <Sparkles class="h-3.5 w-3.5" /> Portal Guru Pengampu
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight">
                        Selamat Datang, {{ user.name }}!
                    </h1>
                    <p
                        class="text-xs leading-relaxed font-medium text-indigo-100"
                    >
                        Siap melakukan absensi mata pelajaran kelas hari ini?
                        Gunakan kamera AI di kelas untuk mencatat presensi siswa
                        secara presisi.
                    </p>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <Link
                            href="/absensi-mapel"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-white px-5 py-3 text-xs font-extrabold text-indigo-700 shadow-md transition hover:bg-indigo-50"
                        >
                            <BookOpen class="h-4 w-4" /> Buka Kamera Absensi
                            Kelas <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    class="space-y-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between text-slate-500 dark:text-slate-400"
                    >
                        <span class="text-xs font-bold tracking-wider uppercase"
                            >Jadwal Mengajar Hari Ini</span
                        >
                        <div
                            class="rounded-2xl bg-indigo-50 p-2.5 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <Clock class="h-5 w-5" />
                        </div>
                    </div>
                    <div
                        class="text-2xl font-extrabold text-slate-900 dark:text-white"
                    >
                        {{ schedules.length }} Kelas
                    </div>
                    <p class="text-[11px] font-medium text-slate-400">
                        Hari {{ todayDayName }}, {{ todayDate }}
                    </p>
                </div>

                <div
                    class="space-y-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between text-slate-500 dark:text-slate-400"
                    >
                        <span class="text-xs font-bold tracking-wider uppercase"
                            >Metode Verifikasi</span
                        >
                        <div
                            class="rounded-2xl bg-emerald-50 p-2.5 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                    </div>
                    <div
                        class="text-2xl font-extrabold text-slate-900 dark:text-white"
                    >
                        InsightFace AI
                    </div>
                    <p class="text-[11px] font-medium text-slate-400">
                        Kemiripan wajah & Checklist Guru
                    </p>
                </div>

                <div
                    class="space-y-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:col-span-2 lg:col-span-1 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between text-slate-500 dark:text-slate-400"
                    >
                        <span class="text-xs font-bold tracking-wider uppercase"
                            >NIP / Identitas Guru</span
                        >
                        <div
                            class="rounded-2xl bg-purple-50 p-2.5 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div
                        class="truncate font-mono text-xl font-extrabold text-slate-900 dark:text-white"
                    >
                        {{ user.nip || 'Guru Pengampu' }}
                    </div>
                    <p class="truncate text-[11px] font-medium text-slate-400">
                        {{ user.email }}
                    </p>
                </div>
            </div>

            <!-- Schedules Table -->
            <div
                class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                >
                    <div>
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Jadwal Pelajaran Anda ({{ todayDayName }})
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Daftar kelas dan jam tatap muka hari ini
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800"
                    >
                        {{ schedules.length }} Jadwal
                    </span>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800"
                >
                    <table
                        class="w-full border-separate border-spacing-0 text-left text-sm"
                    >
                        <thead>
                            <tr
                                class="bg-slate-50 text-xs font-semibold tracking-wider text-slate-500 uppercase dark:bg-slate-800/80 dark:text-slate-400"
                            >
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Kelas
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Mata Pelajaran
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Jam Pelajaran (WIB)
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 text-right dark:border-slate-800"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                        >
                            <tr
                                v-for="sch in schedules"
                                :key="sch.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td
                                    class="border-b border-slate-100 px-4 py-3.5 font-bold text-slate-900 dark:border-slate-800/60 dark:text-white"
                                >
                                    {{ sch.class_name }}
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3.5 font-semibold text-indigo-600 dark:border-slate-800/60 dark:text-indigo-400"
                                >
                                    {{ sch.subject?.name }} ({{
                                        sch.subject?.code
                                    }})
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3.5 font-mono text-xs dark:border-slate-800/60"
                                >
                                    {{ sch.start_time.slice(0, 5) }} -
                                    {{ sch.end_time.slice(0, 5) }} WIB
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3.5 text-right dark:border-slate-800/60"
                                >
                                    <Link
                                        :href="`/absensi-mapel?class_name=${sch.class_name}&schedule_id=${sch.id}`"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400"
                                    >
                                        <BookOpen class="h-3.5 w-3.5" /> Absen
                                        Kelas
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="schedules.length === 0">
                                <td
                                    colspan="4"
                                    class="py-12 text-center text-sm text-slate-400"
                                >
                                    Tidak ada jadwal pelajaran mengajar untuk
                                    hari ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
