<script setup lang="ts">
import { ScanFace } from '@lucide/vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-400 text-white shadow-lg shadow-indigo-500/25 shrink-0"
    >
        <ScanFace class="size-5 text-white" />
    </div>
    <div class="ml-2.5 grid flex-1 text-left">
        <span class="truncate leading-tight font-extrabold text-sm text-slate-900 dark:text-slate-100 tracking-wide">
            Absensi AI
        </span>
        <span class="truncate leading-none text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
            InsightFace System
        </span>
    </div>
</template>
