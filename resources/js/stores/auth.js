import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('tdms_token'));
    const user = ref(null);

    const isAuthenticated = computed(() => !!token.value);
    const hasRole = (role) => user.value?.roles?.some(r => r.name === role);
    const hasPermission = (perm) => {
        // Prefer direct (per-user) permissions when admin has customized them
        const direct = user.value?.direct_permission_names || [];
        if (direct.length > 0) return direct.includes(perm);
        if (user.value?.permission_names?.includes(perm)) return true;
        return user.value?.roles?.some(r => r.permissions?.some(p => p.name === perm));
    };

    async function login(credentials) {
        const res = await axios.post('/auth/login', credentials);
        token.value = res.data.token;
        user.value = res.data.user;
        localStorage.setItem('tdms_token', token.value);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
        return res.data;
    }

    async function logout() {
        try {
            await axios.post('/auth/logout');
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem('tdms_token');
            delete axios.defaults.headers.common['Authorization'];
        }
    }

    async function fetchUser() {
        const res = await axios.get('/auth/me');
        user.value = res.data;
        return res.data;
    }

    async function changePassword(data) {
        return axios.post('/auth/change-password', data);
    }

    async function updateProfile(data) {
        const res = await axios.put('/auth/profile', data);
        user.value = { ...user.value, ...res.data };
        return res.data;
    }

    return { token, user, isAuthenticated, hasRole, hasPermission, login, logout, fetchUser, changePassword, updateProfile };
});
