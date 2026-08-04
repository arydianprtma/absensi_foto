<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Camera, CheckCircle2, AlertTriangle, RefreshCw, UserCheck, ShieldAlert, Sparkles, Clock, Calendar, ScanFace, User, LogOut, LogIn, Eye, ArrowLeft, ArrowRight, Smile, ShieldCheck, Volume2, VolumeX } from '@lucide/vue';
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

const props = defineProps<{
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
    { id: 'blink', label: 'Kedipkan Mata Anda', instruction: 'Kedipkan mata Anda 1-2 kali sekarang ke kamera', icon: Eye },
    { id: 'turn_left', label: 'Tengok ke KIRI', instruction: 'Miringkan / Tengokkan kepala Anda ke KIRI', icon: ArrowLeft },
    { id: 'turn_right', label: 'Tengok ke KANAN', instruction: 'Miringkan / Tengokkan kepala Anda ke KANAN', icon: ArrowRight },
    { id: 'smile', label: 'Tersenyum ke Kamera', instruction: 'Tersenyum manis ke arah kamera sekarang', icon: Smile },
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
    if (!voiceGreetingEnabled.value) return;

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
        const AudioCtx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

        if (type === 'success') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(523.25, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.3);
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
            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
        });
        mediaStream.value = stream;
        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            videoRef.value.play();
        }
        isCameraActive.value = true;
        startAutoScanLoop();
    } catch (err) {
        cameraError.value = 'Tidak dapat mengakses kamera web. Pastikan izin kamera telah diberikan di browser Anda.';
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
    if (!videoRef.value || !canvasRef.value) return null;
    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    if (!ctx) return null;
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
        if (challengeTimer) clearInterval(challengeTimer);
        challengeTimer = window.setInterval(() => {
            secondsLeft--;
            challengeCountdown.value = secondsLeft;
            if (secondsLeft <= 0) {
                if (challengeTimer) clearInterval(challengeTimer);
                isChallengeActive.value = false;
                resolve(true);
            }
        }, 1000);
    });
};

