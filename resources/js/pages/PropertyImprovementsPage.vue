<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Property Improvements</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Buildings, structures, utilities, and site improvements linked to tax declarations
                </p>
            </div>
            <Button label="Add Improvement" icon="pi pi-plus" size="small" @click="openDialog()" />
        </div>

        <!-- Summary -->
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
                    placeholder="Search TD#, owner, description..."
                    class="flex-1 min-w-0"
                    @keyup.enter="loadData(1)"
                />
                <Select
                    v-model="filters.road_access"
                    :options="roadAccessOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Road access"
                    showClear
                    class="w-full lg:w-44"
                    @change="loadData(1)"
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
                        <i class="pi pi-building text-5xl mb-3 block opacity-30"></i>
                        <p class="font-medium text-gray-500 dark:text-gray-400">No property improvements yet</p>
                        <p class="text-sm mt-1 mb-4">Link buildings, fences, and utilities to a tax declaration</p>
                        <Button label="Add Improvement" icon="pi pi-plus" size="small" outlined @click="openDialog()" />
                    </div>
                </template>

                <Column header="Tax Declaration" style="min-width: 150px">
                    <template #body="{ data }">
                        <RouterLink
                            v-if="data.tax_declaration"
                            :to="`/tax-declarations/${data.tax_declaration.id}`"
                            class="text-sm font-medium text-blue-600 hover:underline"
                        >
                            {{ data.tax_declaration.td_number }}
                        </RouterLink>
                        <span v-else class="text-sm text-gray-400">—</span>
                        <p class="text-xs text-gray-400 mt-0.5">{{ data.tax_declaration?.barangay?.name || '—' }}</p>
                    </template>
                </Column>

                <Column header="Owner" style="min-width: 150px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ data.tax_declaration?.owner?.owner_name || '—' }}
                        </span>
                    </template>
                </Column>

                <Column header="Features" style="min-width: 200px">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <Tag v-if="data.has_building" value="Building" severity="info" class="text-xs" />
                            <Tag v-if="data.has_structure" value="Structure" severity="secondary" class="text-xs" />
                            <Tag v-if="data.has_fence" value="Fence" severity="warn" class="text-xs" />
                            <Tag v-if="data.has_electricity" value="Electricity" severity="success" class="text-xs" />
                            <span
                                v-if="!data.has_building && !data.has_structure && !data.has_fence && !data.has_electricity"
                                class="text-xs text-gray-400"
                            >No features marked</span>
                        </div>
                    </template>
                </Column>

                <Column header="Road / Water" style="min-width: 140px">
                    <template #body="{ data }">
                        <p class="text-sm text-gray-700 dark:text-gray-300 capitalize">
                            {{ data.road_access ? `${data.road_access} road` : '—' }}
                        </p>
                        <p class="text-xs text-gray-400 truncate max-w-[160px]">{{ data.water_source || 'No water source' }}</p>
                    </template>
                </Column>

                <Column header="Description" style="min-width: 180px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                            {{ primaryDescription(data) }}
                        </span>
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

        <!-- Create / Edit / View dialog -->
        <Dialog
            v-model:visible="showDialog"
            :header="dialogTitle"
            :modal="true"
            class="w-full max-w-2xl"
            @hide="resetForm"
        >
            <form class="space-y-5 pt-1" @submit.prevent="saveRecord">
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
                    <p class="text-xs text-gray-400 mt-1">Each tax declaration can have one improvement record</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-800 dark:text-white">Building</label>
                            <ToggleSwitch v-model="form.has_building" :disabled="viewOnly" />
                        </div>
                        <Textarea
                            v-model="form.building_description"
                            class="w-full"
                            rows="2"
                            autoResize
                            placeholder="Building description..."
                            :disabled="viewOnly || !form.has_building"
                        />
                    </div>

                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-800 dark:text-white">Structure</label>
                            <ToggleSwitch v-model="form.has_structure" :disabled="viewOnly" />
                        </div>
                        <Textarea
                            v-model="form.structure_description"
                            class="w-full"
                            rows="2"
                            autoResize
                            placeholder="Structure description..."
                            :disabled="viewOnly || !form.has_structure"
                        />
                    </div>

                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-800 dark:text-white">Fence</label>
                            <ToggleSwitch v-model="form.has_fence" :disabled="viewOnly" />
                        </div>
                        <Textarea
                            v-model="form.fence_description"
                            class="w-full"
                            rows="2"
                            autoResize
                            placeholder="Fence description..."
                            :disabled="viewOnly || !form.has_fence"
                        />
                    </div>

                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-800 dark:text-white">Electricity</label>
                            <ToggleSwitch v-model="form.has_electricity" :disabled="viewOnly" />
                        </div>
                        <div>
                            <label class="form-label">Water Source</label>
                            <InputText
                                v-model="form.water_source"
                                class="w-full"
                                placeholder="e.g. Deep well, Level III"
                                :disabled="viewOnly"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Road Access</label>
                        <Select
                            v-model="form.road_access"
                            :options="roadAccessOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select road access"
                            showClear
                            class="w-full"
                            :disabled="viewOnly"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Land Improvements</label>
                        <Textarea
                            v-model="form.land_improvements"
                            class="w-full"
                            rows="2"
                            autoResize
                            placeholder="Landscaping, drainage, pavement..."
                            :disabled="viewOnly"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Other Improvements</label>
                        <Textarea
                            v-model="form.other_improvements"
                            class="w-full"
                            rows="2"
                            autoResize
                            placeholder="Additional notes..."
                            :disabled="viewOnly"
                        />
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
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Paginator from 'primevue/paginator';
import ToggleSwitch from 'primevue/toggleswitch';

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
    road_access: null,
    tax_declaration_id: null,
});

