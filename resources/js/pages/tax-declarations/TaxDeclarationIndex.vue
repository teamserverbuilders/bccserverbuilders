<template>
    <div class="space-y-5">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Tax Declarations</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage all real property tax declaration records</p>
            </div>
            <div class="flex items-center gap-2">
                <RouterLink to="/tax-declarations/create">
                    <Button label="New Declaration" icon="pi pi-plus" size="small" />
                </RouterLink>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <InputText v-model="filters.search" placeholder="Search TD#, ARP, Owner..." class="col-span-2" @keyup.enter="loadData" />
                <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All Status" showClear />
                <Select v-model="filters.classification_id" :options="classifications" optionLabel="name" optionValue="id" placeholder="Classification" showClear />
                <Select v-model="filters.barangay_id" :options="barangays" optionLabel="name" optionValue="id" placeholder="Barangay" showClear />
                <div class="flex gap-2">
                    <Button label="Filter" icon="pi pi-filter" @click="loadData" size="small" class="flex-1" />
                    <Button icon="pi pi-refresh" outlined size="small" @click="resetFilters" v-tooltip="'Reset'" />
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <DataTable
                :value="records"
                :loading="loading"
                :paginator="false"
                striped-rows
                class="p-datatable-sm"
                scrollable
                scroll-height="calc(100vh - 380px)"
            >
                <template #empty>
                    <div class="text-center py-12">
                        <i class="pi pi-file-edit text-4xl text-gray-300 mb-3 block"></i>
                        <p class="text-gray-500">No records found</p>
                    </div>
                </template>

                <Column field="td_number" header="TD Number" sortable style="min-width: 140px">
                    <template #body="{ data }">
                        <RouterLink :to="`/tax-declarations/${data.id}`" class="text-blue-600 hover:underline font-medium text-sm">
                            {{ data.td_number }}
                        </RouterLink>
                    </template>
                </Column>
                <Column field="owner.owner_name" header="Owner" sortable style="min-width: 180px">
                    <template #body="{ data }">
                        <div>
                            <p class="font-medium text-sm text-gray-800 dark:text-white">{{ data.owner?.owner_name }}</p>
                            <p class="text-xs text-gray-400">{{ data.arp_number }}</p>
                        </div>
                    </template>
                </Column>
                <Column field="classification.name" header="Classification" style="min-width: 130px">
                    <template #body="{ data }">
                        <Tag :value="data.classification?.name" :style="{ background: data.classification?.color + '20', color: data.classification?.color }" class="text-xs" />
                    </template>
                </Column>
                <Column field="barangay.name" header="Barangay" style="min-width: 130px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ data.barangay?.name }}</span>
                    </template>
                </Column>
                <Column field="assessed_value" header="Assessed Value" sortable style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="text-sm font-medium">₱{{ data.assessed_value ? Number(data.assessed_value).toLocaleString() : '—' }}</span>
                    </template>
                </Column>
                <Column field="status" header="Status" style="min-width: 150px">
                    <template #body="{ data }">
                        <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" class="text-xs" />
                    </template>
                </Column>
                <Column field="created_at" header="Date" sortable style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-xs text-gray-500">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>
                <Column header="Actions" frozen alignFrozen="right" style="min-width: 160px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <RouterLink :to="`/tax-declarations/${data.id}`">
                                <Button icon="pi pi-eye" size="small" text rounded v-tooltip.top="'View'" />
                            </RouterLink>
                            <RouterLink :to="`/tax-declarations/${data.id}/edit`">
                                <Button icon="pi pi-pencil" size="small" text rounded severity="secondary" v-tooltip.top="'Edit'" />
                            </RouterLink>
                            <RouterLink :to="`/tax-declarations/${data.id}/pdf`">
                                <Button icon="pi pi-file-pdf" size="small" text rounded severity="help" v-tooltip.top="'Export PDF'" />
                            </RouterLink>
                            <Button icon="pi pi-trash" size="small" text rounded severity="danger" v-tooltip.top="'Delete'" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Pagination -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }} records
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
import { ref, reactive, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';
import axios from 'axios';

const confirm = useConfirm();
const toast = useToast();

const records = ref([]);
const loading = ref(false);
const classifications = ref([]);
const barangays = ref([]);
const pagination = ref({ total: 0, per_page: 15, current_page: 1, from: 0, to: 0 });

const filters = reactive({ search: '', status: null, classification_id: null, barangay_id: null });

const statusOptions = [
    { label: 'Draft', value: 'draft' }, { label: 'OCR Processing', value: 'ocr_processing' },
    { label: 'OCR Review', value: 'ocr_review' }, { label: 'Encoder Review', value: 'encoder_review' },
    { label: 'Assessor Verification', value: 'assessor_verification' }, { label: 'Supervisor Review', value: 'supervisor_review' },
    { label: 'Approved', value: 'approved' }, { label: 'Released', value: 'released' },
    { label: 'Archived', value: 'archived' }, { label: 'Rejected', value: 'rejected' },
];

async function loadData(page = 1) {
    loading.value = true;
    try {
        const res = await axios.get('/tax-declarations', {
            params: { ...filters, page, per_page: pagination.value.per_page },
        });
        records.value = res.data.data;
        pagination.value = {
            total: res.data.total, per_page: res.data.per_page,
            current_page: res.data.current_page, from: res.data.from, to: res.data.to,
        };
    } finally {
        loading.value = false;
    }
}

async function loadMetadata() {
    const [cls, br] = await Promise.all([
        axios.get('/settings/classifications'),
        axios.get('/settings/barangays'),
    ]);
    classifications.value = cls.data;
    barangays.value = br.data;
}

function onPage(event) {
    pagination.value.per_page = event.rows;
    loadData(event.page + 1);
}

function resetFilters() {
    Object.assign(filters, { search: '', status: null, classification_id: null, barangay_id: null });
    loadData();
}

function confirmDelete(td) {
    confirm.require({
        message: `Delete TD# ${td.td_number}? This action can be undone from the trash.`,
        header: 'Confirm Delete',
        icon: 'pi pi-trash',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            try {
                await axios.delete(`tax-declarations/${td.id}`);
                toast.success('Deleted', 'Record moved to trash.');
                await loadData(pagination.value.current_page);
            } catch (err) {
                toast.apiError(err, 'Delete failed');
            }
        },
    });
}

function formatStatus(status) {
    return status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || '';
}

function statusSeverity(status) {
    const map = {
        draft: 'secondary', ocr_processing: 'warn', ocr_review: 'warn',
        encoder_review: 'info', assessor_verification: 'info', supervisor_review: 'info',
        approved: 'success', released: 'success', archived: 'secondary',
        rejected: 'danger', returned: 'warn',
    };
    return map[status] || 'secondary';
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

onMounted(() => { loadData(); loadMetadata(); });
</script>

