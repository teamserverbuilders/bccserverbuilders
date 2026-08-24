<template>
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Supporting Documents</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Upload and manage property documents linked to tax declarations
                </p>
            </div>
            <Button label="Upload Document" icon="pi pi-upload" size="small" @click="openUpload()" />
        </div>

        <!-- Type summary -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <button
                v-for="card in typeCards"
                :key="card.value ?? 'all'"
                type="button"
                class="text-left bg-white dark:bg-gray-800 rounded-xl p-3.5 shadow-sm border transition-all"
                :class="[
                    card.filterable ? 'hover:shadow-md cursor-pointer' : 'cursor-default',
                    isCardActive(card)
                        ? 'border-blue-400 ring-2 ring-blue-100 dark:ring-blue-900/40'
                        : 'border-gray-100 dark:border-gray-700',
                ]"
                @click="card.filterable && toggleTypeFilter(card.value)"
            >
                <div :class="['w-9 h-9 rounded-lg flex items-center justify-center mb-2', card.bg]">
                    <i :class="['pi text-base', card.icon, card.color]"></i>
                </div>
                <p class="text-lg font-bold text-gray-800 dark:text-white leading-none">{{ card.count }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ card.label }}</p>
            </button>
        </div>

        <!-- Filters + table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
                <InputText
                    v-model="filters.search"
                    placeholder="Search title, TD#, owner, file name..."
                    class="flex-1 min-w-0"
                    @keyup.enter="loadData(1)"
                />
                <Select
                    v-model="filters.document_type"
                    :options="documentTypeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All types"
                    showClear
                    class="w-full lg:w-52"
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

            <DataTable :value="documents" :loading="loading" class="p-datatable-sm" striped-rows>
                <template #empty>
                    <div class="text-center py-14 text-gray-400">
                        <i class="pi pi-folder-open text-5xl mb-3 block opacity-30"></i>
                        <p class="font-medium text-gray-500 dark:text-gray-400">No documents found</p>
                        <p class="text-sm mt-1 mb-4">Link a file to a tax declaration to get started</p>
                        <Button label="Upload Document" icon="pi pi-upload" size="small" outlined @click="openUpload()" />
                    </div>
                </template>

                <Column header="Document" style="min-width: 220px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3 py-0.5">
                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center shrink-0', fileIconBg(data)]">
                                <i :class="['pi text-lg', fileIcon(data)]"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ data.title }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ data.file_name }} · {{ formatFileSize(data.file_size) }}</p>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="TD Number" style="min-width: 130px">
                    <template #body="{ data }">
                        <RouterLink
                            v-if="data.tax_declaration"
                            :to="`/tax-declarations/${data.tax_declaration.id}`"
                            class="text-sm font-medium text-blue-600 hover:underline"
                        >
                            {{ data.tax_declaration.td_number }}
                        </RouterLink>
                        <span v-else class="text-sm text-gray-400">—</span>
                    </template>
                </Column>

                <Column header="Owner" style="min-width: 150px">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ data.tax_declaration?.owner?.owner_name || '—' }}
                        </span>
                    </template>
                </Column>

                <Column header="Type" style="min-width: 140px">
                    <template #body="{ data }">
                        <Tag :value="typeLabel(data.document_type)" :severity="typeSeverity(data.document_type)" class="text-xs" />
                    </template>
                </Column>

                <Column header="Uploaded" style="min-width: 140px">
                    <template #body="{ data }">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ formatDate(data.created_at) }}</p>
                        <p class="text-xs text-gray-400">{{ data.uploaded_by?.name || '—' }}</p>
                    </template>
                </Column>

                <Column header="Actions" style="min-width: 110px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Button icon="pi pi-download" size="small" text rounded v-tooltip="'Download'" @click="downloadDoc(data)" />
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
                    :rows="pagination.per_page || 15"
                    :totalRecords="pagination.total || 0"
                    :first="((pagination.current_page || 1) - 1) * (pagination.per_page || 15)"
                    @page="onPage"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                />
            </div>
        </div>

        <!-- Upload dialog -->
        <Dialog
            v-model:visible="showUpload"
            header="Upload Supporting Document"
            :modal="true"
            class="w-full max-w-lg"
            @hide="resetForm"
        >
            <form class="space-y-4 pt-1" @submit.prevent="submitUpload">
                <div>
                    <label class="form-label">Tax Declaration <span class="text-red-500">*</span></label>
                    <Select
                        v-model="form.tax_declaration_id"
                        :options="tdOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select TD number to link"
                        filter
                        class="w-full"
                        :invalid="!!errors.tax_declaration_id"
                    />
                    <small v-if="errors.tax_declaration_id" class="text-red-500">{{ errors.tax_declaration_id }}</small>
                    <p class="text-xs text-gray-400 mt-1">The document will be attached to this TD record</p>
                </div>

                <div>
                    <label class="form-label">Document Type <span class="text-red-500">*</span></label>
                    <Select
                        v-model="form.document_type"
                        :options="documentTypeOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select document type"
                        class="w-full"
                        :invalid="!!errors.document_type"
                    />
                    <small v-if="errors.document_type" class="text-red-500">{{ errors.document_type }}</small>
                </div>

                <div>
                    <label class="form-label">Title <span class="text-red-500">*</span></label>
                    <InputText
                        v-model="form.title"
                        class="w-full"
                        placeholder="e.g. Land Title Copy, Tax Receipt 2024"
                        :invalid="!!errors.title"
                    />
                    <small v-if="errors.title" class="text-red-500">{{ errors.title }}</small>
                </div>

                <div>
                    <label class="form-label">File <span class="text-red-500">*</span></label>
                    <div
                        class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-colors"
                        :class="form.file
                            ? 'border-blue-300 bg-blue-50/50 dark:bg-blue-900/10 dark:border-blue-700'
                            : 'border-gray-200 dark:border-gray-600 hover:border-blue-300 hover:bg-gray-50 dark:hover:bg-gray-700/40'"
                        @click="fileInput?.click()"
                        @dragover.prevent
                        @drop.prevent="onDrop"
                    >
                        <input ref="fileInput" type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.tif,.tiff" @change="onFileSelect" />
                        <template v-if="form.file">
                            <i :class="['pi text-3xl mb-2 block', form.file.name.toLowerCase().endsWith('.pdf') ? 'pi-file-pdf text-red-500' : 'pi-file text-blue-500']"></i>
                            <p class="text-sm font-medium text-gray-800 dark:text-white truncate px-2">{{ form.file.name }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ formatFileSize(form.file.size) }} · Click to change</p>
                        </template>
                        <template v-else>
                            <i class="pi pi-cloud-upload text-3xl text-gray-300 mb-2 block"></i>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Drop a file here or click to browse</p>
                            <p class="text-xs text-gray-400 mt-1">PDF, images, or Office files · max 50 MB</p>
                        </template>
                    </div>
                    <small v-if="errors.file" class="text-red-500">{{ errors.file }}</small>
                </div>

                <div class="flex gap-2 pt-2">
                    <Button type="button" label="Cancel" outlined class="flex-1" @click="showUpload = false" />
                    <Button type="submit" label="Upload" icon="pi pi-upload" class="flex-1" :loading="uploading" />
                </div>
            </form>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Paginator from 'primevue/paginator';

