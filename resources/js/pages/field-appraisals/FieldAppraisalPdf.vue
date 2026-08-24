<template>
    <div class="flex flex-col gap-4 h-full" style="min-height: calc(100vh - 140px);">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="goBack" v-tooltip.top="'Back'" />
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Field Appraisal (FAAS) PDF preview</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    label="Download"
                    icon="pi pi-download"
                    size="small"
                    outlined
                    :disabled="!pdfUrl"
                    @click="downloadPdf"
                />
                <Button
                    label="Refresh"
                    icon="pi pi-refresh"
                    size="small"
                    severity="secondary"
                    :loading="loading"
                    @click="loadPdf"
                />
            </div>
        </div>

        <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden relative"
             style="min-height: calc(100vh - 220px);">
            <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-white/80 dark:bg-gray-900/80 z-10">
                <i class="pi pi-spin pi-spinner text-2xl text-violet-500"></i>
                <p class="text-sm text-gray-500">Generating PDF…</p>
            </div>
            <div v-else-if="error" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
                <i class="pi pi-exclamation-triangle text-3xl text-amber-500"></i>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ error }}</p>
                <Button label="Try again" icon="pi pi-refresh" size="small" @click="loadPdf" />
            </div>
            <iframe
                v-else-if="pdfUrl"
                :src="pdfUrl"
                title="Field Appraisal PDF"
                class="w-full h-full border-0"
                style="min-height: calc(100vh - 220px);"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const loading = ref(false);
const error = ref('');
const pdfUrl = ref(null);
const appraisalNo = ref('');

const title = computed(() => appraisalNo.value ? `FAAS ${appraisalNo.value}` : 'Field Appraisal PDF');
const filename = computed(() => `FAAS-${appraisalNo.value || route.params.id}.pdf`);

function revokeUrl() {
    if (pdfUrl.value) {
        URL.revokeObjectURL(pdfUrl.value);
        pdfUrl.value = null;
    }
}

function goBack() {
    if (window.history.length > 1) router.back();
    else router.push('/field-appraisals');
}

function downloadPdf() {
    if (!pdfUrl.value) return;
    const a = document.createElement('a');
    a.href = pdfUrl.value;
    a.download = filename.value;
    document.body.appendChild(a);
    a.click();
    a.remove();
}

async function loadPdf() {
    loading.value = true;
    error.value = '';
    revokeUrl();
    try {
        const [metaRes, pdfRes] = await Promise.all([
            axios.get(`field-appraisals/${route.params.id}`).catch(() => null),
            axios.get(`field-appraisals/${route.params.id}/pdf`, { responseType: 'blob' }),
        ]);
        if (metaRes?.data?.appraisal_no) appraisalNo.value = metaRes.data.appraisal_no;
        const blob = new Blob([pdfRes.data], { type: 'application/pdf' });
        pdfUrl.value = URL.createObjectURL(blob);
    } catch (err) {
        let message = 'Could not generate the PDF for this Field Appraisal.';
        const data = err?.response?.data;
        if (data instanceof Blob) {
            try { message = JSON.parse(await data.text())?.message || message; } catch { /* not JSON */ }
        } else if (data?.message) {
            message = data.message;
        }
        error.value = message;
        toast.error('Export failed', message);
    } finally {
        loading.value = false;
    }
}

watch(() => route.params.id, () => { loadPdf(); });

onMounted(() => { loadPdf(); });
onBeforeUnmount(() => { revokeUrl(); });
</script>
