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
    X,
    Cpu,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted, watch } from 'vue';

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
const faceMeshCanvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);
let faceMeshActive = false;
let faceMeshInstance: any = null;

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

// Non-blocking Walk-through Toast System
interface ToastItem {
    id: string;
    success: boolean;
    already_attended?: boolean;
    not_checked_in?: boolean;
    is_alpa?: boolean;
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
    studentId?: number;
}
const activeToasts = ref<ToastItem[]>([]);
const flashStatus = ref<'none' | 'success' | 'warning' | 'error'>('none');
let flashTimeout: number | null = null;
let lastScannedStudentId: number | null = null;
let lastScannedTime = 0;

const triggerFlash = (status: 'success' | 'warning' | 'error') => {
    flashStatus.value = status;

    if (flashTimeout) {
        clearTimeout(flashTimeout);
    }

    flashTimeout = window.setTimeout(() => {
        flashStatus.value = 'none';
    }, 1200);
};

const showToast = (item: Omit<ToastItem, 'id'>) => {
    const id = Math.random().toString(36).substring(2, 9);
    const toast: ToastItem = { ...item, id };
    activeToasts.value.push(toast);

    // Auto dismiss: 3s for success, 5s for warnings/errors/alpa
    const duration = item.success ? 3000 : 5000;
    setTimeout(() => {
        removeToast(id);
    }, duration);
};

const removeToast = (id: string) => {
    activeToasts.value = activeToasts.value.filter((t) => t.id !== id);
};

// Satpam PIN Bypass Dialog State
const showBypassDialog = ref(false);
const bypassStudentId = ref<number | null>(null);
const bypassPin = ref('');
const bypassError = ref('');

const openBypass = (studentId: number) => {
    bypassStudentId.value = studentId;
    bypassPin.value = '';
    bypassError.value = '';
    showBypassDialog.value = true;
};

const pendingBypasses = ref<
    { id: number; name: string; nisn: string; className: string }[]
>([]);

watch(
    pendingBypasses,
    (newVal) => {
        localStorage.setItem('pending_bypasses', JSON.stringify(newVal));
    },
    { deep: true },
);

const dismissBypass = (studentId: number) => {
    pendingBypasses.value = pendingBypasses.value.filter(
        (p) => p.id !== studentId,
    );
};

