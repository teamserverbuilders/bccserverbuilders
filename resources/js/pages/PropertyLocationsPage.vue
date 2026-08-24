<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Property Locations</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Address and map coordinates linked to tax declarations
                </p>
            </div>
            <div class="flex items-center gap-2">
                <RouterLink to="/land-map">
                    <Button label="Land Mapping" icon="pi pi-sitemap" outlined size="small" />
                </RouterLink>
                <Button label="Add Location" icon="pi pi-plus" size="small" @click="openDialog()" />
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div
                v-for="card in summaryCards"
                :key="card.label"
                class="bg-white dark:bg-gray-800 rounded-xl p-3.5 shadow-sm border border-gray-100 dark:border-gray-700"
            >
                <div :class="['w-9 h-9 rounded-lg flex items-center justify-center mb-2', card.bg]">
                    <i :class="['pi text-base', card.icon, card.color]"></i>
                </div>
                <p class="text-lg font-bold text-gray-800 dark:text-white leading-none">{{ card.count }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ card.label }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
                <InputText
                    v-model="filters.search"
                    placeholder="Search TD#, owner, barangay, street..."
                    class="flex-1 min-w-0"
                    @keyup.enter="loadData(1)"
                />
                <InputText
                    v-model="filters.barangay"
                    placeholder="Barangay"
                    class="w-full lg:w-40"
                    @keyup.enter="loadData(1)"
                />
                <Select
                    v-model="filters.tax_declaration_id"
                    :options="tdOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All TD numbers"
                    filter
                    showClear
                    class="w-full lg:w-56"
                    @change="loadData(1)"
                />
                <div class="flex gap-2 shrink-0">
                    <Button label="Search" icon="pi pi-search" size="small" @click="loadData(1)" />
                    <Button icon="pi pi-refresh" outlined size="small" v-tooltip="'Reset'" @click="resetFilters" />
                </div>
            </div>

            <DataTable :value="records" :loading="loading" class="p-datatable-sm" striped-rows>
                <template #empty>
                    <div class="text-center py-14 text-gray-400">
                        <i class="pi pi-map-marker text-5xl mb-3 block opacity-30"></i>
                        <p class="font-medium text-gray-500 dark:text-gray-400">No property locations yet</p>
                        <p class="text-sm mt-1 mb-4">Link an address and coordinates to a tax declaration</p>
                        <Button label="Add Location" icon="pi pi-plus" size="small" outlined @click="openDialog()" />
                    </div>
                </template>

                <Column header="Tax Declaration" style="min-width: 140px">
                    <template #body="{ data }">
                        <RouterLink
                            v-if="data.tax_declaration"
                            :to="`/tax-declarations/${data.tax_declaration.id}`"
                            class="text-sm font-medium text-blue-600 hover:underline"
                        >
                            {{ data.tax_declaration.td_number }}
                        </RouterLink>
                        <span v-else class="text-sm text-gray-400">—</span>
                        <p class="text-xs text-gray-400 mt-0.5 truncate max-w-[160px]">
                            {{ data.tax_declaration?.owner?.owner_name || '—' }}
                        </p>
                    </template>
                </Column>

                <Column header="Address" style="min-width: 200px">
                    <template #body="{ data }">
                        <p class="text-sm text-gray-800 dark:text-white">{{ formatAddress(data) }}</p>
                        <p class="text-xs text-gray-400">{{ [data.municipality, data.province].filter(Boolean).join(', ') || '—' }}</p>
                    </template>
                </Column>

                <Column header="Barangay" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ data.barangay || '—' }}</span>
                    </template>
                </Column>

                <Column header="Coordinates" style="min-width: 150px">
                    <template #body="{ data }">
                        <template v-if="data.latitude != null && data.longitude != null">
                            <p class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ Number(data.latitude).toFixed(5) }}, {{ Number(data.longitude).toFixed(5) }}</p>
                            <a
                                v-if="data.google_maps_link"
                                :href="data.google_maps_link"
                                target="_blank"
                                rel="noopener"
                                class="text-xs text-blue-600 hover:underline"
                            >Open map</a>
                        </template>
                        <span v-else class="text-xs text-gray-400">No coordinates</span>
                    </template>
                </Column>

                <Column header="Actions" style="min-width: 120px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Button icon="pi pi-eye" size="small" text rounded v-tooltip="'View'" @click="openDialog(data, true)" />
                            <Button icon="pi pi-pencil" size="small" text rounded severity="secondary" v-tooltip="'Edit'" @click="openDialog(data)" />
                            <Button icon="pi pi-trash" size="small" text rounded severity="danger" v-tooltip="'Delete'" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500">
                    Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }} of {{ pagination.total || 0 }}
                </span>
                <Paginator
                    :rows="pagination.per_page"
                    :totalRecords="pagination.total"
                    :first="(pagination.current_page - 1) * pagination.per_page"
                    @page="onPage"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                />
            </div>
        </div>

        <Dialog
            v-model:visible="showDialog"
            :header="dialogTitle"
            :modal="true"
            class="w-full max-w-2xl"
            @hide="resetForm"
        >
            <form class="space-y-4 pt-1" @submit.prevent="saveRecord">
                <div>
                    <label class="form-label">Tax Declaration <span class="text-red-500">*</span></label>
                    <Select
                        v-model="form.tax_declaration_id"
                        :options="availableTdOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select TD number to link"
                        filter
                        class="w-full"
                        :disabled="viewOnly || !!editRecord"
                    />
                    <p class="text-xs text-gray-400 mt-1">Each tax declaration can have one location record</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Region</label>
                        <Select
                            v-model="psgc.regionId"
                            :options="psgc.regions"
                            optionLabel="name"
                            optionValue="psgc_id"
                            placeholder="Select region"
                            filter
                            showClear
                            class="w-full"
                            :loading="psgc.loadingRegions"
                            :disabled="viewOnly"
                            @change="onRegionChange"
                        />
                    </div>
                    <div>
                        <label class="form-label">Province</label>
                        <Select
                            v-model="psgc.provinceId"
                            :options="psgc.provinces"
                            optionLabel="name"
                            optionValue="psgc_id"
                            placeholder="Select province"
                            filter
                            showClear
                            class="w-full"
                            :loading="psgc.loadingProvinces"
                            :disabled="viewOnly || !psgc.regionId"
                            @change="onProvinceChange"
                        />
                    </div>
                    <div>
                        <label class="form-label">Municipality / City</label>
                        <Select
                            v-model="psgc.cityId"
                            :options="psgc.cities"
                            optionLabel="name"
                            optionValue="psgc_id"
                            placeholder="Select municipality / city"
                            filter
                            showClear
                            class="w-full"
                            :loading="psgc.loadingCities"
                            :disabled="viewOnly || !psgc.provinceId"
                            @change="onCityChange"
                        />
                    </div>
                    <div>
                        <label class="form-label">Barangay</label>
                        <Select
                            v-model="psgc.barangayId"
                            :options="psgc.barangays"
                            optionLabel="name"
                            optionValue="psgc_id"
                            placeholder="Select barangay"
                            filter
                            showClear
                            class="w-full"
                            :loading="psgc.loadingBarangays"
                            :disabled="viewOnly || !psgc.cityId"
                            @change="onBarangayChange"
                        />
                    </div>
                    <div>
                        <label class="form-label">Purok / Sitio</label>
                        <InputText v-model="form.purok" class="w-full" :disabled="viewOnly" />
                    </div>
                    <div>
                        <label class="form-label">ZIP Code</label>
                        <InputText v-model="form.zip_code" class="w-full" :disabled="viewOnly" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Street / Lot</label>
                        <InputText v-model="form.street" class="w-full" :disabled="viewOnly" />
                    </div>
                </div>
                <p v-if="locationSummary" class="text-xs text-gray-500 dark:text-gray-400 -mt-1">
                    Selected: {{ locationSummary }}
                </p>

                <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                    <p class="text-sm font-medium text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="pi pi-map text-blue-500"></i> Map coordinates
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Latitude</label>
                            <InputText v-model="form.latitude" class="w-full" placeholder="e.g. 13.45678" :disabled="viewOnly" />
                        </div>
                        <div>
                            <label class="form-label">Longitude</label>
                            <InputText v-model="form.longitude" class="w-full" placeholder="e.g. 123.45678" :disabled="viewOnly" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Google Maps Link</label>
                            <InputText v-model="form.google_maps_link" class="w-full" placeholder="https://maps.google.com/..." :disabled="viewOnly" />
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-1">
                    <Button type="button" :label="viewOnly ? 'Close' : 'Cancel'" outlined class="flex-1" @click="showDialog = false" />
                    <Button
                        v-if="viewOnly"
                        type="button"
                        label="Edit"
                        icon="pi pi-pencil"
                        class="flex-1"
                        @click="viewOnly = false"
                    />
                    <Button
                        v-else
                        type="submit"
                        :label="editRecord ? 'Update' : 'Create'"
                        icon="pi pi-check"
                        class="flex-1"
                        :loading="saving"
                    />
                </div>
            </form>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Paginator from 'primevue/paginator';

