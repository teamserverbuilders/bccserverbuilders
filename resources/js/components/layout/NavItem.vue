<template>
    <RouterLink :to="item.to" custom v-slot="{ isActive, navigate }">
        <button
            @click="navigate"
            :title="collapsed ? item.label : undefined"
            :class="[
                'w-full flex items-center gap-2.5 px-2 py-2 rounded-md text-[13px] transition-colors duration-150',
                isActive
                    ? 'bg-white/10 text-white font-semibold border-l-2 border-[#d4a017]'
                    : 'text-blue-200/80 hover:bg-white/5 hover:text-white font-normal border-l-2 border-transparent',
                collapsed ? 'justify-center px-0 border-l-0' : ''
            ]"
        >
            <i :class="['pi text-[14px] shrink-0', item.icon, isActive ? 'text-[#d4a017]' : '']"></i>
            <span v-if="!collapsed" class="truncate leading-none">{{ item.label }}</span>
            <span
                v-if="!collapsed && item.badge"
                class="ml-auto inline-flex items-center justify-center w-4 h-4 text-[9px] font-bold bg-red-500 text-white rounded-full leading-none"
            >{{ item.badge }}</span>
        </button>
    </RouterLink>
</template>

<script setup>
import { RouterLink } from 'vue-router';
defineProps({ item: Object, collapsed: Boolean });
</script>
