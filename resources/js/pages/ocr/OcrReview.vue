<template>
    <div class="space-y-5">
        <div class="flex items-center gap-4">
            <RouterLink to="/ocr"><Button icon="pi pi-arrow-left" rounded outlined size="small" /></RouterLink>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">OCR Review</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Review and correct extracted text fields</p>
            </div>
        </div>

        <div v-if="ocr" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Original Document Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="pi pi-file text-blue-500"></i> Original Document
                </h3>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 min-h-80 flex items-center justify-center">
                    <img v-if="ocr.source_type === 'image'" :src="`/storage/${ocr.source_file}`" class="max-w-full rounded" alt="Document" />
                    <div v-else class="text-center">
                        <i class="pi pi-file-pdf text-6xl text-red-500 mb-3 block"></i>
                        <p class="text-sm text-gray-500">PDF Document</p>
                        <a :href="`/storage/${ocr.source_file}`" target="_blank">
                            <Button label="Open PDF" icon="pi pi-external-link" size="small" class="mt-2" />
                        </a>
                    </div>
                </div>

                <!-- Raw Text -->
                <div class="mt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Raw OCR Text</h4>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 h-48 overflow-y-auto">
                        <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-mono">{{ ocr.raw_text || 'No text available.' }}</pre>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-600 rounded-full">
                        <div :class="['h-full rounded-full', confidenceColor]" :style="{ width: ocr.confidence_score + '%' }"></div>
                    </div>
                    <span class="text-sm font-semibold">{{ ocr.confidence_score }}% confidence</span>
                </div>
            </div>

            <!-- Correction Form -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="pi pi-pencil text-orange-500"></i> Field Correction
                </h3>

                <div class="space-y-3">
                    <div v-for="field in editableFields" :key="field.key" class="p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ field.label }}</label>
                            <span v-if="ocr.extracted_fields?.[field.key]" class="text-xs text-gray-400 dark:text-gray-500">
                                OCR: {{ ocr.extracted_fields[field.key] }}
                            </span>
                        </div>
                        <InputText v-model="corrections[field.key]" class="w-full" size="small" :placeholder="`Enter ${field.label.toLowerCase()}...`" />
                    </div>
                </div>

                <div class="mt-6">
                    <Button label="Save Corrections" icon="pi pi-save" class="w-full" :loading="saving" @click="saveCorrections" />
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2 text-center">
                        Corrected fields are available to the "Existing TD in the OCR Scanner" dropdown on the Tax Declaration form.
                    </p>
                </div>

                <div v-if="ocr.status === 'reviewed'" class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm text-green-700 dark:text-green-400 flex items-center gap-2">
                        <i class="pi pi-check-circle"></i> Reviewed by {{ ocr.reviewed_by?.name }}
                    </p>
                </div>
            </div>
        </div>
        <div v-else class="flex items-center justify-center h-64"><ProgressSpinner /></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import ProgressSpinner from 'primevue/progressspinner';
import axios from 'axios';

const route = useRoute();
const toast = useToast();
const ocr = ref(null);
const saving = ref(false);
const corrections = ref({});

const editableFields = [
    { key: 'td_number', label: 'TD Number' }, { key: 'arp_number', label: 'ARP Number' },
    { key: 'lot_number', label: 'Lot Number' }, { key: 'owner_name', label: 'Owner Name' },
    { key: 'land_area', label: 'Land Area (sq.m.)' }, { key: 'market_value', label: 'Market Value' },
    { key: 'assessed_value', label: 'Assessed Value' },
];

const confidenceColor = computed(() => {
    const s = ocr.value?.confidence_score;
    return s >= 80 ? 'bg-green-500' : s >= 60 ? 'bg-yellow-500' : 'bg-red-500';
});

async function saveCorrections() {
    saving.value = true;
    try {
        await axios.put(`/ocr/${route.params.id}/correct`, {
            corrected_fields: corrections.value,
        });
        toast.add({ severity: 'success', summary: 'Saved', detail: 'Corrections saved.' });
        await loadOcr();
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Save failed',
            detail: err?.response?.data?.message || 'Could not save corrections.',
        });
    } finally { saving.value = false; }
}

async function loadOcr() {
    try {
        const res = await axios.get(`/ocr/${route.params.id}`);
        ocr.value = res.data;
        if (ocr.value) {
            const fields = ocr.value.corrected_fields || ocr.value.extracted_fields || {};
            corrections.value = { ...fields };
        }
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Load failed',
            detail: err?.response?.status === 404 ? 'OCR record not found.' : (err?.response?.data?.message || 'Could not load OCR record.'),
        });
    }
}

onMounted(loadOcr);
</script>

