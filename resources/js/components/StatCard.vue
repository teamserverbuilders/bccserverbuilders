<template>
    <div :class="[
        'bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border transition-all hover:shadow-md cursor-default',
        highlight ? 'border-orange-200 dark:border-orange-800' : 'border-gray-100 dark:border-gray-700'
    ]">
        <div class="flex items-center justify-between mb-3">
            <div :class="['w-9 h-9 rounded-lg flex items-center justify-center text-sm', iconBg]">
                <i :class="['pi text-base', icon, iconColor]"></i>
            </div>
            <span v-if="highlight" class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></span>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ formattedValue }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-tight">{{ title }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    value: [Number, String],
    icon: String,
    color: { type: String, default: 'blue' },
    highlight: Boolean,
});

const colorMap = {
    blue: { bg: 'bg-blue-100 dark:bg-blue-900/30', icon: 'text-blue-600 dark:text-blue-400' },
    green: { bg: 'bg-green-100 dark:bg-green-900/30', icon: 'text-green-600 dark:text-green-400' },
    red: { bg: 'bg-red-100 dark:bg-red-900/30', icon: 'text-red-600 dark:text-red-400' },
    yellow: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', icon: 'text-yellow-600 dark:text-yellow-400' },
    orange: { bg: 'bg-orange-100 dark:bg-orange-900/30', icon: 'text-orange-600 dark:text-orange-400' },
    purple: { bg: 'bg-purple-100 dark:bg-purple-900/30', icon: 'text-purple-600 dark:text-purple-400' },
    sky: { bg: 'bg-sky-100 dark:bg-sky-900/30', icon: 'text-sky-600 dark:text-sky-400' },
    amber: { bg: 'bg-amber-100 dark:bg-amber-900/30', icon: 'text-amber-600 dark:text-amber-400' },
    gray: { bg: 'bg-gray-100 dark:bg-gray-700', icon: 'text-gray-600 dark:text-gray-400' },
};

const colors = computed(() => colorMap[props.color] || colorMap.blue);
const iconBg = computed(() => colors.value.bg);
const iconColor = computed(() => colors.value.icon);
const formattedValue = computed(() => (props.value ?? 0).toLocaleString());
</script>
