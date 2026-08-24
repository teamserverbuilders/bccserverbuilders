<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-[#1a3557] dark:text-white">Archive</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Soft-deleted records. Restore them or permanently remove them.
                </p>
            </div>
            <Button icon="pi pi-refresh" label="Refresh" outlined size="small" :loading="loading" @click="refresh" />
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700">
            <button
                v-for="tab in tabs"
                :key="tab.type"
                type="button"
                @click="switchTab(tab.type)"
                :class="[
                    'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px flex items-center gap-2',
                    activeType === tab.type
                        ? 'border-[#1a3557] text-[#1a3557] dark:border-blue-400 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                ]"
            >
                <i :class="['pi text-xs', tab.icon]"></i>
                {{ tab.label }}
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    {{ counts[tab.type] ?? 0 }}
                </span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <InputText
                    v-model="search"
                    :placeholder="searchPlaceholder"
                    class="flex-1"
                    @keyup.enter="loadData(1)"
                />
                <div class="flex gap-2">
                    <Button label="Search" icon="pi pi-search" size="small" @click="loadData(1)" />
                    <Button icon="pi pi-times" outlined size="small" v-tooltip="'Clear'" @click="clearSearch" />
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <DataTable
                :value="records"
                :loading="loading"
                striped-rows
                class="p-datatable-sm"
                scrollable
                scroll-height="calc(100vh - 420px)"
            >
                <template #empty>
                    <div class="text-center py-12">
                        <i class="pi pi-inbox text-4xl text-gray-300 mb-3 block"></i>
                        <p class="text-gray-500">No archived records</p>
                    </div>
                </template>

                <!-- Tax Declarations -->
                <Column v-if="activeType === 'tax_declarations'" field="td_number" header="TD Number" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="font-medium text-sm text-gray-800 dark:text-white">{{ data.td_number }}</span>
                        <p class="text-xs text-gray-400">{{ data.arp_number || '—' }}</p>
                    </template>
                </Column>
                <Column v-if="activeType === 'tax_declarations'" header="Owner" style="min-width: 160px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.owner?.owner_name || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'tax_declarations'" header="Classification" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ data.classification?.name || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'tax_declarations'" header="Barangay" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ data.barangay?.name || '—' }}</span>
                    </template>
                </Column>

                <!-- Field Appraisals -->
                <Column v-if="activeType === 'field_appraisals'" field="appraisal_no" header="Appraisal No." style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="font-medium text-sm">{{ data.appraisal_no || '—' }}</span>
                        <p class="text-xs text-gray-400">{{ data.arp_no || data.pin || '—' }}</p>
                    </template>
                </Column>
                <Column v-if="activeType === 'field_appraisals'" header="Owner" style="min-width: 160px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.owner_name || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'field_appraisals'" header="Location" style="min-width: 160px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ [data.property_barangay, data.property_municipality].filter(Boolean).join(', ') || '—' }}
                        </span>
                    </template>
                </Column>
                <Column v-if="activeType === 'field_appraisals'" header="Linked TD" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.tax_declaration?.td_number || '—' }}</span>
                    </template>
                </Column>

                <!-- Property Owners -->
                <Column v-if="activeType === 'property_owners'" field="owner_name" header="Owner Name" style="min-width: 180px">
                    <template #body="{ data }">
                        <span class="font-medium text-sm">{{ data.owner_name }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_owners'" field="tin" header="TIN" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.tin || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_owners'" field="contact_number" header="Contact" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.contact_number || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_owners'" field="address" header="Address" style="min-width: 180px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300 truncate block max-w-[220px]">{{ data.address || '—' }}</span>
                    </template>
                </Column>

                <!-- Property Locations -->
                <Column v-if="activeType === 'property_locations'" header="Linked TD" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="font-medium text-sm">{{ data.tax_declaration?.td_number || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_locations'" header="Barangay" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.barangay || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_locations'" header="Municipality" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.municipality || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_locations'" header="Province" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.province || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_locations'" header="Street" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ data.street || '—' }}</span>
                    </template>
                </Column>

                <!-- Property Improvements -->
                <Column v-if="activeType === 'property_improvements'" header="Linked TD" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="font-medium text-sm">{{ data.tax_declaration?.td_number || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_improvements'" header="Building" style="min-width: 100px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.has_building ? 'Yes' : 'No' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_improvements'" header="Structure" style="min-width: 100px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.has_structure ? 'Yes' : 'No' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_improvements'" header="Road Access" style="min-width: 110px">
                    <template #body="{ data }">
                        <span class="text-sm capitalize">{{ data.road_access || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_improvements'" header="Description" style="min-width: 180px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300 truncate block max-w-[220px]">
                            {{ data.building_description || data.structure_description || data.land_improvements || '—' }}
                        </span>
                    </template>
                </Column>

                <!-- Property Versions -->
                <Column v-if="activeType === 'property_versions'" header="Linked TD" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="font-medium text-sm">{{ data.tax_declaration?.td_number || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_versions'" header="Version" style="min-width: 90px">
                    <template #body="{ data }">
                        <span class="text-sm font-medium">v{{ data.version_number }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_versions'" header="Change Summary" style="min-width: 200px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300 truncate block max-w-[260px]">{{ data.change_summary || '—' }}</span>
                    </template>
                </Column>
                <Column v-if="activeType === 'property_versions'" header="Created By" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.created_by?.name || '—' }}</span>
                    </template>
                </Column>

                <Column field="deleted_at" header="Deleted" style="min-width: 130px">
                    <template #body="{ data }">
                        <span class="text-xs text-gray-500">{{ formatDate(data.deleted_at) }}</span>
                    </template>
                </Column>

                <Column header="Actions" frozen alignFrozen="right" style="min-width: 140px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Button
                                icon="pi pi-replay"
                                size="small"
                                text
                                rounded
                                severity="success"
                                v-tooltip="'Restore'"
                                :loading="actionId === `restore-${data.id}`"
                                @click="restoreRecord(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                text
                                rounded
                                severity="danger"
                                v-tooltip="'Delete permanently'"
                                :loading="actionId === `delete-${data.id}`"
                                @click="confirmForceDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }} of {{ pagination.total || 0 }} records
                </span>
                <Paginator
                    :rows="pagination.per_page"
                    :totalRecords="pagination.total"
                    :first="(pagination.current_page - 1) * pagination.per_page"
                    @page="onPage"
                    :rowsPerPageOptions="[10, 15, 25, 50]"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import axios from 'axios';

const confirm = useConfirm();
const toast = useToast();

const tabs = [
    { type: 'tax_declarations', label: 'Tax Declarations', icon: 'pi-file-edit' },
    { type: 'field_appraisals', label: 'Field Appraisals', icon: 'pi-clipboard' },
    { type: 'property_owners', label: 'Property Owners', icon: 'pi-users' },
    { type: 'property_locations', label: 'Locations', icon: 'pi-map-marker' },
    { type: 'property_improvements', label: 'Improvements', icon: 'pi-building' },
    { type: 'property_versions', label: 'Versions', icon: 'pi-history' },
];

const activeType = ref('tax_declarations');
const records = ref([]);
const loading = ref(false);
const search = ref('');
const actionId = ref(null);
const counts = ref({
    tax_declarations: 0,
    field_appraisals: 0,
    property_owners: 0,
    property_locations: 0,
    property_improvements: 0,
    property_versions: 0,
});
const pagination = ref({ total: 0, per_page: 15, current_page: 1, from: 0, to: 0 });

const searchPlaceholder = computed(() => {
    const map = {
        tax_declarations: 'Search TD#, ARP, Owner...',
        field_appraisals: 'Search Appraisal No., ARP, Owner, PIN...',
        property_owners: 'Search Owner, TIN, Contact...',
        property_locations: 'Search barangay, municipality, TD#...',
        property_improvements: 'Search description, TD#...',
        property_versions: 'Search version, summary, TD#...',
    };
    return map[activeType.value] || 'Search...';
});

async function loadCounts() {
    try {
        const res = await axios.get('/archive/counts');
        counts.value = res.data;
    } catch {
        // ignore
    }
}

async function loadData(page = 1) {
    loading.value = true;
    try {
        const res = await axios.get('/archive', {
            params: {
                type: activeType.value,
                search: search.value || undefined,
                page,
                per_page: pagination.value.per_page,
            },
        });
        records.value = res.data.data;
        pagination.value = {
            total: res.data.total,
            per_page: res.data.per_page,
            current_page: res.data.current_page,
            from: res.data.from,
            to: res.data.to,
        };
    } catch (err) {
        toast.apiError(err, 'Failed to load archive');
    } finally {
        loading.value = false;
    }
}

async function refresh() {
    await Promise.all([loadCounts(), loadData(pagination.value.current_page)]);
}

function switchTab(type) {
    if (activeType.value === type) return;
    activeType.value = type;
    search.value = '';
    loadData(1);
}

function clearSearch() {
    search.value = '';
    loadData(1);
}

function onPage(event) {
    pagination.value.per_page = event.rows;
    loadData(event.page + 1);
}

async function restoreRecord(row) {
    actionId.value = `restore-${row.id}`;
    try {
        await axios.post(`/archive/${activeType.value}/${row.id}/restore`);
        toast.success('Restored', 'Record has been restored.');
        await refresh();
    } catch (err) {
        toast.apiError(err, 'Restore failed');
    } finally {
        actionId.value = null;
    }
}

function confirmForceDelete(row) {
    const label = row.td_number
        || row.appraisal_no
        || row.owner_name
        || row.tax_declaration?.td_number
        || (row.version_number != null ? `v${row.version_number}` : null)
        || `#${row.id}`;
    confirm.require({
        message: `Permanently delete "${label}"? This cannot be undone.`,
        header: 'Permanent Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete Forever',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            actionId.value = `delete-${row.id}`;
            try {
                await axios.delete(`/archive/${activeType.value}/${row.id}`);
                toast.success('Deleted', 'Record permanently removed.');
                await refresh();
            } catch (err) {
                toast.apiError(err, 'Permanent delete failed');
            } finally {
                actionId.value = null;
            }
        },
    });
}

function formatDate(date) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

onMounted(() => {
    loadCounts();
    loadData();
});
</script>
