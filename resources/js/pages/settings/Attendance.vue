<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Clock,
    Save,
    CheckCircle2,
    LogIn,
    LogOut,
    Sparkles,
    ShieldCheck,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Settings {
    check_in_start: string;
    check_in_end: string;
    check_out_start: string;
    check_out_end: string;
}

const props = defineProps<{
    settings: Settings;
}>();

// Helper options for 24-Hour format (00 - 23) and Minutes (00 - 59)
const hoursOptions = Array.from({ length: 24 }, (_, i) =>
    String(i).padStart(2, '0'),
);
const minutesOptions = Array.from({ length: 60 }, (_, i) =>
    String(i).padStart(2, '0'),
);

// Local 24-hour state for each field
const parseHHmm = (timeStr: string) => {
    const parts = (timeStr || '00:00').split(':');

    return {
        h: parts[0] ? parts[0].padStart(2, '0') : '08',
        m: parts[1] ? parts[1].padStart(2, '0') : '00',
    };
};

const inStart = ref(parseHHmm(props.settings.check_in_start));
const inEnd = ref(parseHHmm(props.settings.check_in_end));
const outStart = ref(parseHHmm(props.settings.check_out_start));
const outEnd = ref(parseHHmm(props.settings.check_out_end));

const form = useForm({
    check_in_start: `${inStart.value.h}:${inStart.value.m}`,
    check_in_end: `${inEnd.value.h}:${inEnd.value.m}`,
    check_out_start: `${outStart.value.h}:${outStart.value.m}`,
    check_out_end: `${outEnd.value.h}:${outEnd.value.m}`,
});

watch(
    [inStart, inEnd, outStart, outEnd],
    () => {
        form.check_in_start = `${inStart.value.h}:${inStart.value.m}`;
        form.check_in_end = `${inEnd.value.h}:${inEnd.value.m}`;
        form.check_out_start = `${outStart.value.h}:${outStart.value.m}`;
        form.check_out_end = `${outEnd.value.h}:${outEnd.value.m}`;
    },
    { deep: true },
);

const page = usePage();