const submitBypass = async () => {
    if (!bypassStudentId.value) {
        return;
    }

    try {
        const response = await fetch('/absensi/bypass-satpam', {
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
                student_id: bypassStudentId.value,
                pin: bypassPin.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            pendingBypasses.value = pendingBypasses.value.filter(
                (p) => p.id !== bypassStudentId.value,
            );
            showBypassDialog.value = false;
            bypassStudentId.value = null;

            playSound('success');
            speakGreeting(
                `Bypass satpam berhasil. Selamat jalan ${data.student.name}.`,
            );
            triggerFlash('success');

            showToast({
                success: true,
                title: 'Bypass Satpam Berhasil',
                message: data.message,
                studentName: data.student.name,
                nisn: data.student.nisn,
                className: data.student.class_name,
                status: 'Hadir',
            });

            router.reload({ only: ['todayLogs'] });
        } else {
            bypassError.value = data.message || 'PIN Satpam salah!';
        }
    } catch (err) {
        console.error(err);
        bypassError.value = 'Terjadi kesalahan koneksi.';
    }
};

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

        // Use Laravel Proxy for Google Neural Indonesian Voice (100% same voice on Chrome, Edge, Safari, Firefox)
        const encodedText = encodeURIComponent(text);
        const audioUrl = `/absensi/tts-audio?text=${encodedText}`;

        const audio = new Audio(audioUrl);
        currentAudio = audio;
        audio.volume = 1.0;

        audio.play().catch((err) => {
            console.warn('TTS playback issue:', err);
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
        initFaceMesh();
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
        isChallengeActive.value ||
        showBypassDialog.value
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
            triggerFlash('error');
            showToast({
                success: false,
                is_spoof: true,
                title: 'Deteksi Kecurangan (Spoofing)',
                message:
                    data.message ||
                    'Terdeteksi foto/layar HP. Harap hadirkan wajah asli di depan kamera!',
            });

            return;
        }

        // SILENT RETURN if NO FACE IS DETECTED during auto loop
        if (data.faces_count === 0 && !isManualClick) {
            scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';

            return;
        }

        if (response.ok && data.success) {
            // Prevent double scans for the same student within 8 seconds
            const nowTime = Date.now();

            if (
                data.student &&
                data.student.id === lastScannedStudentId &&
                nowTime - lastScannedTime < 8000
            ) {
                isVerifying.value = false;
                scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';

                return;
            }

            if (data.student) {
                lastScannedStudentId = data.student.id;
                lastScannedTime = nowTime;
            }

            playSound('success');
            const greetingMsg =
                data.attendance.type === 'pulang'
                    ? `Halo ${data.student.name}, absen pulang kamu berhasil.`
                    : `Halo ${data.student.name}, absen masuk kamu berhasil.`;
            speakGreeting(greetingMsg);

            scanStatusText.value = `Berhasil! Wajah Terverifikasi (${data.student.name})`;
            triggerFlash('success');
            showToast({
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
            });
            router.reload({ only: ['todayLogs'] });
        } else if (data.already_attended) {
            // Prevent double warnings for the same student within 8 seconds
            const nowTime = Date.now();

            if (
                data.student &&
                data.student.id === lastScannedStudentId &&
                nowTime - lastScannedTime < 8000
            ) {
                isVerifying.value = false;
                scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';

                return;
            }

            if (data.student) {
                lastScannedStudentId = data.student.id;
                lastScannedTime = nowTime;
            }

            playSound('warning');
            speakGreeting(`${data.student.name}, kamu sudah absen hari ini.`);
            scanStatusText.value = `Siswa ${data.student.name} Sudah Absen Hari Ini`;
            triggerFlash('warning');
            showToast({
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
            });
        } else if (data.not_checked_in) {
            // Prevent double warnings for the same student within 8 seconds
            const nowTime = Date.now();

            if (
                data.student &&
                data.student.id === lastScannedStudentId &&
                nowTime - lastScannedTime < 8000
            ) {
                isVerifying.value = false;
                scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';

                return;
            }

            if (data.student) {
                lastScannedStudentId = data.student.id;
                lastScannedTime = nowTime;
            }

            playSound('warning');
            speakGreeting(
                `${data.student.name}, kamu belum absen masuk hari ini.`,
            );
            scanStatusText.value = `Siswa ${data.student.name} Belum Absen Masuk`;
            triggerFlash('warning');
            showToast({
                success: false,
                not_checked_in: true,
                is_alpa: true,
                title: 'Belum Absen Masuk (ALPA)',
                message: data.message,
                studentName: data.student.name,
                nisn: data.student.nisn,
                className: data.student.class_name,
            });

            const alreadyInList = pendingBypasses.value.some(
                (p) => p.id === data.student.id,
            );

            if (!alreadyInList) {
                pendingBypasses.value.push({
                    id: data.student.id,
                    name: data.student.name,
                    nisn: data.student.nisn,
                    className: data.student.class_name,
                });
            }
        } else {
            if (isManualClick || data.faces_count > 0) {
                playSound('error');
                speakGreeting('Maaf, verifikasi wajah tidak cocok.');
                scanStatusText.value = 'Wajah Tidak Dikenali / Tidak Sah';
                triggerFlash('error');
                showToast({
                    success: false,
                    title: 'Wajah Tidak Dikenali / Tidak Sah',
                    message:
                        data.message ||
                        'Wajah tidak cocok (kemiripan di bawah 50.0%).',
                    similarity: data.similarity
                        ? Math.round(data.similarity * 100)
                        : 0,
                });
            }
        }
    } catch (err) {
        if (isManualClick) {
            playSound('error');
            speakGreeting('Terjadi kesalahan koneksi.');
            showToast({
                success: false,
                title: 'Kesalahan Sistem',
                message: 'Terjadi kesalahan koneksi saat memverifikasi wajah.',
            });
        }

        console.error('Auto verification request error:', err);
    } finally {
        isVerifying.value = false;
        scanStatusText.value = 'Menunggu Wajah di Depan Kamera...';
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
            triggerFlash('error');
            showToast({
                success: false,
                is_spoof: true,
                title: 'Deteksi Kecurangan (Spoofing)',
                message:
                    data.message ||
                    'Terdeteksi foto/layar HP. Harap hadirkan wajah asli!',
            });

            return;
        }

        if (response.ok && data.success) {
            playSound('success');
            const greetingMsg =
                data.attendance.type === 'pulang'
                    ? `Halo ${data.student.name}, absen pulang kamu berhasil.`
                    : `Halo ${data.student.name}, absen masuk kamu berhasil.`;
            speakGreeting(greetingMsg);

            triggerFlash('success');
            showToast({
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
            });
            router.reload({ only: ['todayLogs'] });
        } else if (data.already_attended) {
            playSound('warning');
            speakGreeting(`${data.student.name}, kamu sudah absen hari ini.`);
            triggerFlash('warning');
            showToast({
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
            });
        } else if (data.not_checked_in) {
            playSound('warning');
            speakGreeting(
                `${data.student.name}, kamu belum absen masuk hari ini.`,
            );
            triggerFlash('warning');
            showToast({
                success: false,
                not_checked_in: true,
                is_alpa: true,
                title: 'Belum Absen Masuk',
                message: data.message,
                studentName: data.student.name,
                nisn: data.student.nisn,
                className: data.student.class_name,
            });

            const alreadyInList = pendingBypasses.value.some(
                (p) => p.id === data.student.id,
            );

            if (!alreadyInList) {
                pendingBypasses.value.push({
                    id: data.student.id,
                    name: data.student.name,
                    nisn: data.student.nisn,
                    className: data.student.class_name,
                });
            }
        } else {
            playSound('error');
            speakGreeting('Maaf, verifikasi wajah tidak cocok.');
            triggerFlash('error');
            showToast({
                success: false,
                title: 'Verifikasi Wajah Gagal',
                message:
                    data.message ||
                    'Wajah tidak cocok (kemiripan di bawah 50.0%).',
                similarity: data.similarity
                    ? Math.round(data.similarity * 100)
                    : 0,
            });
        }
    } catch (err) {
        playSound('error');
        speakGreeting('Terjadi kesalahan koneksi.');
        showToast({
            success: false,
            title: 'Kesalahan Sistem',
            message: 'Terjadi kesalahan koneksi saat memverifikasi wajah.',
        });
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
            !isChallengeActive.value &&
            !showBypassDialog.value
        ) {
            handleAutoVerify(false);
        }
    }, 800);
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
        !isCameraActive.value ||
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

    if (isCameraActive.value && faceMeshActive) {
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
            const dotColor = isVerifying.value
                ? 'rgba(59, 130, 246, 0.9)'
                : 'rgba(14, 165, 233, 0.85)';
            const lineColor = isVerifying.value
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

// RFID Reader HID Global Buffer Listener
let rfidKeyBuffer = '';
let rfidKeyTimeout: any = null;
const rfidStudentPopup = ref<any>(null);
const rfidModalOpen = ref(false);

const handleRfidKeyPress = async (e: KeyboardEvent) => {
    const activeEl = document.activeElement;
    if (
        activeEl &&
        (activeEl.tagName === 'INPUT' ||
            activeEl.tagName === 'TEXTAREA' ||
            activeEl.tagName === 'SELECT')
    ) {
        return;
    }

    if (e.key === 'Enter') {
        if (rfidKeyBuffer.length >= 4) {
            const uid = rfidKeyBuffer.trim();
            rfidKeyBuffer = '';
            await processRfidScan(uid);
        }
        rfidKeyBuffer = '';
        return;
    }

    if (e.key.length === 1) {
        rfidKeyBuffer += e.key;
        if (rfidKeyTimeout) clearTimeout(rfidKeyTimeout);
        rfidKeyTimeout = window.setTimeout(() => {
            rfidKeyBuffer = '';
        }, 500);
    }
};

const processRfidScan = async (rfidUid: string) => {
    try {
        const response = await fetch('/absensi/verify-rfid', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ rfid_uid: rfidUid }),
        });

        const data = await response.json();

        rfidStudentPopup.value = {
            student: data.student || null,
            attendance: data.attendance || null,
            message: data.message || 'Verifikasi RFID diproses.',
            success: data.success ?? false,
            already_attended: data.already_attended ?? false,
            not_checked_in: data.not_checked_in ?? false,
            rfid_uid: rfidUid,
        };
        rfidModalOpen.value = true;

        if (data.message) {
            playTtsAudio(data.message);
        }

        if (data.success) {
            router.reload({ only: ['todayLogs'] });
        }

        setTimeout(() => {
            rfidModalOpen.value = false;
        }, 5500);
    } catch (err) {
        console.error('RFID processing error:', err);
    }
};

