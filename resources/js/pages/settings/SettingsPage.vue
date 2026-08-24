<template>
    <div class="space-y-5">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">System Settings</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage reference data used across the system</p>
        </div>

        <TabView>
            <!-- Barangays -->
            <TabPanel header="Barangays">
                <div class="space-y-4 pt-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Manage Barangays</h3>
                        <div class="flex gap-2">
                            <Button label="Clear All" icon="pi pi-trash" severity="danger" size="small" outlined @click="confirmClearBarangays" :loading="clearingBrgys" />
                        </div>
                    </div>

                    <!-- PSGC Cascading Dropdowns to Add Barangay -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-3">
                            <i class="pi pi-search mr-1"></i> Add Barangay from PSGC
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-1 block">Region</label>
                                <Select v-model="psgcRegion" :options="psgcRegions" optionLabel="name" optionValue="psgc_id"
                                    placeholder="Select Region" class="w-full" :loading="loadingRegions" @change="onRegionChange" showClear filter />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-1 block">Province</label>
                                <Select v-model="psgcProvince" :options="psgcProvinces" optionLabel="name" optionValue="psgc_id"
                                    placeholder="Select Province" class="w-full" :loading="loadingProvinces" @change="onProvinceChange" :disabled="!psgcRegion" showClear filter />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-1 block">Municipality / City</label>
                                <Select v-model="psgcCity" :options="psgcCities" optionLabel="name" optionValue="psgc_id"
                                    placeholder="Select City/Municipality" class="w-full" :loading="loadingCities" @change="onCityChange" :disabled="!psgcProvince" showClear filter />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-1 block">Barangay <span class="text-blue-500 font-normal">(optional)</span></label>
                                <Select v-model="psgcBarangay" :options="psgcBarangayList" optionLabel="name" optionValue="psgc_id"
                                    placeholder="Select one or use Import All →" class="w-full" :loading="loadingBrgys" :disabled="!psgcCity" showClear filter />
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <Button label="Add Selected Barangay" icon="pi pi-plus" size="small" :disabled="!psgcBarangay" :loading="addingBrgy" @click="addPsgcBarangay" />
                            <Button
                                :label="psgcBarangayList.length
                                    ? `Import All ${psgcBarangayList.length} Barangays`
                                    : 'Import All Barangays'"
                                icon="pi pi-download"
                                size="small"
                                severity="success"
                                :disabled="!psgcCity || !psgcBarangayList.length"
                                :loading="importingAll"
                                @click="importAllPsgcBarangays"
                            />
                            <span class="text-xs text-blue-600 dark:text-blue-300">
                                Data from PSGC (Philippine Statistics Authority). Coordinates auto-fetched from OpenStreetMap for single adds.
                            </span>
                        </div>
                    </div>

                    <!-- Saved Barangays Table -->
                    <DataTable :value="barangays" class="p-datatable-sm" striped-rows :rows="10" paginator>
                        <Column field="name" header="Barangay Name" />
                        <Column field="code" header="PSGC Code" />
                        <Column header="Municipality">
                            <template #body="{ data }">{{ data.municipality?.name || '—' }}</template>
                        </Column>
                        <Column field="latitude" header="Latitude">
                            <template #body="{ data }">{{ data.latitude ? Number(data.latitude).toFixed(5) : '—' }}</template>
                        </Column>
                        <Column field="longitude" header="Longitude">
                            <template #body="{ data }">{{ data.longitude ? Number(data.longitude).toFixed(5) : '—' }}</template>
                        </Column>
                        <Column header="">
                            <template #body="{ data }">
                                <Button icon="pi pi-trash" size="small" text rounded severity="danger" @click="deleteBrgy(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </TabPanel>

            <!-- Classifications -->
            <TabPanel header="Classifications">
                <div class="space-y-4 pt-4">
                    <div class="flex justify-between">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Property Classifications</h3>
                        <Button label="Add" icon="pi pi-plus" size="small" @click="showClsDialog = true" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="c in classifications" :key="c.id" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-100 dark:border-gray-600">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-4 h-4 rounded-full" :style="{ background: c.color }"></div>
                                <h4 class="font-semibold text-gray-800 dark:text-white">{{ c.name }}</h4>
                                <span class="ml-auto text-xs text-gray-500">{{ c.assessment_rate }}%</span>
                            </div>
                            <p class="text-xs text-gray-500">Code: {{ c.code }}</p>
                        </div>
                    </div>
                </div>
            </TabPanel>
        </TabView>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from '@/composables/useToast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Select from 'primevue/select';
import axios from 'axios';

const toast = useToast();
const confirm = useConfirm();
const barangays = ref([]);
const classifications = ref([]);
const showClsDialog = ref(false);

// PSGC Cascading Dropdowns
const psgcRegion = ref(null);
const psgcProvince = ref(null);
const psgcCity = ref(null);
const psgcBarangay = ref(null);
const psgcRegions = ref([]);
const psgcProvinces = ref([]);
const psgcCities = ref([]);
const psgcBarangayList = ref([]);
const loadingRegions = ref(false);
const loadingProvinces = ref(false);
const loadingCities = ref(false);
const loadingBrgys = ref(false);
const addingBrgy = ref(false);
const importingAll = ref(false);
const clearingBrgys = ref(false);

