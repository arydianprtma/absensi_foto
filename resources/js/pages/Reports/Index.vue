<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    FileText,
    Filter,
    Eye,
    Edit,
    Save,
    X,
    FileSpreadsheet,
    Printer,
} from '@lucide/vue';
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
    router.get(
        '/reports',
        {
            date: selectedDate.value,
            class_name: selectedClass.value,
            status: selectedStatus.value,
        },
        { preserveState: true },
    );
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
    router.put(
        `/attendances/${id}`,
        {
            status: editStatusValue.value,
        },
        {
            onSuccess: () => {
                editingId.value = null;

                if (
                    detailModal.value.show &&
                    detailModal.value.item?.id === id
                ) {
                    detailModal.value.item.status = editStatusValue.value;
                }
            },
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Laporan Absensi', href: '/reports' }]">
        <Head title="Laporan Absensi Siswa" />

        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <!-- Top Bar -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <FileText
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Laporan & History Absensi Siswa
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Kelola rekap absensi, cek bukti foto verifikasi wajah
                        AI, dan ubah status kehadiran siswa.
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        :href="`/reports/export-excel?date=${selectedDate}&class_name=${selectedClass}&status=${selectedStatus}`"
                        target="_blank"
                        class="flex cursor-pointer items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-700/60 dark:bg-emerald-500/10 dark:text-emerald-400"
                    >
                        <FileSpreadsheet class="h-4 w-4" /> Export Excel (.xlsx)
                    </a>

                    <button
                        @click="printReport"
                        class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        <Printer class="h-4 w-4" /> Cetak Laporan
                    </button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div
                class="grid grid-cols-1 items-end gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3 md:grid-cols-4 dark:border-slate-800 dark:bg-slate-900"
            >
                <div>
                    <label
                        class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Tanggal Absensi
                    </label>
                    <input
                        type="date"
                        v-model="selectedDate"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Pilih Kelas
                    </label>
                    <select
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    >
                        <option value="all">Semua Kelas</option>
                        <option v-for="c in classes" :key="c" :value="c">
                            {{ c }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Status Kehadiran
                    </label>
                    <select
                        v-model="selectedStatus"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
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
                        class="flex w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700"
                    >
                        <Filter class="h-3.5 w-3.5" /> Filter Data
                    </button>
                </div>
            </div>

            <!-- Table Section -->
            <div
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="overflow-x-auto">
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
                                    Foto Absen AI
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Tanggal
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
                                    Jam Masuk / Pulang
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Kemiripan AI
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Status Kehadiran
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 text-right dark:border-slate-800"
                                >
                                    Aksi Admin
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                        >
                            <tr
                                v-for="item in reports"
                                :key="item.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <!-- Foto Preview -->
                                <td
                                    class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                >
                                    <div
                                        @click="openDetailModal(item)"
                                        class="group relative h-11 w-11 cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm transition hover:opacity-80 dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <img
                                            v-if="item.photo_url"
                                            :src="item.photo_url"
                                            alt="Foto Absen"
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-400"
                                        >
                                            N/A
                                        </div>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-slate-950/40 opacity-0 transition group-hover:opacity-100"
                                        >
                                            <Eye class="h-4 w-4 text-white" />
                                        </div>
                                    </div>
                                </td>

                                <td
                                    class="border-b border-slate-100 px-4 py-3 font-mono text-xs whitespace-nowrap dark:border-slate-800/60"
                                >
                                    {{ item.date }}
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 font-mono font-medium whitespace-nowrap text-slate-900 dark:border-slate-800/60 dark:text-slate-100"
                                >
                                    {{ item.nisn }}
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 font-semibold whitespace-nowrap text-slate-900 dark:border-slate-800/60 dark:text-slate-100"
                                >
                                    {{ item.student_name }}
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 whitespace-nowrap dark:border-slate-800/60"
                                >
                                    <span
                                        class="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{ item.class_name }}
                                    </span>
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 text-xs whitespace-nowrap dark:border-slate-800/60"
                                >
                                    <div
                                        class="font-semibold text-emerald-600 dark:text-emerald-400"
                                    >
                                        Masuk: {{ item.check_in_time }} WIB
                                    </div>
                                    <div
                                        v-if="item.check_out_time"
                                        class="font-semibold text-indigo-600 dark:text-indigo-400"
                                    >
                                        Pulang: {{ item.check_out_time }} WIB
                                    </div>
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 whitespace-nowrap dark:border-slate-800/60"
                                >
                                    <span
                                        class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold whitespace-nowrap text-indigo-700 shadow-sm dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400"
                                    >
                                        {{ item.similarity_percentage }}% Match
                                    </span>
                                </td>

                                <!-- Status Column (Editable inline) -->
                                <td
                                    class="border-b border-slate-100 px-4 py-3 whitespace-nowrap dark:border-slate-800/60"
                                >
                                    <div
                                        v-if="editingId === item.id"
                                        class="flex items-center gap-1.5"
                                    >
                                        <select
                                            v-model="editStatusValue"
                                            class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        >
                                            <option value="hadir">Hadir</option>
                                            <option value="terlambat">
                                                Terlambat
                                            </option>
                                            <option value="izin">Izin</option>
                                            <option value="sakit">Sakit</option>
                                            <option value="alpa">Alpa</option>
                                        </select>
                                        <button
                                            @click="saveStatus(item.id)"
                                            class="cursor-pointer rounded-lg bg-emerald-500 p-1 text-white transition hover:bg-emerald-600"
                                            title="Simpan"
                                        >
                                            <Save class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            @click="cancelEditStatus"
                                            class="cursor-pointer rounded-lg bg-slate-200 p-1 text-slate-700 transition hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200"
                                            title="Batal"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                    </div>

                                    <span
                                        v-else
                                        :class="[
                                            'inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold whitespace-nowrap capitalize shadow-sm',
                                            item.status === 'hadir'
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                : item.status === 'terlambat'
                                                  ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400'
                                                  : item.status === 'izin'
                                                    ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400'
                                                    : item.status === 'sakit'
                                                      ? 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-500/30 dark:bg-purple-500/10 dark:text-purple-400'
                                                      : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400',
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td
                                    class="border-b border-slate-100 px-4 py-3 text-right whitespace-nowrap dark:border-slate-800/60"
                                >
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            @click="openDetailModal(item)"
                                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold whitespace-nowrap text-slate-700 shadow-sm transition hover:bg-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                            title="Cek Detail Foto & Siswa"
                                        >
                                            <Eye class="h-3.5 w-3.5" /> Detail
                                            Foto
                                        </button>
                                        <button
                                            @click="startEditStatus(item)"
                                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold whitespace-nowrap text-indigo-700 shadow-sm transition hover:bg-indigo-100 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400"
                                            title="Ubah Status Absensi"
                                        >
                                            <Edit class="h-3.5 w-3.5" /> Edit
                                            Status
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="reports.length === 0">
                                <td
                                    colspan="9"
                                    class="py-12 text-center text-sm text-slate-400"
                                >
                                    Tidak ada data absensi untuk filter yang
                                    dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Foto & Status Modal -->
        <div
            v-if="detailModal.show && detailModal.item"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
        >
            <div
                class="relative w-full max-w-lg animate-in space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl duration-200 zoom-in-95 fade-in dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800"
                >
                    <h3
                        class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100"
                    >
                        <Eye
                            class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                        />
                        Detail Absensi AI Siswa
                    </h3>
                    <button
                        @click="closeDetailModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Foto Snapshot Preview -->
                <div
                    class="relative flex h-64 w-full items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 dark:border-slate-800"
                >
                    <img
                        v-if="detailModal.item.photo_url"
                        :src="detailModal.item.photo_url"
                        alt="Foto Absen"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="text-sm font-semibold text-slate-500">
                        Tidak ada bukti foto
                    </div>
                    <span
                        class="absolute top-3 right-3 rounded-full border border-indigo-500/30 bg-slate-950/80 px-3 py-1 text-xs font-bold text-indigo-400 backdrop-blur"
                    >
                        {{ detailModal.item.similarity_percentage }}% Match AI
                    </span>
                </div>

                <!-- Info Grid -->
                <div
                    class="space-y-2.5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-800 dark:bg-slate-950/60"
                >
                    <div
                        class="flex justify-between border-b border-slate-200/60 pb-2 dark:border-slate-800"
                    >
                        <span class="text-slate-500">Nama Siswa:</span>
                        <span
                            class="font-bold text-slate-900 dark:text-slate-100"
                            >{{ detailModal.item.student_name }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between border-b border-slate-200/60 pb-2 dark:border-slate-800"
                    >
                        <span class="text-slate-500">NISN / Kelas:</span>
                        <span
                            class="font-medium text-slate-800 dark:text-slate-200"
                            >{{ detailModal.item.nisn }} ({{
                                detailModal.item.class_name
                            }})</span
                        >
                    </div>
                    <div
                        class="flex justify-between border-b border-slate-200/60 pb-2 dark:border-slate-800"
                    >
                        <span class="text-slate-500">Tanggal:</span>
                        <span
                            class="font-medium text-slate-800 dark:text-slate-200"
                            >{{ detailModal.item.date }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between border-b border-slate-200/60 pb-2 dark:border-slate-800"
                    >
                        <span class="text-slate-500">Jam Masuk:</span>
                        <span
                            class="font-bold text-emerald-600 dark:text-emerald-400"
                            >{{ detailModal.item.check_in_time }} WIB</span
                        >
                    </div>
                    <div
                        v-if="detailModal.item.check_out_time"
                        class="flex justify-between border-b border-slate-200/60 pb-2 dark:border-slate-800"
                    >
                        <span class="text-slate-500">Jam Pulang:</span>
                        <span
                            class="font-bold text-indigo-600 dark:text-indigo-400"
                            >{{ detailModal.item.check_out_time }} WIB</span
                        >
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-slate-500">Status Kehadiran:</span>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="detailModal.item.status"
                                @change="saveStatus(detailModal.item.id)"
                                class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 capitalize dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
                        class="w-full rounded-xl bg-slate-900 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800 dark:bg-slate-800"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
