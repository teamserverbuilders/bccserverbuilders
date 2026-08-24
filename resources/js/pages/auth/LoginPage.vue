<template>
    <div class="min-h-screen bg-white flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
        <!-- Subtle background accents -->
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-[#1a3557]/[0.03]"></div>
            <div class="absolute -bottom-32 -left-32 w-[28rem] h-[28rem] rounded-full bg-[#b8860b]/[0.04]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-slate-100"></div>
        </div>

        <div class="w-full max-w-[420px] relative">
            <!-- Login card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-[0_8px_40px_rgba(26,53,87,0.08)] overflow-hidden">
                <!-- Gold accent strip -->
                <div class="h-[3px] bg-gradient-to-r from-[#1a3557] via-[#b8860b] to-[#1a3557]"></div>

                <!-- Brand header -->
                <div class="px-8 pt-8 pb-6 text-center border-b border-slate-100">
                    <div class="w-[72px] h-[72px] mx-auto mb-4 rounded-full ring-2 ring-[#b8860b] ring-offset-2 ring-offset-white overflow-hidden shadow-sm">
                        <img :src="logoSrc" alt="Municipality Seal" class="w-full h-full object-cover" />
                    </div>
                    <h1 class="text-2xl font-bold text-[#1a3557] tracking-tight">TDMS</h1>
                    <p class="text-sm text-slate-500 mt-1">Tax Declaration Management System</p>
                    <p class="text-xs text-slate-400 mt-0.5">Municipal / City Assessor's Office</p>
                </div>

                <!-- Form -->
                <div class="px-8 py-7">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-[#1a3557]">Welcome back</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Sign in to access your account</p>
                    </div>

                    <form @submit.prevent="handleLogin" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                            <div class="relative">
                                <i class="pi pi-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                                <InputText
                                    v-model="form.email"
                                    type="email"
                                    placeholder="your@email.gov.ph"
                                    class="w-full !pl-10"
                                    :class="{ 'p-invalid': errors.email }"
                                    autocomplete="email"
                                />
                            </div>
                            <small v-if="errors.email" class="text-red-500 text-xs mt-1 block">{{ errors.email[0] }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                            <div class="relative">
                                <i class="pi pi-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none z-10"></i>
                                <Password
                                    v-model="form.password"
                                    placeholder="Enter your password"
                                    class="w-full login-password"
                                    :class="{ 'p-invalid': errors.password }"
                                    :feedback="false"
                                    :toggleMask="true"
                                    inputClass="w-full !pl-10"
                                    autocomplete="current-password"
                                />
                            </div>
                            <small v-if="errors.password" class="text-red-500 text-xs mt-1 block">{{ errors.password[0] }}</small>
                        </div>

                        <div class="flex items-center justify-between pt-0.5">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <Checkbox v-model="form.remember" :binary="true" />
                                <span class="text-sm text-slate-600">Remember me</span>
                            </label>
                            <RouterLink to="/forgot-password" class="text-sm text-[#1a3557] hover:text-[#1e4880] font-medium transition-colors">
                                Forgot password?
                            </RouterLink>
                        </div>

                        <Message v-if="generalError" severity="error" :closable="false" class="w-full">
                            {{ generalError }}
                        </Message>

                        <Button
                            type="submit"
                            label="Sign In"
                            icon="pi pi-sign-in"
                            class="w-full !mt-1"
                            :loading="loading"
                            size="large"
                        />
                    </form>
                </div>

                <!-- Card footer -->
                <div class="px-8 py-4 bg-slate-50 border-t border-slate-100">
                    <p class="text-center text-xs text-slate-400">
                        Secure government portal &mdash; authorized personnel only
                    </p>
                </div>
            </div>

            <!-- Page footer -->
            <p class="text-center text-slate-400 text-xs mt-6">
                &copy; {{ new Date().getFullYear() }} Municipal Assessor's Office. All rights reserved.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const logoSrc = '/images/sidelogo.png';

const loading = ref(false);
const errors = ref({});
const generalError = ref('');

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

async function handleLogin() {
    loading.value = true;
    errors.value = {};
    generalError.value = '';

    try {
        await authStore.login(form);
        const redirect = route.query.redirect || '/';
        router.push(redirect);
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else if (err.response?.data?.message) {
            generalError.value = err.response.data.message;
        } else {
            generalError.value = 'An error occurred. Please try again.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.login-password :deep(.p-password-input) {
    padding-left: 2.5rem;
}
</style>
