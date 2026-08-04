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
    <SidebarGroup class="space-y-1 px-3 py-2">
        <SidebarGroupLabel
            class="mb-2 px-3 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase dark:text-slate-500"
        >
            Menu Utama
        </SidebarGroupLabel>
        <SidebarMenu class="space-y-1">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-medium transition-all duration-150"
                    :class="[
                        isCurrentUrl(item.href)
                            ? 'bg-slate-900 font-semibold text-white shadow-sm dark:bg-slate-800 dark:text-white'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-100',
                    ]"
                >
                    <Link :href="item.href">
                        <component
                            :is="item.icon"
                            class="h-4 w-4 shrink-0 transition-transform duration-150 group-hover:scale-105"
                            :class="[
                                isCurrentUrl(item.href)
                                    ? 'text-white'
                                    : 'text-slate-400 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300',
                            ]"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
