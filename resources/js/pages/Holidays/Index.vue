<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Plus, Trash2, ShieldAlert } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface HolidayItem {
    id: number;
    date: string;
    name: string;
    description: string | null;
}

const props = defineProps<{
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
    <AppLayout :breadcrumbs="[{ title: 'Kalender Hari Libur', href: '/holidays' }]">
        <Head title="Kelola Kalender Hari Libur Sekolah" />

        <div class="p-6 max-w-7xl mx-auto space-y-8 font-sans">
            <!-- Header -->
            <div class="border-b border-slate-200 dark:border-slate-800 pb-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <Calendar class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        Kelola Kalender & Hari Libur Sekolah
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Atur jadwal hari libur nasional atau libur sekolah agar sistem absensi tidak menghitung siswa alpa.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Form: Add Holiday -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-5 h-fit">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Plus class="w-5 h-5 text-emerald-600 dark:text-emerald-400" /> Tambah Hari Libur Baru
                    </h2>

                    <form @submit.prevent="submitHoliday" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Tanggal Libur
                            </label>
                            <input
                                type="date"
                                v-model="form.date"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none"
                            />
                            <p v-if="form.errors.date" class="text-xs text-rose-500 mt-1">{{ form.errors.date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Nama Hari Libur
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                placeholder="Contoh: Hari Kemerdekaan RI"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Keterangan Tambahan (Opsional)
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Keterangan singkat..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:outline-none"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-xs text-white transition flex items-center justify-center gap-2 cursor-pointer shadow-sm disabled:opacity-50"
                        >
                            <Plus class="w-4 h-4" /> Simpan Hari Libur
                        </button>
                    </form>
                </div>

                <!-- Right Table: Holidays List -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Daftar Hari Libur Terjadwal</h2>
                        <span class="text-xs bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-full text-slate-500 font-semibold">
                            {{ holidays.length }} Hari
                        </span>
                    </div>

                    <div class="overflow-hidden border border-slate-200 dark:border-slate-800 rounded-2xl">
                        <table class="w-full text-left text-sm border-separate border-spacing-0">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                    <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Tanggal</th>
                                    <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Nama Libur</th>
                                    <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800">Keterangan</th>
                                    <th class="py-3 px-4 border-b border-slate-200 dark:border-slate-800 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                                <tr v-for="item in holidays" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono font-semibold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800/60">
                                        {{ item.date }}
                                    </td>
                                    <td class="py-3 px-4 font-bold text-indigo-600 dark:text-indigo-400 border-b border-slate-100 dark:border-slate-800/60">
                                        {{ item.name }}
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-500 border-b border-slate-100 dark:border-slate-800/60">
                                        {{ item.description || '-' }}
                                    </td>
                                    <td class="py-3 px-4 border-b border-slate-100 dark:border-slate-800/60 text-right">
                                        <button
                                            @click="deleteHoliday(item.id)"
                                            class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 text-rose-600 dark:text-rose-400 transition"
                                            title="Hapus"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="holidays.length === 0">
                                    <td colspan="4" class="text-center py-12 text-slate-400 text-sm">
                                        Belum ada jadwal hari libur yang ditambahkan.
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
