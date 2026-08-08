<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BookOpen,
    CheckCircle2,
    RefreshCw,
    ScanFace,
    Sparkles,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted } from 'vue';
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

interface StudentItem {
    id: number;
    nisn: string;
    name: string;
    class_name: string;
    photo_url: string | null;
}

interface SubjectAttendanceRecord {
    id: number;
    schedule_id: number;
    student_id: number;
    date: string;
    check_in_time: string;
    status: string;
    verified_by: string;
    similarity_score: number | null;
    photo_url: string | null;
}

const props = defineProps<{
    classes: string[];
    selectedClass: string;
    schedules: ScheduleItem[];
    activeSchedule: ScheduleItem | null;
    students: StudentItem[];
    todayAttendances: Record<number, SubjectAttendanceRecord>;
    todayDate: string;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);

const isCameraActive = ref(false);
const isVerifying = ref(false);
const cameraError = ref<string | null>(null);
const scanStatusText = ref<string>('Kamera Siap Presensi Kelas...');

const statusForm = useForm({
    schedule_id: 0,
    student_id: 0,
    date: props.todayDate,
    status: 'hadir',
});

const currentClass = ref(props.selectedClass);
const currentScheduleId = ref<number | string>(props.activeSchedule?.id || '');

const onClassChange = () => {
    router.get(
        '/absensi-mapel',
        {
            class_name: currentClass.value,
        },
        { preserveState: true },
    );
};

const onScheduleChange = () => {
    router.get(
        '/absensi-mapel',
        {
            class_name: currentClass.value,
            schedule_id: currentScheduleId.value,
        },
        { preserveState: true },
    );
};

const startCamera = async () => {
    cameraError.value = null;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 },
                facingMode: 'user',
            },
        });
        mediaStream.value = stream;

        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            videoRef.value.play();
        }

        isCameraActive.value = true;
    } catch (err) {
        cameraError.value =
            'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan di browser.';
        console.error(err);
    }
};

const stopCamera = () => {
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach((t) => t.stop());
        mediaStream.value = null;
    }

    isCameraActive.value = false;
};

const captureSnapshot = (): string | null => {
    if (!videoRef.value || !canvasRef.value) {
        return null;
    }

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return null;
    }

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    return canvas.toDataURL('image/jpeg', 0.9);
};

const speakGreeting = (text: string) => {
    try {
        const encodedText = encodeURIComponent(text);
        const audioUrl = `/absensi/tts-audio?text=${encodedText}`;
        const audio = new Audio(audioUrl);
        audio.play().catch(() => {});
    } catch {}
};

