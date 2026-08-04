<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Trash2, Edit3, CheckCircle2, AlertCircle, Users, ArrowLeft } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Student {
    id: number;
    nisn: string;
    name: string;
    class_name: string;
    photo_url: string | null;
    has_face_registered: boolean;
    total_attendances: number;
    created_at: string;
}

const props = defineProps<{
    students: Student[];
}>();

const deleteStudent = (student: Student) => {
    if (confirm(`Apakah Anda yakin ingin menghapus data siswa ${student.name}?`)) {
        router.delete(`/students/${student.id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Data Siswa', href: '/students' }]">
        <Head title="Manajemen Data Siswa" />

        <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <Users class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        Manajemen Data Siswa & Wajah
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Daftarkan siswa beserta foto wajah referensi untuk sistem verifikasi InsightFace AI.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/absensi"
                        class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center gap-2"
                    >
                        <ArrowLeft class="w-4 h-4" /> Mode Kamera Absensi
                    </Link>
                    <Link
                        href="/students/create"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-semibold text-white shadow-lg shadow-indigo-500/20 transition flex items-center gap-2"
                    >
                        <Plus class="w-4 h-4" /> Tambah Siswa Baru
                    </Link>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-3.5 px-4">Foto Wajah</th>
                                <th class="py-3.5 px-4">NISN</th>
                                <th class="py-3.5 px-4">Nama Lengkap</th>
                                <th class="py-3.5 px-4">Kelas</th>
                                <th class="py-3.5 px-4">Status AI InsightFace</th>
                                <th class="py-3.5 px-4">Total Absen</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="student in students" :key="student.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden border border-slate-200 dark:border-slate-700">
                                        <img
                                            v-if="student.photo_url"
                                            :src="student.photo_url"
                                            alt="Foto Siswa"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400 font-bold">
                                            -
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono font-medium text-slate-900 dark:text-slate-100">
                                    {{ student.nisn }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-slate-100">
                                    {{ student.name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-1 rounded-lg text-xs font-medium">
                                        {{ student.class_name }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        v-if="student.has_face_registered"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5" /> Terdaftar 512-d
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20"
                                    >
                                        <AlertCircle class="w-3.5 h-3.5" /> Belum Terdaftar
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-medium">
                                    {{ student.total_attendances }} Kali
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="`/students/${student.id}/edit`"
                                            class="p-2 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition"
                                            title="Edit Data & Wajah Siswa"
                                        >
                                            <Edit3 class="w-4 h-4" />
                                        </Link>

                                        <button
                                            @click="deleteStudent(student)"
                                            class="p-2 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                                            title="Hapus Data Siswa"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="students.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400 text-sm">
                                    Belum ada data siswa terdaftar. Klik <strong>"Tambah Siswa Baru"</strong> untuk mendaftarkan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