const toast = useToast();
const confirm = useConfirm();
const route = useRoute();
const router = useRouter();

const loading = ref(false);
const uploading = ref(false);
const showUpload = ref(false);
const documents = ref([]);
const typeCounts = ref({});
const tdOptions = ref([]);
const fileInput = ref(null);

const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
});

const filters = reactive({
    search: '',
    document_type: null,
    tax_declaration_id: null,
});

const form = reactive({
    tax_declaration_id: null,
    document_type: 'supporting',
    title: '',
    file: null,
});

const errors = reactive({
    tax_declaration_id: '',
    document_type: '',
    title: '',
    file: '',
});

const documentTypeOptions = [
    { label: 'Original Scan', value: 'original_scan' },
    { label: 'Compressed Copy', value: 'compressed_copy' },
    { label: 'PDF Copy', value: 'pdf_copy' },
    { label: 'OCR Text', value: 'ocr_text' },
    { label: 'Supporting Document', value: 'supporting' },
    { label: 'Transfer Document', value: 'transfer' },
    { label: 'Land Title', value: 'land_title' },
    { label: 'Tax Receipt', value: 'tax_receipt' },
    { label: 'Survey Plan', value: 'survey_plan' },
    { label: 'Sketch Plan', value: 'sketch_plan' },
    { label: 'Legal Document', value: 'legal' },
    { label: 'Other', value: 'other' },
];

