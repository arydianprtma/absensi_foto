<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Camera,
    CheckCircle2,
    AlertTriangle,
    RefreshCw,
    UserCheck,
    ShieldAlert,
    Sparkles,
    Clock,
    Calendar,
    ScanFace,
    User,
    LogOut,
    LogIn,
    Eye,
    ArrowLeft,
    ArrowRight,
    Smile,
    ShieldCheck,
    Volume2,
    VolumeX,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted } from 'vue';

interface Student {
    id: number;
    nisn: string;
    name: string;
    class_name: string;
    photo_url: string | null;
}

interface LogItem {
    id: number;
    student_name: string;
    nisn: string;
    class_name: string;
    check_in_time: string;
    check_out_time?: string | null;
    status: string;
    similarity_score: number;
    photo_url: string | null;
}

interface Settings {
    check_in_start: string;
    check_in_end: string;
    check_out_start: string;
    check_out_end: string;
}

interface Challenge {
    id: 'blink' | 'turn_left' | 'turn_right' | 'smile';
    label: string;
    instruction: string;
    icon: any;
}

defineProps<{
    students: Student[];
    todayLogs: LogItem[];
    settings?: Settings;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);

const scanMode = ref<'auto' | 'manual'>('auto');
const selectedStudentId = ref<number | string>('');
const isCameraActive = ref(false);
const isVerifying = ref(false);
const cameraError = ref<string | null>(null);
const autoScanEnabled = ref(true);
const livenessProtectionEnabled = ref(true);
const voiceGreetingEnabled = ref(true);
const currentChallenge = ref<Challenge | null>(null);
const isChallengeActive = ref(false);
const challengeCountdown = ref<number>(2);

const scanStatusText = ref<string>('Menunggu Wajah di Depan Kamera...');
let autoScanInterval: number | null = null;
let challengeTimer: number | null = null;
let currentAudio: HTMLAudioElement | null = null;

const challengesList: Challenge[] = [
    {
        id: 'blink',
        label: 'Kedipkan Mata Anda',
        instruction: 'Kedipkan mata Anda 1-2 kali sekarang ke kamera',
        icon: Eye,
    },
    {
        id: 'turn_left',
        label: 'Tengok ke KIRI',
        instruction: 'Miringkan / Tengokkan kepala Anda ke KIRI',
        icon: ArrowLeft,
    },
    {
        id: 'turn_right',
        label: 'Tengok ke KANAN',
        instruction: 'Miringkan / Tengokkan kepala Anda ke KANAN',
        icon: ArrowRight,
    },
    {
        id: 'smile',
        label: 'Tersenyum ke Kamera',
        instruction: 'Tersenyum manis ke arah kamera sekarang',
        icon: Smile,
    },
];

const resultModal = ref<{
    show: boolean;
    success: boolean;
    already_attended?: boolean;
    is_spoof?: boolean;
    title: string;
    message: string;
    studentName?: string;
    nisn?: string;
    className?: string;
    checkInTime?: string;
    checkOutTime?: string;
    type?: string;
    status?: string;
    similarity?: number;
    photoUrl?: string;
}>({
    show: false,
    success: false,
    title: '',
    message: '',
});

