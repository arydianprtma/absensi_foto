<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Bookmark,
    BookOpen,
    Plus,
    Trash2,
    Clock,
    TriangleAlert,
    X,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface SubjectItem {
    id: number;
    code: string;
    name: string;
}

interface ScheduleItem {
    id: number;
    class_name: string;
    subject_id: number;
    teacher_name: string;
    day_of_week: string;
    start_time: string;
    end_time: string;
    subject?: SubjectItem;
}

defineProps<{
    subjects: SubjectItem[];
    schedules: ScheduleItem[];
    classes: string[];
}>();

const activeTab = ref<'schedules' | 'subjects'>('schedules');

const hoursOptions = Array.from({ length: 24 }, (_, i) =>
    String(i).padStart(2, '0'),
);
const minutesOptions = Array.from({ length: 60 }, (_, i) =>
    String(i).padStart(2, '0'),
);

const startHour = ref('07');
const startMinute = ref('30');
const endHour = ref('09');
const endMinute = ref('00');

const subjectForm = useForm({
    code: '',
    name: '',
});

const scheduleForm = useForm({
    class_name: '',
    subject_id: '',
    teacher_name: '',
    day_of_week: 'Senin',
    start_time: '07:30',
    end_time: '09:00',
});

// Custom Delete Modal State
const deleteModal = ref<{
    show: boolean;
    type: 'subject' | 'schedule';
    id: number | null;
    title: string;
    description: string;
}>({
    show: false,
    type: 'schedule',
    id: null,
    title: '',
    description: '',
});

watch(
    [startHour, startMinute, endHour, endMinute],
    () => {
        scheduleForm.start_time = `${startHour.value}:${startMinute.value}`;
        scheduleForm.end_time = `${endHour.value}:${endMinute.value}`;
    },
    { immediate: true },
);

const submitSubject = () => {
    subjectForm.post('/subjects', {
        onSuccess: () => subjectForm.reset(),
    });
};

const confirmDeleteSubject = (id: number, name: string) => {
    deleteModal.value = {
        show: true,
        type: 'subject',
        id,
        title: 'Hapus Mata Pelajaran',
        description: `Apakah Anda yakin ingin menghapus mata pelajaran "${name}"? Tindakan ini tidak dapat dibatalkan.`,
    };
};

const submitSchedule = () => {
    scheduleForm.post('/schedules', {
        onSuccess: () => scheduleForm.reset('teacher_name'),
    });
};

const confirmDeleteSchedule = (id: number, label: string) => {
    deleteModal.value = {
        show: true,
        type: 'schedule',
        id,
        title: 'Hapus Jadwal Pelajaran',
        description: `Apakah Anda yakin ingin menghapus jadwal pelajaran "${label}"? Tindakan ini tidak dapat dibatalkan.`,
    };
};

