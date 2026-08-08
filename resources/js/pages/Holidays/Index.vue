<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Plus, Trash2, TriangleAlert, X } from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface HolidayItem {
    id: number;
    date: string;
    name: string;
    description: string | null;
}

defineProps<{
    holidays: HolidayItem[];
}>();

const form = useForm({
    date: '',
    name: '',
    description: '',
});

const deleteModal = ref<{
    show: boolean;
    holiday: HolidayItem | null;
}>({
    show: false,
    holiday: null,
});

const submitHoliday = () => {
    form.post('/holidays', {
        onSuccess: () => {
            form.reset();
        },
    });
};

const confirmDeleteHoliday = (holiday: HolidayItem) => {
    deleteModal.value = {
        show: true,
        holiday,
    };
};

const executeDeleteHoliday = () => {
    if (!deleteModal.value.holiday) {
        return;
    }

    form.delete(`/holidays/${deleteModal.value.holiday.id}`, {
        onSuccess: () => {
            deleteModal.value.show = false;
        },
    });
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[{ title: 'Kalender Hari Libur', href: '/holidays' }]"
    >
        <Head title="Kelola Kalender Hari Libur Sekolah" />

        <div class="mx-auto max-w-7xl space-y-8 p-6 font-sans">
            <!-- Header -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <Calendar
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Kelola Kalender & Hari Libur Sekolah
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Atur jadwal hari libur nasional atau libur sekolah agar
                        sistem absensi tidak menghitung siswa alpa.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Form Add Holiday -->
                <div
                    class="h-fit space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                    >
                        <Plus
                            class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                        />
                        Tambah Hari Libur Baru
                    </h2>

                    <form @submit.prevent="submitHoliday" class="space-y-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Tanggal Libur
                            </label>
                            <input
                                type="date"
                                v-model="form.date"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Nama Keterangan Libur
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                placeholder="Contoh: Tahun Baru Imlek"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Catatan / Deskripsi Tambahan (Opsional)
                            </label>
                            <textarea
                                v-model="form.description"
                                placeholder="Catatan opsional..."
                                rows="3"
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <Plus class="h-4 w-4" /> Simpan Hari Libur
                        </button>
                    </form>
                </div>

                <!-- Table Holidays -->
                <div
                    class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                    >
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Daftar Hari Libur Terdaftar
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800"
                        >
                            {{ holidays.length }} Libur
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
                                        Tanggal
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Keterangan
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
                                    v-for="h in holidays"
                                    :key="h.id"
                                    class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-mono font-bold text-indigo-600 dark:border-slate-800/60 dark:text-indigo-400"
                                    >
                                        {{ h.date }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <div
                                            class="font-semibold text-slate-900 dark:text-white"
                                        >
                                            {{ h.name }}
                                        </div>
                                        <div
                                            v-if="h.description"
                                            class="text-xs text-slate-400"
                                        >
                                            {{ h.description }}
                                        </div>
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-right dark:border-slate-800/60"
                                    >
                                        <button
                                            @click="confirmDeleteHoliday(h)"
                                            class="cursor-pointer rounded-lg bg-rose-50 p-1.5 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400"
                                            title="Hapus Libur"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="holidays.length === 0">
                                    <td
                                        colspan="3"
                                        class="py-12 text-center text-sm text-slate-400"
                                    >
                                        Belum ada hari libur terdaftar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Delete Modal -->
        <div
            v-if="deleteModal.show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
        >
            <div
                class="relative w-full max-w-md animate-in space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl duration-150 fade-in zoom-in dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-2xl bg-rose-50 p-2.5 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400"
                        >
                            <TriangleAlert class="h-6 w-6" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                Hapus Hari Libur
                            </h3>
                            <p class="text-xs text-slate-500">
                                Konfirmasi Hapus Hari Libur
                            </p>
                        </div>
                    </div>
                    <button
                        @click="deleteModal.show = false"
                        class="cursor-pointer rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <p
                    class="text-xs leading-relaxed font-medium text-slate-600 dark:text-slate-300"
                >
                    Apakah Anda yakin ingin menghapus hari libur
                    <strong class="text-slate-900 dark:text-white">{{
                        deleteModal.holiday?.name
                    }}</strong>
                    (Tanggal: {{ deleteModal.holiday?.date }})?
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        @click="deleteModal.show = false"
                        class="cursor-pointer rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDeleteHoliday"
                        :disabled="form.processing"
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-rose-700 disabled:opacity-50"
                    >
                        <Trash2 class="h-4 w-4" /> Ya, Hapus Sekarang
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