const confirm = useConfirm();
const toast = useToast();

const loading = ref(false);
const saving = ref(false);
const showDialog = ref(false);
const viewOnly = ref(false);
const editRecord = ref(null);
const records = ref([]);
const tdOptions = ref([]);
const linkedTdIds = ref(new Set());

const pagination = reactive({
    total: 0,
    per_page: 15,
    current_page: 1,
    from: 0,
    to: 0,
});

const filters = reactive({
    search: '',
    barangay: '',
    tax_declaration_id: null,
});

const summary = reactive({
    total: 0,
    with_coordinates: 0,
    with_maps_link: 0,
    barangays: 0,
});

const emptyForm = () => ({
    tax_declaration_id: null,
    province: '',
    municipality: '',
    barangay: '',
    purok: '',
    street: '',
    zip_code: '',
    latitude: '',
    longitude: '',
    google_maps_link: '',
});

const form = reactive(emptyForm());

const psgc = reactive({
    regionId: null,
    provinceId: null,
    cityId: null,
    barangayId: null,
    regions: [],
    provinces: [],
    cities: [],
    barangays: [],
    loadingRegions: false,
    loadingProvinces: false,
    loadingCities: false,
    loadingBarangays: false,
});

const dialogTitle = computed(() => {
    if (viewOnly.value) return 'View Property Location';
    return editRecord.value ? 'Edit Property Location' : 'Add Property Location';
});

