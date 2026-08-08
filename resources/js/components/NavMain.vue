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

export interface NavGroup {
    title: string;
    items: NavItem[];
}

defineProps<{
    groups: NavGroup[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="space-y-4">
        <SidebarGroup
            v-for="group in groups"
            :key="group.title"
            class="space-y-1.5 px-3 py-1"
        >
            <SidebarGroupLabel
                class="mb-2 px-3 text-[10px] font-extrabold tracking-widest text-slate-400 uppercase dark:text-slate-500"
            >
                {{ group.title }}
            </SidebarGroupLabel>
            <SidebarMenu class="space-y-1">
                <SidebarMenuItem v-for="item in group.items" :key="item.title">
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(item.href)"
                        :tooltip="item.title"
                        class="group relative flex cursor-pointer items-center gap-3 rounded-2xl px-3.5 py-2.5 text-xs font-semibold transition-all duration-150"
                        :class="[
                            isCurrentUrl(item.href)
                                ? '!bg-indigo-600 font-bold !text-white shadow-md shadow-indigo-600/30 data-[active=true]:!bg-indigo-600 data-[active=true]:!text-white'
                                : 'text-slate-700 hover:!bg-indigo-50 hover:!text-indigo-700 dark:text-slate-300 dark:hover:!bg-indigo-950/60 dark:hover:!text-indigo-300',
                        ]"
                    >
                        <Link :href="item.href">
                            <component
                                :is="item.icon"
                                class="h-4 w-4 shrink-0 transition-all duration-150 group-hover:scale-110"
                                :class="[
                                    isCurrentUrl(item.href)
                                        ? '!text-white'
                                        : '!text-indigo-600 group-hover:!text-indigo-700 dark:!text-indigo-400 dark:group-hover:!text-indigo-300',
                                ]"
                            />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </div>
</template>
