<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Camera, Upload, ArrowLeft, RefreshCw, UserPlus, Sparkles, CheckCircle2 } from '@lucide/vue';
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
        cameraError.value = 'Gagal mengakses kamera web. Izinkan akses kamera atau gunakan opsi Upload Foto.';
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
    if (!videoRef.value || !canvasRef.value) return;
    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const base64 = canvas.toDataURL('image/jpeg', 0.9);

    previewImage.value = base64;
    form.photo_base64 = base64;
};

const handleFileUpload = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;

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
    <AppLayout :breadcrumbs="[{ title: 'Data Siswa', href: '/students' }, { title: 'Tambah Siswa', href: '/students/create' }]">
        <Head title="Registrasi Wajah Siswa Baru" />

        <div class="p-6 max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <UserPlus class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        Registrasi Siswa & Wajah InsightFace
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Lengkapi data siswa dan ambil cuplikan foto wajah beresolusi jelas untuk ekstraksi embedding AI.
                    </p>
                </div>

                <Link
                    href="/students"
                    class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center gap-2"
                >
                    <ArrowLeft class="w-4 h-4" /> Kembali
                </Link>
            </div>

            <!-- Main Form Card -->
            <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            NISN Siswa *
                        </label>
                        <input
                            v-model="form.nisn"
                            type="text"
                            placeholder="Contoh: 0054321098"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                        <p v-if="form.errors.nisn" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.nisn }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Nama Lengkap *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: Ahmad Rizki"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                        <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Kelas / Rombel *
                        </label>
                        <input
                            v-model="form.class_name"
                            type="text"
                            placeholder="Contoh: XII RPL 1"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                        <p v-if="form.errors.class_name" class="text-xs text-rose-500 mt-1 font-medium">{{ form.errors.class_name }}</p>
                    </div>
                </div>

                <!-- Face Photo Capture Section -->
                <div class="border-t border-slate-200 dark:border-slate-800 pt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <Sparkles class="w-4 h-4 text-indigo-500" /> Referensi Foto Wajah Siswa
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Diperlukan untuk membentuk model pengenalan wajah InsightFace AI (512 dimensi).
                            </p>
                        </div>

                        <!-- Mode Toggle -->
                        <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl text-xs font-semibold">
                            <button
                                type="button"
                                @click="captureMode = 'webcam'; startCamera();"
                                :class="['px-3 py-1.5 rounded-lg transition cursor-pointer', captureMode === 'webcam' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500']"
                            >
                                Kamera Live
                            </button>
                            <button
                                type="button"
                                @click="captureMode = 'file'; stopCamera();"
                                :class="['px-3 py-1.5 rounded-lg transition cursor-pointer', captureMode === 'file' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500']"
                            >
                                Upload Foto
                            </button>
                        </div>
                    </div>

                    <!-- Webcam Box -->
                    <div v-if="captureMode === 'webcam'" class="space-y-4">
                        <div class="relative max-w-md mx-auto aspect-video bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 flex items-center justify-center">
                            <video v-show="!previewImage" ref="videoRef" autoplay playsinline muted class="w-full h-full object-cover transform -scale-x-100"></video>
                            <img v-if="previewImage" :src="previewImage" alt="Preview" class="w-full h-full object-cover" />
                            <canvas ref="canvasRef" class="hidden"></canvas>

                            <div v-if="cameraError && !previewImage" class="p-4 text-center text-xs text-rose-400 font-medium">
                                {{ cameraError }}
                            </div>
                        </div>

                        <div class="flex justify-center gap-3">
                            <button
                                v-if="!previewImage"
                                type="button"
                                @click="takeSnapshot"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-semibold text-xs text-white shadow-md transition flex items-center gap-2 cursor-pointer"
                            >
                                <Camera class="w-4 h-4" /> Ambil Foto Siswa
                            </button>

                            <button
                                v-else
                                type="button"
                                @click="resetPhoto"
                                class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-300 transition flex items-center gap-2 cursor-pointer"
                            >
                                <RefreshCw class="w-4 h-4" /> Ambil Ulang Foto
                            </button>
                        </div>
                    </div>

                    <!-- Upload File Box -->
                    <div v-else class="space-y-4 max-w-md mx-auto">
                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 text-center hover:border-indigo-500 transition">
                            <img v-if="previewImage" :src="previewImage" alt="Uploaded Preview" class="max-h-48 mx-auto rounded-xl object-cover mb-3" />
                            <div v-else class="space-y-2">
                                <Upload class="w-10 h-10 text-slate-400 mx-auto" />
                                <p class="text-xs text-slate-500">Pilih berkas foto JPG/PNG dengan wajah siswa terlihat jelas</p>
                            </div>
                            <input type="file" accept="image/*" @change="handleFileUpload" class="mt-3 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        </div>
                    </div>

                    <p v-if="form.errors.photo_base64" class="text-xs text-rose-500 text-center font-semibold mt-2">
                        {{ form.errors.photo_base64 }}
                    </p>

                    <div v-if="previewImage" class="flex items-center justify-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <CheckCircle2 class="w-4 h-4" /> Foto Siap Diekstrak InsightFace AI
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="border-t border-slate-200 dark:border-slate-800 pt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.photo_base64"
                        class="px-8 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 font-bold text-sm text-white shadow-lg shadow-indigo-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                    >
                        {{ form.processing ? 'Ekstraksi Feature Wajah...' : 'Simpan & Ekstrak Wajah Siswa' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