const roadAccessOptions = [
    { label: 'Paved', value: 'paved' },
    { label: 'Unpaved', value: 'unpaved' },
    { label: 'None', value: 'none' },
];

const emptyForm = () => ({
    tax_declaration_id: null,
    has_building: false,
    building_description: '',
    has_structure: false,
    structure_description: '',
    has_fence: false,
    fence_description: '',
    road_access: null,
    has_electricity: false,
    water_source: '',
    land_improvements: '',
    other_improvements: '',
});

const form = reactive(emptyForm());

const dialogTitle = computed(() => {
    if (viewOnly.value) return 'View Property Improvement';
    return editRecord.value ? 'Edit Property Improvement' : 'Add Property Improvement';
});

const summary = reactive({
    total: 0,
    has_building: 0,
    has_electricity: 0,
    has_fence: 0,
});

const summaryCards = computed(() => [
    { label: 'Total Records', count: summary.total, icon: 'pi-building', color: 'text-slate-600', bg: 'bg-slate-100 dark:bg-slate-700/50' },
    { label: 'With Building', count: summary.has_building, icon: 'pi-home', color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-900/30' },
    { label: 'With Electricity', count: summary.has_electricity, icon: 'pi-bolt', color: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-900/30' },
    { label: 'With Fence', count: summary.has_fence, icon: 'pi-stop', color: 'text-violet-600', bg: 'bg-violet-100 dark:bg-violet-900/30' },
]);

const availableTdOptions = computed(() => {
    if (editRecord.value) return tdOptions.value;
    return tdOptions.value.filter((opt) => !linkedTdIds.value.has(opt.value));
});

function primaryDescription(row) {
    return (
        row.building_description
        || row.structure_description
        || row.land_improvements
        || row.other_improvements
        || row.fence_description
        || '—'
    );
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
        const res = await axios.get('property-improvements', { params: { per_page: 500 } });
        const rows = res.data.data || [];
        linkedTdIds.value = new Set(rows.map((r) => r.tax_declaration_id));
    } catch {
        linkedTdIds.value = new Set();
    }
}

async function loadData(page = pagination.current_page) {
    loading.value = true;
    try {
        const res = await axios.get('property-improvements', {
            params: {
                page,
                per_page: pagination.per_page,
                search: filters.search || undefined,
                road_access: filters.road_access || undefined,
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
            has_building: res.data.summary?.has_building ?? 0,
            has_electricity: res.data.summary?.has_electricity ?? 0,
            has_fence: res.data.summary?.has_fence ?? 0,
        });
    } catch (err) {
        toast.apiError(err, 'Failed to load improvements');
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
    filters.road_access = null;
    filters.tax_declaration_id = null;
    loadData(1);
}

function resetForm() {
    Object.assign(form, emptyForm());
    editRecord.value = null;
    viewOnly.value = false;
}

function openDialog(record = null, readOnly = false) {
    editRecord.value = record;
    viewOnly.value = readOnly;
    Object.assign(form, emptyForm(), record ? {
        tax_declaration_id: record.tax_declaration_id,
        has_building: !!record.has_building,
        building_description: record.building_description || '',
        has_structure: !!record.has_structure,
        structure_description: record.structure_description || '',
        has_fence: !!record.has_fence,
        fence_description: record.fence_description || '',
        road_access: record.road_access || null,
        has_electricity: !!record.has_electricity,
        water_source: record.water_source || '',
        land_improvements: record.land_improvements || '',
        other_improvements: record.other_improvements || '',
    } : {});
    showDialog.value = true;
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
            has_building: !!form.has_building,
            building_description: form.has_building ? (form.building_description || null) : null,
            has_structure: !!form.has_structure,
            structure_description: form.has_structure ? (form.structure_description || null) : null,
            has_fence: !!form.has_fence,
            fence_description: form.has_fence ? (form.fence_description || null) : null,
            road_access: form.road_access || null,
            has_electricity: !!form.has_electricity,
            water_source: form.water_source || null,
            land_improvements: form.land_improvements || null,
            other_improvements: form.other_improvements || null,
        };

        if (editRecord.value) {
            await axios.put(`property-improvements/${editRecord.value.id}`, payload);
            toast.success('Updated', 'Property improvement updated.');
        } else {
            await axios.post('property-improvements', payload);
            toast.success('Created', 'Property improvement linked to TD.');
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
        message: `Delete improvements for ${td}? It will move to Archive.`,
        header: 'Delete Improvement',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`property-improvements/${record.id}`);
                toast.success('Deleted', 'Moved to archive.');
                await Promise.all([loadData(pagination.current_page), loadLinkedTdIds()]);
            } catch (err) {
                toast.apiError(err, 'Delete failed');
            }
        },
    });
}

onMounted(async () => {
    await Promise.all([loadTdOptions(), loadLinkedTdIds(), loadData(1)]);
});
</script>
