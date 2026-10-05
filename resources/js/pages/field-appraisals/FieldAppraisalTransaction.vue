<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <RouterLink :to="`/field-appraisals/${route.params.id}`">
                    <button type="button" class="h-8 w-8 inline-flex items-center justify-center rounded-md border border-[#1a3557] text-[#1a3557] hover:bg-[#1a3557] hover:text-white transition-colors">
                        <i class="pi pi-arrow-left text-sm"></i>
                    </button>
                </RouterLink>
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold text-[#1a3557] dark:text-slate-50">Transaction</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        <span class="font-mono">{{ transactionCode || 'Enter a transaction code' }}</span>
                        <span v-if="appraisal?.appraisal_no"> · Appraisal #{{ appraisal.appraisal_no }}</span>
                        <span v-if="td"> · TD# {{ td.td_number }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center h-40">
            <ProgressSpinner />
        </div>

        <div v-else-if="loadError" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ loadError }}
        </div>

        <template v-else>
            <section class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <h2 class="text-sm font-bold text-[#1a3557] dark:text-slate-100">Issue new tax declaration</h2>
                </div>

                <div v-if="!td" class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">
                    This appraisal is not linked to a tax declaration. Link one on the appraisal form before issuing a new TD. The FAAS form below can still be filled.
                </div>

                <template v-else>
                    <div class="border-b border-blue-200 dark:border-blue-800 bg-blue-50/60 dark:bg-blue-900/20 px-5 py-3 text-xs text-blue-800 dark:text-blue-200">
                        <p class="flex items-start gap-2">
                            <i class="pi pi-info-circle mt-0.5"></i>
                            <span>
                                A <strong>new Tax Declaration</strong> will be issued for the new owner. The current TD
                                <strong class="font-mono">{{ td.td_number }}</strong> will be <strong>cancelled (archived)</strong>
                                and referenced as the previous TD on the new record.
                            </span>
                        </p>
                    </div>

                    <div v-if="transferBlocked" class="px-5 py-3 text-xs text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800">
                        {{ transferBlocked }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-stretch gap-0 border-b border-gray-100 dark:border-gray-700">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/40">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Cancels TD</p>
                            <p class="font-mono text-sm font-semibold text-gray-800 dark:text-white break-all">{{ td.td_number }}</p>
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ currentOwnerName }}</p>
                        </div>
                        <div class="hidden md:flex items-center justify-center px-3 text-gray-400">
                            <i class="pi pi-arrow-right text-lg"></i>
                        </div>
                        <div class="p-4 bg-emerald-50/60 dark:bg-emerald-900/20 border-l border-emerald-200 dark:border-emerald-800">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-1">Issues new TD</p>
                            <p class="font-mono text-sm font-semibold text-emerald-800 dark:text-emerald-200 break-all">
                                {{ transferForm.new_td_number || '—' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ transferForm.owner_name || 'New owner' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 dark:divide-gray-700">
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1.5">
                                    <i class="pi pi-file"></i> New Declaration
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2">
                                        <label class="form-label">Transaction code <span class="text-red-500">*</span></label>
                                        <InputText v-model="transactionCode" class="w-full font-mono" placeholder="Enter transaction code" required />
                                    </div>
                                    <div>
                                        <label class="form-label">New TD No. <span class="text-red-500">*</span></label>
                                        <InputText v-model="transferForm.new_td_number" class="w-full" placeholder="e.g. 2026-05-0001" />
                                        <p class="text-[11px] text-gray-400 mt-1">Must be unique.</p>
                                    </div>
                                    <div>
                                        <label class="form-label">New ARP No.</label>
                                        <InputText v-model="transferForm.new_arp_number" class="w-full" :placeholder="td.arp_number || 'Leave blank to reuse'" />
                                        <p class="text-[11px] text-gray-400 mt-1">Optional — reuses current if empty.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1.5">
                                    <i class="pi pi-calendar"></i> Transfer Details
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="form-label">Transfer date <span class="text-red-500">*</span></label>
                                        <DatePicker v-model="transferForm.transfer_date" class="w-full" dateFormat="yy-mm-dd" showIcon />
                                    </div>
                                    <div>
                                        <label class="form-label">Reason</label>
                                        <InputText v-model="transferForm.transfer_reason" class="w-full" placeholder="Sale, inheritance, donation…" />
                                    </div>
                                    <div class="col-span-2">
                                        <label class="form-label">Remarks</label>
                                        <Textarea v-model="transferForm.remarks" class="w-full" rows="3" autoResize />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 space-y-4 bg-gray-50/40 dark:bg-gray-900/20">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                    <i class="pi pi-user-plus"></i> New Owner
                                </p>
                                <div v-if="transferForm.owner_id" class="flex items-center gap-2">
                                    <Tag :value="`Linked #${transferForm.owner_id}`" severity="info" class="text-[10px]" />
                                    <Button label="Clear" size="small" text @click="clearTransferOwnerSelection" />
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Search existing owner</label>
                                <AutoComplete
                                    v-model="transferOwnerSearch"
                                    :suggestions="transferOwnerSuggestions"
                                    optionLabel="owner_name"
                                    placeholder="Type name to search…"
                                    class="w-full"
                                    :forceSelection="false"
                                    @complete="searchTransferOwners"
                                    @item-select="onTransferOwnerSelect"
                                />
                                <p class="text-[11px] text-gray-400 mt-1">Or enter a new owner below to create one.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="form-label">Owner name <span class="text-red-500">*</span></label>
                                    <InputText v-model="transferForm.owner_name" class="w-full" :disabled="!!transferForm.owner_id" />
                                </div>
                                <div>
                                    <label class="form-label">TIN</label>
                                    <InputText v-model="transferForm.owner_tin" class="w-full" :disabled="!!transferForm.owner_id" />
                                </div>
                                <div>
                                    <label class="form-label">Telephone</label>
                                    <InputText v-model="transferForm.owner_telephone" class="w-full" :disabled="!!transferForm.owner_id" />
                                </div>
                                <div class="col-span-2">
                                    <label class="form-label">Address</label>
                                    <Textarea v-model="transferForm.owner_address" class="w-full" rows="2" autoResize :disabled="!!transferForm.owner_id" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                        <RouterLink :to="`/field-appraisals/${route.params.id}`">
                            <Button label="Cancel" outlined size="small" />
                        </RouterLink>
                        <Button
                            label="Issue New TD & Transfer"
                            icon="pi pi-check"
                            size="small"
                            severity="success"
                            :disabled="!!transferBlocked"
                            :loading="transferLoading"
                            @click="submitTransfer"
                        />
                    </div>
                </template>
            </section>

            <section class="space-y-3">
                <div>
                    <h2 class="text-sm font-bold text-[#1a3557] dark:text-slate-100">Field Appraisal and Assessment Sheet (FAAS)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">The appraisal form is added here. Its fields are the same as the field appraisal fill-out.</p>
                </div>
                <FieldAppraisalForm embedded />
            </section>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import AutoComplete from 'primevue/autocomplete';
import DatePicker from 'primevue/datepicker';
import ProgressSpinner from 'primevue/progressspinner';
import FieldAppraisalForm from '@/pages/field-appraisals/FieldAppraisalForm.vue';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const appraisal = ref(null);
const td = ref(null);
const loading = ref(true);
const loadError = ref('');
const transferLoading = ref(false);
const transactionCode = ref('');
const transferOwnerSearch = ref('');
const transferOwnerSuggestions = ref([]);
const transferForm = ref({
    new_td_number: '',
    new_arp_number: '',
    owner_id: null,
    owner_name: '',
    owner_tin: '',
    owner_address: '',
    owner_telephone: '',
    transfer_date: new Date(),
    transfer_reason: '',
    remarks: '',
});

const currentOwnerName = computed(() => td.value?.owner?.owner_name || td.value?.owner_name || 'No owner assigned');

const transferBlocked = computed(() => {
    if (!td.value) return '';
    if (String(td.value.status || '').toLowerCase() === 'archived') {
        return 'This TD is already cancelled. Open the successor to record further transfers.';
    }
    if (td.value.is_locked && String(td.value.status || '').toLowerCase() !== 'approved') {
        return 'Unlock or return this tax declaration before transferring.';
    }
    return '';
});

function formatTransferDate(value) {
    if (!value) return null;
    if (value instanceof Date) {
        const y = value.getFullYear();
        const m = String(value.getMonth() + 1).padStart(2, '0');
        const d = String(value.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    return String(value).slice(0, 10);
}

async function searchTransferOwners(event) {
    try {
        const { data } = await axios.get('property-owners', { params: { search: event.query } });
        transferOwnerSuggestions.value = data.data || [];
    } catch {
        transferOwnerSuggestions.value = [];
    }
}

function onTransferOwnerSelect(event) {
    const owner = event.value;
    if (!owner?.id) return;
    transferForm.value.owner_id = owner.id;
    transferForm.value.owner_name = owner.owner_name || '';
    transferForm.value.owner_tin = owner.tin || '';
    transferForm.value.owner_address = owner.address || '';
    transferForm.value.owner_telephone = owner.contact_number || '';
    transferOwnerSearch.value = owner.owner_name || '';
}

function clearTransferOwnerSelection() {
    transferForm.value.owner_id = null;
    transferOwnerSearch.value = '';
}

async function submitTransfer() {
    if (!td.value || transferBlocked.value) return;
    if (!transactionCode.value.trim()) {
        toast.error('Missing transaction code', 'Enter the transaction code for this transfer.');
        return;
    }
    if (!transferForm.value.new_td_number?.trim()) {
        toast.error('Missing TD number', 'Enter the new TD number that will be issued.');
        return;
    }
    if (transferForm.value.new_td_number.trim() === (td.value?.td_number || '').trim()) {
        toast.error('Same TD number', 'New TD number must be different from the current TD.');
        return;
    }
    if (!transferForm.value.owner_id) {
        const typedName = typeof transferOwnerSearch.value === 'string'
            ? transferOwnerSearch.value.trim()
            : (transferOwnerSearch.value?.owner_name || '');
        if (!transferForm.value.owner_name?.trim() && typedName) {
            transferForm.value.owner_name = typedName;
        }
    }
    if (!transferForm.value.owner_id && !transferForm.value.owner_name?.trim()) {
        toast.error('Missing owner', 'Select an existing owner or enter a new owner name.');
        return;
    }
    if (!transferForm.value.transfer_date) {
        toast.error('Missing date', 'Transfer date is required.');
        return;
    }
    if (transferForm.value.owner_id && Number(transferForm.value.owner_id) === Number(td.value?.owner_id)) {
        toast.error('Same owner', 'Choose a different owner than the current one.');
        return;
    }

    transferLoading.value = true;
    try {
        const payload = {
            transaction_code: transactionCode.value.trim(),
            new_td_number: transferForm.value.new_td_number.trim(),
            new_arp_number: transferForm.value.new_arp_number?.trim() || undefined,
            owner_id: transferForm.value.owner_id || undefined,
            owner_name: transferForm.value.owner_name?.trim() || undefined,
            owner_tin: transferForm.value.owner_tin || undefined,
            owner_address: transferForm.value.owner_address || undefined,
            owner_telephone: transferForm.value.owner_telephone || undefined,
            transfer_date: formatTransferDate(transferForm.value.transfer_date),
            transfer_reason: transferForm.value.transfer_reason || undefined,
            remarks: transferForm.value.remarks || undefined,
        };
        const { data } = await axios.post(`tax-declarations/${td.value.id}/transfer-ownership`, payload);
        if (data?.transaction_code) transactionCode.value = data.transaction_code;
        toast.success('Transferred', `${transactionCode.value} issued new TD ${payload.new_td_number}. Old TD cancelled.`);
        const newId = data?.new_tax_declaration_id || data?.new_tax_declaration?.id;
        if (newId) router.push(`/tax-declarations/${newId}`);
    } catch (err) {
        toast.apiError(err, 'Ownership transfer failed');
    } finally {
        transferLoading.value = false;
    }
}

onMounted(async () => {
    try {
        const { data } = await axios.get(`field-appraisals/${route.params.id}`);
        appraisal.value = data;
        const tdId = data.tax_declaration_id || data.tax_declaration?.id;
        if (tdId) {
            const tdRes = await axios.get(`tax-declarations/${tdId}`);
            td.value = tdRes.data;
        }
    } catch (err) {
        loadError.value = err?.response?.data?.message || 'Could not open this transaction.';
    } finally {
        loading.value = false;
    }
});
</script>
