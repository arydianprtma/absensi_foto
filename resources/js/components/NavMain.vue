<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-3 py-2 space-y-1">
        <SidebarGroupLabel class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 px-3 mb-2">
            Menu Utama AI Absensi
        </SidebarGroupLabel>
        <SidebarMenu class="space-y-1.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-xs transition-all duration-200"
                    :class="[
                        isCurrentUrl(item.href)
                            ? 'bg-gradient-to-r from-indigo-600/20 to-emerald-500/10 text-indigo-600 dark:text-emerald-400 font-bold border-l-4 border-indigo-600 dark:border-emerald-400 shadow-sm'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white'
                    ]"
                >
                    <Link :href="item.href">
                        <component
                            :is="item.icon"
                            class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:scale-110"
                            :class="[
                                isCurrentUrl(item.href) ? 'text-indigo-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-200'
                            ]"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
