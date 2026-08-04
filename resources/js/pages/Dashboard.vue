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

const props = defineProps<{
    stats: Stats;
    weeklyTrend: TrendDay[];
    recentLogs: LogItem[];
}>();

const currentTime = ref('');
const currentDate = ref('');
let timer: number | null = null;

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
    currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

onMounted(() => {
    updateClock();
    timer = window.setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]">
        <Head title="Dashboard Analytics Absensi Siswa" />

        <div class="p-6 md:p-8 max-w-7xl mx-auto space-y-8 font-sans">
            <!-- Header Welcome Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-slate-900 text-white border border-slate-800 p-8 md:p-10 shadow-lg">
                <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <Sparkles class="w-3.5 h-3.5 text-emerald-400" />
                            InsightFace AI System Active
                        </div>

                        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white leading-tight">
                            Dashboard Analytics Absensi Siswa
                        </h1>

                        <p class="text-sm text-slate-300 leading-relaxed">
                            Pantau tingkat kehadiran harian, data foto wajah 512-dimensi, dan log absensi siswa secara real-time.
                        </p>

                        <!-- Live Clock Widget -->
                        <div class="flex items-center gap-4 text-xs font-medium text-slate-300 pt-1">
                            <span class="flex items-center gap-1.5 bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700">
                                <Clock class="w-3.5 h-3.5 text-indigo-400" /> {{ currentTime }}
                            </span>
                            <span class="flex items-center gap-1.5 bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700">
                                <Calendar class="w-3.5 h-3.5 text-emerald-400" /> {{ currentDate }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                        <Link
                            href="/absensi"
                            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <Camera class="w-5 h-5" /> Buka Kamera Absensi
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Siswa -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Siswa</span>
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                            <Users class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ stats.total_students }}</h3>
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                <ShieldCheck class="w-3.5 h-3.5" /> {{ stats.registered_faces }} Terdaftar AI
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hadir Tepat Waktu -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hadir Tepat Waktu</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <UserCheck class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">{{ stats.present_today }}</h3>
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span>Sebelum Batas Terlambat</span>
                        </div>
                    </div>
                </div>

                <!-- Terlambat Hari Ini -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Terlambat</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <Clock class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">{{ stats.late_today }}</h3>
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span>Setelah Batas Terlambat</span>
                        </div>
                    </div>
                </div>

                <!-- Rate Kehadiran -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Persentase Kehadiran</span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <TrendingUp class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400 tracking-tight">{{ stats.attendance_rate }}%</h3>
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-rose-500 font-medium">{{ stats.absent_today }} Siswa Belum Absen</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Analytics Trends Chart -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <BarChart3 class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-wide">Grafik Analytics Tren Kehadiran Mingguan</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Perbandingan siswa Hadir Tepat Waktu vs Terlambat selama 7 hari terakhir</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Hadir
                        </span>
                        <span class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span> Terlambat
                        </span>
                    </div>
                </div>

                <!-- Custom SVG Bar Chart -->
                <div class="grid grid-cols-7 gap-3 items-end h-44 pt-6 pb-2 px-2 border-b border-slate-100 dark:border-slate-800">
                    <div v-for="t in weeklyTrend" :key="t.date" class="flex flex-col items-center gap-2 h-full justify-end group">
                        <!-- Bar container -->
                        <div class="w-full flex items-end justify-center gap-1.5 h-32 bg-slate-50 dark:bg-slate-800/40 rounded-xl p-1.5 relative overflow-hidden">
                            <!-- Hadir Bar -->
                            <div
                                :style="{ height: Math.min(100, Math.max(8, t.hadir * 25)) + '%' }"
                                class="w-1/2 bg-emerald-500 rounded-lg transition-all duration-300 group-hover:bg-emerald-400 relative"
                            >
                                <span v-if="t.hadir > 0" class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ t.hadir }}
                                </span>
                            </div>

                            <!-- Terlambat Bar -->
                            <div
                                :style="{ height: Math.min(100, Math.max(8, t.terlambat * 25)) + '%' }"
                                class="w-1/2 bg-amber-500 rounded-lg transition-all duration-300 group-hover:bg-amber-400 relative"
                            >
                                <span v-if="t.terlambat > 0" class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                    {{ t.terlambat }}
                                </span>
                            </div>
                        </div>

                        <!-- X Label -->
                        <div class="text-center">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 capitalize">{{ t.day }}</p>
                            <p class="text-[10px] text-slate-400">{{ t.date }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Table Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                            <Activity class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-wide">Log Absensi Verifikasi Terbaru</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Aktivitas kehadiran siswa terkini yang diverifikasi oleh InsightFace AI</p>
                        </div>
                    </div>

                    <Link
                        href="/reports"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 transition"
                    >
                        Lihat Selengkapnya <ArrowUpRight class="w-4 h-4" />
                    </Link>
                </div>

                <div class="overflow-hidden border border-slate-200 dark:border-slate-800 rounded-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-separate border-spacing-0">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Bukti Foto AI</th>
                                    <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">NISN</th>
                                    <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Nama Siswa</th>
                                    <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Kelas</th>
                                    <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Waktu Masuk</th>
                                    <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Akurasi InsightFace</th>
                                    <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                                <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                        <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm">
                                            <img v-if="log.photo_url" :src="log.photo_url" alt="Foto Absen" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400 font-bold">N/A</div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-medium text-slate-900 dark:text-slate-100 border-b border-slate-100 dark:border-slate-800/60">{{ log.nisn }}</td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800/60">{{ log.student_name }}</td>
                                    <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                        <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-1 rounded-lg text-xs font-medium border border-slate-200 dark:border-slate-700">
                                            {{ log.class_name }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-emerald-600 dark:text-emerald-400 border-b border-slate-100 dark:border-slate-800/60">{{ log.check_in_time }} WIB</td>
                                    <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                                            {{ log.similarity_percentage }}% Match
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                        <span
                                            :class="[
                                                'px-3 py-1 text-xs font-bold rounded-full border shadow-sm',
                                                log.status === 'hadir'
                                                    ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30'
                                                    : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30'
                                            ]"
                                        >
                                            {{ log.status }}
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="recentLogs.length === 0">
                                    <td colspan="7" class="text-center py-16 text-slate-400">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400">
                                                <ScanFace class="w-6 h-6" />
                                            </div>
                                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Belum ada aktivitas absensi tercatat hari ini.</p>
                                            <Link
                                                href="/absensi"
                                                class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
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
