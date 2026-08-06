<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Users,
    UserCheck,
    Clock,
    Sparkles,
    Camera,
    TrendingUp,
    ScanFace,
    Calendar,
    ArrowUpRight,
    Activity,
    ShieldCheck,
    BarChart3,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Stats {
    total_students: number;
    registered_faces: number;
    present_today: number;
    late_today: number;
    absent_today: number;
    attendance_rate: number;
}

interface TrendDay {
    day: string;
    date: string;
    hadir: number;
    terlambat: number;
    izin: number;
}

interface LogItem {
    id: number;
    student_name: string;
    nisn: string;
    class_name: string;
    check_in_time: string;
    status: string;
    similarity_percentage: number;
    photo_url: string | null;
}

defineProps<{
    stats: Stats;
    weeklyTrend: TrendDay[];
    recentLogs: LogItem[];
}>();

const currentTime = ref('');
const currentDate = ref('');
let timer: number | null = null;

const updateClock = () => {
    const now = new Date();
    currentTime.value =
        now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }) + ' WIB';
    currentDate.value = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

onMounted(() => {
    updateClock();
    timer = window.setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]">
        <Head title="Dashboard Analytics Absensi Siswa" />

        <div class="mx-auto max-w-7xl space-y-8 p-6 font-sans md:p-8">
            <!-- Header Welcome Banner -->
            <div
                class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 p-8 text-white shadow-lg md:p-10"
            >
                <div
                    class="relative z-10 flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center"
                >
                    <div class="max-w-2xl space-y-3">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400"
                        >
                            <span
                                class="h-2 w-2 animate-ping rounded-full bg-emerald-400"
                            ></span>
                            <Sparkles class="h-3.5 w-3.5 text-emerald-400" />
                            InsightFace AI System Active
                        </div>

                        <h1
                            class="text-3xl leading-tight font-extrabold tracking-tight text-white md:text-4xl"
                        >
                            Dashboard Analytics Absensi Siswa
                        </h1>

                        <p class="text-sm leading-relaxed text-slate-300">
                            Pantau tingkat kehadiran harian, data foto wajah
                            512-dimensi, dan log absensi siswa secara real-time.
                        </p>

                        <!-- Live Clock Widget -->
                        <div
                            class="flex items-center gap-4 pt-1 text-xs font-medium text-slate-300"
                        >
                            <span
                                class="flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-1.5"
                            >
                                <Clock class="h-3.5 w-3.5 text-indigo-400" />
                                {{ currentTime }}
                            </span>
                            <span
                                class="flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-1.5"
                            >
                                <Calendar
                                    class="h-3.5 w-3.5 text-emerald-400"
                                />
                                {{ currentDate }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex w-full flex-col items-center gap-3 sm:flex-row lg:w-auto"
                    >
                        <Link
                            href="/absensi"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-md transition hover:bg-emerald-400 sm:w-auto"
                        >
                            <Camera class="h-5 w-5" /> Buka Kamera Absensi
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Siswa -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Total Siswa</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3
                            class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                        >
                            {{ stats.total_students }}
                        </h3>
                        <div
                            class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                        >
                            <span
                                class="flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                <ShieldCheck class="h-3.5 w-3.5" />
                                {{ stats.registered_faces }} Terdaftar AI
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hadir Tepat Waktu -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Hadir Tepat Waktu</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <UserCheck class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3
                            class="text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400"
                        >
                            {{ stats.present_today }}
                        </h3>
                        <div
                            class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                        >
                            <span>Sebelum Batas Terlambat</span>
                        </div>
                    </div>
                </div>

                <!-- Terlambat Hari Ini -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Terlambat</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                        >
                            <Clock class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3
                            class="text-3xl font-extrabold tracking-tight text-amber-600 dark:text-amber-400"
                        >
                            {{ stats.late_today }}
                        </h3>
                        <div
                            class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                        >
                            <span>Setelah Batas Terlambat</span>
                        </div>
                    </div>
                </div>

                <!-- Rate Kehadiran -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Persentase Kehadiran</span
                        >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3
                            class="text-3xl font-extrabold tracking-tight text-indigo-600 dark:text-indigo-400"
                        >
                            {{ stats.attendance_rate }}%
                        </h3>
                        <div
                            class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400"
                        >
                            <span class="font-medium text-rose-500"
                                >{{ stats.absent_today }} Siswa Belum
                                Absen</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Analytics Trends Chart -->
            <div
                class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8 dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <BarChart3 class="h-5 w-5" />
                        </div>
                        <div>
                            <h2
                                class="text-lg font-bold tracking-wide text-slate-900 dark:text-white"
                            >
                                Grafik Analytics Tren Kehadiran Mingguan
                            </h2>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Perbandingan siswa Hadir Tepat Waktu vs
                                Terlambat selama 7 hari terakhir
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <span
                            class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400"
                        >
                            <span
                                class="h-3 w-3 rounded-full bg-emerald-500"
                            ></span>
                            Hadir
                        </span>
                        <span
                            class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400"
                        >
                            <span
                                class="h-3 w-3 rounded-full bg-amber-500"
                            ></span>
                            Terlambat
                        </span>
                    </div>
                </div>

                <!-- Custom SVG Bar Chart -->
                <div
                    class="grid h-44 grid-cols-7 items-end gap-3 border-b border-slate-100 px-2 pt-6 pb-2 dark:border-slate-800"
                >
                    <div
                        v-for="t in weeklyTrend"
                        :key="t.date"
                        class="group flex h-full flex-col items-center justify-end gap-2"
                    >
                        <!-- Bar container -->
                        <div
                            class="relative flex h-32 w-full items-end justify-center gap-1.5 overflow-hidden rounded-xl bg-slate-50 p-1.5 dark:bg-slate-800/40"
                        >
                            <!-- Hadir Bar -->
                            <div
                                :style="{
                                    height:
                                        Math.min(
                                            100,
                                            Math.max(8, t.hadir * 25),
                                        ) + '%',
                                }"
                                class="relative w-1/2 rounded-lg bg-emerald-500 transition-all duration-300 group-hover:bg-emerald-400"
                            >
                                <span
                                    v-if="t.hadir > 0"
                                    class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ t.hadir }}
                                </span>
                            </div>

                            <!-- Terlambat Bar -->
                            <div
                                :style="{
                                    height:
                                        Math.min(
                                            100,
                                            Math.max(8, t.terlambat * 25),
                                        ) + '%',
                                }"
                                class="relative w-1/2 rounded-lg bg-amber-500 transition-all duration-300 group-hover:bg-amber-400"
                            >
                                <span
                                    v-if="t.terlambat > 0"
                                    class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-bold text-amber-600 dark:text-amber-400"
                                >
                                    {{ t.terlambat }}
                                </span>
                            </div>
                        </div>

                        <!-- X Label -->
                        <div class="text-center">
                            <p
                                class="text-xs font-bold text-slate-800 capitalize dark:text-slate-200"
                            >
                                {{ t.day }}
                            </p>
                            <p class="text-[10px] text-slate-400">
                                {{ t.date }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Table Card -->
            <div
                class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8 dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-5 dark:border-slate-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            <Activity class="h-5 w-5" />
                        </div>
                        <div>
                            <h2
                                class="text-lg font-bold tracking-wide text-slate-900 dark:text-white"
                            >
                                Log Absensi Verifikasi Terbaru
                            </h2>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Aktivitas kehadiran siswa terkini yang
                                diverifikasi oleh InsightFace AI
                            </p>
                        </div>
                    </div>

                    <Link
                        href="/reports"
                        class="flex items-center gap-1 text-xs font-semibold text-indigo-600 transition hover:underline dark:text-indigo-400"
                    >
                        Lihat Selengkapnya <ArrowUpRight class="h-4 w-4" />
                    </Link>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800"
                >
                    <div class="overflow-x-auto">
                        <table
                            class="w-full border-separate border-spacing-0 text-left text-sm"
                        >
                            <thead>
                                <tr
                                    class="bg-slate-50 text-xs font-bold tracking-wider text-slate-500 uppercase dark:bg-slate-800/80 dark:text-slate-400"
                                >
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Bukti Foto AI
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        NISN
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Nama Siswa
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Kelas
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Waktu Masuk
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Akurasi InsightFace
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                            >
                                <tr
                                    v-for="log in recentLogs"
                                    :key="log.id"
                                    class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <div
                                            class="h-11 w-11 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                                        >
                                            <img
                                                v-if="log.photo_url"
                                                :src="log.photo_url"
                                                alt="Foto Absen"
                                                class="h-full w-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-400"
                                            >
                                                N/A
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-mono font-medium text-slate-900 dark:border-slate-800/60 dark:text-slate-100"
                                    >
                                        {{ log.nisn }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-900 dark:border-slate-800/60 dark:text-white"
                                    >
                                        {{ log.student_name }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <span
                                            class="rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {{ log.class_name }}
                                        </span>
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-semibold text-emerald-600 dark:border-slate-800/60 dark:text-emerald-400"
                                    >
                                        {{ log.check_in_time }} WIB
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <span
                                            class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400"
                                        >
                                            {{ log.similarity_percentage }}%
                                            Match
                                        </span>
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <span
                                            :class="[
                                                'rounded-full border px-3 py-1 text-xs font-bold shadow-sm',
                                                log.status === 'hadir'
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                    : log.status === 'alpa'
                                                      ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400'
                                                      : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400',
                                            ]"
                                        >
                                            {{ log.status }}
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="recentLogs.length === 0">
                                    <td
                                        colspan="7"
                                        class="py-16 text-center text-slate-400"
                                    >
                                        <div
                                            class="flex flex-col items-center justify-center space-y-3"
                                        >
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800"
                                            >
                                                <ScanFace class="h-6 w-6" />
                                            </div>
                                            <p
                                                class="text-sm font-medium text-slate-600 dark:text-slate-300"
                                            >
                                                Belum ada aktivitas absensi
                                                tercatat hari ini.
                                            </p>
                                            <Link
                                                href="/absensi"
                                                class="text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                            >
                                                Mulai Kamera Absensi &rarr;
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
