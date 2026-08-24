<template>
    <div :class="{ dark: isDark }">
        <RouterView />
        <Toaster rich-colors position="top-right" :expand="true" :visible-toasts="5" close-button />
        <ConfirmDialog />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterView } from 'vue-router';
import { Toaster } from 'vue-sonner';
import ConfirmDialog from 'primevue/confirmdialog';
import { useSettingsStore } from '@/stores/settings';

const settingsStore = useSettingsStore();
const isDark = computed(() => settingsStore.darkMode);
</script>
