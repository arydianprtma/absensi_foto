<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Printer,
    ArrowLeft,
    Cpu,
    QrCode,
    ShieldCheck,
    MapPin,
    School,
    GraduationCap,
    Filter,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface StudentCard {
    id: number;
    nisn: string;
    rfid_uid: string | null;
    nis: string | null;
    name: string;
    class_name: string;
    address: string | null;
    school_origin: string | null;
    photo_url: string | null;
}

const props = defineProps<{
    students: StudentCard[];
    classes: string[];
    selectedClass: string;
}>();

const handleFilterClass = (e: Event) => {
    const val = (e.target as HTMLSelectElement).value;
    router.get(
        '/students/cards/batch-print',
        { class_name: val },
        { preserveState: true },
    );
};

// Chunk students into pages of 5 students (10 cards = 5 front + 5 back per A4 page)
const pages = computed(() => {
    const chunkSize = 5;
    const result: StudentCard[][] = [];
    for (let i = 0; i < props.students.length; i += chunkSize) {
        result.push(props.students.slice(i, i + chunkSize));
    }
    return result;
});

const printSheet = () => {
    window.print();
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Data Siswa', href: '/students' },
            { title: 'Cetak Massal Stiker PVC (TU)', href: '#' },
        ]"
    >
        <Head title="Cetak Massal Stiker PVC RFID Siswa" />

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
                        Cetak Lembar Stiker PVC RFID Massal (Tata Usaha)
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Layout otomatis:
                        <strong>5 Pasang Siswa (10 Kartu 8.5cm × 5.5cm)</strong>
                        per lembar A4. Total terdeteksi:
                        <strong
                            >{{ students.length }} Siswa ({{
                                pages.length
                            }}
                            Lembar A4)</strong
                        >.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Filter Class -->
                    <div
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-3 py-1.5 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <Filter class="h-4 w-4 text-slate-500" />
                        <select
                            :value="selectedClass"
                            @change="handleFilterClass"
                            class="cursor-pointer bg-transparent text-xs font-bold text-slate-800 focus:outline-none dark:text-slate-200"
                        >
                            <option value="">Semua Kelas (RFID)</option>
                            <option v-for="c in classes" :key="c" :value="c">
                                Kelas {{ c }}
                            </option>
                        </select>
                    </div>

                    <Link
                        href="/students"
                        class="flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <ArrowLeft class="h-4 w-4" /> Kembali
                    </Link>
                    <button
                        @click="printSheet"
                        :disabled="students.length === 0"
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-extrabold text-white shadow-md transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Printer class="h-4 w-4" /> Cetak
                        {{ students.length }} Stiker PVC
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="students.length === 0"
                class="rounded-3xl border border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900"
            >
                <p class="text-sm font-bold text-slate-600 dark:text-slate-300">
                    Belum ada siswa yang memiliki nomor Kartu RFID (UID)
                    terdaftar.
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Isi Nomor Kartu RFID pada menu Edit Data Siswa terlebih
                    dahulu.
                </p>
            </div>

            <!-- Printable A4 Pages Container -->
            <div v-else class="batch-printable-wrapper space-y-12">
                <div
                    v-for="(pageStudents, pIdx) in pages"
                    :key="pIdx"
                    class="a4-page-sheet space-y-3 rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-950"
                >
                    <div
                        class="flex justify-between border-b pb-2 text-xs font-bold text-slate-400 print:hidden"
                    >
                        <span
                            >LEMBAR A4 KE-{{ pIdx + 1 }} ({{
                                pageStudents.length
                            }}
                            SISWA / {{ pageStudents.length * 2 }} KARTU)</span
                        >
                        <span>UKURAN KARTU: 8.5 cm × 5.5 cm</span>
                    </div>

                    <!-- 5 Rows x 2 Columns Grid (Side by Side Front & Back) -->
                    <div
                        class="cards-grid flex flex-col items-center justify-start gap-2.5"
                    >
                        <div
                            v-for="st in pageStudents"
                            :key="st.id"
                            class="card-pair-row flex flex-row items-center justify-center gap-4"
                        >
                            <!-- FRONT CARD (8.5cm x 5.5cm) -->
                            <div
                                class="card-item relative flex h-[55mm] w-[85mm] max-w-[85mm] min-w-[85mm] flex-col justify-between overflow-hidden rounded-xl border border-indigo-500/40 bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 p-2.5 text-white select-none"
                            >
                                <!-- Card Header -->
                                <div
                                    class="relative z-10 flex items-center justify-between border-b border-white/15 pb-1"
                                >
                                    <div class="flex items-center gap-1">
                                        <div
                                            class="flex h-5 w-5 items-center justify-center rounded bg-indigo-500 text-[9px] font-black text-white"
                                        >
                                            AI
                                        </div>
                                        <div>
                                            <h3
                                                class="text-[9.5px] leading-none font-black tracking-wider uppercase"
                                            >
                                                SMK NEGERI ABSENSI AI
                                            </h3>
                                            <p
                                                class="text-[7px] font-medium tracking-wide text-indigo-200"
                                            >
                                                KARTU TANDA PENGENAL SISWA
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center gap-0.5 rounded-full border border-white/10 bg-white/10 px-1.5 py-0.5"
                                    >
                                        <Cpu class="h-2 w-2 text-amber-400" />
                                        <span
                                            class="text-[6.5px] font-bold tracking-widest uppercase"
                                            >RFID 1K</span
                                        >
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div
                                    class="relative z-10 my-auto flex items-center gap-2"
                                >
                                    <!-- Photo -->
                                    <div class="relative shrink-0">
                                        <div
                                            class="h-16 w-14 overflow-hidden rounded-md border border-white/30 bg-slate-800"
                                        >
                                            <img
                                                v-if="st.photo_url"
                                                :src="st.photo_url"
                                                :alt="st.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-[8px] font-bold text-slate-500"
                                            >
                                                NO PHOTO
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Info -->
                                    <div class="space-y-0.5 overflow-hidden">
                                        <div
                                            class="inline-block rounded border border-indigo-400/30 bg-indigo-500/30 px-1.5 py-0.5 text-[7.5px] font-black tracking-wider text-indigo-200"
                                        >
                                            KELAS: {{ st.class_name }}
                                        </div>
                                        <h2
                                            class="truncate text-[10px] leading-tight font-black text-white uppercase"
                                        >
                                            {{ st.name }}
                                        </h2>
                                        <div
                                            class="font-mono text-[8px] leading-tight text-indigo-200/90"
                                        >
                                            NISN:
                                            <strong class="text-white">{{
                                                st.nisn
                                            }}</strong>
                                            <span v-if="st.nis">
                                                • NIS:
                                                <strong class="text-white">{{
                                                    st.nis
                                                }}</strong></span
                                            >
                                        </div>
                                        <div
                                            v-if="st.school_origin"
                                            class="flex items-center gap-1 truncate text-[7px] text-indigo-200/80"
                                        >
                                            <School class="h-2 w-2 shrink-0" />
                                            {{ st.school_origin }}
                                        </div>
                                        <div
                                            v-if="st.address"
                                            class="flex items-center gap-1 truncate text-[7px] text-indigo-200/80"
                                        >
                                            <MapPin class="h-2 w-2 shrink-0" />
                                            {{ st.address }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div
                                    class="relative z-10 flex items-center justify-between border-t border-white/15 pt-1 font-mono text-[7px] text-indigo-200"
                                >
                                    <div>
                                        UID:
                                        <strong
                                            class="font-bold text-amber-400"
                                            >{{ st.rfid_uid }}</strong
                                        >
                                    </div>
                                    <div
                                        class="flex items-center gap-0.5 font-bold text-emerald-400"
                                    >
                                        <ShieldCheck class="h-2 w-2" /> SMART
                                        CARD
                                    </div>
                                </div>
                            </div>

                            <!-- BACK CARD (8.5cm x 5.5cm) -->
                            <div
                                class="card-item relative flex h-[55mm] w-[85mm] max-w-[85mm] min-w-[85mm] flex-col justify-between overflow-hidden rounded-xl border border-slate-800 bg-slate-900 p-2.5 text-white select-none"
                            >
                                <!-- Magnetic Stripe -->
                                <div
                                    class="-mx-2.5 h-6 w-[calc(100%+1.25rem)] border-y border-slate-800 bg-slate-950"
                                ></div>

                                <!-- Terms -->
                                <div
                                    class="space-y-0.5 text-[7px] leading-tight text-slate-400"
                                >
                                    <p class="font-bold text-slate-200">
                                        KETENTUAN PENGGUNAAN KARTU:
                                    </p>
                                    <ol
                                        class="list-inside list-decimal space-y-0.5"
                                    >
                                        <li>
                                            Kartu resmi siswa terhubung sistem
                                            absensi otomatis.
                                        </li>
                                        <li>
                                            Wajib dibawa & di-tap setiap hari di
                                            gerbang/kelas.
                                        </li>
                                        <li>
                                            Jika hilang, laporkan ke Petugas
                                            Tata Usaha/Admin.
                                        </li>
                                    </ol>
                                </div>

                                <!-- Footer Barcode -->
                                <div
                                    class="flex items-center justify-between border-t border-slate-800 pt-1"
                                >
                                    <div
                                        class="font-mono text-[8px] text-slate-400"
                                    >
                                        ID: {{ st.id }} • {{ st.nisn }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <QrCode class="h-4 w-4 text-white" />
                                    </div>
                                </div>
                            </div>
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
        margin: 5mm;
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

    .batch-printable-wrapper {
        space-y: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .a4-page-sheet {
        page-break-after: always !important;
        break-after: page !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 auto !important;
        background: transparent !important;
    }

    .cards-grid {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 3mm !important;
    }

    .card-pair-row {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 5mm !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .card-item {
        width: 85mm !important;
        height: 55mm !important;
        min-width: 85mm !important;
        min-height: 55mm !important;
        max-width: 85mm !important;
        max-height: 55mm !important;
        box-shadow: none !important;
        border-radius: 8px !important;
    }
}
</style>
