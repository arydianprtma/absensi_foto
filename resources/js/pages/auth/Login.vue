<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Cpu, ShieldCheck } from '@lucide/vue';

defineOptions({
    layout: {
        title: 'Masuk ke Akun Anda',
        description: 'Tempelkan Kartu RFID atau Masukkan Email & Password',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const rfidStatusMessage = ref<string>('');
const isRfidProcessing = ref<boolean>(false);

let currentAudio: HTMLAudioElement | null = null;
const speakGreeting = (text: string) => {
    try {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
            currentAudio = null;
        }

        const encodedText = encodeURIComponent(text);
        const audioUrl = `/absensi/tts-audio?text=${encodedText}`;

        const audio = new Audio(audioUrl);
        currentAudio = audio;
        audio.volume = 1.0;

        audio.play().catch(() => {});
    } catch {}
};

// RFID Reader HID Global Buffer Listener
let rfidKeyBuffer = '';
let rfidKeyTimeout: any = null;
let lastScannedUid = '';
let lastScannedTime = 0;

const handleRfidKeyPress = (e: KeyboardEvent) => {
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
            processRfidLogin(uid);
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

const processRfidLogin = async (rfidUid: string) => {
    const now = Date.now();
    if (rfidUid === lastScannedUid && now - lastScannedTime < 3000) {
        return;
    }
    lastScannedUid = rfidUid;
    lastScannedTime = now;

    isRfidProcessing.value = true;
    rfidStatusMessage.value = 'Memproses Login Kartu...';

    try {
        const response = await fetch('/login/rfid', {
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

        if (response.ok && data.success) {
            rfidStatusMessage.value = `Login Berhasil! Selamat datang, ${data.user.name}.`;
            speakGreeting(`Login berhasil. Selamat datang, ${data.user.name}.`);
            setTimeout(() => {
                window.location.href = data.redirect || '/dashboard';
            }, 800);
        } else {
            const errorMsg =
                data.message ||
                `Kartu (${rfidUid}) belum terdaftar pada akun manapun.`;
            rfidStatusMessage.value = errorMsg;
            speakGreeting('Kartu belum terdaftar pada akun guru.');
        }
    } catch (err) {
        console.error('RFID Login Error:', err);
        rfidStatusMessage.value = 'Gagal memproses login kartu.';
    } finally {
        isRfidProcessing.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleRfidKeyPress);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleRfidKeyPress);
});
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <!-- Quick RFID Card Login Banner Indicator -->
    <div
        class="mb-6 flex flex-col items-center justify-center gap-2.5 rounded-2xl border border-indigo-500/20 bg-indigo-500/10 p-4 text-center dark:border-indigo-400/20 dark:bg-indigo-950/40"
    >
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
                <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                ></span>
                <span
                    class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"
                ></span>
            </span>
            <span
                class="flex items-center gap-1.5 text-xs font-bold tracking-wide text-indigo-700 uppercase dark:text-indigo-300"
            >
                <Cpu class="h-3.5 w-3.5" />
                Quick Login Ready
            </span>
        </div>

        <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
            Tempelkan Kartu Guru / Staf untuk Login Otomatis
        </p>

        <div
            v-if="rfidStatusMessage"
            :class="[
                'mt-1 rounded-xl px-3 py-1.5 text-xs font-bold transition-all',
                rfidStatusMessage.includes('Berhasil')
                    ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'
                    : 'bg-rose-500/20 text-rose-700 dark:text-rose-300',
            ]"
        >
            <Spinner v-if="isRfidProcessing" class="mr-1 inline h-3 w-3" />
            {{ rfidStatusMessage }}
        </div>
    </div>

    <div class="relative mb-6 flex items-center justify-center">
        <div class="absolute inset-0 flex items-center">
            <div
                class="w-full border-t border-slate-200 dark:border-slate-800"
            ></div>
        </div>
        <div
            class="relative bg-white px-3 text-[10px] font-bold text-slate-400 uppercase dark:bg-slate-900 dark:text-slate-500"
        >
            Atau Masuk Manual
        </div>
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Forgot your password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Log in
            </Button>
        </div>
    </Form>
</template>