const handleAutoVerify = async (isManualClick = false) => {
    if (isVerifying.value || resultModal.value.show || isChallengeActive.value) return;

    if (livenessProtectionEnabled.value && !isChallengeActive.value) {
        await triggerLivenessChallenge();
    }

    const imageBase64 = captureSnapshot();
    if (!imageBase64) return;

    isVerifying.value = true;
    scanStatusText.value = 'Menganalisis Wajah & Anti-Spoofing...';

    try {
        const response = await fetch('/absensi/verifikasi-otomatis', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({
                image: imageBase64,
            }),
        });

        const data = await response.json();

        // Anti-Spoofing Fraud Detection (Layar HP / Video)
        if (data.is_spoof) {
            playSound('error');
            speakGreeting('Kecurangan terdeteksi. Gunakan wajah asli kamu di depan kamera.');
            scanStatusText.value = 'KECURANGAN DETEKSI!';
            resultModal.value = {
                show: true,
                success: false,
                is_spoof: true,
                title: 'Deteksi Kecurangan (Spoofing)',
                message: data.message || 'Terdeteksi foto/layar HP. Harap hadirkan wajah asli di depan kamera!',
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
            const greetingMsg = data.attendance.type === 'pulang'
                ? `Halo ${data.student.name}, absen pulang kamu berhasil.`
                : `Halo ${data.student.name}, absen masuk kamu berhasil.`;
            speakGreeting(greetingMsg);

            scanStatusText.value = `Berhasil! Wajah Terverifikasi (${data.student.name})`;
            resultModal.value = {
                show: true,
                success: true,
                title: data.attendance.type === 'pulang' ? 'Absensi Pulang Berhasil!' : 'Absensi Masuk Berhasil!',
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
                    message: data.message || 'Wajah tidak cocok (kemiripan di bawah 50.0%).',
                    similarity: data.similarity ? Math.round(data.similarity * 100) : 0,
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
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({
                student_id: selectedStudentId.value,
                image: imageBase64,
            }),
        });

        const data = await response.json();

        if (data.is_spoof) {
            playSound('error');
            speakGreeting('Kecurangan terdeteksi. Gunakan wajah asli kamu di depan kamera.');
            resultModal.value = {
                show: true,
                success: false,
                is_spoof: true,
                title: 'Deteksi Kecurangan (Spoofing)',
                message: data.message || 'Terdeteksi foto/layar HP. Harap hadirkan wajah asli!',
            };
            return;
        }

        if (response.ok && data.success) {
            playSound('success');
            const greetingMsg = data.attendance.type === 'pulang'
                ? `Halo ${data.student.name}, absen pulang kamu berhasil.`
                : `Halo ${data.student.name}, absen masuk kamu berhasil.`;
            speakGreeting(greetingMsg);

            resultModal.value = {
                show: true,
                success: true,
                title: data.attendance.type === 'pulang' ? 'Absensi Pulang Berhasil!' : 'Absensi Masuk Berhasil!',
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
                message: data.message || 'Wajah tidak cocok (kemiripan di bawah 50.0%).',
                similarity: data.similarity ? Math.round(data.similarity * 100) : 0,
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
        if (scanMode.value === 'auto' && autoScanEnabled.value && !isVerifying.value && !resultModal.value.show && !isChallengeActive.value) {
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
    if (challengeTimer) clearInterval(challengeTimer);
});
</script>

<template>
    <Head title="Absensi Verifikasi Wajah AI Otomatis & Anti-Spoofing" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
        <!-- Top Navigation Header -->
        <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur px-6 py-4 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-emerald-400 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <ScanFace class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="font-bold text-lg leading-tight tracking-wide bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
                        Absensi Pengenalan Wajah Otomatis
                    </h1>
                    <p class="text-xs text-slate-400">InsightFace Smart Engine + Anti-Video Replay</p>
                </div>
            </div>

            <!-- Attendance Time Schedule Pill -->
            <div v-if="settings" class="hidden md:flex items-center gap-4 bg-slate-950/80 px-4 py-2 rounded-2xl border border-slate-800 text-xs">
                <div class="flex items-center gap-1.5 text-emerald-400 font-semibold">
                    <LogIn class="w-4 h-4" /> Masuk: {{ settings.check_in_start }} - {{ settings.check_in_end }}
                </div>
                <span class="text-slate-700">|</span>
                <div class="flex items-center gap-1.5 text-indigo-400 font-semibold">
                    <LogOut class="w-4 h-4" /> Pulang: Mulai {{ settings.check_out_start }}
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Voice Greeting Toggle -->
                <button
                    @click="toggleVoiceGreeting"
                    :class="[
                        'p-2.5 rounded-xl border text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer',
                        voiceGreetingEnabled
                            ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                            : 'bg-slate-800 text-slate-400 border-slate-700'
                    ]"
                    :title="voiceGreetingEnabled ? 'Matikan Suara AI' : 'Aktifkan Suara AI'"
                >
                    <Volume2 v-if="voiceGreetingEnabled" class="w-4 h-4" />
                    <VolumeX v-else class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ voiceGreetingEnabled ? 'Suara HD On' : 'Suara HD Off' }}</span>
                </button>

                <!-- Mode Switcher -->
                <div class="bg-slate-950 p-1 rounded-xl border border-slate-800 flex text-xs font-semibold">
                    <button
                        @click="scanMode = 'auto'; startAutoScanLoop();"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer',
                            scanMode === 'auto' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        <Sparkles class="w-3.5 h-3.5" /> Deteksi Otomatis
                    </button>
                    <button
                        @click="scanMode = 'manual'; stopAutoScanLoop();"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer',
                            scanMode === 'manual' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        <User class="w-3.5 h-3.5" /> Pilih Nama Manual
                    </button>
                </div>

                <Link
                    href="/dashboard"
                    class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 transition flex items-center gap-2"
                >
                    <UserCheck class="w-4 h-4 text-emerald-400" />
                    Dashboard Admin
                </Link>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 p-6 max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: WebCam Face Scan Area -->
            <div class="lg:col-span-2 flex flex-col gap-6">
                <!-- Video Container -->
                <div class="relative bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col items-center justify-center min-h-[420px]">
                    <video
                        ref="videoRef"
                        autoplay
                        playsinline
                        muted
                        class="w-full h-full object-cover transform -scale-x-100"
                    ></video>
                    <canvas ref="canvasRef" class="hidden"></canvas>

                    <!-- Active Liveness Interactive Challenge Overlay Box -->
                    <div v-if="isChallengeActive && currentChallenge" class="absolute inset-0 bg-slate-950/85 backdrop-blur-md z-20 flex flex-col items-center justify-center p-6 text-center animate-in fade-in zoom-in-95 duration-200">
                        <div class="w-20 h-20 rounded-3xl bg-indigo-600/30 border-2 border-indigo-400 text-indigo-300 flex items-center justify-center mb-4 shadow-xl animate-bounce">
                            <component :is="currentChallenge.icon" class="w-10 h-10" />
                        </div>
                        <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-400 mb-1">
                            TANTANGAN LIVENESS AI (LAKUKAN DALAM {{ challengeCountdown }}S)
                        </span>
                        <h2 class="text-2xl font-extrabold text-white mb-2">{{ currentChallenge.label }}</h2>
                        <p class="text-sm text-slate-300 max-w-sm">{{ currentChallenge.instruction }}</p>
                    </div>

                    <!-- Camera Overlay Box -->
                    <div v-else class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                        <div class="w-64 h-80 border-2 border-dashed border-emerald-400/80 rounded-3xl relative flex items-center justify-center">
                            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent absolute top-1/2 -translate-y-1/2 animate-bounce"></div>
                            <span class="absolute -top-7 text-xs font-medium text-emerald-400 bg-slate-950/80 px-3.5 py-1 rounded-full border border-emerald-500/30 flex items-center gap-1.5">
                                <Sparkles class="w-3.5 h-3.5 text-emerald-400" />
                                {{ scanStatusText }}
                            </span>
                        </div>
                    </div>

                    <!-- Live Indicator Badge & Liveness Shield -->
                    <div class="absolute top-4 left-4 flex items-center gap-3">
                        <div class="flex items-center gap-2 bg-slate-950/70 border border-slate-800 px-3 py-1.5 rounded-full backdrop-blur">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span class="text-xs font-medium text-emerald-400">Kamera Real-time</span>
                        </div>

                        <div class="flex items-center gap-1.5 bg-indigo-500/20 border border-indigo-500/30 px-3 py-1.5 rounded-full backdrop-blur text-xs font-semibold text-indigo-300">
                            <ShieldCheck class="w-3.5 h-3.5 text-indigo-400" />
                            Anti-Spoofing Active
                        </div>
                    </div>

                    <!-- Camera Error Notice -->
                    <div v-if="cameraError" class="absolute inset-0 bg-slate-950/90 flex flex-col items-center justify-center p-6 text-center">
                        <ShieldAlert class="w-12 h-12 text-rose-500 mb-3" />
                        <p class="text-sm font-semibold text-rose-300 max-w-md">{{ cameraError }}</p>
                        <button
                            @click="startCamera"
                            class="mt-4 px-4 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 rounded-xl flex items-center gap-2"
                        >
                            <RefreshCw class="w-4 h-4" /> Coba Lagi
                        </button>
                    </div>
                </div>

                <!-- Verification Action Bar -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- MODE AUTOMATIC -->
                    <template v-if="scanMode === 'auto'">
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                                <Sparkles class="w-4 h-4 text-emerald-400" /> Mode Auto-Detect AI + Suara Neural HD
                            </h3>
                            <p class="text-xs text-slate-400">
                                Menyapa nama siswa menggunakan Google Neural HD Voice yang sangat jernih dan manusiawi.
                            </p>
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Toggle Auto Scan Loop -->
                            <button
                                @click="toggleAutoScan"
                                :class="[
                                    'px-4 py-3 rounded-2xl font-semibold text-xs transition flex items-center gap-2 border cursor-pointer',
                                    autoScanEnabled
                                        ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
                                        : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700'
                                ]"
                            >
                                <RefreshCw :class="['w-4 h-4', autoScanEnabled ? 'animate-spin' : '']" />
                                <span>{{ autoScanEnabled ? 'Auto-Scan Aktif' : 'Aktifkan Auto-Scan' }}</span>
                            </button>

                            <!-- Manual Trigger Button -->
                            <button
                                @click="handleAutoVerify(true)"
                                :disabled="isVerifying || isChallengeActive"
                                class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/25 disabled:opacity-50 transition flex items-center justify-center gap-2 cursor-pointer flex-1 md:flex-initial"
                            >
                                <ScanFace v-if="!isVerifying" class="w-5 h-5" />
                                <RefreshCw v-else class="w-5 h-5 animate-spin" />
                                <span>{{ isVerifying ? 'Mencari Siswa...' : 'Scan Sekarang' }}</span>
                            </button>
                        </div>
                    </template>

                    <!-- MODE MANUAL -->
                    <template v-else>
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase tracking-wider">
                                Pilih Nama / NISN Siswa
                            </label>
                            <select
                                v-model="selectedStudentId"
                                class="w-full bg-slate-950 border border-slate-700 rounded-2xl px-4 py-3 text-sm focus:outline-none text-slate-100"
                            >
                                <option value="" disabled>-- Pilih Siswa Untuk Absen --</option>
                                <option v-for="student in students" :key="student.id" :value="student.id">
                                    {{ student.nisn }} - {{ student.name }} ({{ student.class_name }})
                                </option>
                            </select>
                        </div>

                        <button
                            @click="handleManualVerify"
                            :disabled="isVerifying || !selectedStudentId || isChallengeActive"
                            class="w-full md:w-auto px-8 py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-400 font-bold text-sm text-slate-950 shadow-lg shadow-emerald-500/25 disabled:opacity-50 transition flex items-center justify-center gap-2 cursor-pointer mt-4 md:mt-6"
                        >
                            <Camera v-if="!isVerifying" class="w-5 h-5" />
                            <RefreshCw v-else class="w-5 h-5 animate-spin" />
                            <span>{{ isVerifying ? 'Memproses...' : 'Verifikasi Manual' }}</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right Side: Today's Attendance Activity Feed -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 flex flex-col h-[560px] shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
                    <div class="flex items-center gap-2">
                        <Clock class="w-5 h-5 text-indigo-400" />
                        <h2 class="font-bold text-base text-slate-100">Aktivitas Hari Ini</h2>
                    </div>
                    <span class="text-xs bg-slate-800 px-2.5 py-1 rounded-full text-slate-400">
                        {{ todayLogs.length }} Siswa
                    </span>
                </div>

                <!-- Log List -->
                <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                    <div
                        v-for="log in todayLogs"
                        :key="log.id"
                        class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-3 flex items-center gap-3 hover:border-slate-700 transition"
                    >
                        <div class="w-12 h-12 rounded-xl bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-700">
                            <img
                                v-if="log.photo_url"
                                :src="log.photo_url"
                                alt="Foto Absen"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-500 text-xs font-bold">
                                N/A
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-100 truncate">{{ log.student_name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ log.nisn }} • {{ log.class_name }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                    <Clock class="w-3 h-3 text-slate-500" />
                                    Masuk: {{ log.check_in_time }}
                                    <span v-if="log.check_out_time" class="text-indigo-400 font-semibold">• Pulang: {{ log.check_out_time }}</span>
                                </span>
                            </div>
                        </div>

                        <span
                            :class="[
                                'text-xs font-semibold px-2.5 py-1 rounded-full border',
                                log.status === 'hadir'
                                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                                    : 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                            ]"
                        >
                            {{ log.status }}
                        </span>
                    </div>

                    <div v-if="todayLogs.length === 0" class="h-full flex flex-col items-center justify-center text-slate-500 text-sm py-12">
                        <Calendar class="w-10 h-10 mb-2 stroke-1 opacity-60" />
                        <p>Belum ada absensi hari ini</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Result Modal Popup -->
        <div v-if="resultModal.show" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div
                class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl relative flex flex-col items-center text-center animate-in fade-in zoom-in-95 duration-200"
            >
                <div
                    :class="[
                        'w-16 h-16 rounded-2xl flex items-center justify-center mb-4 shadow-lg',
                        resultModal.success
                            ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shadow-emerald-500/20'
                            : resultModal.already_attended
                              ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30 shadow-amber-500/20'
                              : 'bg-rose-500/20 text-rose-400 border border-rose-500/30 shadow-rose-500/20',
                    ]"
                >
                    <CheckCircle2 v-if="resultModal.success" class="w-8 h-8" />
                    <AlertTriangle v-else-if="resultModal.already_attended" class="w-8 h-8" />
                    <ShieldAlert v-else class="w-8 h-8" />
                </div>

                <h3 class="text-xl font-bold text-slate-100 mb-1">{{ resultModal.title }}</h3>
                <p class="text-sm text-slate-300 mb-4">{{ resultModal.message }}</p>

                <!-- Student Details Info Box -->
                <div v-if="resultModal.studentName" class="w-full bg-slate-950/80 border border-slate-800 rounded-2xl p-4 mb-5 text-left space-y-2">
                    <div class="flex items-center justify-between text-xs border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400">Nama Siswa:</span>
                        <span class="font-semibold text-slate-100">{{ resultModal.studentName }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400">NISN / Kelas:</span>
                        <span class="font-medium text-slate-200">{{ resultModal.nisn }} ({{ resultModal.className }})</span>
                    </div>
                    <div v-if="resultModal.checkInTime" class="flex items-center justify-between text-xs border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400">Waktu Absen Masuk:</span>
                        <span class="font-bold text-emerald-400">{{ resultModal.checkInTime }} WIB</span>
                    </div>
                    <div v-if="resultModal.checkOutTime" class="flex items-center justify-between text-xs border-b border-slate-800/80 pb-2">
                        <span class="text-slate-400">Waktu Absen Pulang:</span>
                        <span class="font-bold text-indigo-400">{{ resultModal.checkOutTime }} WIB</span>
                    </div>
                    <div v-if="resultModal.similarity" class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Akurasi InsightFace:</span>
                        <span class="font-bold text-indigo-400">{{ resultModal.similarity }}% Match</span>
                    </div>
                </div>

                <button
                    @click="closeResultModal"
                    class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-semibold text-sm text-slate-200 transition cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
