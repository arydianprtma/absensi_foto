<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    Edit3,
    CheckCircle2,
    AlertCircle,
    Users,
    ArrowLeft,
} from '@lucide/vue';
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

defineProps<{
    students: Student[];
}>();

const deleteStudent = (student: Student) => {
    if (
        confirm(`Apakah Anda yakin ingin menghapus data siswa ${student.name}?`)
    ) {
        router.delete(`/students/${student.id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Data Siswa', href: '/students' }]">
        <Head title="Manajemen Data Siswa" />

        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <!-- Header Banner -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <Users
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Manajemen Data Siswa & Wajah
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Daftarkan siswa beserta foto wajah referensi untuk
                        sistem verifikasi InsightFace AI.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/absensi"
                        class="flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        <ArrowLeft class="h-4 w-4" /> Mode Kamera Absensi
                    </Link>
                    <Link
                        href="/students/create"
                        class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700"
                    >
                        <Plus class="h-4 w-4" /> Tambah Siswa Baru
                    </Link>
                </div>
            </div>

            <!-- Table Card -->
            <div
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-200 bg-slate-50 text-xs font-semibold tracking-wider text-slate-500 uppercase dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400"
                            >
                                <th class="px-4 py-3.5">Foto Wajah</th>
                                <th class="px-4 py-3.5">NISN</th>
                                <th class="px-4 py-3.5">Nama Lengkap</th>
                                <th class="px-4 py-3.5">Kelas</th>
                                <th class="px-4 py-3.5">
                                    Status AI InsightFace
                                </th>
                                <th class="px-4 py-3.5">Total Absen</th>
                                <th class="px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-200 text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                        >
                            <tr
                                v-for="student in students"
                                :key="student.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td class="px-4 py-3">
                                    <div
                                        class="h-10 w-10 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <img
                                            v-if="student.photo_url"
                                            :src="student.photo_url"
                                            alt="Foto Siswa"
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-400"
                                        >
                                            -
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 font-mono font-medium text-slate-900 dark:text-slate-100"
                                >
                                    {{ student.nisn }}
                                </td>
                                <td
                                    class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100"
                                >
                                    {{ student.name }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{ student.class_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="student.has_face_registered"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        Terdaftar 512-d
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-600 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400"
                                    >
                                        <AlertCircle class="h-3.5 w-3.5" />
                                        Belum Terdaftar
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ student.total_attendances }} Kali
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="`/students/${student.id}/edit`"
                                            class="rounded-lg p-2 text-indigo-600 transition hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-500/10"
                                            title="Edit Data & Wajah Siswa"
                                        >
                                            <Edit3 class="h-4 w-4" />
                                        </Link>

                                        <button
                                            @click="deleteStudent(student)"
                                            class="cursor-pointer rounded-lg p-2 text-rose-500 transition hover:bg-rose-50 dark:hover:bg-rose-500/10"
                                            title="Hapus Data Siswa"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="students.length === 0">
                                <td
                                    colspan="7"
                                    class="py-12 text-center text-sm text-slate-400"
                                >
                                    Belum ada data siswa terdaftar. Klik
                                    <strong>"Tambah Siswa Baru"</strong> untuk
                                    mendaftarkan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
