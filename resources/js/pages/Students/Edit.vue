<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Camera,
    Upload,
    ArrowLeft,
    RefreshCw,
    Edit3,
    Sparkles,
    CheckCircle2,
    ShieldCheck,
} from '@lucide/vue';
import { ref, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Student {
    id: number;
    nisn: string;
    name: string;
    class_name: string;
    photo_url: string | null;
    has_face_registered: boolean;
}

const props = defineProps<{
    student: Student;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const faceMeshCanvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);

let faceMeshActive = false;
let faceMeshInstance: any = null;

const captureMode = ref<'none' | 'webcam' | 'file'>('none');
const previewImage = ref<string | null>(null);
const cameraError = ref<string | null>(null);

const form = useForm({
    nisn: props.student.nisn,
    name: props.student.name,
    class_name: props.student.class_name,
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

        initFaceMesh();
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

    faceMeshActive = false;

    if (faceMeshCanvasRef.value) {
        const ctx = faceMeshCanvasRef.value.getContext('2d');

        if (ctx) {
            ctx.clearRect(
                0,
                0,
                faceMeshCanvasRef.value.width,
                faceMeshCanvasRef.value.height,
            );
        }
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

const loadScript = (src: string): Promise<boolean> => {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve(true);

            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve(true);
        script.onerror = () =>
            reject(new Error(`Failed to load script ${src}`));
        document.head.appendChild(script);
    });
};

const initFaceMesh = async () => {
    try {
        await loadScript(
            'https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js',
        );
        await loadScript(
            'https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js',
        );

        if (!(window as any).FaceMesh) {
            console.warn('FaceMesh not loaded from CDN');

            return;
        }

        faceMeshInstance = new (window as any).FaceMesh({
            locateFile: (file: string) =>
                `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`,
        });

        faceMeshInstance.setOptions({
            maxNumFaces: 1,
            refineLandmarks: false,
            minDetectionConfidence: 0.5,
            minTrackingConfidence: 0.5,
        });

        faceMeshInstance.onResults(onFaceMeshResults);
        faceMeshActive = true;

        requestAnimationFrame(processFaceMeshFrame);
    } catch (err) {
        console.error('Failed to initialize client-side face mesh:', err);
    }
};

const processFaceMeshFrame = async () => {
    if (
        !faceMeshActive ||
        !mediaStream.value ||
        !videoRef.value ||
        !faceMeshInstance
    ) {
        return;
    }

    if (videoRef.value.readyState >= 2) {
        try {
            await faceMeshInstance.send({ image: videoRef.value });
        } catch (err) {
            console.error('FaceMesh frame send error:', err);
        }
    }

    if (mediaStream.value && faceMeshActive) {
        requestAnimationFrame(processFaceMeshFrame);
    }
};

const onFaceMeshResults = (results: any) => {
    if (!faceMeshCanvasRef.value || !videoRef.value) {
        return;
    }

    const canvas = faceMeshCanvasRef.value;
    const video = videoRef.value;

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return;
    }

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
        for (const landmarks of results.multiFaceLandmarks) {
            const isProcessing = form.processing;
            const dotColor = isProcessing
                ? 'rgba(59, 130, 246, 0.9)'
                : 'rgba(14, 165, 233, 0.85)';
            const lineColor = isProcessing
                ? 'rgba(59, 130, 246, 0.35)'
                : 'rgba(14, 165, 233, 0.25)';

            // Draw 468 landmark dots
            ctx.fillStyle = dotColor;

            for (const landmark of landmarks) {
                const x = landmark.x * canvas.width;
                const y = landmark.y * canvas.height;
                ctx.beginPath();
                ctx.arc(x, y, 1.2, 0, 2 * Math.PI);
                ctx.fill();
            }

            // Draw connected face lines
            ctx.strokeStyle = lineColor;
            ctx.lineWidth = 0.55;

            const drawPath = (indices: number[]) => {
                ctx.beginPath();

                for (let i = 0; i < indices.length; i++) {
                    const lm = landmarks[indices[i]];

                    if (!lm) {
                        continue;
                    }

                    const x = lm.x * canvas.width;
                    const y = lm.y * canvas.height;

                    if (i === 0) {
                        ctx.moveTo(x, y);
                    } else {
                        ctx.lineTo(x, y);
                    }
                }

                ctx.stroke();
            };

            // Outer oval
            const faceOvalIndices = [
                10, 338, 297, 332, 284, 251, 389, 356, 454, 323, 361, 288, 397,
                365, 379, 378, 400, 377, 152, 148, 176, 149, 150, 136, 172, 58,
                132, 93, 234, 127, 162, 21, 54, 103, 67, 109, 10,
            ];
            drawPath(faceOvalIndices);

            // Left eye & eyebrow
            const leftEyeIndices = [
                33, 7, 163, 144, 145, 153, 154, 155, 133, 173, 157, 158, 159,
                160, 161, 246, 33,
            ];
            const leftEyebrowIndices = [
                70, 63, 105, 66, 107, 55, 65, 52, 53, 46,
            ];
            drawPath(leftEyeIndices);
            drawPath(leftEyebrowIndices);

            // Right eye & eyebrow
            const rightEyeIndices = [
                263, 249, 390, 373, 374, 380, 381, 382, 362, 398, 384, 385, 386,
                387, 388, 466, 263,
            ];
            const rightEyebrowIndices = [
                300, 293, 334, 296, 336, 285, 295, 282, 283, 276,
            ];
            drawPath(rightEyeIndices);
            drawPath(rightEyebrowIndices);

            // Lips
            const lipsIndices = [
                78, 95, 88, 178, 87, 14, 317, 402, 318, 324, 308, 415, 310, 311,
                312, 13, 82, 81, 80, 191, 78,
            ];
            drawPath(lipsIndices);

            // Nose bridge
            const noseIndices = [
                168, 6, 197, 195, 5, 4, 1, 242, 94, 2, 328, 462,
            ];
            drawPath(noseIndices);
        }
    }
};

