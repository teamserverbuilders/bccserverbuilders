import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useSettingsStore = defineStore('settings', () => {
    const darkMode = ref(localStorage.getItem('tdms_dark') === 'true');
    const sidebarCollapsed = ref(false);

    function toggleDarkMode() {
        darkMode.value = !darkMode.value;
        localStorage.setItem('tdms_dark', darkMode.value);
    }

    function toggleSidebar() {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    }

    return { darkMode, sidebarCollapsed, toggleDarkMode, toggleSidebar };
});
