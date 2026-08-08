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
    GraduationCap,
    ShieldCheck,
    Camera,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
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

const props = defineProps<{
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

// Live Real-Time Clock
const currentTime = ref('');
let clockInterval: any = null;

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const updateClock = () => {
    const now = new Date();
    currentTime.value =
        now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }) + ' WIB';
};

onMounted(() => {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
});
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard Guru', href: '/dashboard' }]">
        <Head title="Dashboard Guru Pengampu" />

        <div class="mx-auto max-w-7xl space-y-8 p-6 font-sans md:p-8">
            <!-- Hero Banner Premium -->
            <div
                class="relative overflow-hidden rounded-3xl border border-indigo-500/20 bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 p-8 text-white shadow-2xl md:p-10"
            >
                <!-- Background Geometric Decorations -->
                <div
                    class="pointer-events-none absolute -top-16 -right-16 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl"
                ></div>
                <div
                    class="pointer-events-none absolute right-40 -bottom-20 h-60 w-60 rounded-full bg-purple-500/20 blur-2xl"
                ></div>

                <div
                    class="relative z-10 flex flex-col justify-between gap-6 lg:flex-row lg:items-center"
                >
                    <div class="max-w-2xl space-y-3">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-bold text-indigo-200 backdrop-blur"
                        >
                            <Sparkles class="h-3.5 w-3.5 text-amber-400" />
                            {{ greeting }}, Bapak/Ibu Guru
                        </div>
                        <h1
                            class="text-3xl font-extrabold tracking-tight text-white md:text-4xl"
                        >
                            {{ user.name }}
                        </h1>
                        <p
                            class="text-xs leading-relaxed font-medium text-indigo-200/90 md:text-sm"
                        >
                            Selamat datang di Portal Presensi Mata Pelajaran.
                            Pantau jadwal mengajar hari ini dan verifikasi
                            kehadiran siswa menggunakan kamera kecerdasan buatan
                            InsightFace.
                        </p>
                    </div>

                    <!-- Live Clock & Quick Action Box -->
                    <div
                        class="flex shrink-0 flex-col items-start gap-3 lg:items-end"
                    >
                        <div
                            class="space-y-0.5 rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-right backdrop-blur"
                        >
                            <div
                                class="text-[11px] font-bold tracking-wider text-indigo-300 uppercase"
                            >
                                {{ todayDayName }}, {{ todayDate }}
                            </div>
                            <div
                                class="font-mono text-xl font-black tracking-widest text-white md:text-2xl"
                            >
                                {{ currentTime || '00:00:00 WIB' }}
                            </div>
                        </div>

                        <Link
                            href="/absensi-mapel"
                            class="inline-flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-2xl bg-indigo-500 px-6 py-3.5 text-xs font-extrabold text-white shadow-lg transition-all hover:bg-indigo-400 hover:shadow-indigo-500/40 lg:w-auto"
                        >
                            <Camera class="h-4 w-4" /> Buka Kamera Absensi AI
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Highlight Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Card 1 -->
                <div
                    class="relative space-y-4 overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-extrabold tracking-wider text-slate-400 uppercase"
                            >Jadwal Mengajar Hari Ini</span
                        >
                        <div
                            class="rounded-2xl bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <Clock class="h-6 w-6" />
                        </div>
                    </div>
                    <div>
                        <div
                            class="text-3xl font-black text-slate-900 dark:text-white"
                        >
                            {{ schedules.length }} Kelas
                        </div>
                        <p
                            class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Tatap muka aktif hari {{ todayDayName }}
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-slate-100 pt-2 text-xs dark:border-slate-800"
                    >
                        <span class="text-slate-400">Status Hari Ini</span>
                        <span
                            class="flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400"
                        >
                            <span
                                class="inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-500"
                            ></span>
                            Siap Mengajar
                        </span>
                    </div>
                </div>

                <!-- Card 2 -->
                <div
                    class="relative space-y-4 overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-extrabold tracking-wider text-slate-400 uppercase"
                            >Metode Verifikasi AI</span
                        >
                        <div
                            class="rounded-2xl bg-emerald-50 p-3 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-6 w-6" />
                        </div>
                    </div>
                    <div>
                        <div
                            class="text-2xl font-extrabold text-slate-900 dark:text-white"
                        >
                            InsightFace 100D
                        </div>
                        <p
                            class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Deteksi kemiripan & Checklist Guru
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-slate-100 pt-2 text-xs dark:border-slate-800"
                    >
                        <span class="text-slate-400">Akurasi AI</span>
                        <span
                            class="font-bold text-indigo-600 dark:text-indigo-400"
                        >
                            Ambangan ≥ 50.0% Match
                        </span>
                    </div>
                </div>

                <!-- Card 3 -->
                <div
                    class="relative space-y-4 overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md sm:col-span-2 lg:col-span-1 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-extrabold tracking-wider text-slate-400 uppercase"
                            >Identitas Guru</span
                        >
                        <div
                            class="rounded-2xl bg-purple-50 p-3 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400"
                        >
                            <ShieldCheck class="h-6 w-6" />
                        </div>
                    </div>
                    <div>
                        <div
                            class="truncate font-mono text-xl font-bold text-slate-900 dark:text-white"
                        >
                            {{ user.nip || 'NIP Belum Diisi' }}
                        </div>
                        <p
                            class="mt-1 truncate text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            {{ user.email }}
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-slate-100 pt-2 text-xs dark:border-slate-800"
                    >
                        <span class="text-slate-400">Peran Pengguna</span>
                        <span
                            class="rounded-full border border-purple-200 bg-purple-50 px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider text-purple-600 uppercase dark:border-purple-500/20 dark:bg-purple-500/10 dark:text-purple-400"
                        >
                            {{ user.role }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Schedules Timeline Cards -->
            <div class="space-y-5">
                <div
                    class="flex flex-col justify-between gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center dark:border-slate-800"
                >
                    <div>
                        <h2
                            class="flex items-center gap-2 text-xl font-extrabold text-slate-900 dark:text-white"
                        >
                            <GraduationCap
                                class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                            />
                            Jadwal Pelajaran Mengajar Hari Ini ({{
                                todayDayName
                            }})
                        </h2>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                        >
                            Pilih kelas yang sedang berlangsung untuk memulai
                            absensi tatap muka siswa.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            {{ schedules.length }} Kelas Mengajar
                        </span>
                    </div>
                </div>

                <!-- Grid Cards for Schedules -->
                <div
                    v-if="schedules.length > 0"
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="sch in schedules"
                        :key="sch.id"
                        class="group relative flex flex-col justify-between space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-200 hover:border-indigo-500/40 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold text-slate-900 dark:bg-slate-800 dark:text-slate-100"
                                >
                                    {{ sch.class_name }}
                                </span>
                                <span
                                    class="rounded-xl bg-indigo-50 px-2.5 py-1 font-mono text-xs font-bold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                                >
                                    {{ sch.start_time.slice(0, 5) }} -
                                    {{ sch.end_time.slice(0, 5) }} WIB
                                </span>
                            </div>

                            <div>
                                <h3
                                    class="text-base font-extrabold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
                                >
                                    {{ sch.subject?.name }}
                                </h3>
                                <p
                                    class="mt-0.5 font-mono text-xs text-slate-400"
                                >
                                    Kode Mapel: {{ sch.subject?.code }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="border-t border-slate-100 pt-4 dark:border-slate-800"
                        >
                            <Link
                                :href="`/absensi-mapel?class_name=${sch.class_name}&schedule_id=${sch.id}`"
                                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md transition-all hover:bg-indigo-700"
                            >
                                <BookOpen class="h-4 w-4" /> Presensi Kelas Ini
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="space-y-4 rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800"
                    >
                        <Calendar class="h-8 w-8" />
                    </div>
                    <div class="space-y-1">
                        <h3
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Tidak Ada Jadwal Mengajar Hari Ini
                        </h3>
                        <p
                            class="mx-auto max-w-sm text-xs text-slate-500 dark:text-slate-400"
                        >
                            Anda tidak memiliki jam pelajaran tatap muka pada
                            hari {{ todayDayName }}. Silakan cek jadwal
                            pelajaran sekolah lengkap.
                        </p>
                    </div>
                    <Link
                        href="/schedules"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Lihat Seluruh Jadwal Sekolah
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