const locationSummary = computed(() => {
    const parts = [form.barangay, form.municipality, form.province].filter(Boolean);
    return parts.length ? parts.join(', ') : '';
});

const summaryCards = computed(() => [
    { label: 'Total Locations', count: summary.total, icon: 'pi-map-marker', color: 'text-slate-600', bg: 'bg-slate-100 dark:bg-slate-700/50' },
    { label: 'With Coordinates', count: summary.with_coordinates, icon: 'pi-compass', color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-900/30' },
    { label: 'With Maps Link', count: summary.with_maps_link, icon: 'pi-external-link', color: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-900/30' },
    { label: 'Barangays', count: summary.barangays, icon: 'pi-building', color: 'text-violet-600', bg: 'bg-violet-100 dark:bg-violet-900/30' },
]);

const availableTdOptions = computed(() => {
    if (editRecord.value) return tdOptions.value;
    return tdOptions.value.filter((opt) => !linkedTdIds.value.has(opt.value));
});

function namesMatch(a, b) {
    return String(a || '').trim().toLowerCase() === String(b || '').trim().toLowerCase();
}

function findByName(list, name) {
    if (!name) return null;
    return list.find((item) => namesMatch(item.name, name)) || null;
}

function syncProvinceName() {
    const item = psgc.provinces.find((p) => p.psgc_id === psgc.provinceId);
    form.province = item?.name || '';
}

function syncMunicipalityName() {
    const item = psgc.cities.find((c) => c.psgc_id === psgc.cityId);
    form.municipality = item?.name || '';
}

function syncBarangayName() {
    const item = psgc.barangays.find((b) => b.psgc_id === psgc.barangayId);
    form.barangay = item?.name || '';
}

function resetPsgc(keepRegions = true) {
    psgc.regionId = null;
    psgc.provinceId = null;
    psgc.cityId = null;
    psgc.barangayId = null;
    if (!keepRegions) psgc.regions = [];
    psgc.provinces = [];
    psgc.cities = [];
    psgc.barangays = [];
}

async function loadPsgcRegions() {
    if (psgc.regions.length) return;
    psgc.loadingRegions = true;
    try {
        const { data } = await axios.get('settings/psgc/regions');
        psgc.regions = Array.isArray(data) ? data : [];
    } catch {
        psgc.regions = [];
        toast.error('PSGC', 'Failed to load regions.');
    } finally {
        psgc.loadingRegions = false;
    }
}

async function onRegionChange() {
    psgc.provinceId = null;
    psgc.cityId = null;
    psgc.barangayId = null;
    psgc.provinces = [];
    psgc.cities = [];
    psgc.barangays = [];
    form.province = '';
    form.municipality = '';
    form.barangay = '';
    if (!psgc.regionId) return;

    psgc.loadingProvinces = true;
    try {
        const { data } = await axios.get('settings/psgc/provinces', { params: { region_id: psgc.regionId } });
        psgc.provinces = Array.isArray(data) ? data : [];
    } catch {
        psgc.provinces = [];
        toast.error('PSGC', 'Failed to load provinces.');
    } finally {
        psgc.loadingProvinces = false;
    }
}

async function onProvinceChange() {
    psgc.cityId = null;
    psgc.barangayId = null;
    psgc.cities = [];
    psgc.barangays = [];
    form.municipality = '';
    form.barangay = '';
    syncProvinceName();
    if (!psgc.provinceId) {
        form.province = '';
        return;
    }

    psgc.loadingCities = true;
    try {
        const { data } = await axios.get('settings/psgc/municipalities', { params: { province_id: psgc.provinceId } });
        psgc.cities = Array.isArray(data) ? data : [];
    } catch {
        psgc.cities = [];
        toast.error('PSGC', 'Failed to load municipalities.');
    } finally {
        psgc.loadingCities = false;
    }
}

async function onCityChange() {
    psgc.barangayId = null;
    psgc.barangays = [];
    form.barangay = '';
    syncMunicipalityName();
    if (!psgc.cityId) {
        form.municipality = '';
        return;
    }

    psgc.loadingBarangays = true;
    try {
        const { data } = await axios.get('settings/psgc/barangays', { params: { city_id: psgc.cityId } });
        psgc.barangays = Array.isArray(data) ? data : [];
    } catch {
        psgc.barangays = [];
        toast.error('PSGC', 'Failed to load barangays.');
    } finally {
        psgc.loadingBarangays = false;
    }
}

function onBarangayChange() {
    syncBarangayName();
    if (!psgc.barangayId) form.barangay = '';
}

async function hydratePsgcFromNames({ province, municipality, barangay }) {
    if (!province && !municipality && !barangay) return;
    await loadPsgcRegions();

    for (const region of psgc.regions) {
        psgc.regionId = region.psgc_id;
        psgc.loadingProvinces = true;
        try {
            const { data } = await axios.get('settings/psgc/provinces', { params: { region_id: region.psgc_id } });
            psgc.provinces = Array.isArray(data) ? data : [];
        } catch {
            psgc.provinces = [];
        } finally {
            psgc.loadingProvinces = false;
        }

        const matchedProvince = findByName(psgc.provinces, province);
        if (!matchedProvince) continue;

        psgc.provinceId = matchedProvince.psgc_id;
        form.province = matchedProvince.name;

        psgc.loadingCities = true;
        try {
            const { data } = await axios.get('settings/psgc/municipalities', { params: { province_id: matchedProvince.psgc_id } });
            psgc.cities = Array.isArray(data) ? data : [];
        } catch {
            psgc.cities = [];
        } finally {
            psgc.loadingCities = false;
        }

        const matchedCity = findByName(psgc.cities, municipality);
        if (matchedCity) {
            psgc.cityId = matchedCity.psgc_id;
            form.municipality = matchedCity.name;

            psgc.loadingBarangays = true;
            try {
                const { data } = await axios.get('settings/psgc/barangays', { params: { city_id: matchedCity.psgc_id } });
                psgc.barangays = Array.isArray(data) ? data : [];
            } catch {
                psgc.barangays = [];
            } finally {
                psgc.loadingBarangays = false;
            }

            const matchedBarangay = findByName(psgc.barangays, barangay);
            if (matchedBarangay) {
                psgc.barangayId = matchedBarangay.psgc_id;
                form.barangay = matchedBarangay.name;
            }
        }
        return;
    }

    // No region match — keep saved names, clear cascade IDs
    psgc.regionId = null;
    psgc.provinceId = null;
    psgc.cityId = null;
    psgc.barangayId = null;
    psgc.provinces = [];
    psgc.cities = [];
    psgc.barangays = [];
}

function formatAddress(row) {
    const parts = [row.street, row.purok, row.barangay].filter(Boolean);
    return parts.length ? parts.join(', ') : '—';
}

function toNullableNumber(value) {
    if (value === '' || value === null || value === undefined) return null;
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
}

async function loadTdOptions() {
    try {
        const res = await axios.get('tax-declarations', { params: { per_page: 500 } });
        const rows = res.data.data || res.data || [];
        tdOptions.value = rows.map((td) => ({
            value: td.id,
            label: `${td.td_number}${td.owner?.owner_name ? ` — ${td.owner.owner_name}` : ''}`,
        }));
    } catch {
        tdOptions.value = [];
    }
}

async function loadLinkedTdIds() {
    try {
        const res = await axios.get('property-locations', { params: { per_page: 500 } });
        linkedTdIds.value = new Set((res.data.data || []).map((r) => r.tax_declaration_id));
    } catch {
        linkedTdIds.value = new Set();
    }
}

async function loadData(page = pagination.current_page) {
    loading.value = true;
    try {
        const res = await axios.get('property-locations', {
            params: {
                page,
                per_page: pagination.per_page,
                search: filters.search || undefined,
                barangay: filters.barangay || undefined,
                tax_declaration_id: filters.tax_declaration_id || undefined,
            },
        });
        records.value = res.data.data || [];
        pagination.total = res.data.total || 0;
        pagination.per_page = res.data.per_page || 15;
        pagination.current_page = res.data.current_page || 1;
        pagination.from = res.data.from || 0;
        pagination.to = res.data.to || 0;
        Object.assign(summary, {
            total: res.data.summary?.total ?? pagination.total,
            with_coordinates: res.data.summary?.with_coordinates ?? 0,
            with_maps_link: res.data.summary?.with_maps_link ?? 0,
            barangays: res.data.summary?.barangays ?? 0,
        });
    } catch (err) {
        toast.apiError(err, 'Failed to load locations');
    } finally {
        loading.value = false;
    }
}

function onPage(event) {
    pagination.per_page = event.rows;
    loadData(event.page + 1);
}

function resetFilters() {
    filters.search = '';
    filters.barangay = '';
    filters.tax_declaration_id = null;
    loadData(1);
}

function resetForm() {
    Object.assign(form, emptyForm());
    editRecord.value = null;
    viewOnly.value = false;
    resetPsgc(true);
}

async function openDialog(record = null, readOnly = false) {
    editRecord.value = record;
    viewOnly.value = readOnly;
    resetPsgc(true);
    Object.assign(form, emptyForm(), record ? {
        tax_declaration_id: record.tax_declaration_id,
        province: record.province || '',
        municipality: record.municipality || '',
        barangay: record.barangay || '',
        purok: record.purok || '',
        street: record.street || '',
        zip_code: record.zip_code || '',
        latitude: record.latitude != null ? String(record.latitude) : '',
        longitude: record.longitude != null ? String(record.longitude) : '',
        google_maps_link: record.google_maps_link || '',
    } : {});
    showDialog.value = true;
    await loadPsgcRegions();
    if (record && (record.province || record.municipality || record.barangay)) {
        await hydratePsgcFromNames({
            province: record.province,
            municipality: record.municipality,
            barangay: record.barangay,
        });
    }
}

async function saveRecord() {
    if (!form.tax_declaration_id) {
        toast.warn('Required', 'Please select a tax declaration.');
        return;
    }

    saving.value = true;
    try {
        const payload = {
            tax_declaration_id: form.tax_declaration_id,
            province: form.province || null,
            municipality: form.municipality || null,
            barangay: form.barangay || null,
            purok: form.purok || null,
            street: form.street || null,
            zip_code: form.zip_code || null,
            latitude: toNullableNumber(form.latitude),
            longitude: toNullableNumber(form.longitude),
            google_maps_link: form.google_maps_link || null,
        };

        if (editRecord.value) {
            await axios.put(`property-locations/${editRecord.value.id}`, payload);
            toast.success('Updated', 'Property location updated.');
        } else {
            await axios.post('property-locations', payload);
            toast.success('Created', 'Property location linked to TD.');
        }

        showDialog.value = false;
        await Promise.all([loadData(pagination.current_page), loadLinkedTdIds()]);
    } catch (err) {
        toast.apiError(err, 'Save failed');
    } finally {
        saving.value = false;
    }
}

function confirmDelete(record) {
    const td = record.tax_declaration?.td_number || 'this record';
    confirm.require({
        message: `Delete location for ${td}? It will move to Archive.`,
        header: 'Delete Location',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`property-locations/${record.id}`);
                toast.success('Deleted', 'Moved to archive.');
                await Promise.all([loadData(pagination.current_page), loadLinkedTdIds()]);
            } catch (err) {
                toast.apiError(err, 'Delete failed');
            }
        },
    });
}

onMounted(async () => {
    await Promise.all([loadTdOptions(), loadLinkedTdIds(), loadData(1), loadPsgcRegions()]);
});
</script>
