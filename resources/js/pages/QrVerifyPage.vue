<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-center">
                <i class="pi pi-verified text-white text-4xl mb-2 block"></i>
                <h1 class="text-xl font-bold text-white">Property Verification</h1>
                <p class="text-blue-100 text-sm mt-1">Tax Declaration Management System</p>
            </div>

            <div class="p-6">
                <div v-if="loading" class="text-center py-8"><ProgressSpinner /></div>

                <div v-else-if="td" class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-check-circle text-green-500 text-xl"></i>
                            <span class="font-semibold text-green-700 dark:text-green-400">Verified Record</span>
                        </div>
                        <Tag :value="td.status?.replace(/_/g,' ')" severity="success" class="text-xs capitalize" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div v-for="field in verifyFields" :key="field.label" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ field.label }}</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ field.value }}</p>
                        </div>
                    </div>

                    <div v-if="td.gis_location" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <p class="text-xs text-blue-600 mb-1">Location</p>
                        <p class="text-sm font-mono text-blue-700 dark:text-blue-300">{{ td.gis_location.latitude }}, {{ td.gis_location.longitude }}</p>
                        <a :href="`https://www.google.com/maps?q=${td.gis_location.latitude},${td.gis_location.longitude}`" target="_blank" class="text-xs text-blue-600 hover:underline mt-1 block">
                            <i class="pi pi-map-marker mr-1"></i> Open in Google Maps
                        </a>
                    </div>

                    <p class="text-xs text-gray-400 text-center">
                        Verified by TDMS · {{ new Date().toLocaleDateString('en-PH', { dateStyle: 'full' }) }}
                    </p>
                </div>

                <div v-else class="text-center py-8">
                    <i class="pi pi-times-circle text-red-500 text-5xl mb-3 block"></i>
                    <h3 class="font-semibold text-gray-800 dark:text-white">Record Not Found</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">TD# {{ $route.params.tdNumber }} does not exist.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import axios from 'axios';

const route = useRoute();
const td = ref(null);
const loading = ref(true);

const verifyFields = computed(() => td.value ? [
    { label: 'TD Number', value: td.value.td_number },
    { label: 'ARP Number', value: td.value.arp_number || 'N/A' },
    { label: 'Owner', value: td.value.owner?.owner_name },
    { label: 'Classification', value: td.value.classification?.name },
    { label: 'Barangay', value: td.value.barangay?.name },
    { label: 'Assessed Value', value: td.value.assessed_value ? `₱${Number(td.value.assessed_value).toLocaleString()}` : 'N/A' },
] : []);

onMounted(async () => {
    try {
        const res = await axios.get(`/verify/${route.params.tdNumber}`);
        td.value = res.data;
    } catch { td.value = null; } finally { loading.value = false; }
});
</script>
