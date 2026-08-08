<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    Edit3,
    UserCheck,
    Shield,
    TriangleAlert,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface TeacherItem {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'teacher';
    nip: string | null;
    phone: string | null;
    created_at: string;
}

defineProps<{
    teachers: TeacherItem[];
}>();

const createForm = useForm({
    name: '',
    email: '',
    nip: '',
    phone: '',
    role: 'teacher' as 'admin' | 'teacher',
    password: '',
});

const editForm = useForm({
    id: 0,
    name: '',
    email: '',
    nip: '',
    phone: '',
    role: 'teacher' as 'admin' | 'teacher',
    password: '',
});

const editModal = ref({
    show: false,
});

const deleteModal = ref<{
    show: boolean;
    teacher: TeacherItem | null;
}>({
    show: false,
    teacher: null,
});

const submitCreate = () => {
    createForm.post('/teachers', {
        onSuccess: () => {
            createForm.reset();
        },
    });
};

const openEditModal = (t: TeacherItem) => {
    editForm.id = t.id;
    editForm.name = t.name;
    editForm.email = t.email;
    editForm.nip = t.nip || '';
    editForm.phone = t.phone || '';
    editForm.role = t.role;
    editForm.password = '';
    editModal.value.show = true;
};

const submitEdit = () => {
    editForm.put(`/teachers/${editForm.id}`, {
        onSuccess: () => {
            editModal.value.show = false;
        },
    });
};

const confirmDelete = (t: TeacherItem) => {
    deleteModal.value = {
        show: true,
        teacher: t,
    };
};

const executeDelete = () => {
    if (!deleteModal.value.teacher) return;
    createForm.delete(`/teachers/${deleteModal.value.teacher.id}`, {
        onSuccess: () => {
            deleteModal.value.show = false;
        },
    });
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Kelola Akun Guru & Peran', href: '/teachers' },
        ]"
    >
        <Head title="Kelola Akun Guru & Hak Akses Role" />

        <div class="mx-auto max-w-7xl space-y-8 p-6 font-sans">
            <!-- Header Banner -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <UserCheck
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Manajemen Akun Guru & Hak Akses Role
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Daftarkan akun login guru pengampu dan atur hak akses
                        peran (Admin / Guru Kelas).
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Form Add Teacher -->
                <div
                    class="h-fit space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                    >
                        <Plus
                            class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                        />
                        Tambah Akun Guru Baru
                    </h2>

                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Nama Lengkap & Gelar
                            </label>
                            <input
                                type="text"
                                v-model="createForm.name"
                                placeholder="Contoh: Pak Budi, S.Pd"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                NIP (Nomor Induk Pegawai)
                            </label>
                            <input
                                type="text"
                                v-model="createForm.nip"
                                placeholder="Contoh: 198501012010011001"
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Email Login
                            </label>
                            <input
                                type="email"
                                v-model="createForm.email"
                                placeholder="guru@sekolah.sch.id"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                No. Telepon / WhatsApp
                            </label>
                            <input
                                type="text"
                                v-model="createForm.phone"
                                placeholder="081234567890"
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Peran / Hak Akses (Role)
                            </label>
                            <select
                                v-model="createForm.role"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-indigo-600 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400"
                            >
                                <option value="teacher">
                                    Guru Kelas (Akses Terbatas Mapel & Absen)
                                </option>
                                <option value="admin">
                                    Administrator (Akses Penuh Sistem)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Password Login
                            </label>
                            <input
                                type="password"
                                v-model="createForm.password"
                                placeholder="Minimal 8 karakter"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <Plus class="h-4 w-4" /> Simpan Akun Guru
                        </button>
                    </form>
                </div>

                <!-- Table Teachers List -->
                <div
                    class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                    >
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Daftar Akun Pengguna Terdaftar
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800"
                        >
                            Total: {{ teachers.length }} Akun
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
                                        Nama Guru / Admin
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Email Login
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Role
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
                                    v-for="t in teachers"
                                    :key="t.id"
                                    class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <div
                                            class="font-bold text-slate-900 dark:text-white"
                                        >
                                            {{ t.name }}
                                        </div>
                                        <div
                                            class="font-mono text-[11px] text-slate-400"
                                        >
                                            NIP: {{ t.nip || '-' }} • Telp:
                                            {{ t.phone || '-' }}
                                        </div>
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-mono text-xs dark:border-slate-800/60"
                                    >
                                        {{ t.email }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <span
                                            :class="[
                                                'rounded-full border px-2.5 py-1 text-xs font-bold tracking-wider uppercase',
                                                t.role === 'admin'
                                                    ? 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-500/30 dark:bg-purple-500/10 dark:text-purple-400'
                                                    : 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400',
                                            ]"
                                        >
                                            {{ t.role }}
                                        </span>
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-right dark:border-slate-800/60"
                                    >
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <button
                                                @click="openEditModal(t)"
                                                class="cursor-pointer rounded-lg bg-indigo-50 p-1.5 text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400"
                                                title="Edit Akun"
                                            >
                                                <Edit3 class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="confirmDelete(t)"
                                                class="cursor-pointer rounded-lg bg-rose-50 p-1.5 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400"
                                                title="Hapus Akun"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Teacher Modal -->
        <div
            v-if="editModal.show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
        >
            <div
                class="relative w-full max-w-lg animate-in space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl duration-150 fade-in zoom-in dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800"
                >
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        Edit Akun Guru & Peran
                    </h3>
                    <button
                        @click="editModal.show = false"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Nama Guru</label
                        >
                        <input
                            type="text"
                            v-model="editForm.name"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Email Login</label
                        >
                        <input
                            type="email"
                            v-model="editForm.email"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                                >NIP</label
                            >
                            <input
                                type="text"
                                v-model="editForm.nip"
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                                >Telepon</label
                            >
                            <input
                                type="text"
                                v-model="editForm.phone"
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Peran (Role)</label
                        >
                        <select
                            v-model="editForm.role"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs font-bold text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400"
                        >
                            <option value="teacher">
                                Guru Kelas (Akses Terbatas)
                            </option>
                            <option value="admin">
                                Administrator (Akses Penuh)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Password Baru (Kosongkan Jika Tidak Diubah)</label
                        >
                        <input
                            type="password"
                            v-model="editForm.password"
                            placeholder="Min. 8 karakter"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="editModal.show = false"
                            class="rounded-xl border px-4 py-2 text-xs font-bold"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white hover:bg-indigo-700"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
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
                                Hapus Akun Pengguna
                            </h3>
                            <p class="text-xs text-slate-500">
                                Konfirmasi Hapus Akun Guru
                            </p>
                        </div>
                    </div>
                    <button
                        @click="deleteModal.show = false"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <p
                    class="text-xs leading-relaxed font-medium text-slate-600 dark:text-slate-300"
                >
                    Apakah Anda yakin ingin menghapus akun guru
                    <strong class="text-slate-900 dark:text-white">{{
                        deleteModal.teacher?.name
                    }}</strong>
                    ({{ deleteModal.teacher?.email }})?
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        @click="deleteModal.show = false"
                        class="rounded-xl border px-4 py-2.5 text-xs font-bold"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDelete"
                        class="rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-rose-700"
                    >
                        Ya, Hapus Sekarang
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
