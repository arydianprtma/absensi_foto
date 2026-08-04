<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileText, Filter, Calendar, Download } from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface ReportItem {
    id: number;
    date: string;
    student_name: string;
    nisn: string;
    class_name: string;
    check_in_time: string;
    status: string;
    similarity_percentage: number;
    photo_url: string | null;
}

const props = defineProps<{
    reports: ReportItem[];
    classes: string[];
    filters: {
        date: string;
        class_name: string;
        status: string;
    };
}>();

const selectedDate = ref(props.filters.date);
const selectedClass = ref(props.filters.class_name);
const selectedStatus = ref(props.filters.status);

const applyFilters = () => {
    router.get('/reports', {
        date: selectedDate.value,
        class_name: selectedClass.value,
        status: selectedStatus.value,
    }, { preserveState: true });
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Laporan Absensi', href: '/reports' }]">
        <Head title="Laporan Absensi Siswa" />

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Top Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <FileText class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        Laporan & History Absensi Siswa
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Rekap hasil absensi dan verifikasi wajah berdasarkan tanggal dan kelas.
                    </p>
                </div>

                <button
                    @click="printReport"
                    class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition flex items-center gap-2 cursor-pointer shrink-0"
                >
                    <Download class="w-4 h-4" /> Cetak / Export Laporan
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Tanggal Absensi
                    </label>
                    <input
                        type="date"
                        v-model="selectedDate"
                        @change="applyFilters"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Pilih Kelas
                    </label>
                    <select
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none"
                    >
                        <option value="all">Semua Kelas</option>
                        <option v-for="c in classes" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Status Kehadiran
                    </label>
                    <select
                        v-model="selectedStatus"
                        @change="applyFilters"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none"
                    >
                        <option value="all">Semua Status</option>
                        <option value="hadir">Hadir Tepat Waktu</option>
                        <option value="terlambat">Terlambat</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="applyFilters"
                        class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 font-semibold text-xs text-white rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <Filter class="w-3.5 h-3.5" /> Filter Data
                    </button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-separate border-spacing-0">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Foto Absen</th>
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Tanggal</th>
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">NISN</th>
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Nama Siswa</th>
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Kelas</th>
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Jam Masuk</th>
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Kemiripan Wajah</th>
                                <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="item in reports" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="py-2.5 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm">
                                        <img v-if="item.photo_url" :src="item.photo_url" alt="Foto Absen" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400 font-bold">N/A</div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 font-mono text-xs border-b border-slate-100 dark:border-slate-800/60">{{ item.date }}</td>
                                <td class="py-2.5 px-4 font-mono font-medium text-slate-900 dark:text-slate-100 border-b border-slate-100 dark:border-slate-800/60">{{ item.nisn }}</td>
                                <td class="py-2.5 px-4 font-semibold text-slate-900 dark:text-slate-100 border-b border-slate-100 dark:border-slate-800/60">{{ item.student_name }}</td>
                                <td class="py-2.5 px-4 border-b border-slate-100 dark:border-slate-800/60">{{ item.class_name }}</td>
                                <td class="py-2.5 px-4 font-semibold text-emerald-600 dark:text-emerald-400 border-b border-slate-100 dark:border-slate-800/60">{{ item.check_in_time }} WIB</td>
                                <td class="py-2.5 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                                        {{ item.similarity_percentage }}% Match
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 text-xs font-semibold rounded-full border shadow-sm',
                                            item.status === 'hadir'
                                                ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30'
                                                : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30'
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="reports.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 text-sm">
                                    Tidak ada data absensi untuk filter yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
