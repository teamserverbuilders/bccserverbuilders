<template>
    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="h-[3px] bg-[#b8860b]"></div>
                <div class="px-8 py-8">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                            <i class="pi pi-lock text-[#1a3557] text-xl"></i>
                        </div>
                        <p class="text-xs font-bold tracking-[0.18em] uppercase text-[#b8860b] mb-2">TDRMS</p>
                        <h1 class="text-xl font-bold text-[#1a3557]">{{ title }}</h1>
                        <p class="text-sm text-slate-500 mt-1">{{ subtitle }}</p>
                    </div>

                    <form v-if="step === 'email'" class="space-y-4" @submit.prevent="sendCode">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                            <InputText v-model="email" type="email" class="w-full" placeholder="your@email.gov.ph" autocomplete="email" :class="{ 'p-invalid': error }" />
                            <small v-if="error" class="text-red-500 text-xs mt-1 block">{{ error }}</small>
                        </div>
                        <Button type="submit" label="Send code" icon="pi pi-envelope" class="w-full" :loading="loading" />
                    </form>

                    <form v-else-if="step === 'otp'" class="space-y-4" @submit.prevent="verifyCode">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Verification code</label>
                            <InputText
                                v-model="otp"
                                inputmode="numeric"
                                maxlength="6"
                                class="w-full tracking-[0.4em] text-center"
                                placeholder="000000"
                                autocomplete="one-time-code"
                                :class="{ 'p-invalid': error }"
                                @update:modelValue="otp = String($event || '').replace(/\D/g, '').slice(0, 6)"
                            />
                            <small v-if="error" class="text-red-500 text-xs mt-1 block">{{ error }}</small>
                        </div>
                        <Button type="submit" label="Verify code" icon="pi pi-check" class="w-full" :loading="loading" :disabled="otp.length !== 6" />
                        <button type="button" class="w-full text-sm text-[#1a3557] font-medium" :disabled="loading" @click="sendCode">
                            Resend code
                        </button>
                    </form>

                    <form v-else class="space-y-4" @submit.prevent="savePassword">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">New password</label>
                            <Password
                                v-model="password"
                                class="w-full reset-password"
                                inputClass="w-full"
                                :feedback="false"
                                toggleMask
                                autocomplete="new-password"
                                placeholder="At least 8 characters"
                                :class="{ 'p-invalid': error }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm password</label>
                            <Password
                                v-model="passwordConfirmation"
                                class="w-full reset-password"
                                inputClass="w-full"
                                :feedback="false"
                                toggleMask
                                autocomplete="new-password"
                                placeholder="Re-enter your password"
                            />
                        </div>
                        <small v-if="error" class="text-red-500 text-xs block">{{ error }}</small>
                        <Button type="submit" label="Save password" icon="pi pi-lock" class="w-full" :loading="loading" />
                    </form>

                    <RouterLink to="/login" class="mt-6 flex items-center justify-center gap-2 text-sm text-[#1a3557] font-medium">
                        <i class="pi pi-arrow-left text-xs"></i> Back to Login
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import axios from 'axios';

const router = useRouter();
const step = ref('email');
const email = ref('');
const otp = ref('');
const resetToken = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref('');

const title = computed(() => {
    if (step.value === 'otp') return 'Enter the code';
    if (step.value === 'password') return 'Set a new password';
    return 'Forgot password';
});

const subtitle = computed(() => {
    if (step.value === 'otp') return `We sent a 6-digit code to ${email.value}`;
    if (step.value === 'password') return 'Choose a new password for your account';
    return 'Enter the email address on your account';
});

function messageFrom(err, fallback) {
    return err.response?.data?.errors?.email?.[0]
        || err.response?.data?.errors?.otp?.[0]
        || err.response?.data?.errors?.password?.[0]
        || err.response?.data?.message
        || fallback;
}

async function sendCode() {
    loading.value = true;
    error.value = '';
    try {
        await axios.post('/auth/forgot-password', { email: email.value });
        step.value = 'otp';
        otp.value = '';
    } catch (err) {
        error.value = messageFrom(err, 'Could not send the code.');
    } finally {
        loading.value = false;
    }
}

async function verifyCode() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.post('/auth/verify-otp', { email: email.value, otp: otp.value });
        resetToken.value = data.reset_token;
        step.value = 'password';
        password.value = '';
        passwordConfirmation.value = '';
    } catch (err) {
        error.value = messageFrom(err, 'The code could not be verified.');
    } finally {
        loading.value = false;
    }
}

async function savePassword() {
    if (password.value !== passwordConfirmation.value) {
        error.value = 'The passwords do not match.';
        return;
    }
    loading.value = true;
    error.value = '';
    try {
        await axios.post('/auth/reset-password', {
            email: email.value,
            reset_token: resetToken.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        router.push({ name: 'login' });
    } catch (err) {
        error.value = messageFrom(err, 'The password could not be updated.');
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.reset-password :deep(.p-password-input) {
    width: 100%;
}
</style>
