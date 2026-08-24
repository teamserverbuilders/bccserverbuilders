<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <RouterLink to="/property-owners">
                    <Button icon="pi pi-arrow-left" rounded outlined size="small" />
                </RouterLink>
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">
                        {{ owner?.owner_name || 'Property Owner' }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Owner profile with Tax Declaration and Field Appraisal forms
                    </p>
                </div>
            </div>
            <div v-if="owner" class="flex items-center gap-2 shrink-0">
                <Button label="Edit" icon="pi pi-pencil" outlined size="small" @click="openEdit" />
                <Button label="Delete" icon="pi pi-trash" severity="danger" outlined size="small" @click="confirmDelete" />
            </div>
        </div>

        <div v-if="loading" class="flex justify-center py-20">
            <ProgressSpinner />
        </div>

        <template v-else-if="owner">
            <!-- Owner summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <i class="pi pi-user text-[#1a3557]"></i>
                    <h2 class="font-semibold text-gray-800 dark:text-white">Owner Information</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Full Name</p>
                        <p class="font-medium text-gray-800 dark:text-white">{{ owner.owner_name || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">TIN</p>
                        <p class="text-gray-700 dark:text-gray-300">{{ owner.tin || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Contact</p>
                        <p class="text-gray-700 dark:text-gray-300">{{ owner.contact_number || '—' }}</p>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Address</p>
                        <p class="text-gray-700 dark:text-gray-300">{{ owner.address || '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Section tabs -->
            <div class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700">
                <button
                    v-for="tab in mainTabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    :class="[
                        'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px flex items-center gap-2',
                        activeTab === tab.id
                            ? 'border-[#1a3557] text-[#1a3557] dark:border-blue-400 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    ]"
                >
                    <i :class="['pi text-xs', tab.icon]"></i>
                    {{ tab.label }}
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700">{{ tab.count }}</span>
                </button>
            </div>

            <!-- Tax Declaration forms -->
            <div v-show="activeTab === 'td'" class="space-y-4">
                <div v-if="!taxDeclarations.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 py-12 text-center text-gray-400 text-sm">
                    No tax declarations linked to this owner
                </div>
                <template v-else>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="td in taxDeclarations"
                            :key="td.id"
                            type="button"
                            @click="selectTd(td.id)"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors',
                                selectedTdId === td.id
                                    ? 'bg-[#1a3557] text-white border-[#1a3557]'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:border-[#1a3557]'
                            ]"
                        >
                            {{ td.td_number }}
                        </button>
                        <RouterLink
                            v-if="selectedTdId"
                            :to="`/tax-declarations/${selectedTdId}`"
                            class="ml-auto"
                        >
                            <Button label="Open full page" icon="pi pi-external-link" outlined size="small" />
                        </RouterLink>
                    </div>

                    <div v-if="sheetLoading === 'td'" class="flex justify-center py-16">
                        <ProgressSpinner />
                    </div>
                    <TaxDeclarationSheet v-else-if="selectedTd" :td="selectedTd" :classifications="classifications" />
                </template>
            </div>

            <!-- Field Appraisal forms -->
            <div v-show="activeTab === 'fa'" class="space-y-4">
                <div v-if="!fieldAppraisals.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 py-12 text-center text-gray-400 text-sm">
                    No field appraisals linked to this owner
                </div>
                <template v-else>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="fa in fieldAppraisals"
                            :key="fa.id"
                            type="button"
                            @click="selectFa(fa.id)"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors',
                                selectedFaId === fa.id
                                    ? 'bg-[#1a3557] text-white border-[#1a3557]'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:border-[#1a3557]'
                            ]"
                        >
                            {{ fa.appraisal_no || `#${fa.id}` }}
                        </button>
                        <RouterLink
                            v-if="selectedFaId"
                            :to="`/field-appraisals/${selectedFaId}`"
                            class="ml-auto"
                        >
                            <Button label="Open full page" icon="pi pi-external-link" outlined size="small" />
                        </RouterLink>
                    </div>

                    <div v-if="sheetLoading === 'fa'" class="flex justify-center py-16">
                        <ProgressSpinner />
                    </div>
                    <FieldAppraisalSheet v-else-if="selectedFa" :appraisal="selectedFa" />
                </template>
            </div>

            <!-- Previously owned tax declarations -->
            <div v-show="activeTab === 'previous'" class="space-y-3">
                <div v-if="!previouslyOwned.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 py-12 text-center text-gray-400 text-sm">
                    No previously owned tax declarations for this owner
                </div>
                <div
                    v-for="row in previouslyOwned"
                    :key="row.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">TD# {{ row.td_number }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Owned until {{ formatDate(row.effective_to || row.transfer_date) }}
                            <span v-if="row.transfer_reason"> · {{ row.transfer_reason }}</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ [row.barangay?.name, row.municipality?.name, row.classification?.name].filter(Boolean).join(' · ') || '—' }}
                        </p>
                    </div>
                    <RouterLink :to="`/tax-declarations/${row.tax_declaration_id}`" class="shrink-0">
                        <Button label="Open TD" icon="pi pi-external-link" outlined size="small" />
                    </RouterLink>
                </div>
            </div>
        </template>

        <div v-else class="text-center py-16 text-gray-400">
            <i class="pi pi-exclamation-circle text-4xl mb-3 block opacity-40"></i>
            <p>Owner not found</p>
            <RouterLink to="/property-owners" class="inline-block mt-3">
                <Button label="Back to Owners" outlined size="small" />
            </RouterLink>
        </div>

        <!-- Edit Dialog -->
        <Dialog v-model:visible="showEdit" header="Edit Property Owner" :modal="true" class="w-full max-w-lg">
            <form @submit.prevent="saveOwner" class="space-y-4 pt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="form-label">Owner Name <span class="text-red-500">*</span></label>
                        <InputText v-model="form.owner_name" class="w-full" required />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Co-Owner</label>
                        <InputText v-model="form.co_owner_name" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">TIN</label>
                        <InputText v-model="form.tin" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Contact Number</label>
                        <InputText v-model="form.contact_number" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Citizenship</label>
                        <InputText v-model="form.citizenship" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Sex</label>
                        <Select v-model="form.sex" :options="sexOptions" class="w-full" showClear placeholder="Select" />
                    </div>
                    <div>
                        <label class="form-label">Civil Status</label>
                        <Select v-model="form.civil_status" :options="civilOptions" class="w-full" showClear placeholder="Select" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Address</label>
                        <Textarea v-model="form.address" class="w-full" rows="3" autoResize />
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <Button type="submit" label="Update" class="flex-1" :loading="saving" />
                    <Button type="button" label="Cancel" outlined class="flex-1" @click="showEdit = false" />
                </div>
            </form>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import TaxDeclarationSheet from '@/components/TaxDeclarationSheet.vue';
import FieldAppraisalSheet from '@/components/FieldAppraisalSheet.vue';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const confirm = useConfirm();
const toast = useToast();

const owner = ref(null);
const loading = ref(true);
const showEdit = ref(false);
const saving = ref(false);
const activeTab = ref('td');
const selectedTdId = ref(null);
const selectedFaId = ref(null);
const selectedTd = ref(null);
const selectedFa = ref(null);
const sheetLoading = ref(null);
const classifications = ref([]);

const sexOptions = ['male', 'female', 'other'];
const civilOptions = ['single', 'married', 'widowed', 'separated', 'divorced'];

const form = reactive({
    owner_name: '',
    co_owner_name: '',
    tin: '',
    contact_number: '',
    email: '',
    citizenship: 'Filipino',
    sex: null,
    civil_status: null,
    address: '',
});

const taxDeclarations = computed(() => owner.value?.tax_declarations || []);
const fieldAppraisals = computed(() => owner.value?.field_appraisals || []);
const previouslyOwned = computed(() => owner.value?.previously_owned_declarations || []);

const mainTabs = computed(() => [
    { id: 'td', label: 'Tax Declarations', icon: 'pi-file-edit', count: taxDeclarations.value.length },
    { id: 'fa', label: 'Field Appraisals', icon: 'pi-clipboard', count: fieldAppraisals.value.length },
    { id: 'previous', label: 'Previously Owned', icon: 'pi-history', count: previouslyOwned.value.length },
]);

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
}

async function loadOwner() {
    loading.value = true;
    try {
        const res = await axios.get(`/property-owners/${route.params.id}`);
        owner.value = res.data;
        if (taxDeclarations.value.length) {
            activeTab.value = 'td';
            await selectTd(taxDeclarations.value[0].id);
        } else if (fieldAppraisals.value.length) {
            activeTab.value = 'fa';
            await selectFa(fieldAppraisals.value[0].id);
        }
    } catch (err) {
        owner.value = null;
        toast.apiError?.(err, 'Failed to load owner') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load owner.' });
    } finally {
        loading.value = false;
    }
}

async function selectTd(id) {
    selectedTdId.value = id;
    sheetLoading.value = 'td';
    try {
        const res = await axios.get(`/tax-declarations/${id}`);
        selectedTd.value = res.data;
    } catch (err) {
        selectedTd.value = null;
        toast.apiError?.(err, 'Failed to load tax declaration');
    } finally {
        sheetLoading.value = null;
    }
}

async function selectFa(id) {
    selectedFaId.value = id;
    sheetLoading.value = 'fa';
    try {
        const res = await axios.get(`/field-appraisals/${id}`);
        selectedFa.value = res.data;
    } catch (err) {
        selectedFa.value = null;
        toast.apiError?.(err, 'Failed to load field appraisal');
    } finally {
        sheetLoading.value = null;
    }
}

watch(activeTab, async (tab) => {
    if (tab === 'td' && taxDeclarations.value.length && !selectedTd.value) {
        await selectTd(taxDeclarations.value[0].id);
    }
    if (tab === 'fa' && fieldAppraisals.value.length && !selectedFa.value) {
        await selectFa(fieldAppraisals.value[0].id);
    }
});

function openEdit() {
    if (!owner.value) return;
    Object.assign(form, {
        owner_name: owner.value.owner_name || '',
        co_owner_name: owner.value.co_owner_name || '',
        tin: owner.value.tin || '',
        contact_number: owner.value.contact_number || '',
        email: owner.value.email || '',
        citizenship: owner.value.citizenship || 'Filipino',
        sex: owner.value.sex || null,
        civil_status: owner.value.civil_status || null,
        address: owner.value.address || '',
    });
    showEdit.value = true;
}

async function saveOwner() {
    if (!form.owner_name?.trim()) {
        toast.add({ severity: 'warn', summary: 'Required', detail: 'Owner name is required.' });
        return;
    }
    saving.value = true;
    try {
        await axios.put(`/property-owners/${route.params.id}`, { ...form });
        toast.add({ severity: 'success', summary: 'Updated', detail: 'Owner updated.' });
        showEdit.value = false;
        await loadOwner();
    } catch (err) {
        toast.apiError?.(err, 'Update failed') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update owner.' });
    } finally {
        saving.value = false;
    }
}

function confirmDelete() {
    if (!owner.value) return;
    confirm.require({
        message: `Delete owner "${owner.value.owner_name}"? This owner will move to Archive.`,
        header: 'Confirm Delete',
        icon: 'pi pi-trash',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            try {
                await axios.delete(`/property-owners/${route.params.id}`);
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Owner moved to archive.' });
                router.push('/property-owners');
            } catch (err) {
                toast.apiError?.(err, 'Delete failed') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete owner.' });
            }
        },
    });
}

onMounted(async () => {
    try {
        const cls = await axios.get('/settings/classifications');
        classifications.value = cls.data;
    } catch {
        classifications.value = [];
    }
    await loadOwner();
});
</script>
