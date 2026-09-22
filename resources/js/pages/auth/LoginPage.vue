<template>
    <div class="login-screen">
        <div class="flag-bar" aria-hidden="true">
            <span class="flag-blue"></span>
            <span class="flag-white"></span>
            <span class="flag-red"></span>
        </div>

        <header class="masthead">
            <img :src="logoSrc" alt="Seal of the Municipality of Baao" class="mast-seal" />
            <div class="mast-identity">
                <p class="mast-republic">Republic of the Philippines</p>
                <p class="mast-line">Province of Camarines Sur</p>
                <p class="mast-muni">Municipality of Baao</p>
            </div>
            <div class="mast-office">
                <p class="mast-office-label">Office of the Municipal Assessor</p>
                <p class="mast-system">Tax Declaration Records Management System</p>
            </div>
        </header>

        <div class="gold-rule" aria-hidden="true"></div>

        <div class="login-body">
            <section class="brand-panel" aria-label="System identity">
                <div class="brand-inner">
                    <p class="brand-place">Province of Camarines Sur</p>
                    <p class="brand-place brand-place-strong">Municipality of Baao</p>
                    <p class="brand-kicker">Office of the Municipal Assessor</p>
                    <h1 class="brand-title">TDRMS</h1>
                    <div class="brand-rule" aria-hidden="true"></div>
                    <p class="brand-name">Tax Declaration Records Management System</p>
                    <p class="brand-statement">
                        The official record of real property tax declarations, field appraisals, and assessments for the Municipality of Baao.
                    </p>
                    <ul class="duty-list">
                        <li>
                            <span>01</span>
                            <div>
                                <strong>Tax declarations</strong>
                                <small>Property records on file</small>
                            </div>
                        </li>
                        <li>
                            <span>02</span>
                            <div>
                                <strong>Field appraisal</strong>
                                <small>Assessment of real property</small>
                            </div>
                        </li>
                        <li>
                            <span>03</span>
                            <div>
                                <strong>Official archive</strong>
                                <small>Permanent office record</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="access-panel">
                <img :src="logoSrc" alt="" class="access-watermark" />
                <div class="access-frame" aria-hidden="true"></div>
                <div class="access-card">
                    <div class="card-banner">
                        <span>Municipality of Baao</span>
                        <strong>Assessor's Office</strong>
                    </div>
                    <div class="card-body">
                    <div class="card-head">
                        <div>
                            <p class="form-code">Form TDRMS-ACC</p>
                            <h2>Authorized sign in</h2>
                        </div>
                        <span class="official-mark">Official use</span>
                    </div>
                    <p class="card-lead">Enter the account issued by the Municipal Assessor's Office.</p>

                    <form @submit.prevent="handleLogin" class="access-form">
                        <div>
                            <label for="login-email">Email address</label>
                            <div class="field">
                                <i class="pi pi-envelope field-icon"></i>
                                <InputText
                                    id="login-email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="name@office.gov.ph"
                                    class="w-full"
                                    :class="{ 'p-invalid': errors.email }"
                                    autocomplete="email"
                                />
                            </div>
                            <small v-if="errors.email">{{ errors.email[0] }}</small>
                        </div>

                        <div>
                            <label for="login-password">Password</label>
                            <div class="field">
                                <i class="pi pi-lock field-icon"></i>
                                <Password
                                    v-model="form.password"
                                    inputId="login-password"
                                    placeholder="Enter your password"
                                    class="w-full login-password"
                                    :class="{ 'p-invalid': errors.password }"
                                    :feedback="false"
                                    :toggleMask="true"
                                    inputClass="w-full"
                                    autocomplete="current-password"
                                />
                            </div>
                            <small v-if="errors.password">{{ errors.password[0] }}</small>
                        </div>

                        <div class="form-row">
                            <label class="remember">
                                <Checkbox v-model="form.remember" :binary="true" inputId="remember" />
                                <span>Keep this session</span>
                            </label>
                            <RouterLink to="/forgot-password">Forgot password</RouterLink>
                        </div>

                        <Message v-if="generalError" severity="error" :closable="false" class="w-full">
                            {{ generalError }}
                        </Message>

                        <Button
                            type="submit"
                            label="Sign in"
                            icon="pi pi-sign-in"
                            class="sign-in"
                            :loading="loading"
                        />
                    </form>

                    <p class="notice">
                        This portal is for authorized personnel only. Use of this system is recorded.
                    </p>
                    </div>
                </div>
            </section>
        </div>

        <footer class="login-foot">
            <span>Republic of the Philippines</span>
            <span class="dot" aria-hidden="true"></span>
            <span>Municipality of Baao</span>
            <span class="dot" aria-hidden="true"></span>
            <span>&copy; {{ year }} Office of the Municipal Assessor</span>
        </footer>
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
const year = new Date().getFullYear();

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
    display: flex;
    flex-direction: column;
    background: #f3efe6;
    color: #1a2332;
}

