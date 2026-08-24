<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-[#1a3557] dark:text-white">Workflow Management</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Track declaration status and workflow activity</p>
            </div>
            <Button icon="pi pi-refresh" label="Refresh" outlined size="small" :loading="loading" @click="loadAll" />
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div v-for="col in summaryColumns" :key="col.status"
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-2 mb-1">
                    <div :class="['w-2 h-2 rounded-full', col.dot]"></div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">{{ col.label }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ counts[col.status] || 0 }}</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
            <button v-for="tab in tabs" :key="tab.id" type="button"
                @click="activeTab = tab.id"
                :class="[
                    'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px',
                    activeTab === tab.id
                        ? 'border-[#1a3557] text-[#1a3557] dark:border-blue-400 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                ]">
                <i :class="['pi mr-1.5 text-xs', tab.icon]"></i>{{ tab.label }}
            </button>
        </div>

        <!-- Kanban board -->
        <div v-show="activeTab === 'board'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-3 overflow-x-auto pb-2">
            <div v-for="col in workflowColumns" :key="col.status"
                class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 min-w-[180px] border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div :class="['w-2 h-2 rounded-full shrink-0', col.dot]"></div>
                        <h3 class="text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider truncate">{{ col.label }}</h3>
                    </div>
                    <span :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0', col.badge]">{{ counts[col.status] || 0 }}</span>
                </div>

                <div class="space-y-2 min-h-[80px] max-h-[420px] overflow-y-auto">
                    <div v-for="td in byStatus[col.status]?.slice(0, 8)" :key="td.id"
                        class="bg-white dark:bg-gray-800 rounded-lg p-2.5 shadow-sm border border-gray-100 dark:border-gray-600 hover:shadow-md transition-shadow">
                        <RouterLink :to="`/tax-declarations/${td.id}`" class="block">
                            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">{{ td.td_number }}</p>
                            <p class="text-[11px] text-gray-600 dark:text-gray-400 truncate mt-0.5">{{ td.owner?.owner_name || '—' }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ td.barangay?.name || td.classification?.name || '—' }}</p>
                        </RouterLink>
                    </div>
                    <RouterLink v-if="(counts[col.status] || 0) > 8"
                        :to="`/tax-declarations?status=${col.status}`"
                        class="block text-center text-[11px] text-blue-600 hover:underline py-1">
                        +{{ counts[col.status] - 8 }} more
                    </RouterLink>
                    <p v-if="!(byStatus[col.status]?.length)" class="text-[11px] text-gray-400 text-center py-4">Empty</p>
                </div>
            </div>
        </div>

        <!-- Pending actions -->
        <div v-show="activeTab === 'board'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="pi pi-clock text-amber-500"></i> Records Requiring Action
                </h3>
            </div>
            <DataTable :value="pendingRecords" :loading="loading" class="p-datatable-sm" striped-rows paginator :rows="10">
                <template #empty>
                    <div class="text-center py-8 text-gray-400 text-sm">No pending records</div>
                </template>
                <Column field="td_number" header="TD Number">
                    <template #body="{ data }">
                        <RouterLink :to="`/tax-declarations/${data.id}`" class="text-blue-600 hover:underline font-medium text-sm">{{ data.td_number }}</RouterLink>
                    </template>
                </Column>
                <Column field="owner.owner_name" header="Owner" />
                <Column field="status" header="Status">
                    <template #body="{ data }">
                        <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" class="text-xs" />
                    </template>
                </Column>
                <Column header="Days in Status">
                    <template #body="{ data }">
                        <span :class="['text-xs font-medium', daysSince(data.updated_at) > 3 ? 'text-red-500' : 'text-green-600']">
                            {{ daysSince(data.updated_at) }} day(s)
                        </span>
                    </template>
                </Column>
                <Column header="Actions">
                    <template #body="{ data }">
                        <RouterLink :to="`/tax-declarations/${data.id}`">
                            <Button label="Review" icon="pi pi-eye" size="small" outlined />
                        </RouterLink>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Workflow history -->
        <div v-show="activeTab === 'history'" class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <InputText v-model="historyFilters.search" placeholder="Search TD# or owner…" @keyup.enter="loadHistory(1)" />
                    <Select v-model="historyFilters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" showClear />
                    <Select v-model="historyFilters.action" :options="actionOptions" optionLabel="label" optionValue="value" placeholder="All actions" showClear />
                    <Button label="Filter" icon="pi pi-filter" size="small" @click="loadHistory(1)" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <DataTable :value="historyRecords" :loading="historyLoading" class="p-datatable-sm" striped-rows>
                    <template #empty>
                        <div class="text-center py-10">
                            <i class="pi pi-history text-3xl text-gray-300 mb-2 block"></i>
                            <p class="text-gray-500 text-sm">No workflow activity yet</p>
                        </div>
                    </template>
                    <Column header="Date" style="min-width: 140px">
                        <template #body="{ data }">
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ formatDateTime(data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column header="TD Number" style="min-width: 130px">
                        <template #body="{ data }">
                            <RouterLink v-if="data.tax_declaration"
                                :to="`/tax-declarations/${data.tax_declaration.id}`"
                                class="text-blue-600 hover:underline font-medium text-sm">
                                {{ data.tax_declaration.td_number }}
                            </RouterLink>
                            <span v-else class="text-gray-400">—</span>
                        </template>
                    </Column>
                    <Column header="Owner" style="min-width: 160px">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.tax_declaration?.owner?.owner_name || '—' }}</span>
                        </template>
                    </Column>
                    <Column header="From" style="min-width: 120px">
                        <template #body="{ data }">
                            <Tag v-if="data.from_status" :value="formatStatus(data.from_status)" severity="secondary" class="text-xs" />
                            <span v-else class="text-xs text-gray-400">—</span>
                        </template>
                    </Column>
                    <Column header="To" style="min-width: 120px">
                        <template #body="{ data }">
                            <Tag :value="formatStatus(data.to_status)" :severity="statusSeverity(data.to_status)" class="text-xs" />
                        </template>
                    </Column>
                    <Column header="Action" style="min-width: 100px">
                        <template #body="{ data }">
                            <Tag :value="data.action" :severity="actionSeverity(data.action)" class="text-xs capitalize" />
                        </template>
                    </Column>
                    <Column header="Performed By" style="min-width: 130px">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.performed_by?.name || 'System' }}</span>
                        </template>
                    </Column>
                    <Column header="Remarks" style="min-width: 180px">
                        <template #body="{ data }">
                            <span class="text-xs text-gray-500">{{ data.remarks || '—' }}</span>
                        </template>
                    </Column>
                </DataTable>

                <div v-if="historyPagination.total > historyPagination.per_page" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-500">
                        Showing {{ historyPagination.from }}–{{ historyPagination.to }} of {{ historyPagination.total }}
                    </span>
                    <div class="flex gap-2">
                        <Button icon="pi pi-chevron-left" size="small" outlined :disabled="historyPagination.current_page <= 1" @click="loadHistory(historyPagination.current_page - 1)" />
                        <Button icon="pi pi-chevron-right" size="small" outlined :disabled="historyPagination.current_page >= lastHistoryPage" @click="loadHistory(historyPagination.current_page + 1)" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import axios from 'axios';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const activeTab = ref('board');
