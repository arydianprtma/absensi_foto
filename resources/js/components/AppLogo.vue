<script setup lang="ts">
import { ScanFace } from '@lucide/vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-400 text-white shadow-lg shadow-indigo-500/25"
    >
        <ScanFace class="size-5 text-white" />
    </div>
    <div class="ml-2.5 grid flex-1 text-left">
        <span
            class="truncate text-sm leading-tight font-extrabold tracking-wide text-slate-900 dark:text-slate-100"
        >
            Absensi AI
        </span>
        <span
            class="mt-0.5 truncate text-[10px] leading-none font-semibold text-emerald-600 dark:text-emerald-400"
        >
            InsightFace System
        </span>
    </div>
</template>