.flag-bar {
    display: flex;
    height: 6px;
    flex-shrink: 0;
}
.flag-blue { flex: 1; background: #0038a8; }
.flag-white { flex: 1; background: #f7f7f7; }
.flag-red { flex: 1; background: #ce1126; }

.masthead {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 72px;
    padding: 10px 28px;
    background: #fffef9;
    border-bottom: 1px solid #e4dcc8;
}

.mast-seal {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    background: #fff;
    box-shadow: 0 0 0 2px #c5a059, 0 0 0 4px #fffef9, 0 0 0 5px #1a3557;
}

.mast-identity {
    min-width: 0;
    line-height: 1.25;
}

.mast-republic {
    font-family: Georgia, "Times New Roman", Times, serif;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.01em;
    color: #1a3557;
}

.mast-line,
.mast-muni {
    font-size: 12px;
    color: #3d4c63;
}

.mast-muni {
    font-weight: 700;
    color: #1a3557;
}

.mast-office {
    margin-left: auto;
    text-align: right;
    min-width: 0;
}

.mast-office-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #1a3557;
}

.mast-system {
    margin-top: 2px;
    font-size: 12px;
    color: #6b5a32;
}

.gold-rule {
    height: 3px;
    flex-shrink: 0;
    background: linear-gradient(90deg, #8a6a24, #e6c36a 40%, #c5a059 60%, #8a6a24);
}

.login-body {
    flex: 1;
    min-height: 0;
    display: flex;
}

.brand-panel {
    position: relative;
    display: none;
    width: 46%;
    overflow: hidden;
    color: #fff;
    background-color: #0e2340;
    background-image:
        linear-gradient(180deg, rgba(197, 160, 89, 0.09), transparent 28%),
        repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.035) 0 1px, transparent 1px 72px),
        repeating-linear-gradient(0deg, rgba(255, 255, 255, 0.03) 0 1px, transparent 1px 72px),
        linear-gradient(165deg, #1a3d66 0%, #122a4a 42%, #0b1c33 100%);
}

.brand-panel::before {
    content: "TDRMS";
    position: absolute;
    right: -8px;
    bottom: -18px;
    z-index: 0;
    font-family: Georgia, "Times New Roman", Times, serif;
    font-size: 118px;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1;
    color: rgba(255, 255, 255, 0.045);
    pointer-events: none;
}

.brand-panel::after {
    content: "";
    position: absolute;
    inset: 16px;
    border: 1px solid rgba(197, 160, 89, 0.35);
    pointer-events: none;
}

.brand-inner {
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: left;
    padding: 36px 56px 36px 64px;
    border-left: 3px solid #c5a059;
    margin: 28px 28px 28px 0;
}

.brand-place {
    font-size: 13px;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.72);
}

.brand-place-strong {
    font-family: Georgia, "Times New Roman", Times, serif;
    font-size: 20px;
    color: #fff;
    margin-top: 2px;
}

.brand-kicker {
    margin-top: 22px;
    font-size: 11px;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: #e6c36a;
}

.brand-title {
    margin-top: 8px;
    font-family: Georgia, "Times New Roman", Times, serif;
    font-size: 64px;
    line-height: 0.9;
    font-weight: 700;
    letter-spacing: 0.06em;
}

.brand-rule {
    width: 72px;
    height: 2px;
    margin: 16px 0 14px;
    background: #c5a059;
}

.brand-name {
    max-width: 420px;
    font-family: Georgia, "Times New Roman", Times, serif;
    font-size: 22px;
    line-height: 1.3;
    color: rgba(255, 255, 255, 0.94);
}

.brand-statement {
    max-width: 420px;
    margin-top: 14px;
    font-size: 14px;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.72);
}

.duty-list {
    list-style: none;
    margin: 26px 0 0;
    padding: 0;
    width: min(100%, 420px);
    border-top: 1px solid rgba(197, 160, 89, 0.35);
}

