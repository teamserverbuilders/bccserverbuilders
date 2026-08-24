<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Ownership History</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Track every ownership transfer — cancelled TD to newly issued TD
                </p>
            </div>
            <Button icon="pi pi-refresh" label="Refresh" outlined size="small" :loading="loading" @click="load(1)" />
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto_auto] gap-3 items-end">
                <div>
                    <label class="form-label">Search</label>
                    <InputText
                        v-model="filters.search"
                        class="w-full"
                        placeholder="TD number, owner name, reason…"
                        @keyup.enter="load(1)"
                    />
                </div>
                <div>
                    <label class="form-label">From</label>
                    <DatePicker v-model="filters.date_from" class="w-full" dateFormat="yy-mm-dd" showIcon showClear />
                </div>
                <div>
                    <label class="form-label">To</label>
                    <DatePicker v-model="filters.date_to" class="w-full" dateFormat="yy-mm-dd" showIcon showClear />
                </div>
                <div class="flex gap-2">
                    <Button label="Search" icon="pi pi-search" @click="load(1)" :loading="loading" />
                    <Button icon="pi pi-times" outlined v-tooltip="'Clear filters'" @click="clearFilters" />
                </div>
            </div>
        </div>

        <!-- Summary strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Transfers found</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ pagination.total }}</p>
            </div>
            <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-4 py-3 sm:col-span-2 flex items-center gap-3 text-xs text-gray-500">
                <i class="pi pi-info-circle text-[#1a3557]"></i>
                <span>
                    Each row is one transfer of ownership. The old TD was cancelled; a new TD was issued to the new owner.
                </span>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <DataTable
                :value="rows"
                :loading="loading"
                class="p-datatable-sm"
                striped-rows
                paginator
                lazy
                :rows="pagination.per_page"
                :totalRecords="pagination.total"
                :first="(pagination.current_page - 1) * pagination.per_page"
                @page="onPage"
            >
                <template #empty>
                    <div class="text-center py-14 text-gray-400">
                        <i class="pi pi-history text-4xl mb-3 block opacity-40"></i>
                        <p class="text-sm font-medium text-gray-500">No ownership transfers yet</p>
                        <p class="text-xs mt-1">Transfers appear here after you use Transfer Ownership on a tax declaration.</p>
                    </div>
                </template>

                <Column header="Transfer date" style="min-width: 120px" sortable>
                    <template #body="{ data }">
                        <span class="text-sm font-medium text-gray-800 dark:text-white">{{ formatDate(data.transfer_date) }}</span>
                        <p class="text-[11px] text-gray-400">
                            {{ formatDate(data.effective_from) }} → {{ formatDate(data.effective_to || data.transfer_date) }}
                        </p>
                    </template>
                </Column>

                <Column header="Previous owner" style="min-width: 160px">
                    <template #body="{ data }">
                        <RouterLink
                            v-if="data.owner_id"
                            :to="`/property-owners/${data.owner_id}`"
                            class="text-sm font-medium text-[#1a3557] dark:text-blue-300 hover:underline"
                        >
                            {{ data.owner_name }}
                        </RouterLink>
                        <span v-else class="text-sm font-medium text-gray-800 dark:text-white">{{ data.owner_name || '—' }}</span>
                        <p v-if="data.owner_tin" class="text-[11px] text-gray-400">TIN {{ data.owner_tin }}</p>
                    </template>
                </Column>

                <Column header="Cancelled TD" style="min-width: 140px">
                    <template #body="{ data }">
                        <RouterLink
                            v-if="data.tax_declaration_id || data.taxDeclaration?.id"
                            :to="`/tax-declarations/${data.tax_declaration_id || data.taxDeclaration?.id}`"
                            class="font-mono text-sm text-amber-700 dark:text-amber-300 hover:underline"
                        >
                            {{ data.taxDeclaration?.td_number || data.tax_declaration?.td_number || data.previous_td_number || '—' }}
                        </RouterLink>
                        <span v-else class="font-mono text-sm text-gray-600">{{ data.previous_td_number || '—' }}</span>
                        <Tag value="Cancelled" severity="warn" class="text-[10px] mt-1" />
                    </template>
                </Column>

                <Column header="" style="width: 3rem">
                    <template #body>
                        <i class="pi pi-arrow-right text-gray-300"></i>
                    </template>
                </Column>

                <Column header="New TD" style="min-width: 140px">
                    <template #body="{ data }">
                        <RouterLink
                            v-if="data.new_tax_declaration_id || data.newTaxDeclaration?.id || data.new_tax_declaration?.id"
                            :to="`/tax-declarations/${data.new_tax_declaration_id || data.newTaxDeclaration?.id || data.new_tax_declaration?.id}`"
                            class="font-mono text-sm text-emerald-700 dark:text-emerald-300 hover:underline"
                        >
                            {{ data.new_td_number || data.newTaxDeclaration?.td_number || data.new_tax_declaration?.td_number || '—' }}
                        </RouterLink>
                        <span v-else class="font-mono text-sm text-gray-600">{{ data.new_td_number || '—' }}</span>
                        <p class="text-[11px] text-gray-400 truncate">
                            {{
                                data.newTaxDeclaration?.owner?.owner_name
                                || data.new_tax_declaration?.owner?.owner_name
                                || 'New owner'
                            }}
                        </p>
                    </template>
                </Column>

                <Column header="Reason" style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ data.transfer_reason || '—' }}</span>
                        <p v-if="data.remarks" class="text-[11px] text-gray-400 truncate max-w-[180px]" :title="data.remarks">{{ data.remarks }}</p>
                    </template>
                </Column>

                <Column header="Recorded by" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ data.transferred_by?.name || data.transferredBy?.name || 'System' }}
                        </span>
                    </template>
                </Column>

                <Column header="" style="width: 5rem">
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-eye"
                            size="small"
                            text
                            rounded
                            v-tooltip="'View details'"
                            @click="openDetail(data)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Detail dialog -->
        <Dialog
            v-model:visible="showDetail"
            header="Transfer details"
            :modal="true"
            class="w-full max-w-2xl"
            :contentStyle="{ padding: '0' }"
        >
            <div v-if="detail" class="divide-y divide-gray-100 dark:divide-gray-700">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-0">
                    <div class="p-5 bg-amber-50/50 dark:bg-amber-900/10">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300 mb-2">Cancelled TD</p>
                        <p class="font-mono text-lg font-semibold text-gray-900 dark:text-white">
                            {{ detail.taxDeclaration?.td_number || detail.previous_td_number || '—' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ detail.owner_name }}</p>
                        <p v-if="detail.previous_av" class="text-xs text-gray-400 mt-1">
                            Previous A.V. ₱{{ Number(detail.previous_av).toLocaleString() }}
                        </p>
                        <RouterLink
                            v-if="detail.tax_declaration_id"
                            :to="`/tax-declarations/${detail.tax_declaration_id}`"
                            class="inline-flex text-xs text-[#1a3557] dark:text-blue-400 hover:underline mt-3"
                            @click="showDetail = false"
                        >
                            Open cancelled TD →
                        </RouterLink>
                    </div>
                    <div class="p-5 bg-emerald-50/50 dark:bg-emerald-900/10">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-2">Issued new TD</p>
                        <p class="font-mono text-lg font-semibold text-gray-900 dark:text-white">
                            {{ detail.new_td_number || detail.newTaxDeclaration?.td_number || '—' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            {{ detail.newTaxDeclaration?.owner?.owner_name || 'New owner' }}
                        </p>
                        <p v-if="detail.new_arp_number" class="text-xs text-gray-400 mt-1">ARP {{ detail.new_arp_number }}</p>
                        <RouterLink
                            v-if="detail.new_tax_declaration_id"
                            :to="`/tax-declarations/${detail.new_tax_declaration_id}`"
                            class="inline-flex text-xs text-[#1a3557] dark:text-blue-400 hover:underline mt-3"
                            @click="showDetail = false"
                        >
                            Open new TD →
                        </RouterLink>
                    </div>
                </div>

                <div class="p-5 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Transfer date</p>
                        <p class="text-gray-800 dark:text-white mt-0.5">{{ formatDate(detail.transfer_date) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Effective period</p>
                        <p class="text-gray-800 dark:text-white mt-0.5">
                            {{ formatDate(detail.effective_from) }} – {{ formatDate(detail.effective_to || detail.transfer_date) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Reason</p>
                        <p class="text-gray-800 dark:text-white mt-0.5">{{ detail.transfer_reason || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Recorded by</p>
                        <p class="text-gray-800 dark:text-white mt-0.5">
                            {{ detail.transferredBy?.name || detail.transferred_by?.name || 'System' }}
                        </p>
                    </div>
                    <div class="col-span-2" v-if="detail.remarks">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Remarks</p>
                        <p class="text-gray-800 dark:text-white mt-0.5">{{ detail.remarks }}</p>
                    </div>
                    <div class="col-span-2" v-if="detail.owner_address">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Previous owner address</p>
                        <p class="text-gray-800 dark:text-white mt-0.5">{{ detail.owner_address }}</p>
                    </div>
                </div>
            </div>
            <div v-else class="p-10 text-center">
                <ProgressSpinner style="width: 2rem; height: 2rem" />
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DatePicker from 'primevue/datepicker';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import axios from 'axios';

const toast = useToast();
const loading = ref(false);
const rows = ref([]);
const showDetail = ref(false);
const detail = ref(null);

const filters = reactive({
    search: '',
    date_from: null,
    date_to: null,
});

const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0,
});

function formatDate(value) {
    if (!value) return '—';
    const d = typeof value === 'string' ? value.slice(0, 10) : value;
    try {
        return new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch {
        return String(value).slice(0, 10);
    }
}

function toYmd(value) {
    if (!value) return undefined;
    if (value instanceof Date) {
        const y = value.getFullYear();
        const m = String(value.getMonth() + 1).padStart(2, '0');
        const d = String(value.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    return String(value).slice(0, 10);
}

async function load(page = 1) {
    loading.value = true;
    try {
        const { data } = await axios.get('ownership-history', {
            params: {
                search: filters.search || undefined,
                date_from: toYmd(filters.date_from),
                date_to: toYmd(filters.date_to),
                page,
                per_page: pagination.per_page,
            },
        });
        rows.value = data.data || [];
        pagination.current_page = data.current_page || 1;
        pagination.per_page = data.per_page || 15;
        pagination.total = data.total || 0;
    } catch (err) {
        toast.apiError?.(err, 'Failed to load ownership history');
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

function onPage(event) {
    const page = Math.floor(event.first / event.rows) + 1;
    pagination.per_page = event.rows;
    load(page);
}

function clearFilters() {
    filters.search = '';
    filters.date_from = null;
    filters.date_to = null;
    load(1);
}

async function openDetail(row) {
    showDetail.value = true;
    detail.value = row;
    try {
        const { data } = await axios.get(`ownership-history/${row.id}`);
        detail.value = data;
    } catch {
        // keep list row data as fallback
    }
}

onMounted(() => load(1));
</script>
