<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    Edit3,
    CheckCircle2,
    AlertCircle,
    Users,
    TriangleAlert,
    X,
    Printer,
} from '@lucide/vue';
import { ref } from 'vue';
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

const deleteModal = ref<{
    show: boolean;
    student: Student | null;
}>({
    show: false,
    student: null,
});

const confirmDeleteStudent = (student: Student) => {
    deleteModal.value = {
        show: true,
        student,
    };
};

const executeDeleteStudent = () => {
    if (!deleteModal.value.student) {
        return;
    }

    router.delete(`/students/${deleteModal.value.student.id}`, {
        onSuccess: () => {
            deleteModal.value.show = false;
        },
    });
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
                        href="/students/cards/batch-print"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <Printer class="h-4 w-4" /> Cetak Massal Stiker RFID
                        (TU)
                    </Link>
                    <Link
                        href="/students/create"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        <Plus class="h-4 w-4" /> Tambah Siswa Baru
                    </Link>
                </div>
            </div>

            <!-- Table Card -->
            <div
                class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                >
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        Daftar Siswa Terdaftar
                    </h2>
                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800"
                    >
                        Total: {{ students.length }} Siswa
                    </span>
                </div>

                <div
                    class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800"
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
                                    Siswa
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    NISN
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Kelas
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Status Wajah AI
                                </th>
                                <th
                                    class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                >
                                    Total Absen
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
                                v-for="st in students"
                                :key="st.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td
                                    class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                >
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="
                                                st.photo_url ||
                                                '/images/default-avatar.png'
                                            "
                                            alt="Foto"
                                            class="h-9 w-9 rounded-full border border-slate-200 object-cover dark:border-slate-700"
                                        />
                                        <div>
                                            <div
                                                class="font-bold text-slate-900 dark:text-white"
                                            >
                                                {{ st.name }}
                                            </div>
                                            <div
                                                class="text-[11px] text-slate-400"
                                            >
                                                Terdaftar {{ st.created_at }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 font-mono font-bold text-indigo-600 dark:border-slate-800/60 dark:text-indigo-400"
                                >
                                    <div>{{ st.nisn }}</div>
                                    <div
                                        v-if="st.rfid_uid"
                                        class="font-mono text-[10px] text-amber-600 dark:text-amber-400"
                                    >
                                        RFID: {{ st.rfid_uid }}
                                    </div>
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 font-semibold dark:border-slate-800/60"
                                >
                                    {{ st.class_name }}
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                >
                                    <span
                                        v-if="st.has_face_registered"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        Terdaftar AI
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400"
                                    >
                                        <AlertCircle class="h-3.5 w-3.5" />
                                        Belum Foto
                                    </span>
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 font-mono text-xs font-bold dark:border-slate-800/60"
                                >
                                    {{ st.total_attendances }} Kali
                                </td>
                                <td
                                    class="border-b border-slate-100 px-4 py-3 text-right dark:border-slate-800/60"
                                >
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="`/students/${st.id}/card`"
                                            class="rounded-lg bg-emerald-50 p-1.5 text-emerald-600 transition hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400"
                                            title="Cetak Kartu Pelajar RFID"
                                        >
                                            <Printer class="h-4 w-4" />
                                        </Link>
                                        <Link
                                            :href="`/students/${st.id}/edit`"
                                            class="rounded-lg bg-indigo-50 p-1.5 text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400"
                                            title="Edit Data"
                                        >
                                            <Edit3 class="h-4 w-4" />
                                        </Link>
                                        <button
                                            @click="confirmDeleteStudent(st)"
                                            class="cursor-pointer rounded-lg bg-rose-50 p-1.5 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400"
                                            title="Hapus Siswa"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="students.length === 0">
                                <td
                                    colspan="6"
                                    class="py-12 text-center text-sm text-slate-400"
                                >
                                    Belum ada data siswa terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                                Hapus Data Siswa
                            </h3>
                            <p class="text-xs text-slate-500">
                                Konfirmasi Hapus Data Siswa
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
                    Apakah Anda yakin ingin menghapus data siswa
                    <strong class="text-slate-900 dark:text-white">{{
                        deleteModal.student?.name
                    }}</strong>
                    (NISN: {{ deleteModal.student?.nisn }})? Seluruh riwayat
                    absensi dan sampel wajah siswa ini juga akan dihapus secara
                    permanen.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        @click="deleteModal.show = false"
                        class="cursor-pointer rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDeleteStudent"
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-rose-700"
                    >
                        <Trash2 class="h-4 w-4" /> Ya, Hapus Sekarang
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