.duty-list li {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.duty-list span {
    font-family: Georgia, "Times New Roman", Times, serif;
    font-size: 13px;
    color: #e6c36a;
    width: 24px;
    padding-top: 2px;
}

.duty-list strong {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #fff;
}

.duty-list small {
    display: block;
    margin-top: 1px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.access-panel {
    position: relative;
    flex: 1;
    min-width: 0;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 28px 32px;
    overflow: auto;
    background-color: #cbb892;
    background-image:
        radial-gradient(circle at 50% 46%, rgba(255, 250, 240, 0.94) 0 18%, rgba(255, 248, 235, 0.72) 34%, rgba(203, 184, 146, 0.15) 62%, transparent 74%),
        repeating-linear-gradient(45deg, rgba(26, 53, 87, 0.05) 0 1px, transparent 1px 16px),
        repeating-linear-gradient(-45deg, rgba(138, 106, 36, 0.14) 0 1px, transparent 1px 16px),
        linear-gradient(165deg, #f6edd9 0%, #e6d3ae 46%, #c9b184 100%);
}

.access-watermark {
    position: absolute;
    z-index: 0;
    width: 300px;
    height: 300px;
    right: 6%;
    bottom: 8%;
    left: auto;
    top: auto;
    border-radius: 50%;
    object-fit: cover;
    opacity: 0.2;
    pointer-events: none;
    filter: sepia(0.2);
}

.access-frame {
    position: absolute;
    z-index: 1;
    inset: 14px;
    pointer-events: none;
    border: 1px solid rgba(26, 53, 87, 0.45);
}

.access-frame::after {
    content: "";
    position: absolute;
    inset: 5px;
    border: 1px solid rgba(138, 106, 36, 0.85);
}

.access-card {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 440px;
    background: rgba(255, 254, 249, 0.96);
    border: 1px solid #1a3557;
    box-shadow: 0 16px 40px rgba(18, 37, 64, 0.16);
    padding: 0;
    overflow: hidden;
}

.card-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 22px;
    background: linear-gradient(90deg, #10243f 0%, #1a3557 58%, #24507f 100%);
    color: #f6edd9;
    border-bottom: 3px solid #c5a059;
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.card-banner strong {
    font-weight: 700;
    color: #e6c36a;
}

.card-body {
    padding: 18px 24px 20px;
}

.card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.form-code {
    font-size: 11px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #8a6a24;
    font-weight: 700;
}

.card-head h2 {
    margin-top: 2px;
    font-family: Georgia, "Times New Roman", Times, serif;
    font-size: 28px;
    line-height: 1.1;
    color: #1a3557;
}

.official-mark {
    flex-shrink: 0;
    margin-top: 4px;
    padding: 4px 8px;
    border: 1px solid #ce1126;
    color: #ce1126;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.card-lead {
    margin: 10px 0 18px;
    font-size: 13px;
    line-height: 1.45;
    color: #4b5568;
}

.access-form {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.access-form label {
    display: block;
    margin-bottom: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #1a3557;
}

.field {
    position: relative;
}

.field-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
    font-size: 13px;
    color: #8a7a58;
    pointer-events: none;
}

.access-form :deep(.p-inputtext),
.login-password :deep(.p-password-input) {
    width: 100%;
    border-radius: 2px;
    border-color: #cfc4ae;
    background: #fff;
    padding-left: 2.4rem;
    color: #1a2332;
}

.access-form :deep(.p-inputtext:enabled:focus),
.login-password :deep(.p-password-input:enabled:focus) {
    border-color: #1a3557;
    box-shadow: 0 0 0 1px #1a3557;
}

.login-password :deep(.p-password) {
    width: 100%;
}

.access-form small {
    display: block;
    margin-top: 4px;
    color: #b91c1c;
    font-size: 12px;
}

.form-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.remember {
    display: flex !important;
    align-items: center;
    gap: 8px;
    margin: 0 !important;
    letter-spacing: 0 !important;
    text-transform: none !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #3d4c63 !important;
    cursor: pointer;
}

.form-row a {
    font-size: 13px;
    font-weight: 700;
    color: #1a3557;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.form-row a:hover {
    color: #8a6a24;
}

.sign-in {
    width: 100%;
}

.access-form :deep(.sign-in.p-button) {
    border-radius: 2px;
    background: #1a3557 !important;
    border: 1px solid #c5a059 !important;
    color: #fffef9 !important;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 700;
    padding: 0.8rem 1rem;
}

.access-form :deep(.sign-in.p-button:not(:disabled):hover) {
    background: #122540 !important;
    border-color: #e6c36a !important;
}

.notice {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #eadfca;
    font-size: 12px;
    line-height: 1.45;
    color: #6b6254;
}

.login-foot {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 36px;
    padding: 8px 16px;
    background: #0e2340;
    color: rgba(255, 255, 255, 0.78);
    font-size: 11px;
    letter-spacing: 0.04em;
}

.dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #c5a059;
}

@media (min-width: 900px) {
    .brand-panel {
        display: flex;
    }
}

@media (max-width: 899px) {
    .masthead {
        padding: 8px 14px;
        min-height: 64px;
    }

    .mast-office {
        display: none;
    }

    .mast-seal {
        width: 44px;
        height: 44px;
    }

    .mast-republic {
        font-size: 13px;
    }

    .access-panel {
        padding: 16px 14px;
    }

    .access-card {
        box-shadow: 0 10px 24px rgba(18, 37, 64, 0.14);
    }

    .card-body {
        padding: 16px 16px 14px;
    }

    .access-frame {
        inset: 8px;
    }

    .access-watermark {
        width: 160px;
        height: 160px;
        opacity: 0.12;
        right: -24px;
        bottom: -24px;
    }

    .card-head h2 {
        font-size: 24px;
    }
}
</style>
