<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Property Owners</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Registered property owners and their linked records</p>
            </div>
            <Button label="Add Owner" icon="pi pi-plus" size="small" @click="openDialog()" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
                <InputText
                    v-model="search"
                    placeholder="Search owner, TIN, contact..."
                    class="flex-1 max-w-md"
                    @keyup.enter="loadData(1)"
                />
                <Button label="Search" icon="pi pi-search" size="small" @click="loadData(1)" />
                <Button icon="pi pi-refresh" outlined size="small" @click="resetSearch" v-tooltip="'Reset'" />
            </div>

            <DataTable :value="owners" :loading="loading" class="p-datatable-sm" striped-rows>
                <template #empty>
                    <div class="text-center py-12 text-gray-400">
                        <i class="pi pi-users text-4xl mb-3 block opacity-40"></i>
                        <p>No property owners found</p>
                    </div>
                </template>

                <Column header="Owner" style="min-width: 200px">
                    <template #body="{ data }">
                        <RouterLink :to="`/property-owners/${data.id}`" class="block">
                            <p class="font-medium text-sm text-blue-600 hover:underline">{{ data.owner_name }}</p>
                            <p class="text-xs text-gray-400">{{ data.co_owner_name || '—' }}</p>
                        </RouterLink>
                    </template>
                </Column>
                <Column field="tin" header="TIN" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.tin || '—' }}</span>
                    </template>
                </Column>
                <Column field="contact_number" header="Contact" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.contact_number || '—' }}</span>
                    </template>
                </Column>
                <Column field="address" header="Address" style="min-width: 180px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-300 truncate block max-w-[240px]">{{ data.address || '—' }}</span>
                    </template>
                </Column>
                <Column header="Tax Declarations" style="min-width: 120px">
                    <template #body="{ data }">
                        <Tag :value="String(data.tax_declarations_count ?? 0)" severity="info" class="text-xs" />
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 140px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <RouterLink :to="`/property-owners/${data.id}`">
                                <Button icon="pi pi-eye" size="small" text rounded v-tooltip="'View'" />
                            </RouterLink>
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

        <!-- Add / Edit Dialog -->
        <Dialog
            v-model:visible="showDialog"
            :header="editOwner ? 'Edit Property Owner' : 'Add Property Owner'"
            :modal="true"
            class="w-full max-w-lg"
        >
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
                    <Button type="submit" :label="editOwner ? 'Update' : 'Create'" class="flex-1" :loading="saving" />
                    <Button type="button" label="Cancel" outlined class="flex-1" @click="showDialog = false" />
                </div>
            </form>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';
import Dialog from 'primevue/dialog';
import axios from 'axios';

const confirm = useConfirm();
const toast = useToast();

const owners = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const showDialog = ref(false);
const editOwner = ref(null);
const pagination = ref({ total: 0, per_page: 15, current_page: 1, from: 0, to: 0 });

const sexOptions = ['male', 'female', 'other'];
const civilOptions = ['single', 'married', 'widowed', 'separated', 'divorced'];

const emptyForm = () => ({
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

const form = reactive(emptyForm());

async function loadData(page = 1) {
    loading.value = true;
    try {
        const res = await axios.get('/property-owners', {
            params: { search: search.value || undefined, page, per_page: pagination.value.per_page },
        });
        owners.value = res.data.data;
        pagination.value = {
            total: res.data.total,
            per_page: res.data.per_page,
            current_page: res.data.current_page,
            from: res.data.from,
            to: res.data.to,
        };
    } finally {
        loading.value = false;
    }
}

function onPage(event) {
    pagination.value.per_page = event.rows;
    loadData(event.page + 1);
}

function resetSearch() {
    search.value = '';
    loadData(1);
}

function openDialog(owner = null) {
    editOwner.value = owner;
    Object.assign(form, emptyForm(), owner ? {
        owner_name: owner.owner_name || '',
        co_owner_name: owner.co_owner_name || '',
        tin: owner.tin || '',
        contact_number: owner.contact_number || '',
        email: owner.email || '',
        citizenship: owner.citizenship || 'Filipino',
        sex: owner.sex || null,
        civil_status: owner.civil_status || null,
        address: owner.address || '',
    } : {});
    showDialog.value = true;
}

async function saveOwner() {
    if (!form.owner_name?.trim()) {
        toast.add({ severity: 'warn', summary: 'Required', detail: 'Owner name is required.' });
        return;
    }
    saving.value = true;
    try {
        const payload = { ...form };
        if (editOwner.value) {
            await axios.put(`/property-owners/${editOwner.value.id}`, payload);
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Owner updated.' });
        } else {
            await axios.post('/property-owners', payload);
            toast.add({ severity: 'success', summary: 'Created', detail: 'Owner created.' });
        }
        showDialog.value = false;
        await loadData(pagination.value.current_page);
    } catch (err) {
        toast.apiError?.(err, 'Save failed') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save owner.' });
    } finally {
        saving.value = false;
    }
}

function confirmDelete(owner) {
    confirm.require({
        message: `Delete owner "${owner.owner_name}"? Linked tax declarations will keep their data, but this owner will move to Archive.`,
        header: 'Confirm Delete',
        icon: 'pi pi-trash',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            try {
                await axios.delete(`/property-owners/${owner.id}`);
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Owner moved to archive.' });
                await loadData(pagination.value.current_page);
            } catch (err) {
                toast.apiError?.(err, 'Delete failed') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete owner.' });
            }
        },
    });
}

onMounted(() => loadData());
</script>
