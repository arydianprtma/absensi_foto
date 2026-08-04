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
            Menu Utama
        </SidebarGroupLabel>
        <SidebarMenu class="space-y-1">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-xs transition-all duration-150"
                    :class="[
                        isCurrentUrl(item.href)
                            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white font-semibold shadow-sm'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-slate-100'
                    ]"
                >
                    <Link :href="item.href">
                        <component
                            :is="item.icon"
                            class="w-4 h-4 shrink-0 transition-transform duration-150 group-hover:scale-105"
                            :class="[
                                isCurrentUrl(item.href) ? 'text-white' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300'
                            ]"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