// ── PSGC Cascading Functions ─────────────────────────────────────────────────
async function loadPsgcRegions() {
    loadingRegions.value = true;
    try {
        const { data } = await axios.get('/settings/psgc/regions');
        psgcRegions.value = data;
    } finally { loadingRegions.value = false; }
}

async function onRegionChange() {
    psgcProvince.value = null;
    psgcCity.value = null;
    psgcBarangay.value = null;
    psgcProvinces.value = [];
    psgcCities.value = [];
    psgcBarangayList.value = [];
    if (!psgcRegion.value) return;
    loadingProvinces.value = true;
    try {
        const { data } = await axios.get('/settings/psgc/provinces', { params: { region_id: psgcRegion.value } });
        psgcProvinces.value = data;
    } finally { loadingProvinces.value = false; }
}

async function onProvinceChange() {
    psgcCity.value = null;
    psgcBarangay.value = null;
    psgcCities.value = [];
    psgcBarangayList.value = [];
    if (!psgcProvince.value) return;
    loadingCities.value = true;
    try {
        const { data } = await axios.get('/settings/psgc/municipalities', { params: { province_id: psgcProvince.value } });
        psgcCities.value = data;
    } finally { loadingCities.value = false; }
}

async function onCityChange() {
    psgcBarangay.value = null;
    psgcBarangayList.value = [];
    if (!psgcCity.value) return;
    loadingBrgys.value = true;
    try {
        const { data } = await axios.get('/settings/psgc/barangays', { params: { city_id: psgcCity.value } });
        psgcBarangayList.value = data;
    } finally { loadingBrgys.value = false; }
}

async function addPsgcBarangay() {
    if (!psgcBarangay.value) return;
    const brgy = psgcBarangayList.value.find(b => b.psgc_id === psgcBarangay.value);
    const city = psgcCities.value.find(c => c.psgc_id === psgcCity.value);
    const province = psgcProvinces.value.find(p => p.psgc_id === psgcProvince.value);
    if (!brgy) return;

    addingBrgy.value = true;
    try {
        const { data } = await axios.post('/settings/barangays', {
            name: brgy.name,
            municipality_name: city?.name || '',
            province_name: province?.name || '',
            psgc_code: brgy.psgc_id,
        });
        barangays.value.push(data);
        toast.add({ severity: 'success', summary: 'Added', detail: `${data.name} saved${data.latitude ? ' with coordinates' : ''}.` });
        psgcBarangay.value = null;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Failed to add.' });
    } finally { addingBrgy.value = false; }
}

async function runImportAll(city, province) {
    importingAll.value = true;
    try {
        const { data } = await axios.post('/settings/barangays/bulk', {
            municipality_name: city.name,
            province_name: province?.name || '',
            barangays: psgcBarangayList.value.map(b => ({ name: b.name, psgc_code: b.psgc_id })),
        });
        const refreshed = await axios.get('/settings/barangays');
        barangays.value = refreshed.data;

        toast.add({
            severity: 'success',
            summary: 'Imported',
            detail: data.message || `${data.created ?? psgcBarangayList.value.length} barangays imported.`,
            life: 4000,
        });
        psgcBarangay.value = null;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Bulk import failed.' });
    } finally { importingAll.value = false; }
}

function importAllPsgcBarangays() {
    if (!psgcCity.value || !psgcBarangayList.value.length) return;
    const city = psgcCities.value.find(c => c.psgc_id === psgcCity.value);
    const province = psgcProvinces.value.find(p => p.psgc_id === psgcProvince.value);
    if (!city) return;

    confirm.require({
        header: 'Import all barangays?',
        message: `This will import ${psgcBarangayList.value.length} barangays of ${city.name}. Existing ones will be kept as-is.`,
        icon: 'pi pi-download',
        acceptLabel: 'Import',
        rejectLabel: 'Cancel',
        accept: () => runImportAll(city, province),
    });
}

function confirmClearBarangays() {
    confirm.require({
        header: 'Clear all barangays?',
        message: 'This will remove ALL barangay records. This cannot be undone.',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Clear All',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            clearingBrgys.value = true;
            try {
                await axios.delete('/settings/barangays/clear');
                barangays.value = [];
                toast.add({ severity: 'success', summary: 'Cleared', detail: 'All barangay records removed.' });
            } finally { clearingBrgys.value = false; }
        },
    });
}

function deleteBrgy(brgy) {
    confirm.require({
        header: 'Remove barangay?',
        message: `Remove ${brgy.name} from the list?`,
        icon: 'pi pi-trash',
        acceptLabel: 'Remove',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            try {
                await axios.delete(`/settings/barangays/${brgy.id}`);
                barangays.value = barangays.value.filter(b => b.id !== brgy.id);
                toast.add({ severity: 'success', summary: 'Removed', detail: `${brgy.name} deleted.` });
            } catch {}
        },
    });
}

onMounted(async () => {
    const [br, cls] = await Promise.all([
        axios.get('/settings/barangays'),
        axios.get('/settings/classifications'),
    ]);
    barangays.value = br.data;
    classifications.value = cls.data;
    loadPsgcRegions();
});
</script>