const typeCardDefs = [
    { label: 'All Documents', value: null, icon: 'pi-folder', color: 'text-slate-600', bg: 'bg-slate-100 dark:bg-slate-700/50', keys: null, filterable: true },
    { label: 'Land Titles', value: 'land_title', icon: 'pi-file-pdf', color: 'text-red-600', bg: 'bg-red-100 dark:bg-red-900/30', keys: ['land_title'], filterable: true },
    { label: 'Tax Receipts', value: 'tax_receipt', icon: 'pi-receipt', color: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-900/30', keys: ['tax_receipt'], filterable: true },
    { label: 'Survey Plans', value: 'survey_plan', icon: 'pi-map', color: 'text-violet-600', bg: 'bg-violet-100 dark:bg-violet-900/30', keys: ['survey_plan', 'sketch_plan'], filterable: false },
    { label: 'Supporting', value: 'supporting', icon: 'pi-paperclip', color: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-900/30', keys: ['supporting'], filterable: true },
    { label: 'Scans & Copies', value: 'original_scan', icon: 'pi-file', color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-900/30', keys: ['original_scan', 'compressed_copy', 'pdf_copy', 'ocr_text'], filterable: false },
];

const typeCards = computed(() =>
    typeCardDefs.map((card) => {
        let count = 0;
        if (!card.keys) {
            count = Object.values(typeCounts.value).reduce((a, b) => a + Number(b), 0);
        } else {
            count = card.keys.reduce((sum, key) => sum + Number(typeCounts.value[key] || 0), 0);
        }
        return { ...card, count };
    })
);

function isCardActive(card) {
    if (!card.filterable) return false;
    return filters.document_type === card.value;
}

function typeLabel(value) {
    return documentTypeOptions.find((o) => o.value === value)?.label || String(value || '').replace(/_/g, ' ');
}

function typeSeverity(value) {
    const map = {
        land_title: 'danger',
        tax_receipt: 'success',
        survey_plan: 'info',
        sketch_plan: 'info',
        supporting: 'warn',
        transfer: 'warn',
        legal: 'secondary',
        original_scan: 'info',
        pdf_copy: 'danger',
    };
    return map[value] || 'secondary';
}

function fileIcon(doc) {
    const mime = doc.mime_type || '';
    const name = (doc.file_name || '').toLowerCase();
    if (mime.includes('pdf') || name.endsWith('.pdf')) return 'pi-file-pdf text-red-500';
    if (mime.startsWith('image/') || /\.(jpe?g|png|gif|webp|tif{1,2})$/.test(name)) return 'pi-image text-sky-500';
    return 'pi-file text-blue-500';
}

function fileIconBg(doc) {
    const mime = doc.mime_type || '';
    const name = (doc.file_name || '').toLowerCase();
    if (mime.includes('pdf') || name.endsWith('.pdf')) return 'bg-red-50 dark:bg-red-900/20';
    if (mime.startsWith('image/') || /\.(jpe?g|png|gif|webp|tif{1,2})$/.test(name)) return 'bg-sky-50 dark:bg-sky-900/20';
    return 'bg-blue-50 dark:bg-blue-900/20';
}

function formatFileSize(bytes) {
    if (!bytes && bytes !== 0) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function toggleTypeFilter(value) {
    filters.document_type = filters.document_type === value ? null : value;
    loadData(1);
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

async function loadData(page = pagination.current_page) {
    loading.value = true;
    try {
        const res = await axios.get('documents', {
            params: {
                page,
                per_page: pagination.per_page,
                search: filters.search || undefined,
                document_type: filters.document_type || undefined,
                tax_declaration_id: filters.tax_declaration_id || undefined,
            },
        });
        documents.value = res.data.data || [];
        Object.assign(pagination, res.data.meta || {});
        typeCounts.value = res.data.type_counts || {};
    } catch {
        toast.error('Error', 'Failed to load documents');
    } finally {
        loading.value = false;
    }
}

function onPage(event) {
    loadData(event.page + 1);
}

function resetFilters() {
    filters.search = '';
    filters.document_type = null;
    filters.tax_declaration_id = null;
    loadData(1);
}

function openUpload(prefill = {}) {
    resetForm();
    if (prefill.tax_declaration_id) form.tax_declaration_id = Number(prefill.tax_declaration_id);
    if (prefill.document_type) form.document_type = prefill.document_type;
    showUpload.value = true;
}

function resetForm() {
    form.tax_declaration_id = null;
    form.document_type = 'supporting';
    form.title = '';
    form.file = null;
    errors.tax_declaration_id = '';
    errors.document_type = '';
    errors.title = '';
    errors.file = '';
    if (fileInput.value) fileInput.value.value = '';
}

function setFile(file) {
    if (!file) return;
    form.file = file;
    errors.file = '';
    if (!form.title.trim()) {
        form.title = file.name.replace(/\.[^.]+$/, '');
    }
}

function onFileSelect(e) {
    setFile(e.target.files?.[0] || null);
}

function onDrop(e) {
    setFile(e.dataTransfer?.files?.[0] || null);
}

function validateForm() {
    errors.tax_declaration_id = form.tax_declaration_id ? '' : 'Please select a tax declaration';
    errors.document_type = form.document_type ? '' : 'Please select a document type';
    errors.title = form.title.trim() ? '' : 'Title is required';
    errors.file = form.file ? '' : 'Please choose a file';
    return !errors.tax_declaration_id && !errors.document_type && !errors.title && !errors.file;
}

async function submitUpload() {
    if (!validateForm()) return;
    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('tax_declaration_id', form.tax_declaration_id);
        fd.append('document_type', form.document_type);
        fd.append('title', form.title.trim());
        fd.append('file', form.file);
        await axios.post('documents', fd);
        toast.success('Uploaded', 'Document linked successfully');
        showUpload.value = false;
        await loadData(1);
    } catch (err) {
        toast.apiError(err, 'Upload failed');
    } finally {
        uploading.value = false;
    }
}

async function downloadDoc(doc) {
    try {
        const res = await axios.get(`documents/${doc.id}/download`, { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([res.data]));
        const a = document.createElement('a');
        a.href = url;
        a.download = doc.file_name || doc.title;
        a.click();
        window.URL.revokeObjectURL(url);
    } catch {
        toast.error('Error', 'Download failed');
    }
}

function confirmDelete(doc) {
    confirm.require({
        message: `Delete "${doc.title}"? This cannot be undone.`,
        header: 'Delete Document',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`documents/${doc.id}`);
                toast.success('Deleted', 'Document removed');
                await loadData();
            } catch {
                toast.error('Error', 'Delete failed');
            }
        },
    });
}

function applyQueryPrefill() {
    const tdId = route.query.td_id || route.query.td;
    const shouldUpload = route.query.upload === '1' || route.query.upload === 'true';
    if (tdId) filters.tax_declaration_id = Number(tdId);
    if (shouldUpload) {
        openUpload({ tax_declaration_id: tdId ? Number(tdId) : null });
        router.replace({ path: '/documents', query: tdId ? { td_id: String(tdId) } : {} });
        if (tdId) loadData(1);
        return;
    }
    if (tdId) {
        loadData(1);
        router.replace({ path: '/documents', query: {} });
    }
}

watch(
    () => route.query,
    () => applyQueryPrefill(),
    { deep: true }
);

onMounted(async () => {
    await Promise.all([loadTdOptions(), loadData(1)]);
    applyQueryPrefill();
});
</script>