const submit = () => {
    form.post('/attendance-settings');
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Pengaturan Jam Absensi', href: '/attendance-settings' },
        ]"
    >
        <Head title="Kelola Jam Absensi AI (Format 24 Jam)" />

        <div class="mx-auto max-w-5xl space-y-8 p-6 md:p-8">
            <!-- Top Header Banner -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 md:flex-row md:items-center dark:border-slate-800"
            >
                <div>
                    <div
                        class="mb-2 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Sparkles class="h-3.5 w-3.5" /> Konfigurasi 24-Jam
                        (Tanpa Format AM/PM)
                    </div>
                    <h1
                        class="flex items-center gap-3 text-2xl font-extrabold text-slate-900 md:text-3xl dark:text-white"
                    >
                        <Clock
                            class="h-8 w-8 text-indigo-600 dark:text-indigo-400"
                        />
                        Kelola Jam Absensi Datang & Pulang (Format 24 Jam)
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Pilih jam dalam format 24 jam penuh (00:00 s/d 23:59
                        WIB) tanpa membingungkan dengan format AM/PM.
                    </p>
                </div>
            </div>

            <!-- Success Alert -->
            <div
                v-if="page.props.flash?.success"
                class="flex animate-in items-center gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-semibold text-emerald-600 shadow-sm duration-300 fade-in dark:text-emerald-400"
            >
                <CheckCircle2 class="h-5 w-5 shrink-0" />
                <span>{{ page.props.flash.success }}</span>
            </div>

            <!-- Settings Form -->
            <form @submit.prevent="submit" class="space-y-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Card 1: Absen Datang / Masuk -->
                    <div
                        class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-500/10 font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                <LogIn class="h-5 w-5" />
                            </div>
                            <div>
                                <h2
                                    class="text-base font-bold text-slate-900 dark:text-slate-100"
                                >
                                    Sesi Absen Datang (Masuk)
                                </h2>
                                <p
                                    class="text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Atur jam mulai datang & batas terlambat
                                </p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <!-- Field 1: Jam Mulai Absen Masuk -->
                            <div>
                                <label
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >
                                    Jam Mulai Absen Masuk (Format 24 Jam) *
                                </label>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <select
                                            v-model="inStart.h"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="h in hoursOptions"
                                                :key="'inStartH-' + h"
                                                :value="h"
                                            >
                                                Jam {{ h }}
                                            </option>
                                        </select>
                                    </div>
                                    <span
                                        class="text-xl font-bold text-slate-400"
                                        >:</span
                                    >
                                    <div class="flex-1">
                                        <select
                                            v-model="inStart.m"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="m in minutesOptions"
                                                :key="'inStartM-' + m"
                                                :value="m"
                                            >
                                                {{ m }} Menit
                                            </option>
                                        </select>
                                    </div>
                                    <div
                                        class="rounded-xl bg-emerald-500/10 px-3 py-2 font-mono text-sm font-bold text-emerald-500"
                                    >
                                        {{ inStart.h }}:{{ inStart.m }} WIB
                                    </div>
                                </div>
                                <p
                                    class="mt-1.5 text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Kamera AI mulai menerima scan masuk siswa
                                    pada jam
                                    <strong
                                        >{{ inStart.h }}:{{
                                            inStart.m
                                        }}
                                        WIB</strong
                                    >.
                                </p>
                            </div>

                            <!-- Field 2: Batas Jam Terlambat -->
                            <div>
                                <label
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >
                                    Batas Jam Terlambat (Format 24 Jam) *
                                </label>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <select
                                            v-model="inEnd.h"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="h in hoursOptions"
                                                :key="'inEndH-' + h"
                                                :value="h"
                                            >
                                                Jam {{ h }}
                                            </option>
                                        </select>
                                    </div>
                                    <span
                                        class="text-xl font-bold text-slate-400"
                                        >:</span
                                    >
                                    <div class="flex-1">
                                        <select
                                            v-model="inEnd.m"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="m in minutesOptions"
                                                :key="'inEndM-' + m"
                                                :value="m"
                                            >
                                                {{ m }} Menit
                                            </option>
                                        </select>
                                    </div>
                                    <div
                                        class="rounded-xl bg-amber-500/10 px-3 py-2 font-mono text-sm font-bold text-amber-500"
                                    >
                                        {{ inEnd.h }}:{{ inEnd.m }} WIB
                                    </div>
                                </div>
                                <p
                                    class="mt-1.5 text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Scan setelah jam
                                    <strong
                                        >{{ inEnd.h }}:{{ inEnd.m }} WIB</strong
                                    >
                                    ditandai status
                                    <span class="font-bold text-amber-500"
                                        >"Terlambat"</span
                                    >.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Absen Pulang / Check-Out -->
                    <div
                        class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div
                            class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-500/10 font-bold text-indigo-600 dark:text-indigo-400"
                            >
                                <LogOut class="h-5 w-5" />
                            </div>
                            <div>
                                <h2
                                    class="text-base font-bold text-slate-900 dark:text-slate-100"
                                >
                                    Sesi Absen Pulang (Check-Out)
                                </h2>
                                <p
                                    class="text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Atur jam mulai & selesai absensi kepulangan
                                </p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <!-- Field 3: Jam Mulai Absen Pulang -->
                            <div>
                                <label
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >
                                    Jam Mulai Absen Pulang (Format 24 Jam) *
                                </label>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <select
                                            v-model="outStart.h"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="h in hoursOptions"
                                                :key="'outStartH-' + h"
                                                :value="h"
                                            >
                                                Jam {{ h }}
                                            </option>
                                        </select>
                                    </div>
                                    <span
                                        class="text-xl font-bold text-slate-400"
                                        >:</span
                                    >
                                    <div class="flex-1">
                                        <select
                                            v-model="outStart.m"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="m in minutesOptions"
                                                :key="'outStartM-' + m"
                                                :value="m"
                                            >
                                                {{ m }} Menit
                                            </option>
                                        </select>
                                    </div>
                                    <div
                                        class="rounded-xl bg-indigo-500/10 px-3 py-2 font-mono text-sm font-bold text-indigo-500"
                                    >
                                        {{ outStart.h }}:{{ outStart.m }} WIB
                                    </div>
                                </div>
                                <p
                                    class="mt-1.5 text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Mulai jam
                                    <strong
                                        >{{ outStart.h }}:{{
                                            outStart.m
                                        }}
                                        WIB</strong
                                    >, scan kamera otomatis mencatat status
                                    <span class="font-bold text-indigo-500"
                                        >"Absen Pulang"</span
                                    >.
                                </p>
                            </div>

                            <!-- Field 4: Jam Selesai Absen Pulang -->
                            <div>
                                <label
                                    class="mb-2 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >
                                    Jam Selesai Absen Pulang (Format 24 Jam) *
                                </label>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <select
                                            v-model="outEnd.h"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="h in hoursOptions"
                                                :key="'outEndH-' + h"
                                                :value="h"
                                            >
                                                Jam {{ h }}
                                            </option>
                                        </select>
                                    </div>
                                    <span
                                        class="text-xl font-bold text-slate-400"
                                        >:</span
                                    >
                                    <div class="flex-1">
                                        <select
                                            v-model="outEnd.m"
                                            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-3 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option
                                                v-for="m in minutesOptions"
                                                :key="'outEndM-' + m"
                                                :value="m"
                                            >
                                                {{ m }} Menit
                                            </option>
                                        </select>
                                    </div>
                                    <div
                                        class="rounded-xl bg-indigo-500/10 px-3 py-2 font-mono text-sm font-bold text-indigo-500"
                                    >
                                        {{ outEnd.h }}:{{ outEnd.m }} WIB
                                    </div>
                                </div>
                                <p
                                    class="mt-1.5 text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Batas akhir waktu scan kepulangan sekolah
                                    (misal
                                    <strong
                                        >{{ outEnd.h }}:{{
                                            outEnd.m
                                        }}
                                        WIB</strong
                                    >).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Summary Box -->
                <div
                    class="flex flex-col items-center justify-between gap-4 rounded-3xl border border-slate-200 bg-slate-50 p-6 md:flex-row dark:border-slate-800 dark:bg-slate-900/60"
                >
                    <div class="flex items-center gap-3">
                        <ShieldCheck
                            class="h-6 w-6 shrink-0 text-emerald-500"
                        />
                        <div>
                            <p
                                class="text-xs font-bold text-slate-900 dark:text-slate-100"
                            >
                                Ringkasan Jadwal (Format 24 Jam)
                            </p>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Masuk:
                                <strong class="text-emerald-500"
                                    >{{ form.check_in_start }} WIB s/d
                                    {{ form.check_in_end }} WIB</strong
                                >
                                • Pulang:
                                <strong class="text-indigo-500"
                                    >Mulai {{ form.check_out_start }} WIB s/d
                                    {{ form.check_out_end }} WIB</strong
                                >
                            </p>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-8 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-700 disabled:opacity-50 md:w-auto"
                    >
                        <Save class="h-5 w-5" />
                        <span>{{
                            form.processing
                                ? 'Menyimpan...'
                                : 'Simpan Pengaturan Jam (24-Jam)'
                        }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
