<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-1 h-6 bg-[#b8860b] rounded-full"></div>
                    <h1 class="text-xl font-bold text-[#1a3557] dark:text-slate-50">Field Appraisals</h1>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 ml-3">Property inspection and valuation records</p>
            </div>
            <RouterLink to="/field-appraisals/create">
                <Button label="New Appraisal" icon="pi pi-plus" />
            </RouterLink>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-3 flex-wrap">
            <InputText v-model="search" placeholder="Search appraisal no…" class="w-60" @keyup.enter="load" />
            <Select v-model="filterStatus" :options="statusOpts" optionLabel="label" optionValue="value"
                placeholder="All Status" showClear size="small" @change="load" />
            <Button icon="pi pi-refresh" outlined size="small" @click="load" />
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <DataTable :value="items" :loading="loading" class="p-datatable-sm" striped-rows>
                <template #empty>
                    <div class="py-12 text-center">
                        <i class="pi pi-clipboard text-4xl text-slate-300 mb-3 block"></i>
                        <p class="text-slate-500">No field appraisals found</p>
                    </div>
                </template>
                <Column field="appraisal_no" header="Appraisal No." style="min-width:140px">
                    <template #body="{ data }">
                        <RouterLink :to="`/field-appraisals/${data.id}`" class="text-sm font-bold text-[#1a3557] dark:text-blue-400 hover:underline">
                            {{ data.appraisal_no }}
                        </RouterLink>
                    </template>
                </Column>
                <Column header="Linked TD" style="min-width:130px">
                    <template #body="{ data }">
                        <RouterLink v-if="data.tax_declaration" :to="`/tax-declarations/${data.tax_declaration.id}`"
                            class="text-xs text-blue-600 hover:underline">
                            {{ data.tax_declaration.td_number }}
                        </RouterLink>
                        <span v-else class="text-xs text-slate-400">—</span>
                    </template>
                </Column>
                <Column header="Inspection Date" style="min-width:120px">
                    <template #body="{ data }">
                        <span class="text-xs">{{ data.inspection_date ? new Date(data.inspection_date).toLocaleDateString() : '—' }}</span>
                    </template>
                </Column>
                <Column header="Assessor" style="min-width:130px">
                    <template #body="{ data }">
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ data.assessor?.name || '—' }}</span>
                    </template>
                </Column>
                <Column header="Market Value" style="min-width:130px">
                    <template #body="{ data }">
                        <span class="text-xs font-semibold">{{ data.computed_market_value ? '₱' + Number(data.computed_market_value).toLocaleString() : '—' }}</span>
                    </template>
                </Column>
                <Column header="Status" style="min-width:110px">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="statusSeverity(data.status)" class="text-[10px]" />
                    </template>
                </Column>
                <Column header="" style="width:170px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <RouterLink :to="`/field-appraisals/${data.id}`">
                                <Button icon="pi pi-eye" size="small" text rounded v-tooltip.top="'View'" />
                            </RouterLink>
                            <RouterLink :to="`/field-appraisals/${data.id}/edit`">
                                <Button icon="pi pi-pencil" size="small" text rounded v-tooltip.top="'Edit'" />
                            </RouterLink>
                            <RouterLink :to="`/field-appraisals/${data.id}/pdf`">
                                <Button icon="pi pi-file-pdf" size="small" text rounded severity="help" v-tooltip.top="'Export PDF'" />
                            </RouterLink>
                            <Button icon="pi pi-trash" size="small" text rounded severity="danger"
                                v-tooltip.top="'Delete'" :loading="deletingId === data.id"
                                @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                <Paginator :rows="15" :totalRecords="total" :first="(page - 1) * 15"
                    @page="e => { page = e.page + 1; load(); }"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
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

const confirm     = useConfirm();
const toast       = useToast();
const items       = ref([]);
const loading     = ref(false);
const deletingId  = ref(null);
const total       = ref(0);
const page        = ref(1);
const search      = ref('');
const filterStatus = ref(null);

const statusOpts = [
    { label: 'Draft', value: 'draft' },
    { label: 'Inspected', value: 'inspected' },
    { label: 'Computed', value: 'computed' },
    { label: 'Approved', value: 'approved' },
    { label: 'Revision', value: 'revision' },
];

function statusSeverity(s) {
    return { draft: 'secondary', inspected: 'info', computed: 'warn', approved: 'success', revision: 'danger' }[s] || 'secondary';
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('field-appraisals', {
            params: { search: search.value || undefined, status: filterStatus.value || undefined, page: page.value },
        });
        items.value = data.data;
        total.value = data.total;
    } finally { loading.value = false; }
}

function confirmDelete(row) {
    confirm.require({
        message: `Delete appraisal ${row.appraisal_no}? This cannot be undone from this page.`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => deleteAppraisal(row),
    });
}

async function deleteAppraisal(row) {
    deletingId.value = row.id;
    try {
        await axios.delete(`field-appraisals/${row.id}`);
        toast.success('Deleted', `Appraisal ${row.appraisal_no} was removed.`);
        await load();
    } catch (err) {
        toast.error('Delete failed', err?.response?.data?.message || 'Could not delete appraisal.');
    } finally {
        deletingId.value = null;
    }
}

onMounted(load);
</script>