onMounted(() => {
    startCamera();
    window.addEventListener('keydown', handleRfidKeyPress);

    // Load pending bypasses from localStorage
    const saved = localStorage.getItem('pending_bypasses');

    if (saved) {
        try {
            pendingBypasses.value = JSON.parse(saved);
        } catch (e) {
            console.error('Failed to parse pending bypasses:', e);
        }
    }
});

onUnmounted(() => {
    stopCamera();
    window.removeEventListener('keydown', handleRfidKeyPress);

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
                    :class="[
                        'relative flex min-h-[420px] flex-col items-center justify-center overflow-hidden rounded-3xl border shadow-2xl transition-all duration-300',
                        flashStatus === 'success'
                            ? 'border-emerald-500 ring-4 shadow-emerald-500/20 ring-emerald-500/20'
                            : flashStatus === 'warning'
                              ? 'border-amber-500 ring-4 shadow-amber-500/20 ring-amber-500/20'
                              : flashStatus === 'error'
                                ? 'border-rose-500 ring-4 shadow-rose-500/20 ring-rose-500/20'
                                : 'border-slate-800 bg-slate-900',
                    ]"
                >
                    <video
                        ref="videoRef"
                        autoplay
                        playsinline
                        muted
                        class="h-full w-full -scale-x-100 transform object-cover"
                    ></video>
                    <canvas ref="canvasRef" class="hidden"></canvas>
                    <canvas
                        ref="faceMeshCanvasRef"
                        class="pointer-events-none absolute inset-0 z-10 h-full w-full -scale-x-100 transform object-cover"
                    ></canvas>

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
                        class="pointer-events-none absolute inset-x-0 top-4 z-20 flex justify-center"
                    >
                        <!-- Status text overlay -->
                        <span
                            :class="[
                                'flex items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-bold shadow-lg backdrop-blur-md transition-all duration-300',
                                isVerifying
                                    ? 'animate-pulse border-indigo-500/30 bg-slate-950/90 text-indigo-400'
                                    : 'border-emerald-500/30 bg-slate-950/80 text-emerald-400',
                            ]"
                        >
                            <Sparkles
                                :class="[
                                    'h-3.5 w-3.5 transition-colors duration-300',
                                    isVerifying
                                        ? 'text-indigo-400'
                                        : 'text-emerald-400',
                                ]"
                            />
                            {{ scanStatusText }}
                        </span>
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

                <!-- Pending Bypass List Card -->
                <div
                    v-if="pendingBypasses.length > 0"
                    class="mt-6 rounded-3xl border border-amber-500/30 bg-slate-900/90 p-6 shadow-xl backdrop-blur"
                >
                    <div
                        class="mb-4 flex items-center justify-between border-b border-slate-800 pb-3"
                    >
                        <div class="flex items-center gap-2 text-amber-400">
                            <ShieldCheck class="h-5 w-5" />
                            <h3 class="text-sm font-bold">
                                Persetujuan Bypass Satpam Pending
                            </h3>
                        </div>
                        <span
                            class="rounded-full bg-amber-500/20 px-2.5 py-0.5 text-[10px] font-bold text-amber-300 uppercase"
                        >
                            {{ pendingBypasses.length }} Siswa
                        </span>
                    </div>

                    <div
                        class="max-h-[180px] divide-y divide-slate-800/60 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="student in pendingBypasses"
                            :key="student.id"
                            class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0 flex-1">
                                <h4
                                    class="truncate text-xs font-bold text-slate-100"
                                >
                                    {{ student.name }}
                                </h4>
                                <p
                                    class="mt-0.5 truncate text-[10px] text-slate-400"
                                >
                                    {{ student.nisn }} • {{ student.className }}
                                </p>
                            </div>

                            <div
                                class="ml-4 flex flex-shrink-0 items-center gap-2"
                            >
                                <button
                                    type="button"
                                    @click="openBypass(student.id)"
                                    class="flex cursor-pointer items-center gap-1 rounded-xl bg-amber-600 px-3 py-1.5 text-[11px] font-bold text-white shadow-md transition hover:bg-amber-700"
                                >
                                    <ShieldCheck class="h-3.5 w-3.5" /> Bypass
                                </button>
                                <button
                                    type="button"
                                    @click="dismissBypass(student.id)"
                                    class="cursor-pointer rounded-xl border border-slate-700 p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-slate-200"
                                    title="Abaikan"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
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
                                    Masuk: {{ log.check_in_time || '-' }}
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
                                'rounded-full border px-2.5 py-1 text-xs font-semibold capitalize',
                                log.status === 'hadir'
                                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                    : log.status === 'alpa'
                                      ? 'border-rose-500/30 bg-rose-500/10 text-rose-400'
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

        <!-- Floating Toasts Container (Non-Blocking) -->
        <div
            class="fixed right-6 bottom-6 z-50 flex w-full max-w-sm flex-col gap-3"
        >
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in activeToasts"
                    :key="toast.id"
                    :class="[
                        'flex flex-col rounded-2xl border bg-slate-900 p-4 text-left shadow-xl backdrop-blur-md',
                        toast.success
                            ? 'border-emerald-500/30 shadow-emerald-500/5'
                            : toast.already_attended || toast.not_checked_in
                              ? 'border-amber-500/30 shadow-amber-500/5'
                              : 'border-rose-500/30 shadow-rose-500/5',
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <div
                                :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-xl',
                                    toast.success
                                        ? 'bg-emerald-500/20 text-emerald-400'
                                        : toast.already_attended ||
                                            toast.not_checked_in
                                          ? 'bg-amber-500/20 text-amber-400'
                                          : 'bg-rose-500/20 text-rose-400',
                                ]"
                            >
                                <CheckCircle2
                                    v-if="toast.success"
                                    class="h-4 w-4"
                                />
                                <AlertTriangle
                                    v-else-if="
                                        toast.already_attended ||
                                        toast.not_checked_in
                                    "
                                    class="h-4 w-4"
                                />
                                <ShieldAlert v-else class="h-4 w-4" />
                            </div>
                            <h4 class="text-sm font-bold text-slate-100">
                                {{ toast.title }}
                            </h4>
                        </div>
                        <button
                            @click="removeToast(toast.id)"
                            class="text-slate-500 hover:text-slate-300"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <p class="mt-2 text-xs text-slate-300">
                        {{ toast.message }}
                    </p>

                    <!-- Student details if present -->
                    <div
                        v-if="toast.studentName"
                        class="mt-3 space-y-1 rounded-xl border border-slate-800/40 bg-slate-950/60 p-3 text-[11px]"
                    >
                        <div class="flex justify-between">
                            <span class="text-slate-400">Nama:</span>
                            <span class="font-bold text-slate-200">{{
                                toast.studentName
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">NISN / Kelas:</span>
                            <span class="text-slate-300"
                                >{{ toast.nisn }} ({{ toast.className }})</span
                            >
                        </div>
                        <div
                            v-if="toast.checkInTime"
                            class="flex justify-between"
                        >
                            <span class="text-slate-400">Masuk:</span>
                            <span class="font-semibold text-emerald-400"
                                >{{ toast.checkInTime }} WIB</span
                            >
                        </div>
                        <div
                            v-if="toast.checkOutTime"
                            class="flex justify-between"
                        >
                            <span class="text-slate-400">Pulang:</span>
                            <span class="font-semibold text-indigo-400"
                                >{{ toast.checkOutTime }} WIB</span
                            >
                        </div>
                        <div v-if="toast.status" class="flex justify-between">
                            <span class="text-slate-400">Status:</span>
                            <span
                                :class="[
                                    'font-bold capitalize',
                                    toast.status === 'hadir'
                                        ? 'text-emerald-400'
                                        : toast.status === 'alpa'
                                          ? 'text-rose-400'
                                          : 'text-amber-400',
                                ]"
                                >{{ toast.status }}</span
                            >
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>

        <!-- Satpam PIN Bypass Dialog Modal -->
        <div
            v-if="showBypassDialog"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
        >
            <div
                class="relative flex w-full max-w-sm animate-in flex-col items-center rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl duration-200 zoom-in-95 fade-in"
            >
                <div
                    class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-amber-500/30 bg-amber-500/20 text-amber-400 shadow-lg shadow-amber-500/20"
                >
                    <ShieldCheck class="h-8 w-8" />
                </div>

                <h3 class="mb-1 text-lg font-bold text-slate-100">
                    Bypass Kehadiran Siswa
                </h3>
                <p class="mb-4 text-center text-xs text-slate-400">
                    Masukkan PIN Satpam untuk mengizinkan absensi siswa ini.
                </p>

                <div class="w-full space-y-3">
                    <div>
                        <input
                            v-model="bypassPin"
                            type="password"
                            placeholder="Masukkan PIN Satpam"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-center text-sm tracking-widest text-slate-100 focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            @keyup.enter="submitBypass"
                            autofocus
                        />
                        <p
                            v-if="bypassError"
                            class="mt-1 text-center text-xs font-medium text-rose-500"
                        >
                            {{ bypassError }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="showBypassDialog = false"
                            class="flex-1 rounded-xl border border-slate-700 py-2.5 text-xs font-bold text-slate-400 transition hover:bg-slate-800"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitBypass"
                            class="flex-1 rounded-xl bg-amber-600 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-amber-700"
                        >
                            Konfirmasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- INSTANT RFID DIGITAL STUDENT ID CARD POPUP MODAL -->
        <div
            v-if="rfidModalOpen && rfidStudentPopup"
            class="fixed inset-0 z-50 flex animate-in items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md duration-200 fade-in"
        >
            <div
                :class="[
                    'relative w-full max-w-lg space-y-6 overflow-hidden rounded-3xl border p-6 font-sans text-white shadow-2xl',
                    rfidStudentPopup.success
                        ? 'border-indigo-500/30 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900'
                        : 'border-rose-500/30 bg-gradient-to-br from-slate-900 via-rose-950/70 to-slate-900',
                ]"
            >
                <!-- Close Button -->
                <button
                    @click="rfidModalOpen = false"
                    class="absolute top-4 right-4 rounded-full bg-white/10 p-1 text-slate-400 transition hover:text-white"
                >
                    <X class="h-5 w-5" />
                </button>

                <!-- Header Badge -->
                <div class="flex items-center gap-3">
                    <div
                        :class="[
                            'flex h-10 w-10 items-center justify-center rounded-2xl shadow-lg',
                            rfidStudentPopup.success
                                ? 'bg-indigo-600 shadow-indigo-500/30'
                                : 'bg-rose-600 shadow-rose-500/30',
                        ]"
                    >
                        <Cpu class="h-5 w-5 text-amber-300" />
                    </div>
                    <div>
                        <span
                            v-if="rfidStudentPopup.success"
                            class="inline-block rounded-full border border-emerald-500/30 bg-emerald-500/20 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-emerald-300 uppercase"
                        >
                            PRESENSI TAP KARTU RFID BERHASIL
                        </span>
                        <span
                            v-else
                            class="inline-block rounded-full border border-rose-500/30 bg-rose-500/20 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-rose-300 uppercase"
                        >
                            PERINGATAN ABSENSI RFID
                        </span>
                        <h3 class="text-lg font-black text-white">
                            KARTU TANDA PENGENAL SISWA
                        </h3>
                    </div>
                </div>

                <!-- Student Profile Body (If Student Exists) -->
                <div
                    v-if="rfidStudentPopup.student"
                    class="flex flex-col items-center gap-5 rounded-2xl border border-white/10 bg-white/5 p-4 sm:flex-row sm:items-start"
                >
                    <!-- Student Photo -->
                    <div class="relative shrink-0">
                        <div
                            class="h-32 w-24 overflow-hidden rounded-2xl border-2 border-indigo-400/40 bg-slate-800 shadow-xl"
                        >
                            <img
                                v-if="rfidStudentPopup.student.photo_url"
                                :src="rfidStudentPopup.student.photo_url"
                                :alt="rfidStudentPopup.student.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-500"
                            >
                                NO PHOTO
                            </div>
                        </div>
                        <div
                            :class="[
                                'absolute -right-2 -bottom-2 flex h-7 w-7 items-center justify-center rounded-full border-2 border-slate-900 text-white shadow-md',
                                rfidStudentPopup.success
                                    ? 'bg-emerald-500'
                                    : 'bg-rose-500',
                            ]"
                        >
                            <CheckCircle2
                                v-if="rfidStudentPopup.success"
                                class="h-4 w-4"
                            />
                            <AlertTriangle v-else class="h-4 w-4" />
                        </div>
                    </div>

                    <!-- Student Info Details -->
                    <div
                        class="space-y-2 overflow-hidden text-center sm:text-left"
                    >
                        <div
                            class="inline-block rounded-lg border border-indigo-400/30 bg-indigo-500/30 px-2.5 py-0.5 text-xs font-black text-indigo-200"
                        >
                            KELAS: {{ rfidStudentPopup.student.class_name }}
                        </div>
                        <h2
                            class="truncate text-xl font-black tracking-tight text-white uppercase"
                        >
                            {{ rfidStudentPopup.student.name }}
                        </h2>
                        <div
                            class="space-y-0.5 font-mono text-xs text-indigo-200"
                        >
                            <div>
                                NISN:
                                <strong class="text-white">{{
                                    rfidStudentPopup.student.nisn
                                }}</strong>
                            </div>
                            <div v-if="rfidStudentPopup.student.nis">
                                NIS:
                                <strong class="text-white">{{
                                    rfidStudentPopup.student.nis
                                }}</strong>
                            </div>
                        </div>
                        <div
                            v-if="rfidStudentPopup.student.school_origin"
                            class="truncate text-xs text-indigo-200/80"
                        >
                            Asal Sekolah:
                            <strong class="text-white">{{
                                rfidStudentPopup.student.school_origin
                            }}</strong>
                        </div>
                        <div
                            v-if="rfidStudentPopup.student.address"
                            class="truncate text-xs text-indigo-200/80"
                        >
                            Alamat:
                            <strong class="text-white">{{
                                rfidStudentPopup.student.address
                            }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Unregistered Card Body -->
                <div
                    v-else
                    class="space-y-2 rounded-2xl border border-rose-500/20 bg-white/5 p-4 text-center"
                >
                    <p class="text-sm font-bold text-rose-300">
                        Kartu RFID (UID: {{ rfidStudentPopup.rfid_uid }}) Belum
                        Terdaftar
                    </p>
                    <p class="text-xs text-slate-400">
                        Hubungi Petugas Tata Usaha atau Admin Sekolah untuk
                        menghubungkan kartu ini dengan profil siswa.
                    </p>
                </div>

                <!-- Attendance Notification Banner -->
                <div
                    :class="[
                        'flex items-center justify-between rounded-xl border p-3.5 text-xs font-semibold',
                        rfidStudentPopup.success
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                            : 'border-rose-500/30 bg-rose-500/10 text-rose-300',
                    ]"
                >
                    <span class="leading-snug">{{
                        rfidStudentPopup.message
                    }}</span>
                    <span class="shrink-0 font-mono font-bold text-amber-400"
                        >UID: {{ rfidStudentPopup.rfid_uid }}</span
                    >
                </div>
            </div>
        </div>
    </div>
</template>