const tabs = [
    { id: 'board', label: 'Status Board', icon: 'pi-sitemap' },
    { id: 'history', label: 'Activity Log', icon: 'pi-history' },
];

const allRecords = ref([]);
const counts = ref({});
const loading = ref(false);

const historyRecords = ref([]);
const historyLoading = ref(false);
const historyPagination = ref({ total: 0, per_page: 20, current_page: 1, from: 0, to: 0 });
const historyFilters = reactive({ search: '', status: null, action: null });

const workflowColumns = [
    { status: 'draft', label: 'Draft', dot: 'bg-gray-400', badge: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' },
    { status: 'ocr_processing', label: 'OCR Processing', dot: 'bg-orange-400', badge: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' },
    { status: 'ocr_review', label: 'OCR Review', dot: 'bg-yellow-400', badge: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' },
    { status: 'encoder_review', label: 'Encoder Review', dot: 'bg-blue-400', badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' },
    { status: 'assessor_verification', label: 'Assessor', dot: 'bg-purple-400', badge: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' },
    { status: 'supervisor_review', label: 'Supervisor', dot: 'bg-indigo-400', badge: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' },
    { status: 'approved', label: 'Approved', dot: 'bg-green-400', badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
    { status: 'released', label: 'Released', dot: 'bg-teal-400', badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300' },
    { status: 'returned', label: 'Returned', dot: 'bg-amber-400', badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
    { status: 'rejected', label: 'Rejected', dot: 'bg-red-400', badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
    { status: 'archived', label: 'Archived', dot: 'bg-slate-400', badge: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' },
];

const summaryColumns = computed(() => workflowColumns.slice(0, 6));

const statusOptions = workflowColumns.map(c => ({ label: c.label, value: c.status }));

const actionOptions = [
    { label: 'Submit', value: 'submit' },
    { label: 'Approve', value: 'approve' },
    { label: 'Reject', value: 'reject' },
    { label: 'Return', value: 'return' },
    { label: 'Archive', value: 'archive' },
    { label: 'Release', value: 'release' },
];

const byStatus = computed(() => {
    const map = {};
    allRecords.value.forEach(r => {
        if (!map[r.status]) map[r.status] = [];
        map[r.status].push(r);
    });
    return map;
});

const pendingRecords = computed(() =>
    allRecords.value.filter(r =>
        ['ocr_processing', 'ocr_review', 'encoder_review', 'assessor_verification', 'supervisor_review', 'returned'].includes(r.status)
    )
);

const lastHistoryPage = computed(() =>
    Math.max(1, Math.ceil(historyPagination.value.total / historyPagination.value.per_page))
);

function formatStatus(status) {
    return status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || '—';
}

function statusSeverity(s) {
    return {
        draft: 'secondary', approved: 'success', released: 'success',
        rejected: 'danger', returned: 'warn', archived: 'secondary',
        ocr_processing: 'warn', ocr_review: 'warn',
    }[s] || 'info';
}

function actionSeverity(a) {
    return { approve: 'success', reject: 'danger', return: 'warn', archive: 'secondary', release: 'info' }[a] || 'info';
}

function daysSince(date) {
    if (!date) return 0;
    return Math.floor((Date.now() - new Date(date)) / 86400000);
}

function formatDateTime(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

async function loadBoard() {
    loading.value = true;
    try {
        const { data } = await axios.get('workflow/board');
        allRecords.value = data.records ?? [];
        counts.value = data.counts ?? {};
    } catch (err) {
        toast.apiError(err, 'Failed to load workflow board');
    } finally {
        loading.value = false;
    }
}

async function loadHistory(page = 1) {
    historyLoading.value = true;
    try {
        const { data } = await axios.get('workflow/history', {
            params: { ...historyFilters, page, per_page: historyPagination.value.per_page },
        });
        historyRecords.value = data.data ?? [];
        historyPagination.value = {
            total: data.total ?? 0,
            per_page: data.per_page ?? 20,
            current_page: data.current_page ?? page,
            from: data.from ?? 0,
            to: data.to ?? 0,
        };
    } catch (err) {
        toast.apiError(err, 'Failed to load workflow history');
    } finally {
        historyLoading.value = false;
    }
}

async function loadAll() {
    await Promise.all([loadBoard(), loadHistory(historyPagination.value.current_page)]);
    toast.success('Refreshed', 'Workflow data updated.');
}

onMounted(() => {
    loadBoard();
    loadHistory();
});
</script>