const verifyFaceClass = async () => {
    if (!props.activeSchedule) {
        alert('Tidak ada jadwal pelajaran aktif untuk kelas ini!');

        return;
    }

    const imageBase64 = captureSnapshot();

    if (!imageBase64) {
        return;
    }

    isVerifying.value = true;
    scanStatusText.value = 'Verifikasi Wajah AI di Kelas...';

    try {
        const response = await fetch('/absensi-mapel/verifikasi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
            },
            body: JSON.stringify({
                schedule_id: props.activeSchedule.id,
                image: imageBase64,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            speakGreeting(
                `Presensi ${props.activeSchedule.subject?.name || 'mapel'} berhasil. Selamat belajar ${data.student.name}.`,
            );
            scanStatusText.value = `Berhasil! ${data.student.name}`;
            router.reload({ only: ['todayAttendances'] });
        } else {
            speakGreeting(data.message || 'Verifikasi gagal.');
            alert(data.message || 'Verifikasi presensi mapel gagal.');
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan koneksi saat verifikasi.');
    } finally {
        isVerifying.value = false;
        scanStatusText.value = 'Kamera Siap Presensi Kelas...';
    }
};

const setStatusManual = (studentId: number, statusValue: string) => {
    if (!props.activeSchedule) {
        return;
    }

    statusForm.schedule_id = props.activeSchedule.id;
    statusForm.student_id = studentId;
    statusForm.date = props.todayDate;
    statusForm.status = statusValue;

    statusForm.post('/absensi-mapel/status', {
        preserveScroll: true,
    });
};

onMounted(() => {
    startCamera();
});

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Absensi Mapel Kelas', href: '/absensi-mapel' },
        ]"
    >
        <Head title="Absensi Per Mata Pelajaran Kelas" />

        <div class="mx-auto max-w-7xl space-y-8 p-6 font-sans md:p-8">
            <!-- Header Banner -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-center dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <BookOpen
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Absensi Siswa per Mata Pelajaran Kelas
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Pemindaian wajah AI kelas & checklist presensi guru per
                        jam tatap muka pelajaran.
                    </p>
                </div>

                <!-- Class & Schedule Selector Controls -->
                <div class="flex flex-col items-center gap-3 sm:flex-row">
                    <div class="w-full sm:w-auto">
                        <select
                            v-model="currentClass"
                            @change="onClassChange"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                        >
                            <option v-for="c in classes" :key="c" :value="c">
                                Kelas: {{ c }}
                            </option>
                        </select>
                    </div>

                    <div class="w-full sm:w-auto">
                        <select
                            v-model="currentScheduleId"
                            @change="onScheduleChange"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-indigo-400"
                        >
                            <option value="" disabled>
                                -- Pilih Jadwal Pelajaran --
                            </option>
                            <option
                                v-for="s in schedules"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.day_of_week }}: {{ s.subject?.name }} ({{
                                    s.start_time.slice(0, 5)
                                }}
                                - {{ s.end_time.slice(0, 5) }} WIB)
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Active Schedule Banner -->
            <div
                v-if="activeSchedule"
                class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-indigo-500/20 bg-indigo-600/10 p-6 md:flex-row md:items-center"
            >
                <div class="space-y-1">
                    <span
                        class="rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-extrabold tracking-wider text-indigo-600 uppercase dark:text-indigo-400"
                    >
                        Jadwal Aktif Terpilih
                    </span>
                    <h2
                        class="text-xl font-extrabold text-slate-900 dark:text-white"
                    >
                        {{ activeSchedule.subject?.name }} ({{
                            activeSchedule.subject?.code
                        }})
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Guru Pengampu:
                        <strong class="text-slate-800 dark:text-slate-200">{{
                            activeSchedule.teacher_name
                        }}</strong>
                        • Jam: {{ activeSchedule.start_time.slice(0, 5) }} -
                        {{ activeSchedule.end_time.slice(0, 5) }} WIB
                    </p>
                </div>

                <div
                    class="flex items-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-2.5 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                >
                    <CheckCircle2 class="h-4 w-4" /> Presensi Kelas
                    {{ activeSchedule.class_name }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Left: WebCam Scan Area -->
                <div class="space-y-5">
                    <div
                        class="relative flex min-h-[320px] items-center justify-center overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-xl"
                    >
                        <video
                            ref="videoRef"
                            autoplay
                            playsinline
                            muted
                            class="h-full w-full -scale-x-100 transform object-cover"
                        ></video>
                        <canvas ref="canvasRef" class="hidden"></canvas>

                        <!-- Camera Status Banner -->
                        <div
                            class="absolute top-3 left-3 flex items-center gap-1.5 rounded-full border border-slate-800 bg-slate-950/80 px-3 py-1.5 text-[11px] font-semibold text-emerald-400 backdrop-blur"
                        >
                            <Sparkles class="h-3.5 w-3.5" />
                            {{ scanStatusText }}
                        </div>
                    </div>

                    <button
                        @click="verifyFaceClass"
                        :disabled="isVerifying || !activeSchedule"
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3.5 text-xs font-bold text-white shadow-md transition hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <ScanFace v-if="!isVerifying" class="h-5 w-5" />
                        <RefreshCw v-else class="h-5 w-5 animate-spin" />
                        <span>{{
                            isVerifying
                                ? 'Memproses Wajah...'
                                : 'Scan Presensi Wajah Siswa'
                        }}</span>
                    </button>
                </div>

                <!-- Right: Class Students Presensi Checklist -->
                <div
                    class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                    >
                        <div>
                            <h2
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                Daftar Siswa Kelas {{ selectedClass }}
                            </h2>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Klik tombol status untuk mengubah presensi
                                manual oleh Guru
                            </p>
                        </div>
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800"
                        >
                            {{ students.length }} Siswa
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
                                        NISN / Nama Siswa
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Jam Presensi
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 dark:border-slate-800"
                                    >
                                        Status Mapel
                                    </th>
                                    <th
                                        class="border-b border-slate-200 px-4 py-3.5 text-right dark:border-slate-800"
                                    >
                                        Ubah Status Manual
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
                                        <div
                                            class="font-bold text-slate-900 dark:text-white"
                                        >
                                            {{ st.name }}
                                        </div>
                                        <div
                                            class="font-mono text-xs text-slate-400"
                                        >
                                            {{ st.nisn }}
                                        </div>
                                    </td>

                                    <td
                                        class="border-b border-slate-100 px-4 py-3 font-mono text-xs dark:border-slate-800/60"
                                    >
                                        <span
                                            v-if="todayAttendances[st.id]"
                                            class="font-bold text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{
                                                todayAttendances[st.id]
                                                    .check_in_time
                                            }}
                                            WIB
                                        </span>
                                        <span v-else class="text-slate-400"
                                            >-</span
                                        >
                                    </td>

                                    <td
                                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800/60"
                                    >
                                        <span
                                            v-if="todayAttendances[st.id]"
                                            :class="[
                                                'rounded-full border px-2.5 py-1 text-xs font-bold capitalize',
                                                todayAttendances[st.id]
                                                    .status === 'hadir'
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                    : todayAttendances[st.id]
                                                            .status ===
                                                        'terlambat'
                                                      ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400'
                                                      : todayAttendances[st.id]
                                                              .status === 'izin'
                                                        ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400'
                                                        : todayAttendances[
                                                                st.id
                                                            ].status === 'sakit'
                                                          ? 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-500/30 dark:bg-purple-500/10 dark:text-purple-400'
                                                          : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400',
                                            ]"
                                        >
                                            {{ todayAttendances[st.id].status }}
                                        </span>
                                        <span
                                            v-else
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                        >
                                            Belum Absen
                                        </span>
                                    </td>

                                    <td
                                        class="border-b border-slate-100 px-4 py-3 text-right dark:border-slate-800/60"
                                    >
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <button
                                                @click="
                                                    setStatusManual(
                                                        st.id,
                                                        'hadir',
                                                    )
                                                "
                                                class="cursor-pointer rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-600 transition hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400"
                                            >
                                                Hadir
                                            </button>
                                            <button
                                                @click="
                                                    setStatusManual(
                                                        st.id,
                                                        'izin',
                                                    )
                                                "
                                                class="cursor-pointer rounded-md bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400"
                                            >
                                                Izin
                                            </button>
                                            <button
                                                @click="
                                                    setStatusManual(
                                                        st.id,
                                                        'sakit',
                                                    )
                                                "
                                                class="cursor-pointer rounded-md bg-purple-50 px-2 py-1 text-[11px] font-bold text-purple-600 transition hover:bg-purple-100 dark:bg-purple-500/10 dark:text-purple-400"
                                            >
                                                Sakit
                                            </button>
                                            <button
                                                @click="
                                                    setStatusManual(
                                                        st.id,
                                                        'alpa',
                                                    )
                                                "
                                                class="cursor-pointer rounded-md bg-rose-50 px-2 py-1 text-[11px] font-bold text-rose-600 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400"
                                            >
                                                Alpa
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
    </AppLayout>
</template>