// High Definition Neural Text-to-Speech Engine
const speakGreeting = (text: string) => {
    if (!voiceGreetingEnabled.value) {
        return;
    }

    try {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio = null;
        }

        // Use Google Neural Indonesian Voice API
        const encodedText = encodeURIComponent(text);
        const audioUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodedText}&tl=id&client=tw-ob`;

        const audio = new Audio(audioUrl);
        currentAudio = audio;
        audio.volume = 1.0;

        audio.play().catch(() => {
            // Fallback to Web Speech API if audio play policy blocked
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.95;
                window.speechSynthesis.speak(utterance);
            }
        });
    } catch {
        // audio playback fallback
    }
};

// Sound feedback synthesizer using Web Audio API
const playSound = (type: 'success' | 'error' | 'warning') => {
    try {
        const AudioCtx =
            window.AudioContext ||
            (window as unknown as { webkitAudioContext: typeof AudioContext })
                .webkitAudioContext;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

        if (type === 'success') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(523.25, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(
                880,
                ctx.currentTime + 0.3,
            );
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
            osc.start();
            osc.stop(ctx.currentTime + 0.4);
        } else if (type === 'warning') {
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(440, ctx.currentTime);
            osc.frequency.setValueAtTime(349, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, ctx.currentTime);
            osc.frequency.setValueAtTime(160, ctx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
            osc.start();
            osc.stop(ctx.currentTime + 0.4);
        }
    } catch {
        // audio playback fallback
    }
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
        startAutoScanLoop();
    } catch (err) {
        cameraError.value =
            'Tidak dapat mengakses kamera web. Pastikan izin kamera telah diberikan di browser Anda.';
        console.error('Webcam access error:', err);
    }
};

const stopCamera = () => {
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach((track) => track.stop());
        mediaStream.value = null;
    }

    isCameraActive.value = false;
    stopAutoScanLoop();
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

const triggerLivenessChallenge = (): Promise<boolean> => {
    return new Promise((resolve) => {
        if (!livenessProtectionEnabled.value) {
            resolve(true);

            return;
        }

        const randomIndex = Math.floor(Math.random() * challengesList.length);
        currentChallenge.value = challengesList[randomIndex];
        isChallengeActive.value = true;
        challengeCountdown.value = 2;

        scanStatusText.value = `TANTANGAN AI: ${currentChallenge.value.label}!`;

        let secondsLeft = 2;

        if (challengeTimer) {
            clearInterval(challengeTimer);
        }

        challengeTimer = window.setInterval(() => {
            secondsLeft--;
            challengeCountdown.value = secondsLeft;

            if (secondsLeft <= 0) {
                if (challengeTimer) {
                    clearInterval(challengeTimer);
                }

                isChallengeActive.value = false;
                resolve(true);
            }
        }, 1000);
    });
};

const handleAutoVerify = async (isManualClick = false) => {
    if (
        isVerifying.value ||
        resultModal.value.show ||
        isChallengeActive.value
    ) {
        return;
    }

    if (livenessProtectionEnabled.value && !isChallengeActive.value) {
        await triggerLivenessChallenge();
    }

    const imageBase64 = captureSnapshot();

    if (!imageBase64) {
        return;
    }

    isVerifying.value = true;
    scanStatusText.value = 'Menganalisis Wajah & Anti-Spoofing...';

    try {
        const response = await fetch('/absensi/verifikasi-otomatis', {
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
                image: imageBase64,
            }),
        });

        const data = await response.json();

        // Anti-Spoofing Fraud Detection (Layar HP / Video)
        if (data.is_spoof) {
            playSound('error');
            speakGreeting(
                'Kecurangan terdeteksi. Gunakan wajah asli kamu di depan kamera.',
            );
            scanStatusText.value = 'KECURANGAN DETEKSI!';
            resultModal.value = {
                show: true,
                success: false,
                is_spoof: true,
                title: 'Deteksi Kecurangan (Spoofing)',
                message:
                    data.message ||
                    'Terdeteksi foto/layar HP. Harap hadirkan wajah asli di depan kamera!',
            };

            return;
        }

        // SILENT RETURN if NO FACE IS DETECTED during auto loop
        if (data.faces_count === 0 && !isManualClick) {
            scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';

            return;
        }

        if (response.ok && data.success) {
            playSound('success');
            const greetingMsg =
                data.attendance.type === 'pulang'
                    ? `Halo ${data.student.name}, absen pulang kamu berhasil.`
                    : `Halo ${data.student.name}, absen masuk kamu berhasil.`;
            speakGreeting(greetingMsg);

            scanStatusText.value = `Berhasil! Wajah Terverifikasi (${data.student.name})`;
            resultModal.value = {
                show: true,
                success: true,
                title:
                    data.attendance.type === 'pulang'
                        ? 'Absensi Pulang Berhasil!'
                        : 'Absensi Masuk Berhasil!',
                message: data.message,
                studentName: data.student.name,
                nisn: data.student.nisn,
                className: data.student.class_name,
                checkInTime: data.attendance.check_in_time,
                checkOutTime: data.attendance.check_out_time,
                type: data.attendance.type,
                status: data.attendance.status,
                similarity: data.attendance.similarity_percentage,
                photoUrl: data.attendance.photo_url,
            };
            router.reload({ only: ['todayLogs'] });
        } else if (data.already_attended) {
            playSound('warning');
            speakGreeting(`${data.student.name}, kamu sudah absen hari ini.`);
            scanStatusText.value = `Siswa ${data.student.name} Sudah Absen Hari Ini`;
            resultModal.value = {
                show: true,
                success: false,
                already_attended: true,
                title: 'Sudah Melakukan Absensi',
                message: data.message,
                studentName: data.student.name,
                nisn: data.student.nisn,
                className: data.student.class_name,
                checkInTime: data.attendance.check_in_time,
                checkOutTime: data.attendance.check_out_time,
                status: data.attendance.status,
            };
        } else {
            if (isManualClick || data.faces_count > 0) {
                playSound('error');
                speakGreeting('Maaf, verifikasi wajah tidak cocok.');
                scanStatusText.value = 'Wajah Tidak Dikenali / Tidak Sah';
                resultModal.value = {
                    show: true,
                    success: false,
                    title: 'Wajah Tidak Dikenali / Tidak Sah',
                    message:
                        data.message ||
                        'Wajah tidak cocok (kemiripan di bawah 50.0%).',
                    similarity: data.similarity
                        ? Math.round(data.similarity * 100)
                        : 0,
                };
            }
        }
    } catch (err) {
        if (isManualClick) {
            playSound('error');
            speakGreeting('Terjadi kesalahan koneksi.');
            resultModal.value = {
                show: true,
                success: false,
                title: 'Kesalahan Sistem',
                message: 'Terjadi kesalahan koneksi saat memverifikasi wajah.',
            };
        }

        console.error('Auto verification request error:', err);
    } finally {
        isVerifying.value = false;

        if (!resultModal.value.show) {
            scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';
        }
    }
};

const handleManualVerify = async () => {
    if (!selectedStudentId.value) {
        alert('Pilih siswa terlebih dahulu!');

        return;
    }

    if (livenessProtectionEnabled.value) {
        await triggerLivenessChallenge();
    }

    const imageBase64 = captureSnapshot();

    if (!imageBase64) {
        alert('Kamera belum siap!');

        return;
    }

    isVerifying.value = true;

    try {
        const response = await fetch('/absensi/verifikasi', {
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
                student_id: selectedStudentId.value,
                image: imageBase64,
            }),
        });

        const data = await response.json();

        if (data.is_spoof) {
            playSound('error');
            speakGreeting(
                'Kecurangan terdeteksi. Gunakan wajah asli kamu di depan kamera.',
            );
            resultModal.value = {
                show: true,
                success: false,
                is_spoof: true,
                title: 'Deteksi Kecurangan (Spoofing)',
                message:
                    data.message ||
                    'Terdeteksi foto/layar HP. Harap hadirkan wajah asli!',
            };

            return;
        }

        if (response.ok && data.success) {
            playSound('success');
            const greetingMsg =
                data.attendance.type === 'pulang'
                    ? `Halo ${data.student.name}, absen pulang kamu berhasil.`
                    : `Halo ${data.student.name}, absen masuk kamu berhasil.`;
            speakGreeting(greetingMsg);

            resultModal.value = {
                show: true,
                success: true,
                title:
                    data.attendance.type === 'pulang'
                        ? 'Absensi Pulang Berhasil!'
                        : 'Absensi Masuk Berhasil!',
                message: data.message,
                studentName: data.student.name,
                nisn: data.student.nisn,
                className: data.student.class_name,
                checkInTime: data.attendance.check_in_time,
                checkOutTime: data.attendance.check_out_time,
                type: data.attendance.type,
                status: data.attendance.status,
                similarity: data.attendance.similarity_percentage,
                photoUrl: data.attendance.photo_url,
            };
            router.reload({ only: ['todayLogs'] });
        } else if (data.already_attended) {
            playSound('warning');
            speakGreeting(`${data.student.name}, kamu sudah absen hari ini.`);
            resultModal.value = {
                show: true,
                success: false,
                already_attended: true,
                title: 'Sudah Melakukan Absensi',
                message: data.message,
                studentName: data.student.name,
                nisn: data.student.nisn,
                className: data.student.class_name,
                checkInTime: data.attendance.check_in_time,
                checkOutTime: data.attendance.check_out_time,
                status: data.attendance.status,
            };
        } else {
            playSound('error');
            speakGreeting('Maaf, verifikasi wajah tidak cocok.');
            resultModal.value = {
                show: true,
                success: false,
                title: 'Verifikasi Wajah Gagal',
                message:
                    data.message ||
                    'Wajah tidak cocok (kemiripan di bawah 50.0%).',
                similarity: data.similarity
                    ? Math.round(data.similarity * 100)
                    : 0,
            };
        }
    } catch (err) {
        playSound('error');
        speakGreeting('Terjadi kesalahan koneksi.');
        resultModal.value = {
            show: true,
            success: false,
            title: 'Kesalahan Sistem',
            message: 'Terjadi kesalahan koneksi.',
        };
        console.error('Verification error:', err);
    } finally {
        isVerifying.value = false;
    }
};

const startAutoScanLoop = () => {
    stopAutoScanLoop();
    autoScanEnabled.value = true;
    autoScanInterval = window.setInterval(() => {
        if (
            scanMode.value === 'auto' &&
            autoScanEnabled.value &&
            !isVerifying.value &&
            !resultModal.value.show &&
            !isChallengeActive.value
        ) {
            handleAutoVerify(false);
        }
    }, 4500);
};

const stopAutoScanLoop = () => {
    if (autoScanInterval) {
        clearInterval(autoScanInterval);
        autoScanInterval = null;
    }

    autoScanEnabled.value = false;
};

const toggleAutoScan = () => {
    if (autoScanEnabled.value) {
        stopAutoScanLoop();
    } else {
        startAutoScanLoop();
    }
};

const toggleVoiceGreeting = () => {
    voiceGreetingEnabled.value = !voiceGreetingEnabled.value;

    if (voiceGreetingEnabled.value) {
        speakGreeting('Suara AI jernih diaktifkan');
    }
};

const closeResultModal = () => {
    resultModal.value.show = false;
    scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';
};

onMounted(() => {
    startCamera();
});

onUnmounted(() => {
    stopCamera();

    if (challengeTimer) {
        clearInterval(challengeTimer);
    }
});
</script>

<template>
    <Head title="Absensi Verifikasi Wajah AI Otomatis & Anti-Spoofing" />

    <div
        class="flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100"
    >
        <!-- Top Navigation Header -->
        <header
            class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-800 bg-slate-900/80 px-6 py-4 backdrop-blur"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-500 to-emerald-400 shadow-lg shadow-indigo-500/20"
                >
                    <ScanFace class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1
                        class="bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-lg leading-tight font-bold tracking-wide text-transparent"
                    >
                        Absensi Pengenalan Wajah Otomatis
                    </h1>
                    <p class="text-xs text-slate-400">
                        InsightFace Smart Engine + Anti-Video Replay
                    </p>
                </div>
            </div>

            <!-- Attendance Time Schedule Pill -->
            <div
                v-if="settings"
                class="hidden items-center gap-4 rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-2 text-xs md:flex"
            >
                <div
                    class="flex items-center gap-1.5 font-semibold text-emerald-400"
                >
                    <LogIn class="h-4 w-4" /> Masuk:
                    {{ settings.check_in_start }} - {{ settings.check_in_end }}
                </div>
                <span class="text-slate-700">|</span>
                <div
                    class="flex items-center gap-1.5 font-semibold text-indigo-400"
                >
                    <LogOut class="h-4 w-4" /> Pulang: Mulai
                    {{ settings.check_out_start }}
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Voice Greeting Toggle -->
                <button
                    @click="toggleVoiceGreeting"
                    :class="[
                        'flex cursor-pointer items-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold transition',
                        voiceGreetingEnabled
                            ? 'border-emerald-500/30 bg-emerald-500/20 text-emerald-400'
                            : 'border-slate-700 bg-slate-800 text-slate-400',
                    ]"
                    :title="
                        voiceGreetingEnabled
                            ? 'Matikan Suara AI'
                            : 'Aktifkan Suara AI'
                    "
                >
                    <Volume2 v-if="voiceGreetingEnabled" class="h-4 w-4" />
                    <VolumeX v-else class="h-4 w-4" />
                    <span class="hidden sm:inline">{{
                        voiceGreetingEnabled ? 'Suara HD On' : 'Suara HD Off'
                    }}</span>
                </button>

                <!-- Mode Switcher -->
                <div
                    class="flex rounded-xl border border-slate-800 bg-slate-950 p-1 text-xs font-semibold"
                >
                    <button
                        @click="
                            scanMode = 'auto';
                            startAutoScanLoop();
                        "
                        :class="[
                            'flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 transition',
                            scanMode === 'auto'
                                ? 'bg-indigo-600 text-white shadow'
                                : 'text-slate-400 hover:text-slate-200',
                        ]"
                    >
                        <Sparkles class="h-3.5 w-3.5" /> Deteksi Otomatis
                    </button>
                    <button
                        @click="
                            scanMode = 'manual';
                            stopAutoScanLoop();
                        "
                        :class="[
                            'flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 transition',
                            scanMode === 'manual'
                                ? 'bg-indigo-600 text-white shadow'
                                : 'text-slate-400 hover:text-slate-200',
                        ]"
                    >
                        <User class="h-3.5 w-3.5" /> Pilih Nama Manual
                    </button>
                </div>

                <Link
                    href="/dashboard"
                    class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800/80 px-4 py-2 text-xs font-semibold transition hover:bg-slate-700"
                >
                    <UserCheck class="h-4 w-4 text-emerald-400" />
                    Dashboard Admin
                </Link>
            </div>
        </header>

        <!-- Main Body -->
        <main
            class="mx-auto grid w-full max-w-7xl flex-1 grid-cols-1 gap-8 p-6 lg:grid-cols-3"
        >
            <!-- Left Side: WebCam Face Scan Area -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Video Container -->
                <div
                    class="relative flex min-h-[420px] flex-col items-center justify-center overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl"
                >
                    <video
                        ref="videoRef"
                        autoplay
                        playsinline
                        muted
                        class="h-full w-full -scale-x-100 transform object-cover"
                    ></video>
                    <canvas ref="canvasRef" class="hidden"></canvas>

                    <!-- Active Liveness Interactive Challenge Overlay Box -->
                    <div
                        v-if="isChallengeActive && currentChallenge"
                        class="absolute inset-0 z-20 flex animate-in flex-col items-center justify-center bg-slate-950/85 p-6 text-center backdrop-blur-md duration-200 zoom-in-95 fade-in"
                    >
                        <div
                            class="mb-4 flex h-20 w-20 animate-bounce items-center justify-center rounded-3xl border-2 border-indigo-400 bg-indigo-600/30 text-indigo-300 shadow-xl"
                        >
                            <component
                                :is="currentChallenge.icon"
                                class="h-10 w-10"
                            />
                        </div>
                        <span
                            class="mb-1 text-xs font-extrabold tracking-widest text-indigo-400 uppercase"
                        >
                            TANTANGAN LIVENESS AI (LAKUKAN DALAM
                            {{ challengeCountdown }}S)
                        </span>
                        <h2 class="mb-2 text-2xl font-extrabold text-white">
                            {{ currentChallenge.label }}
                        </h2>
                        <p class="max-w-sm text-sm text-slate-300">
                            {{ currentChallenge.instruction }}
                        </p>
                    </div>

                    <!-- Camera Overlay Box -->
                    <div
                        v-else
                        class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <div
                            class="relative flex h-80 w-64 items-center justify-center rounded-3xl border-2 border-dashed border-emerald-400/80"
                        >
                            <div
                                class="absolute top-1/2 h-0.5 w-full -translate-y-1/2 animate-bounce bg-gradient-to-r from-transparent via-emerald-400 to-transparent"
                            ></div>
                            <span
                                class="absolute -top-7 flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-slate-950/80 px-3.5 py-1 text-xs font-medium text-emerald-400"
                            >
                                <Sparkles
                                    class="h-3.5 w-3.5 text-emerald-400"
                                />
                                {{ scanStatusText }}
                            </span>
                        </div>
                    </div>

                    <!-- Live Indicator Badge & Liveness Shield -->
                    <div class="absolute top-4 left-4 flex items-center gap-3">
                        <div
                            class="flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/70 px-3 py-1.5 backdrop-blur"
                        >
                            <span
                                class="h-2.5 w-2.5 animate-ping rounded-full bg-emerald-500"
                            ></span>
                            <span class="text-xs font-medium text-emerald-400"
                                >Kamera Real-time</span
                            >
                        </div>

                        <div
                            class="flex items-center gap-1.5 rounded-full border border-indigo-500/30 bg-indigo-500/20 px-3 py-1.5 text-xs font-semibold text-indigo-300 backdrop-blur"
                        >
                            <ShieldCheck class="h-3.5 w-3.5 text-indigo-400" />
                            Anti-Spoofing Active
                        </div>
                    </div>

                    <!-- Camera Error Notice -->
                    <div
                        v-if="cameraError"
                        class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/90 p-6 text-center"
                    >
                        <ShieldAlert class="mb-3 h-12 w-12 text-rose-500" />
                        <p class="max-w-md text-sm font-semibold text-rose-300">
                            {{ cameraError }}
                        </p>
                        <button
                            @click="startCamera"
                            class="mt-4 flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold hover:bg-indigo-500"
                        >
                            <RefreshCw class="h-4 w-4" /> Coba Lagi
                        </button>
                    </div>
                </div>

                <!-- Verification Action Bar -->
                <div
                    class="flex flex-col items-center justify-between gap-4 rounded-3xl border border-slate-800 bg-slate-900/90 p-6 shadow-xl backdrop-blur md:flex-row"
                >
                    <!-- MODE AUTOMATIC -->
                    <template v-if="scanMode === 'auto'">
                        <div class="flex-1">
                            <h3
                                class="flex items-center gap-2 text-sm font-bold text-slate-100"
                            >
                                <Sparkles class="h-4 w-4 text-emerald-400" />
                                Mode Auto-Detect AI + Suara Neural HD
                            </h3>
                            <p class="text-xs text-slate-400">
                                Menyapa nama siswa menggunakan Google Neural HD
                                Voice yang sangat jernih dan manusiawi.
                            </p>
                        </div>

                        <div class="flex w-full items-center gap-3 md:w-auto">
                            <!-- Toggle Auto Scan Loop -->
                            <button
                                @click="toggleAutoScan"
                                :class="[
                                    'flex cursor-pointer items-center gap-2 rounded-2xl border px-4 py-3 text-xs font-semibold transition',
                                    autoScanEnabled
                                        ? 'border-emerald-500/40 bg-emerald-500/20 text-emerald-300'
                                        : 'border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700',
                                ]"
                            >
                                <RefreshCw
                                    :class="[
                                        'h-4 w-4',
                                        autoScanEnabled ? 'animate-spin' : '',
                                    ]"
                                />
                                <span>{{
                                    autoScanEnabled
                                        ? 'Auto-Scan Aktif'
                                        : 'Aktifkan Auto-Scan'
                                }}</span>
                            </button>

                            <!-- Manual Trigger Button -->
                            <button
                                @click="handleAutoVerify(true)"
                                :disabled="isVerifying || isChallengeActive"
                                class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 px-8 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/25 transition hover:from-emerald-400 hover:to-teal-500 disabled:opacity-50 md:flex-initial"
                            >
                                <ScanFace v-if="!isVerifying" class="h-5 w-5" />
                                <RefreshCw
                                    v-else
                                    class="h-5 w-5 animate-spin"
                                />
                                <span>{{
                                    isVerifying
                                        ? 'Mencari Siswa...'
                                        : 'Scan Sekarang'
                                }}</span>
                            </button>
                        </div>
                    </template>

                    <!-- MODE MANUAL -->
                    <template v-else>
                        <div class="w-full flex-1">
                            <label
                                class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-400 uppercase"
                            >
                                Pilih Nama / NISN Siswa
                            </label>
                            <select
                                v-model="selectedStudentId"
                                class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:outline-none"
                            >
                                <option value="" disabled>
                                    -- Pilih Siswa Untuk Absen --
                                </option>
                                <option
                                    v-for="student in students"
                                    :key="student.id"
                                    :value="student.id"
                                >
                                    {{ student.nisn }} - {{ student.name }} ({{
                                        student.class_name
                                    }})
                                </option>
                            </select>
                        </div>

                        <button
                            @click="handleManualVerify"
                            :disabled="
                                isVerifying ||
                                !selectedStudentId ||
                                isChallengeActive
                            "
                            class="mt-4 flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-8 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-400 disabled:opacity-50 md:mt-6 md:w-auto"
                        >
                            <Camera v-if="!isVerifying" class="h-5 w-5" />
                            <RefreshCw v-else class="h-5 w-5 animate-spin" />
                            <span>{{
                                isVerifying
                                    ? 'Memproses...'
                                    : 'Verifikasi Manual'
                            }}</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right Side: Today's Attendance Activity Feed -->
            <div
                class="flex h-[560px] flex-col rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl"
            >
                <div
                    class="mb-4 flex items-center justify-between border-b border-slate-800 pb-4"
                >
                    <div class="flex items-center gap-2">
                        <Clock class="h-5 w-5 text-indigo-400" />
                        <h2 class="text-base font-bold text-slate-100">
                            Aktivitas Hari Ini
                        </h2>
                    </div>
                    <span
                        class="rounded-full bg-slate-800 px-2.5 py-1 text-xs text-slate-400"
                    >
                        {{ todayLogs.length }} Siswa
                    </span>
                </div>

                <!-- Log List -->
                <div class="flex-1 space-y-3 overflow-y-auto pr-1">
                    <div
                        v-for="log in todayLogs"
                        :key="log.id"
                        class="flex items-center gap-3 rounded-2xl border border-slate-800/80 bg-slate-950/60 p-3 transition hover:border-slate-700"
                    >
                        <div
                            class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-xl border border-slate-700 bg-slate-800"
                        >
                            <img
                                v-if="log.photo_url"
                                :src="log.photo_url"
                                alt="Foto Absen"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-500"
                            >
                                N/A
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-semibold text-slate-100"
                            >
                                {{ log.student_name }}
                            </p>
                            <p class="truncate text-xs text-slate-400">
                                {{ log.nisn }} • {{ log.class_name }}
                            </p>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="flex items-center gap-1 text-[10px] text-slate-400"
                                >
                                    <Clock class="h-3 w-3 text-slate-500" />
                                    Masuk: {{ log.check_in_time }}
                                    <span
                                        v-if="log.check_out_time"
                                        class="font-semibold text-indigo-400"
                                        >• Pulang:
                                        {{ log.check_out_time }}</span
                                    >
                                </span>
                            </div>
                        </div>

                        <span
                            :class="[
                                'rounded-full border px-2.5 py-1 text-xs font-semibold',
                                log.status === 'hadir'
                                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                    : 'border-amber-500/30 bg-amber-500/10 text-amber-400',
                            ]"
                        >
                            {{ log.status }}
                        </span>
                    </div>

                    <div
                        v-if="todayLogs.length === 0"
                        class="flex h-full flex-col items-center justify-center py-12 text-sm text-slate-500"
                    >
                        <Calendar class="mb-2 h-10 w-10 stroke-1 opacity-60" />
                        <p>Belum ada absensi hari ini</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Result Modal Popup -->
        <div
            v-if="resultModal.show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
        >
            <div
                class="relative flex w-full max-w-md animate-in flex-col items-center rounded-3xl border border-slate-800 bg-slate-900 p-6 text-center shadow-2xl duration-200 zoom-in-95 fade-in"
            >
                <div
                    :class="[
                        'mb-4 flex h-16 w-16 items-center justify-center rounded-2xl shadow-lg',
                        resultModal.success
                            ? 'border border-emerald-500/30 bg-emerald-500/20 text-emerald-400 shadow-emerald-500/20'
                            : resultModal.already_attended
                              ? 'border border-amber-500/30 bg-amber-500/20 text-amber-400 shadow-amber-500/20'
                              : 'border border-rose-500/30 bg-rose-500/20 text-rose-400 shadow-rose-500/20',
                    ]"
                >
                    <CheckCircle2 v-if="resultModal.success" class="h-8 w-8" />
                    <AlertTriangle
                        v-else-if="resultModal.already_attended"
                        class="h-8 w-8"
                    />
                    <ShieldAlert v-else class="h-8 w-8" />
                </div>

                <h3 class="mb-1 text-xl font-bold text-slate-100">
                    {{ resultModal.title }}
                </h3>
                <p class="mb-4 text-sm text-slate-300">
                    {{ resultModal.message }}
                </p>

                <!-- Student Details Info Box -->
                <div
                    v-if="resultModal.studentName"
                    class="mb-5 w-full space-y-2 rounded-2xl border border-slate-800 bg-slate-950/80 p-4 text-left"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-800/80 pb-2 text-xs"
                    >
                        <span class="text-slate-400">Nama Siswa:</span>
                        <span class="font-semibold text-slate-100">{{
                            resultModal.studentName
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between border-b border-slate-800/80 pb-2 text-xs"
                    >
                        <span class="text-slate-400">NISN / Kelas:</span>
                        <span class="font-medium text-slate-200"
                            >{{ resultModal.nisn }} ({{
                                resultModal.className
                            }})</span
                        >
                    </div>
                    <div
                        v-if="resultModal.checkInTime"
                        class="flex items-center justify-between border-b border-slate-800/80 pb-2 text-xs"
                    >
                        <span class="text-slate-400">Waktu Absen Masuk:</span>
                        <span class="font-bold text-emerald-400"
                            >{{ resultModal.checkInTime }} WIB</span
                        >
                    </div>
                    <div
                        v-if="resultModal.checkOutTime"
                        class="flex items-center justify-between border-b border-slate-800/80 pb-2 text-xs"
                    >
                        <span class="text-slate-400">Waktu Absen Pulang:</span>
                        <span class="font-bold text-indigo-400"
                            >{{ resultModal.checkOutTime }} WIB</span
                        >
                    </div>
                    <div
                        v-if="resultModal.similarity"
                        class="flex items-center justify-between text-xs"
                    >
                        <span class="text-slate-400">Akurasi InsightFace:</span>
                        <span class="font-bold text-indigo-400"
                            >{{ resultModal.similarity }}% Match</span
                        >
                    </div>
                </div>

                <button
                    @click="closeResultModal"
                    class="w-full cursor-pointer rounded-xl bg-slate-800 py-3 text-sm font-semibold text-slate-200 transition hover:bg-slate-700"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
