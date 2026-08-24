<template>
    <div class="login-screen h-dvh w-full overflow-hidden flex flex-col md:flex-row">
        <!-- Left panel: brand & logo -->
        <div class="relative w-full md:w-1/2 h-[34%] md:h-full shrink-0 flex items-center justify-center overflow-hidden bg-[#1a3557]">
            <div class="pointer-events-none absolute inset-0">
                <div class="bg-octagon absolute -top-24 -left-24 w-80 h-80 bg-white/[0.04]"></div>
                <div class="bg-octagon absolute -bottom-28 -right-20 w-96 h-96 bg-[#b8860b]/[0.12]"></div>
                <div class="bg-octagon absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[420px] h-[420px] border border-white/10"></div>
            </div>
            <div class="absolute top-0 left-0 right-0 h-[3px] md:h-auto md:top-0 md:bottom-0 md:left-auto md:right-0 md:w-[3px] bg-gradient-to-r md:bg-gradient-to-b from-[#1a3557] via-[#b8860b] to-[#1a3557]"></div>

            <div class="relative z-10 px-6 py-6 md:py-10 md:px-12 text-center">
                <div class="w-20 h-20 md:w-40 md:h-40 mx-auto mb-3 md:mb-6 rounded-full ring-2 ring-[#b8860b] ring-offset-2 md:ring-offset-4 ring-offset-[#1a3557] overflow-hidden shadow-lg bg-white">
                    <img :src="logoSrc" alt="Municipality Seal" class="w-full h-full object-cover" />
                </div>
                <h1 class="text-2xl md:text-4xl font-bold text-white tracking-tight">TDMS</h1>
                <p class="text-sm md:text-lg text-white/80 mt-1 md:mt-2">Tax Declaration Management System</p>
                <p class="text-xs md:text-sm text-white/55 mt-1">Municipal / City Assessor's Office</p>
                <p class="hidden md:block text-xs text-white/40 mt-10 max-w-sm mx-auto leading-relaxed">
                    Secure government portal — authorized personnel only
                </p>
            </div>
        </div>

        <!-- Right panel: login form -->
        <div class="w-full md:w-1/2 h-[66%] md:h-full flex items-center justify-center bg-white px-6 py-4 sm:p-10 overflow-hidden relative">
            <div class="pointer-events-none absolute inset-0 hidden md:block">
                <div class="bg-octagon absolute -bottom-32 -right-24 w-80 h-80 bg-[#1a3557]/[0.03]"></div>
            </div>

            <div class="w-full max-w-[400px] relative">
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-[#1a3557]">Welcome back</h2>
                    <p class="text-sm text-slate-500 mt-1">Sign in to access your account</p>
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

                <p class="text-center text-slate-400 text-xs mt-6">
                    &copy; {{ new Date().getFullYear() }} Municipal Assessor's Office. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
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

onMounted(() => {
    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');
});

onUnmounted(() => {
    document.documentElement.classList.remove('overflow-hidden');
    document.body.classList.remove('overflow-hidden');
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
.login-screen {
    height: 100dvh;
    max-height: 100dvh;
    width: 100%;
    overflow: hidden;
}

.bg-octagon {
    clip-path: polygon(
        30% 0%,
        70% 0%,
        100% 30%,
        100% 70%,
        70% 100%,
        30% 100%,
        0% 70%,
        0% 30%
    );
}

.login-password :deep(.p-password-input) {
    padding-left: 2.5rem;
}
</style>
