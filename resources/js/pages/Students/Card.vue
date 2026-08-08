<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Printer,
    ArrowLeft,
    Cpu,
    QrCode,
    ShieldCheck,
    MapPin,
    School,
    GraduationCap,
} from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface StudentCardProps {
    student: {
        id: number;
        nisn: string;
        rfid_uid: string | null;
        nis: string | null;
        name: string;
        class_name: string;
        address: string | null;
        school_origin: string | null;
        photo_url: string | null;
    };
}

defineProps<StudentCardProps>();

const printCard = () => {
    window.print();
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Data Siswa', href: '/students' },
            { title: 'Cetak Kartu Pelajar RFID', href: '#' },
        ]"
    >
        <Head :title="`Kartu Pelajar RFID - ${student.name}`" />

        <div class="mx-auto max-w-5xl space-y-8 p-6 font-sans md:p-8">
            <!-- Top Controls (Hidden on Print) -->
            <div
                class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center dark:border-slate-800 print:hidden"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-extrabold text-slate-900 dark:text-white"
                    >
                        <GraduationCap
                            class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                        />
                        Kartu Tanda Pengenal Siswa RFID
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Kartu identitas resmi menyamping ukuran standar PVC CR80
                        (8.5cm × 5.5cm).
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/students"
                        class="flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <ArrowLeft class="h-4 w-4" /> Kembali
                    </Link>
                    <button
                        @click="printCard"
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-extrabold text-white shadow-md transition hover:bg-indigo-700"
                    >
                        <Printer class="h-4 w-4" /> Cetak Stiker PVC
                    </button>
                </div>
            </div>

            <!-- Card Printable Container (Horizontal Grid Menyamping) -->
            <div
                class="printable-card-area flex flex-row items-center justify-center gap-4 py-6"
            >
                <!-- FRONT CARD (SISI DEPAN: 8.5cm x 5.5cm) -->
                <div
                    class="card-item relative flex h-[55mm] w-[85mm] max-w-[85mm] min-w-[85mm] flex-col justify-between overflow-hidden rounded-xl border border-indigo-500/40 bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 p-3 text-white select-none"
                >
                    <!-- Background Accent Ornaments -->
                    <div
                        class="pointer-events-none absolute -top-10 -right-10 h-32 w-32 rounded-full bg-indigo-500/20 blur-xl"
                    ></div>
                    <div
                        class="pointer-events-none absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-purple-500/20 blur-xl"
                    ></div>

                    <!-- Card Header -->
                    <div
                        class="relative z-10 flex items-center justify-between border-b border-white/15 pb-1.5"
                    >
                        <div class="flex items-center gap-1.5">
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-500 text-[10px] font-black text-white"
                            >
                                AI
                            </div>
                            <div>
                                <h3
                                    class="text-[10px] leading-none font-black tracking-wider uppercase"
                                >
                                    SMK NEGERI ABSENSI AI
                                </h3>
                                <p
                                    class="text-[7.5px] font-medium tracking-wide text-indigo-200"
                                >
                                    KARTU TANDA PENGENAL SISWA
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-1 rounded-full border border-white/10 bg-white/10 px-1.5 py-0.5 backdrop-blur"
                        >
                            <Cpu class="h-2.5 w-2.5 text-amber-400" />
                            <span
                                class="text-[7px] font-bold tracking-widest uppercase"
                                >RFID 1K</span
                            >
                        </div>
                    </div>

                    <!-- Card Content Body -->
                    <div
                        class="relative z-10 my-auto flex items-center gap-2.5"
                    >
                        <!-- Photo Frame -->
                        <div class="relative shrink-0">
                            <div
                                class="h-20 w-16 overflow-hidden rounded-lg border border-white/30 bg-slate-800"
                            >
                                <img
                                    v-if="student.photo_url"
                                    :src="student.photo_url"
                                    :alt="student.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-[10px] font-bold text-slate-500"
                                >
                                    NO PHOTO
                                </div>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-0.5 overflow-hidden">
                            <div
                                class="inline-block rounded border border-indigo-400/30 bg-indigo-500/30 px-1.5 py-0.5 text-[8px] font-black tracking-wider text-indigo-200"
                            >
                                KELAS: {{ student.class_name }}
                            </div>
                            <h2
                                class="truncate text-[11px] leading-tight font-black text-white uppercase"
                            >
                                {{ student.name }}
                            </h2>
                            <div
                                class="font-mono text-[8.5px] leading-tight text-indigo-200/90"
                            >
                                NISN:
                                <strong class="text-white">{{
                                    student.nisn
                                }}</strong>
                                <span v-if="student.nis">
                                    • NIS:
                                    <strong class="text-white">{{
                                        student.nis
                                    }}</strong></span
                                >
                            </div>
                            <div
                                v-if="student.school_origin"
                                class="flex items-center gap-1 truncate text-[7.5px] text-indigo-200/80"
                            >
                                <School class="h-2.5 w-2.5 shrink-0" />
                                {{ student.school_origin }}
                            </div>
                            <div
                                v-if="student.address"
                                class="flex items-center gap-1 truncate text-[7.5px] text-indigo-200/80"
                            >
                                <MapPin class="h-2.5 w-2.5 shrink-0" />
                                {{ student.address }}
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div
                        class="relative z-10 flex items-center justify-between border-t border-white/15 pt-1 font-mono text-[7.5px] text-indigo-200"
                    >
                        <div>
                            UID RFID:
                            <strong class="font-bold text-amber-400">{{
                                student.rfid_uid || 'BELUM TERHUBUNG'
                            }}</strong>
                        </div>
                        <div
                            class="flex items-center gap-1 font-bold text-emerald-400"
                        >
                            <ShieldCheck class="h-2.5 w-2.5" /> VERIFIED SMART
                            CARD
                        </div>
                    </div>
                </div>

                <!-- BACK CARD (SISI BELAKANG: 8.5cm x 5.5cm) -->
                <div
                    class="card-item relative flex h-[55mm] w-[85mm] max-w-[85mm] min-w-[85mm] flex-col justify-between overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-3 text-white select-none"
                >
                    <!-- Magnetic Stripe Sim -->
                    <div
                        class="-mx-3 h-7 w-[calc(100%+1.5rem)] border-y border-slate-800 bg-slate-950"
                    ></div>

                    <!-- Terms & Information -->
                    <div
                        class="space-y-1 text-[7.5px] leading-tight text-slate-400"
                    >
                        <p class="font-bold text-slate-200">
                            KETENTUAN PENGGUNAAN KARTU:
                        </p>
                        <ol class="list-inside list-decimal space-y-0.5">
                            <li>
                                Kartu ini adalah identitas resmi siswa yang
                                terhubung dengan sistem absensi otomatis.
                            </li>
                            <li>
                                Wajib dibawa setiap hari dan di-tap saat
                                memasuki gerbang atau ruang kelas.
                            </li>
                            <li>
                                Jika kartu hilang, segera laporkan ke Petugas
                                Tata Usaha / Admin Sekolah.
                            </li>
                        </ol>
                    </div>

                    <!-- Barcode Footer -->
                    <div
                        class="flex items-center justify-between border-t border-slate-800 pt-1.5"
                    >
                        <div class="font-mono text-[8.5px] text-slate-400">
                            SYSTEM ID: {{ student.id }} • {{ student.nisn }}
                        </div>
                        <div class="flex items-center gap-1">
                            <QrCode class="h-5 w-5 text-white" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        box-shadow: none !important;
        text-shadow: none !important;
    }

    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    header,
    nav,
    aside,
    .print\:hidden {
        display: none !important;
    }

    .printable-card-area {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10mm !important;
        padding: 0 !important;
        margin: 20mm auto !important;
        width: 100% !important;
    }

    .card-item {
        width: 85mm !important;
        height: 55mm !important;
        min-width: 85mm !important;
        min-height: 55mm !important;
        max-width: 85mm !important;
        max-height: 55mm !important;
        box-shadow: none !important;
        border-radius: 10px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