const executeDelete = () => {
    if (!deleteModal.value.id) {
        return;
    }

    if (deleteModal.value.type === 'subject') {
        subjectForm.delete(`/subjects/${deleteModal.value.id}`, {
            onSuccess: () => {
                deleteModal.value.show = false;
            },
        });
    } else {
        scheduleForm.delete(`/schedules/${deleteModal.value.id}`, {
            onSuccess: () => {
                deleteModal.value.show = false;
            },
        });
    }
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[{ title: 'Jadwal Pelajaran', href: '/schedules' }]"
    >
        <Head
            title="Kelola Mata Pelajaran & Jadwal Pelajaran (Format 24 Jam)"
        />

        <div class="mx-auto max-w-7xl space-y-8 p-6 font-sans md:p-8">
            <!-- Header -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <Bookmark
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Kelola Mata Pelajaran & Jadwal Pelajaran (24 Jam)
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Atur daftar mata pelajaran dan jadwal jam kelas dalam
                        format 24 Jam (00:00 - 23:59 WIB).
                    </p>
                </div>

                <!-- Tab Buttons -->
                <div
                    class="flex items-center rounded-2xl border border-slate-200 bg-slate-100 p-1 text-xs font-semibold dark:border-slate-700 dark:bg-slate-800"
                >
                    <button
                        @click="activeTab = 'schedules'"
                        :class="[
                            'flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 transition',
                            activeTab === 'schedules'
                                ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-900 dark:text-indigo-400'
                                : 'text-slate-600 dark:text-slate-400',
                        ]"
                    >
                        <Clock class="h-4 w-4" /> Jadwal Pelajaran Kelas
                    </button>
                    <button
                        @click="activeTab = 'subjects'"
                        :class="[
                            'flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 transition',
                            activeTab === 'subjects'
                                ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-900 dark:text-indigo-400'
                                : 'text-slate-600 dark:text-slate-400',
                        ]"
                    >
                        <BookOpen class="h-4 w-4" /> Daftar Mata Pelajaran
                    </button>
                </div>
            </div>

            <!-- TAB 1: SCHEDULES MANAGEMENT -->
            <div
                v-if="activeTab === 'schedules'"
                class="grid grid-cols-1 gap-8 lg:grid-cols-3"
            >
                <!-- Add Schedule Form -->
                <div
                    class="h-fit space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                    >
                        <Plus
                            class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                        />
                        Tambah Jadwal Kelas Baru
                    </h2>

                    <form @submit.prevent="submitSchedule" class="space-y-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Nama Kelas
                            </label>
                            <input
                                type="text"
                                v-model="scheduleForm.class_name"
                                placeholder="Contoh: XII-RPL-1"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Mata Pelajaran
                            </label>
                            <select
                                v-model="scheduleForm.subject_id"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="" disabled>
                                    -- Pilih Mapel --
                                </option>
                                <option
                                    v-for="sub in subjects"
                                    :key="sub.id"
                                    :value="sub.id"
                                >
                                    {{ sub.code }} - {{ sub.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Nama Guru Pengampu
                            </label>
                            <input
                                type="text"
                                v-model="scheduleForm.teacher_name"
                                placeholder="Contoh: Pak Budi, S.Pd"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Hari Pelajaran
                            </label>
                            <select
                                v-model="scheduleForm.day_of_week"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        <!-- Jam Mulai 24 Jam Select Dropdowns -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Jam Mulai (Format 24 Jam)
                            </label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <select
                                        v-model="startHour"
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    >
                                        <option
                                            v-for="h in hoursOptions"
                                            :key="'startH-' + h"
                                            :value="h"
                                        >
                                            {{ h }}
                                        </option>
                                    </select>
                                </div>
                                <span class="text-sm font-bold text-slate-400"
                                    >:</span
                                >
                                <div class="flex-1">
                                    <select
                                        v-model="startMinute"
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    >
                                        <option
                                            v-for="m in minutesOptions"
                                            :key="'startM-' + m"
                                            :value="m"
                                        >
                                            {{ m }}
                                        </option>
                                    </select>
                                </div>
                                <div
                                    class="rounded-lg bg-indigo-500/10 px-2.5 py-1.5 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ startHour }}:{{ startMinute }} WIB
                                </div>
                            </div>
                        </div>

                        <!-- Jam Selesai 24 Jam Select Dropdowns -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Jam Selesai (Format 24 Jam)
                            </label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <select
                                        v-model="endHour"
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    >
                                        <option
                                            v-for="h in hoursOptions"
                                            :key="'endH-' + h"
                                            :value="h"
                                        >
                                            {{ h }}
                                        </option>
                                    </select>
                                </div>
                                <span class="text-sm font-bold text-slate-400"
                                    >:</span
                                >
                                <div class="flex-1">
                                    <select
                                        v-model="endMinute"
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    >
                                        <option
                                            v-for="m in minutesOptions"
                                            :key="'endM-' + m"
                                            :value="m"
                                        >
                                            {{ m }}
                                        </option>
                                    </select>
                                </div>
                                <div
                                    class="rounded-lg bg-indigo-500/10 px-2.5 py-1.5 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ endHour }}:{{ endMinute }} WIB
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="scheduleForm.processing"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <Plus class="h-4 w-4" /> Simpan Jadwal ({{
                                startHour
                            }}:{{ startMinute }} - {{ endHour }}:{{ endMinute }}
                            WIB)
                        </button>
                    </form>
                </div>

                <!-- Schedules List Table -->
                <div
                    class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                    >
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Daftar Jadwal Pelajaran Sekolah (24 Jam)
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800"
                        >
                            {{ schedules.length }} Jadwal
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
                                        Kelas
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Mapel
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Guru
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Hari & Waktu (WIB)
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
                                    v-for="sch in schedules"
                                    :key="sch.id"
                                    class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-bold text-slate-900 dark:border-slate-800/60 dark:text-white"
                                    >
                                        {{ sch.class_name }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-semibold text-indigo-600 dark:border-slate-800/60 dark:text-indigo-400"
                                    >
                                        {{ sch.subject?.name || '-' }} ({{
                                            sch.subject?.code
                                        }})
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-xs font-medium text-slate-700 dark:border-slate-800/60 dark:text-slate-300"
                                    >
                                        {{ sch.teacher_name }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-mono text-xs dark:border-slate-800/60"
                                    >
                                        <span
                                            class="font-bold text-slate-900 dark:text-white"
                                            >{{ sch.day_of_week }}</span
                                        >: {{ sch.start_time.slice(0, 5) }} -
                                        {{ sch.end_time.slice(0, 5) }} WIB
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-right dark:border-slate-800/60"
                                    >
                                        <button
                                            @click="
                                                confirmDeleteSchedule(
                                                    sch.id,
                                                    `${sch.class_name} - ${sch.subject?.name}`,
                                                )
                                            "
                                            class="cursor-pointer rounded-lg bg-rose-50 p-1.5 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400"
                                            title="Hapus Jadwal"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="schedules.length === 0">
                                    <td
                                        colspan="5"
                                        class="py-12 text-center text-sm text-slate-400"
                                    >
                                        Belum ada jadwal pelajaran yang
                                        ditambahkan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: SUBJECTS MANAGEMENT -->
            <div v-else class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Add Subject Form -->
                <div
                    class="h-fit space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                    >
                        <Plus
                            class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                        />
                        Tambah Mata Pelajaran Baru
                    </h2>

                    <form @submit.prevent="submitSubject" class="space-y-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Kode Mapel
                            </label>
                            <input
                                type="text"
                                v-model="subjectForm.code"
                                placeholder="Contoh: MTK-01"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >
                                Nama Mata Pelajaran
                            </label>
                            <input
                                type="text"
                                v-model="subjectForm.name"
                                placeholder="Contoh: Matematika Dasar"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="subjectForm.processing"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Plus class="h-4 w-4" /> Simpan Mapel
                        </button>
                    </form>
                </div>

                <!-- Subjects List Table -->
                <div
                    class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                    >
                        <h2
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Daftar Mata Pelajaran
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800"
                        >
                            {{ subjects.length }} Mapel
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
                                        Kode Mapel
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Nama Mata Pelajaran
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
                                    v-for="sub in subjects"
                                    :key="sub.id"
                                    class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-mono font-bold text-indigo-600 dark:border-slate-800/60 dark:text-indigo-400"
                                    >
                                        {{ sub.code }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-900 dark:border-slate-800/60 dark:text-white"
                                    >
                                        {{ sub.name }}
                                    </td>
                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-right dark:border-slate-800/60"
                                    >
                                        <button
                                            @click="
                                                confirmDeleteSubject(
                                                    sub.id,
                                                    sub.name,
                                                )
                                            "
                                            class="cursor-pointer rounded-lg bg-rose-50 p-1.5 text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400"
                                            title="Hapus Mapel"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="subjects.length === 0">
                                    <td
                                        colspan="3"
                                        class="py-12 text-center text-sm text-slate-400"
                                    >
                                        Belum ada mata pelajaran yang
                                        ditambahkan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Beautiful Delete Confirmation Modal -->
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
                                {{ deleteModal.title }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                Konfirmasi Hapus Data
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
                    {{ deleteModal.description }}
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        @click="deleteModal.show = false"
                        class="cursor-pointer rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDelete"
                        :disabled="
                            subjectForm.processing || scheduleForm.processing
                        "
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-rose-700 disabled:opacity-50"
                    >
                        <Trash2 class="h-4 w-4" /> Ya, Hapus Sekarang
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
