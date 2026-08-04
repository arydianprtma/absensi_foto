<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Plus, Trash2 } from '@lucide/vue';
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

const submitHoliday = () => {
    form.post('/holidays', {
        onSuccess: () => {
            form.reset();
        },
    });
};

const deleteHoliday = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus hari libur ini?')) {
        form.delete(`/holidays/${id}`);
    }
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
                <!-- Left Form: Add Holiday -->
                <div
                    class="h-fit space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                    >
                        <Plus
                            class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
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
                            <p
                                v-if="form.errors.date"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ form.errors.date }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Nama Hari Libur
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                placeholder="Contoh: Hari Kemerdekaan RI"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Keterangan Tambahan (Opsional)
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Keterangan singkat..."
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

                <!-- Right Table: Holidays List -->
                <div
                    class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                    >
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Daftar Hari Libur Terjadwal
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 dark:bg-slate-800"
                        >
                            {{ holidays.length }} Hari
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
                                        class="border-b border-slate-200 px-4 py-3 dark:border-slate-800"
                                    >
                                        Tanggal
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3 dark:border-slate-800"
                                    >
                                        Nama Libur
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3 dark:border-slate-800"
                                    >
                                        Keterangan
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-800"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                            >
                                <tr
                                    v-for="item in holidays"
                                    :key="item.id"
                                    class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-mono font-semibold text-slate-900 dark:border-slate-800/60 dark:text-white"
                                    >
                                        {{ item.date }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-bold text-indigo-600 dark:border-slate-800/60 dark:text-indigo-400"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-xs text-slate-500 dark:border-slate-800/60"
                                    >
                                        {{ item.description || '-' }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-right dark:border-slate-800/60"
                                    >
                                        <button
                                            @click="deleteHoliday(item.id)"
                                            class="rounded-lg bg-rose-50 p-1.5 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400"
                                            title="Hapus"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="holidays.length === 0">
                                    <td
                                        colspan="4"
                                        class="py-12 text-center text-sm text-slate-400"
                                    >
                                        Belum ada jadwal hari libur yang
                                        ditambahkan.
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
