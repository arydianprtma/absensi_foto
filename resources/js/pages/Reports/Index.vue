<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileText, Filter, Calendar, Download, Eye, CheckCircle2, Edit, Save, X, FileSpreadsheet, Printer } from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface ReportItem {
    id: number;
    date: string;
    student_name: string;
    nisn: string;
    class_name: string;
    check_in_time: string;
    check_out_time?: string | null;
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

const editingId = ref<number | null>(null);
const editStatusValue = ref<string>('');

const detailModal = ref<{
    show: boolean;
    item: ReportItem | null;
}>({
    show: false,
    item: null,
});

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

const openDetailModal = (item: ReportItem) => {
    detailModal.value = {
        show: true,
        item: item,
    };
};

const closeDetailModal = () => {
    detailModal.value.show = false;
};

const startEditStatus = (item: ReportItem) => {
    editingId.value = item.id;
    editStatusValue.value = item.status;
};

const cancelEditStatus = () => {
    editingId.value = null;
};

const saveStatus = (id: number) => {
    router.put(`/attendances/${id}`, {
        status: editStatusValue.value,
    }, {
        onSuccess: () => {
            editingId.value = null;
            if (detailModal.value.show && detailModal.value.item?.id === id) {
                detailModal.value.item.status = editStatusValue.value;
            }
        },
    });
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
                        Kelola rekap absensi, cek bukti foto verifikasi wajah AI, dan ubah status kehadiran siswa.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a
                        :href="`/reports/export-excel?date=${selectedDate}&class_name=${selectedClass}&status=${selectedStatus}`"
                        target="_blank"
                        class="px-4 py-2.5 rounded-xl border border-emerald-300 dark:border-emerald-700/60 bg-emerald-50 dark:bg-emerald-500/10 hover:bg-emerald-100 text-xs font-bold text-emerald-700 dark:text-emerald-400 transition flex items-center gap-2 cursor-pointer shadow-sm"
                    >
                        <FileSpreadsheet class="w-4 h-4" /> Export Excel (.xlsx)
                    </a>

                    <button
                        @click="printReport"
                        class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition flex items-center gap-2 cursor-pointer shadow-sm"
                    >
                        <Printer class="w-4 h-4" /> Cetak Laporan
                    </button>
                </div>
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
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpa">Alpa</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="applyFilters"
                        class="w-full py-2.5 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 font-semibold text-xs text-white rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
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
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Foto Absen AI</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Tanggal</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">NISN</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Nama Siswa</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Kelas</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Jam Masuk / Pulang</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Kemiripan AI</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800">Status Kehadiran</th>
                                <th class="py-3.5 px-4 border-b border-slate-200 dark:border-slate-800 text-right">Aksi Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="item in reports" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <!-- Foto Preview -->
                                <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60">
                                    <div
                                        @click="openDetailModal(item)"
                                        class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm cursor-pointer hover:opacity-80 transition group relative"
                                    >
                                        <img v-if="item.photo_url" :src="item.photo_url" alt="Foto Absen" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400 font-bold">N/A</div>
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                            <Eye class="w-4 h-4 text-white" />
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3 px-4 font-mono text-xs border-b border-slate-100 dark:border-slate-800/60 whitespace-nowrap">{{ item.date }}</td>
                                <td class="py-3 px-4 font-mono font-medium text-slate-900 dark:text-slate-100 border-b border-slate-100 dark:border-slate-800/60 whitespace-nowrap">{{ item.nisn }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-slate-100 border-b border-slate-100 dark:border-slate-800/60 whitespace-nowrap">{{ item.student_name }}</td>
                                <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60 whitespace-nowrap">
                                    <span class="whitespace-nowrap inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-semibold">
                                        {{ item.class_name }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-xs border-b border-slate-100 dark:border-slate-800/60 whitespace-nowrap">
                                    <div class="font-semibold text-emerald-600 dark:text-emerald-400">Masuk: {{ item.check_in_time }} WIB</div>
                                    <div v-if="item.check_out_time" class="font-semibold text-indigo-600 dark:text-indigo-400">Pulang: {{ item.check_out_time }} WIB</div>
                                </td>
                                <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60 whitespace-nowrap">
                                    <span class="whitespace-nowrap inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 text-xs font-bold shadow-sm">
                                        {{ item.similarity_percentage }}% Match
                                    </span>
                                </td>

                                <!-- Status Column (Editable inline) -->
                                <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60 whitespace-nowrap">
                                    <div v-if="editingId === item.id" class="flex items-center gap-1.5">
                                        <select
                                            v-model="editStatusValue"
                                            class="px-2 py-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-white"
                                        >
                                            <option value="hadir">Hadir</option>
                                            <option value="terlambat">Terlambat</option>
                                            <option value="izin">Izin</option>
                                            <option value="sakit">Sakit</option>
                                            <option value="alpa">Alpa</option>
                                        </select>
                                        <button
                                            @click="saveStatus(item.id)"
                                            class="p-1 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white transition cursor-pointer"
                                            title="Simpan"
                                        >
                                            <Save class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            @click="cancelEditStatus"
                                            class="p-1 rounded-lg bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 text-slate-700 dark:text-slate-200 transition cursor-pointer"
                                            title="Batal"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>

                                    <span
                                        v-else
                                        :class="[
                                            'whitespace-nowrap inline-flex items-center px-3 py-1 text-xs font-bold rounded-full border shadow-sm capitalize',
                                            item.status === 'hadir' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30' :
                                            item.status === 'terlambat' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30' :
                                            item.status === 'izin' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30' :
                                            item.status === 'sakit' ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-500/30' :
                                            'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/30'
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openDetailModal(item)"
                                            class="whitespace-nowrap px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 cursor-pointer shadow-sm"
                                            title="Cek Detail Foto & Siswa"
                                        >
                                            <Eye class="w-3.5 h-3.5" /> Detail Foto
                                        </button>
                                        <button
                                            @click="startEditStatus(item)"
                                            class="whitespace-nowrap px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 dark:bg-indigo-500/10 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 transition flex items-center gap-1.5 cursor-pointer shadow-sm"
                                            title="Ubah Status Absensi"
                                        >
                                            <Edit class="w-3.5 h-3.5" /> Edit Status
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="reports.length === 0">
                                <td colspan="9" class="text-center py-12 text-slate-400 text-sm">
                                    Tidak ada data absensi untuk filter yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Foto & Status Modal -->
        <div v-if="detailModal.show && detailModal.item" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl relative space-y-5 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <Eye class="w-5 h-5 text-indigo-600 dark:text-indigo-400" /> Detail Absensi AI Siswa
                    </h3>
                    <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Foto Snapshot Preview -->
                <div class="w-full h-64 rounded-2xl bg-slate-950 overflow-hidden border border-slate-200 dark:border-slate-800 relative flex items-center justify-center">
                    <img v-if="detailModal.item.photo_url" :src="detailModal.item.photo_url" alt="Foto Absen" class="w-full h-full object-cover" />
                    <div v-else class="text-slate-500 text-sm font-semibold">Tidak ada bukti foto</div>
                    <span class="absolute top-3 right-3 px-3 py-1 bg-slate-950/80 text-indigo-400 font-bold text-xs rounded-full border border-indigo-500/30 backdrop-blur">
                        {{ detailModal.item.similarity_percentage }}% Match AI
                    </span>
                </div>

                <!-- Info Grid -->
                <div class="bg-slate-50 dark:bg-slate-950/60 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 space-y-2.5 text-xs">
                    <div class="flex justify-between border-b border-slate-200/60 dark:border-slate-800 pb-2">
                        <span class="text-slate-500">Nama Siswa:</span>
                        <span class="font-bold text-slate-900 dark:text-slate-100">{{ detailModal.item.student_name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 dark:border-slate-800 pb-2">
                        <span class="text-slate-500">NISN / Kelas:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ detailModal.item.nisn }} ({{ detailModal.item.class_name }})</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 dark:border-slate-800 pb-2">
                        <span class="text-slate-500">Tanggal:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ detailModal.item.date }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 dark:border-slate-800 pb-2">
                        <span class="text-slate-500">Jam Masuk:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ detailModal.item.check_in_time }} WIB</span>
                    </div>
                    <div v-if="detailModal.item.check_out_time" class="flex justify-between border-b border-slate-200/60 dark:border-slate-800 pb-2">
                        <span class="text-slate-500">Jam Pulang:</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ detailModal.item.check_out_time }} WIB</span>
                    </div>
                    <div class="flex justify-between items-center pt-1">
                        <span class="text-slate-500">Status Kehadiran:</span>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="detailModal.item.status"
                                @change="saveStatus(detailModal.item.id)"
                                class="px-2.5 py-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold capitalize text-slate-900 dark:text-white"
                            >
                                <option value="hadir">Hadir</option>
                                <option value="terlambat">Terlambat</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="alpa">Alpa</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        @click="closeDetailModal"
                        class="w-full py-2.5 rounded-xl bg-slate-900 dark:bg-slate-800 text-white font-semibold text-xs hover:bg-slate-800 transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
