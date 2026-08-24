<template>
    <div class="space-y-0.5">
        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ label }}</p>
        <p :class="['text-sm text-gray-800 dark:text-white font-medium break-words', capitalize ? 'capitalize' : '']">
            {{ displayValue }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: null },
    capitalize: { type: Boolean, default: false },
});

const displayValue = computed(() => {
    if (props.value === null || props.value === undefined || props.value === '') return '—';
    return props.value;
});
</script>
