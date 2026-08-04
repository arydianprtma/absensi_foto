<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Camera,
    Upload,
    ArrowLeft,
    RefreshCw,
    UserPlus,
    Sparkles,
    CheckCircle2,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);

const captureMode = ref<'webcam' | 'file'>('webcam');
const previewImage = ref<string | null>(null);
const cameraError = ref<string | null>(null);

const form = useForm({
    nisn: '',
    name: '',
    class_name: 'XII-RPL-1',
    photo_base64: '',
});

const startCamera = async () => {
    cameraError.value = null;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 1280, height: 720, facingMode: 'user' },
        });
        mediaStream.value = stream;

        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            videoRef.value.play();
        }
    } catch (err) {
        cameraError.value =
            'Gagal mengakses kamera web. Izinkan akses kamera atau gunakan opsi Upload Foto.';
        console.error('Webcam error:', err);
    }
};

const stopCamera = () => {
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach((track) => track.stop());
        mediaStream.value = null;
    }
};

const takeSnapshot = () => {
    if (!videoRef.value || !canvasRef.value) {
        return;
    }

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return;
    }

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const base64 = canvas.toDataURL('image/jpeg', 0.9);

    previewImage.value = base64;
    form.photo_base64 = base64;
};

const handleFileUpload = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        const base64 = event.target?.result as string;
        previewImage.value = base64;
        form.photo_base64 = base64;
    };
    reader.readAsDataURL(file);
};

const resetPhoto = () => {
    previewImage.value = null;
    form.photo_base64 = '';
};

const submit = () => {
    form.post('/students', {
        onSuccess: () => {
            stopCamera();
        },
    });
};

onMounted(() => {
    if (captureMode.value === 'webcam') {
        startCamera();
    }
});

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Data Siswa', href: '/students' },
            { title: 'Tambah Siswa', href: '/students/create' },
        ]"
    >
        <Head title="Registrasi Wajah Siswa Baru" />

        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <UserPlus
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Registrasi Siswa & Wajah InsightFace
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Lengkapi data siswa dan ambil cuplikan foto wajah
                        beresolusi jelas untuk ekstraksi embedding AI.
                    </p>
                </div>

                <Link
                    href="/students"
                    class="flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    <ArrowLeft class="h-4 w-4" /> Kembali
                </Link>
            </div>

            <!-- Main Form Card -->
            <form
                @submit.prevent="submit"
                class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                        >
                            NISN Siswa *
                        </label>
                        <input
                            v-model="form.nisn"
                            type="text"
                            placeholder="Contoh: 0054321098"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                        <p
                            v-if="form.errors.nisn"
                            class="mt-1 text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.nisn }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                        >
                            Nama Lengkap *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: Ahmad Rizki"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                        >
                            Kelas / Rombel *
                        </label>
                        <input
                            v-model="form.class_name"
                            type="text"
                            placeholder="Contoh: XII RPL 1"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                        <p
                            v-if="form.errors.class_name"
                            class="mt-1 text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.class_name }}
                        </p>
                    </div>
                </div>

                <!-- Face Photo Capture Section -->
                <div
                    class="space-y-4 border-t border-slate-200 pt-6 dark:border-slate-800"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3
                                class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100"
                            >
                                <Sparkles class="h-4 w-4 text-indigo-500" />
                                Referensi Foto Wajah Siswa
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Diperlukan untuk membentuk model pengenalan
                                wajah InsightFace AI (512 dimensi).
                            </p>
                        </div>

                        <!-- Mode Toggle -->
                        <div
                            class="flex rounded-xl bg-slate-100 p-1 text-xs font-semibold dark:bg-slate-800"
                        >
                            <button
                                type="button"
                                @click="
                                    captureMode = 'webcam';
                                    startCamera();
                                "
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 transition',
                                    captureMode === 'webcam'
                                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-700 dark:text-indigo-400'
                                        : 'text-slate-500',
                                ]"
                            >
                                Kamera Live
                            </button>
                            <button
                                type="button"
                                @click="
                                    captureMode = 'file';
                                    stopCamera();
                                "
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 transition',
                                    captureMode === 'file'
                                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-700 dark:text-indigo-400'
                                        : 'text-slate-500',
                                ]"
                            >
                                Upload Foto
                            </button>
                        </div>
                    </div>

                    <!-- Webcam Box -->
                    <div v-if="captureMode === 'webcam'" class="space-y-4">
                        <div
                            class="relative mx-auto flex aspect-video max-w-md items-center justify-center overflow-hidden rounded-2xl border border-slate-800 bg-slate-950"
                        >
                            <video
                                v-show="!previewImage"
                                ref="videoRef"
                                autoplay
                                playsinline
                                muted
                                class="h-full w-full -scale-x-100 transform object-cover"
                            ></video>
                            <img
                                v-if="previewImage"
                                :src="previewImage"
                                alt="Preview"
                                class="h-full w-full object-cover"
                            />
                            <canvas ref="canvasRef" class="hidden"></canvas>

                            <div
                                v-if="cameraError && !previewImage"
                                class="p-4 text-center text-xs font-medium text-rose-400"
                            >
                                {{ cameraError }}
                            </div>
                        </div>

                        <div class="flex justify-center gap-3">
                            <button
                                v-if="!previewImage"
                                type="button"
                                @click="takeSnapshot"
                                class="flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-semibold text-white shadow-md transition hover:bg-indigo-700"
                            >
                                <Camera class="h-4 w-4" /> Ambil Foto Siswa
                            </button>

                            <button
                                v-else
                                type="button"
                                @click="resetPhoto"
                                class="flex cursor-pointer items-center gap-2 rounded-xl bg-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-300"
                            >
                                <RefreshCw class="h-4 w-4" /> Ambil Ulang Foto
                            </button>
                        </div>
                    </div>

                    <!-- Upload File Box -->
                    <div v-else class="mx-auto max-w-md space-y-4">
                        <div
                            class="rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center transition hover:border-indigo-500 dark:border-slate-700"
                        >
                            <img
                                v-if="previewImage"
                                :src="previewImage"
                                alt="Uploaded Preview"
                                class="mx-auto mb-3 max-h-48 rounded-xl object-cover"
                            />
                            <div v-else class="space-y-2">
                                <Upload
                                    class="mx-auto h-10 w-10 text-slate-400"
                                />
                                <p class="text-xs text-slate-500">
                                    Pilih berkas foto JPG/PNG dengan wajah siswa
                                    terlihat jelas
                                </p>
                            </div>
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleFileUpload"
                                class="mt-3 block w-full text-xs text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                        </div>
                    </div>

                    <p
                        v-if="form.errors.photo_base64"
                        class="mt-2 text-center text-xs font-semibold text-rose-500"
                    >
                        {{ form.errors.photo_base64 }}
                    </p>

                    <div
                        v-if="previewImage"
                        class="flex items-center justify-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="h-4 w-4" /> Foto Siap Diekstrak
                        InsightFace AI
                    </div>
                </div>

                <!-- Submit Button -->
                <div
                    class="flex justify-end border-t border-slate-200 pt-6 dark:border-slate-800"
                >
                    <button
                        type="submit"
                        :disabled="form.processing || !form.photo_base64"
                        class="cursor-pointer rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-600 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:from-indigo-500 hover:to-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Ekstraksi Feature Wajah...'
                                : 'Simpan & Ekstrak Wajah Siswa'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
