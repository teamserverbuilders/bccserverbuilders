<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Form List</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Forms you created for tax declarations and field appraisals</p>
            </div>
            <RouterLink to="/form-layouts/new">
                <Button label="New form" icon="pi pi-plus" size="small" />
            </RouterLink>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <InputText v-model="search" placeholder="Search form name..." />
                <Select v-model="target" :options="targetOptions" optionLabel="label" optionValue="value" placeholder="All forms" showClear />
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <DataTable :value="filtered" :loading="loading" striped-rows class="p-datatable-sm">
                <template #empty>
                    <div class="text-center py-12">
                        <i class="pi pi-table text-4xl text-gray-300 mb-3 block"></i>
                        <p class="text-gray-500">No forms yet. Create one to start a layout.</p>
                    </div>
                </template>
                <Column field="name" header="Form name" sortable style="min-width: 220px">
                    <template #body="{ data }">
                        <RouterLink :to="`/form-layouts/${data.id}`" class="text-[#1a3557] dark:text-blue-300 hover:underline font-medium text-sm">
                            {{ data.name }}
                        </RouterLink>
                    </template>
                </Column>
                <Column field="target" header="Used for" sortable style="min-width: 160px">
                    <template #body="{ data }">
                        <Tag :value="targetLabel(data.target)" severity="secondary" class="text-xs" />
                    </template>
                </Column>
                <Column field="entries_count" header="Saved answers" sortable style="min-width: 130px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ data.entries_count || 0 }}</span>
                    </template>
                </Column>
                <Column field="updated_at" header="Updated" sortable style="min-width: 140px">
                    <template #body="{ data }">
                        <span class="text-xs text-gray-500">{{ formatDate(data.updated_at) }}</span>
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 150px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <RouterLink :to="`/form-layouts/${data.id}`">
                                <Button icon="pi pi-eye" size="small" text rounded v-tooltip.top="'View'" />
                            </RouterLink>
                            <RouterLink :to="`/form-layouts/${data.id}/edit`">
                                <Button icon="pi pi-pencil" size="small" text rounded severity="secondary" v-tooltip.top="'Edit'" />
                            </RouterLink>
                            <Button icon="pi pi-trash" size="small" text rounded severity="danger" v-tooltip.top="'Delete'" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const confirm = useConfirm();
const toast = useToast();
const records = ref([]);
const loading = ref(false);
const search = ref('');
const target = ref(null);
const targetOptions = [
    { label: 'Tax Declaration', value: 'tax_declaration' },
    { label: 'Field Appraisal', value: 'field_appraisal' },
];

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return records.value.filter((item) => {
        if (target.value && item.target !== target.value) return false;
        if (term && !String(item.name || '').toLowerCase().includes(term)) return false;
        return true;
    });
});

function targetLabel(value) {
    return value === 'field_appraisal' ? 'Field Appraisal' : 'Tax Declaration';
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

async function loadData() {
    loading.value = true;
    try {
        const { data } = await axios.get('form-layouts');
        records.value = data;
    } catch (err) {
        toast.apiError(err, 'Could not load the forms');
    } finally {
        loading.value = false;
    }
}

function confirmDelete(form) {
    confirm.require({
        message: `Delete "${form.name}"? Saved answers for this form will be removed too.`,
        header: 'Delete form',
        icon: 'pi pi-trash',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            try {
                await axios.delete(`form-layouts/${form.id}`);
                toast.success('Deleted', 'The form was removed.');
                await loadData();
            } catch (err) {
                toast.apiError(err, 'Could not delete the form');
            }
        },
    });
}

onMounted(loadData);
</script>
