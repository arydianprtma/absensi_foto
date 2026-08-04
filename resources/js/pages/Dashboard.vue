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
        <Head title="Dashboard Analytics Absensi Wajah AI" />

        <div class="p-6 md:p-8 max-w-7xl mx-auto space-y-8 font-sans">
            <!-- Header Welcome Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950/80 to-slate-950 border border-slate-800/80 p-8 md:p-10 shadow-2xl backdrop-blur-xl">
                <!-- Background ambient glow -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold backdrop-blur-md">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <Sparkles class="w-3.5 h-3.5 text-emerald-400" />
                            InsightFace AI Deep Learning Engine Active
                        </div>

                        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white leading-tight">
                            System Analytics <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-indigo-400 bg-clip-text text-transparent">Absensi Siswa</span>
                        </h1>

                        <p class="text-sm text-slate-300 leading-relaxed">
                            Pantau tingkat kehadiran, verifikasi wajah AI 512-dimensi, dan rekap log siswa secara real-time.
                        </p>

                        <!-- Live Clock Widget -->
                        <div class="flex items-center gap-4 text-xs font-medium text-slate-400 pt-1">
                            <span class="flex items-center gap-1.5 bg-slate-950/60 px-3 py-1.5 rounded-xl border border-slate-800">
                                <Clock class="w-3.5 h-3.5 text-indigo-400" /> {{ currentTime }}
                            </span>
                            <span class="flex items-center gap-1.5 bg-slate-950/60 px-3 py-1.5 rounded-xl border border-slate-800">
                                <Calendar class="w-3.5 h-3.5 text-emerald-400" /> {{ currentDate }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                        <Link
                            href="/absensi"
                            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-bold text-sm shadow-xl shadow-emerald-500/20 transition-all duration-300 hover:scale-[1.02] flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <Camera class="w-5 h-5" /> Buka Kamera Absensi
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Siswa -->
                <div class="relative overflow-hidden rounded-2xl bg-slate-900/60 border border-slate-800/80 p-5 shadow-xl backdrop-blur-xl group hover:border-indigo-500/40 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Siswa</span>
                        <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <Users class="w-5.5 h-5.5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-white tracking-tight">{{ stats.total_students }}</h3>
                        <div class="flex items-center justify-between text-xs text-slate-400 mt-2 pt-2 border-t border-slate-800/60">
                            <span class="flex items-center gap-1 text-emerald-400 font-medium">
                                <ShieldCheck class="w-3.5 h-3.5" /> {{ stats.registered_faces }} Terdaftar AI
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hadir Tepat Waktu -->
                <div class="relative overflow-hidden rounded-2xl bg-slate-900/60 border border-slate-800/80 p-5 shadow-xl backdrop-blur-xl group hover:border-emerald-500/40 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Hadir Tepat Waktu</span>
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <UserCheck class="w-5.5 h-5.5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-emerald-400 tracking-tight">{{ stats.present_today }}</h3>
                        <div class="flex items-center justify-between text-xs text-slate-400 mt-2 pt-2 border-t border-slate-800/60">
                            <span class="text-slate-400">Sebelum Jam 07:30 WIB</span>
                        </div>
                    </div>
                </div>

                <!-- Terlambat Hari Ini -->
                <div class="relative overflow-hidden rounded-2xl bg-slate-900/60 border border-slate-800/80 p-5 shadow-xl backdrop-blur-xl group hover:border-amber-500/40 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Terlambat</span>
                        <div class="w-11 h-11 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <Clock class="w-5.5 h-5.5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-amber-400 tracking-tight">{{ stats.late_today }}</h3>
                        <div class="flex items-center justify-between text-xs text-slate-400 mt-2 pt-2 border-t border-slate-800/60">
                            <span class="text-slate-400">Setelah Jam 07:30 WIB</span>
                        </div>
                    </div>
                </div>

                <!-- Rate Kehadiran -->
                <div class="relative overflow-hidden rounded-2xl bg-slate-900/60 border border-slate-800/80 p-5 shadow-xl backdrop-blur-xl group hover:border-purple-500/40 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Persentase Kehadiran</span>
                        <div class="w-11 h-11 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <TrendingUp class="w-5.5 h-5.5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-extrabold text-purple-400 tracking-tight">{{ stats.attendance_rate }}%</h3>
                        <div class="flex items-center justify-between text-xs text-slate-400 mt-2 pt-2 border-t border-slate-800/60">
                            <span class="text-rose-400 font-medium">{{ stats.absent_today }} Siswa Belum Absen</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 md:p-8 shadow-2xl backdrop-blur-xl space-y-6">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                            <Activity class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white tracking-wide">Log Absensi Verifikasi Terbaru</h2>
                            <p class="text-xs text-slate-400">Aktivitas kehadiran siswa terkini yang diverifikasi oleh InsightFace AI</p>
                        </div>
                    </div>

                    <Link
                        href="/reports"
                        class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition group"
                    >
                        Lihat Selengkapnya <ArrowUpRight class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="py-3.5 px-4 rounded-l-xl">Bukti Foto AI</th>
                                <th class="py-3.5 px-4">NISN</th>
                                <th class="py-3.5 px-4">Nama Siswa</th>
                                <th class="py-3.5 px-4">Kelas</th>
                                <th class="py-3.5 px-4">Waktu Masuk</th>
                                <th class="py-3.5 px-4">Akurasi InsightFace</th>
                                <th class="py-3.5 px-4 rounded-r-xl">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-800/30 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="w-11 h-11 rounded-xl bg-slate-950 overflow-hidden border border-slate-800 shadow-md">
                                        <img v-if="log.photo_url" :src="log.photo_url" alt="Foto Absen" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-600 font-bold">N/A</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono font-medium text-slate-200">{{ log.nisn }}</td>
                                <td class="py-3 px-4 font-semibold text-white">{{ log.student_name }}</td>
                                <td class="py-3 px-4">
                                    <span class="bg-slate-800 text-slate-300 px-2.5 py-1 rounded-lg text-xs font-medium border border-slate-700/60">
                                        {{ log.class_name }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-emerald-400">{{ log.check_in_time }} WIB</td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        {{ log.similarity_percentage }}% Match
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-3 py-1 text-xs font-bold rounded-full border shadow-sm',
                                            log.status === 'hadir'
                                                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                                                : 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                                        ]"
                                    >
                                        {{ log.status }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="recentLogs.length === 0">
                                <td colspan="7" class="text-center py-16 text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-500">
                                            <ScanFace class="w-6 h-6" />
                                        </div>
                                        <p class="text-sm font-medium text-slate-300">Belum ada aktivitas absensi tercatat hari ini.</p>
                                        <Link
                                            href="/absensi"
                                            class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 underline"
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
    </AppLayout>
</template>