const submit = () => {
    form.put(`/students/${props.student.id}`, {
        onSuccess: () => {
            stopCamera();
        },
    });
};

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Data Siswa', href: '/students' },
            { title: 'Edit Siswa', href: `/students/${student.id}/edit` },
        ]"
    >
        <Head title="Edit Data Siswa & Wajah" />

        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        <Edit3
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Edit Data Siswa & Wajah InsightFace
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Perbarui informasi NISN, Nama, Kelas, atau perbarui foto
                        referensi wajah siswa.
                    </p>
                </div>

                <Link
                    href="/students"
                    class="flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    <ArrowLeft class="h-4 w-4" /> Batal & Kembali
                </Link>
            </div>

            <!-- Form Card -->
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

                <!-- Existing Photo & Update Photo Section -->
                <div
                    class="space-y-4 border-t border-slate-200 pt-6 dark:border-slate-800"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3
                                class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100"
                            >
                                <Sparkles class="h-4 w-4 text-indigo-500" />
                                Foto Wajah Terdaftar InsightFace
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Biarkan kosong jika tidak ingin mengganti foto
                                referensi wajah yang sudah ada.
                            </p>
                        </div>

                        <!-- Mode Toggle -->
                        <div
                            class="flex rounded-xl bg-slate-100 p-1 text-xs font-semibold dark:bg-slate-800"
                        >
                            <button
                                type="button"
                                @click="
                                    captureMode = 'none';
                                    stopCamera();
                                    resetPhoto();
                                "
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 transition',
                                    captureMode === 'none'
                                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-700 dark:text-indigo-400'
                                        : 'text-slate-500',
                                ]"
                            >
                                Tetap Pakai Foto Lama
                            </button>
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
                                Ambil Foto Baru (Webcam)
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
                                Upload Foto Baru
                            </button>
                        </div>
                    </div>

                    <!-- Current Photo Preview when mode === 'none' -->
                    <div
                        v-if="captureMode === 'none'"
                        class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60"
                    >
                        <div
                            class="h-20 w-20 overflow-hidden rounded-2xl border border-slate-300 bg-slate-200 shadow-sm dark:border-slate-700 dark:bg-slate-800"
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
                                Belum Ada
                            </div>
                        </div>
                        <div>
                            <span
                                v-if="student.has_face_registered"
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
                            >
                                <ShieldCheck class="h-3.5 w-3.5" /> Model AI
                                Wajah Terdaftar (512 Dimensi)
                            </span>
                            <span
                                v-else
                                class="text-xs font-semibold text-amber-500"
                                >Foto Wajah Belum Terdaftar</span
                            >
                            <p
                                class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                            >
                                Pilih opsi di atas untuk mengganti foto
                                referensi jika wajah siswa mengalami perubahan.
                            </p>
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
                            <canvas
                                v-show="!previewImage"
                                ref="faceMeshCanvasRef"
                                class="pointer-events-none absolute inset-0 z-10 h-full w-full -scale-x-100 transform object-cover"
                            ></canvas>
                            <img
                                v-if="previewImage"
                                :src="previewImage"
                                alt="Preview Baru"
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
                                <Camera class="h-4 w-4" /> Ambil Foto Baru Siswa
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
                    <div
                        v-else-if="captureMode === 'file'"
                        class="mx-auto max-w-md space-y-4"
                    >
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
                                    Pilih berkas foto JPG/PNG baru
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
                        <CheckCircle2 class="h-4 w-4" /> Foto Baru Siap
                        Diekstrak InsightFace AI
                    </div>
                </div>

                <!-- Submit Button -->
                <div
                    class="flex justify-end border-t border-slate-200 pt-6 dark:border-slate-800"
                >
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="cursor-pointer rounded-xl bg-indigo-600 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Menyimpan Perubahan...'
                                : 'Simpan Perubahan Data Siswa'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
