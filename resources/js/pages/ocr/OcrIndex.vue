<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">OCR Management</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Upload and process documents with AI text recognition</p>
            </div>
            <Button label="Upload Document" icon="pi pi-upload" @click="showUpload = true" />
        </div>

        <!-- Upload Area -->
        <div v-if="showUpload"
            class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border-2 border-dashed border-blue-300 dark:border-blue-700"
            @dragover.prevent @drop.prevent="onDrop">
            <div class="text-center">
                <i class="pi pi-cloud-upload text-5xl text-blue-400 mb-4 block"></i>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Drop files here or click to browse</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Supports JPG, PNG, PDF — Max 20MB per file</p>
                <input ref="fileInput" type="file" multiple accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileSelect" />
                <Button label="Browse Files" icon="pi pi-folder-open" outlined @click="$refs.fileInput.click()" />
            </div>

            <!-- Selected Files -->
            <div v-if="pendingFiles.length" class="mt-6 space-y-3">
                <div v-for="(f, i) in pendingFiles" :key="i"
                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <i :class="['pi text-xl', f.type.includes('pdf') ? 'pi-file-pdf text-red-500' : 'pi-image text-blue-500']"></i>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ f.name }}</p>
                        <p class="text-xs text-gray-500">{{ formatSize(f.size) }}</p>
                        <div v-if="uploadProgress[i] !== undefined" class="mt-1.5 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full">
                            <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: uploadProgress[i] + '%' }"></div>
                        </div>
                    </div>
                    <Button icon="pi pi-times" size="small" text rounded severity="danger" @click="pendingFiles.splice(i, 1)" />
                </div>
                <div class="flex gap-3 justify-end">
                    <Button label="Upload All" icon="pi pi-cloud-upload" :loading="uploading" @click="uploadFiles" />
                    <Button label="Cancel" outlined @click="cancelUpload" />
                </div>
            </div>
        </div>

        <!-- OCR Queue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white">OCR Queue</h3>
                <div class="flex items-center gap-2 flex-wrap">
                    <IconField iconPosition="left" class="w-56">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Search filename / TD…" size="small" class="w-full" @keyup.enter="onSearch" />
                    </IconField>
                    <Select v-model="filterStatus" :options="statusOpts" optionLabel="label" optionValue="value"
                        placeholder="All Status" showClear size="small" @change="onFilter" />
                    <Button label="Batch Scan" icon="pi pi-play" size="small" outlined :loading="batchLoading" @click="batchScan" />
                </div>
            </div>

            <!-- Selection action bar (visible when rows are selected) -->
            <div v-if="selected.length"
                class="flex items-center justify-between gap-3 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/30 border-b border-blue-200 dark:border-blue-900">
                <div class="flex items-center gap-2 text-sm text-blue-800 dark:text-blue-300">
                    <i class="pi pi-check-square"></i>
                    <span><strong>{{ selected.length }}</strong> selected</span>
                    <button type="button" class="text-xs underline hover:no-underline text-blue-700 dark:text-blue-400" @click="selected = []">
                        Clear
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <Button label="Delete Selected" icon="pi pi-trash" size="small" severity="danger"
                        :loading="bulkDeleting" @click="confirmBulkDelete" />
                </div>
            </div>

            <DataTable :value="ocrResults" :loading="loading" class="p-datatable-sm" striped-rows
                v-model:selection="selected" dataKey="id">
                <template #empty>
                    <div class="text-center py-12">
                        <i class="pi pi-camera text-4xl text-gray-300 mb-3 block"></i>
                        <p class="text-gray-500">No OCR records found</p>
                    </div>
                </template>
                <Column selectionMode="multiple" headerStyle="width: 3rem" />
                <Column header="File" style="min-width: 220px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2 min-w-0">
                            <i :class="['pi text-lg shrink-0', data.source_type === 'pdf' ? 'pi-file-pdf text-red-500' : 'pi-image text-blue-500']"></i>
                            <span class="text-sm truncate" :title="displayFilename(data)">{{ displayFilename(data) }}</span>
                        </div>
                    </template>
                </Column>
                <Column header="TD Number" style="min-width: 150px">
                    <template #body="{ data }">
                        <span v-if="extractedTd(data)" class="text-sm text-blue-600 dark:text-blue-400 font-medium">{{ extractedTd(data) }}</span>
                        <span v-else-if="data.status === 'pending'" class="text-xs text-gray-400">— not scanned —</span>
                        <span v-else class="text-xs text-gray-400">— not detected —</span>
                    </template>
                </Column>
                <Column header="Confidence" style="min-width: 140px">
                    <template #body="{ data }">
                        <div v-if="data.confidence_score" class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                                <div :class="['h-full rounded-full', data.confidence_score >= 80 ? 'bg-green-500' : data.confidence_score >= 60 ? 'bg-yellow-500' : 'bg-red-500']"
                                    :style="{ width: data.confidence_score + '%' }"></div>
                            </div>
                            <span class="text-xs font-medium">{{ Number(data.confidence_score).toFixed(0) }}%</span>
                        </div>
                        <span v-else class="text-xs text-gray-400">—</span>
                    </template>
                </Column>
                <Column field="status" header="Status" style="min-width: 130px">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="ocrStatusSeverity(data.status)" class="text-xs" />
                    </template>
                </Column>
                <Column header="Processed By" style="min-width: 130px">
                    <template #body="{ data }">
                        <span class="text-xs text-gray-500">{{ data.processed_by?.name || '—' }}</span>
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 150px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Button icon="pi pi-play" size="small" text rounded v-tooltip.top="'Scan'" :loading="scanningId === data.id"
                                v-if="data.status === 'pending'" @click="scanOcr(data)" />
                            <RouterLink :to="`/ocr/${data.id}`" v-if="['completed', 'reviewed'].includes(data.status)">
                                <Button icon="pi pi-pencil" size="small" text rounded severity="secondary" v-tooltip.top="'Review'" />
                            </RouterLink>
                            <Button icon="pi pi-eye" size="small" text rounded v-tooltip.top="'View Results'" @click="viewOcr(data)" />
                            <Button icon="pi pi-trash" size="small" text rounded severity="danger" v-tooltip.top="'Delete'"
                                :loading="deletingId === data.id" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                <Paginator :rows="perPage" :totalRecords="total" :first="(currentPage - 1) * perPage"
                    @page="e => { currentPage = e.page + 1; loadOcr(); }"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    currentPageReportTemplate="{first}–{last} of {totalRecords}" />
            </div>
        </div>

        <!-- OCR Result Viewer -->
        <Dialog
            v-model:visible="showViewer"
            :modal="true"
            :draggable="false"
            class="ocr-viewer-dialog w-full max-w-4xl"
            :pt="{
                root: { class: 'rounded-xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800' },
                header: { class: '!p-0 !border-0 !bg-transparent' },
                content: { class: '!p-0' },
                footer: { class: '!p-0 !border-0 !bg-transparent' },
            }"
        >
            <template #header>
                <div class="w-full bg-[#1a3557] text-white">
                    <div class="h-[3px] bg-gradient-to-r from-[#b8860b] via-[#d4a017] to-[#b8860b]"></div>
                    <div class="flex items-center justify-between px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center ring-1 ring-[#b8860b]/50">
                                <i class="pi pi-file-edit text-[#d4a017]"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold tracking-wide">OCR Scan Results</h2>
                                <p class="text-xs text-blue-200 mt-0.5">Document text extraction &amp; field mapping</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showViewer = false"
                            class="h-8 w-8 rounded-md bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                        >
                            <i class="pi pi-times text-sm"></i>
                        </button>
                    </div>
                </div>
            </template>

            <div v-if="viewingOcr" class="bg-slate-50 dark:bg-slate-950">

                <!-- File meta strip -->
                <div class="px-5 py-3 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 rounded-md bg-[#1a3557]/10 flex items-center justify-center shrink-0">
                            <i :class="['pi text-sm text-[#1a3557] dark:text-blue-400', viewingOcr.source_type === 'pdf' ? 'pi-file-pdf' : 'pi-image']"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-[#1a3557] dark:text-slate-200 truncate">{{ fileName(viewingOcr) }}</p>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wide">{{ viewingOcr.source_type || 'document' }}</p>
                        </div>
                    </div>
                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</span>
                        <Tag :value="viewingOcr.status" :severity="ocrStatusSeverity(viewingOcr.status)" class="text-[10px] !px-2 !py-0.5" />
                    </div>
                    <div v-if="extractedTd(viewingOcr)" class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Extracted TD</span>
                        <span class="text-xs font-bold text-[#1a3557] dark:text-blue-400">{{ extractedTd(viewingOcr) }}</span>
                    </div>
                    <div v-if="viewingOcr.processed_by?.name" class="flex items-center gap-1.5 ml-auto">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Processed by</span>
                        <span class="text-xs text-slate-600 dark:text-slate-300">{{ viewingOcr.processed_by.name }}</span>
                    </div>
                </div>

                <!-- Main content -->
                <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-4">

                    <!-- Extracted Fields -->
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="flex items-center gap-2 px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <i class="pi pi-list text-[#1a3557] dark:text-blue-400 text-sm"></i>
                            <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Extracted Fields</span>
                            <span class="ml-auto text-[10px] font-semibold bg-[#1a3557]/10 text-[#1a3557] dark:text-blue-400 px-2 py-0.5 rounded-full">
                                {{ fieldCount(viewingOcr) }} found
                            </span>
                        </div>
                        <div class="p-3 max-h-72 overflow-y-auto">
                            <div v-if="fieldCount(viewingOcr) > 0" class="space-y-1.5">
                                <div
                                    v-for="(v, k) in displayFields(viewingOcr)"
                                    :key="k"
                                    class="flex items-start gap-3 p-2.5 rounded-md border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                >
                                    <div class="w-7 h-7 rounded bg-[#1a3557]/10 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="pi pi-check text-[10px] text-[#1a3557] dark:text-blue-400"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ fieldLabel(k) }}</p>
                                        <p class="text-sm font-semibold text-[#1a3557] dark:text-slate-100 break-words">{{ formatFieldValue(k, v) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <i class="pi pi-inbox text-xl text-slate-300 dark:text-slate-600"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No fields extracted</p>
                                <p class="text-xs text-slate-400 mt-1 max-w-[200px]">Try scanning again with a clearer document image.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Raw OCR Text -->
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm flex flex-col">
                        <div class="flex items-center gap-2 px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <i class="pi pi-align-left text-[#1a3557] dark:text-blue-400 text-sm"></i>
                            <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Raw OCR Text</span>
                        </div>
                        <div class="p-3 flex-1">
                            <div class="h-72 overflow-y-auto rounded-md bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-3">
                                <pre v-if="viewingOcr.raw_text" class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 whitespace-pre-wrap font-mono">{{ viewingOcr.raw_text }}</pre>
                                <div v-else class="h-full flex flex-col items-center justify-center text-center">
                                    <i class="pi pi-file text-2xl text-slate-300 dark:text-slate-600 mb-2"></i>
                                    <p class="text-xs text-slate-400">No text extracted yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-5 py-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                    <!-- Confidence -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-[#b8860b]"></i>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Confidence</span>
                        </div>
                        <div v-if="viewingOcr.confidence_score" class="flex items-center gap-2">
                            <div class="w-24 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div
                                    :class="['h-full rounded-full transition-all', confidenceBarClass(viewingOcr.confidence_score)]"
                                    :style="{ width: viewingOcr.confidence_score + '%' }"
                                ></div>
                            </div>
                            <span :class="['text-sm font-bold', confidenceTextClass(viewingOcr.confidence_score)]">
                                {{ viewingOcr.confidence_score }}%
                            </span>
                        </div>
                        <span v-else class="text-sm text-slate-400">—</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <Button label="Close" outlined size="small" @click="showViewer = false" />
                        <RouterLink v-if="['completed', 'reviewed'].includes(viewingOcr.status)" :to="`/ocr/${viewingOcr.id}`" @click="showViewer = false">
                            <Button label="Review & Correct" icon="pi pi-pencil" size="small" />
                        </RouterLink>
                        <Button
                            v-if="viewingOcr.status === 'pending'"
                            label="Run Scan"
                            icon="pi pi-play"
                            size="small"
                            :loading="scanningId === viewingOcr.id"
                            @click="scanFromModal"
                        />
                    </div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import axios from 'axios';

const toast = useToast();
const confirm = useConfirm();
const ocrResults = ref([]);
const loading = ref(false);
const batchLoading = ref(false);
const uploading = ref(false);
const showUpload = ref(false);
const pendingFiles = ref([]);
const uploadProgress = ref({});
const filterStatus = ref(null);
const search = ref('');
const scanningId = ref(null);
const deletingId = ref(null);
const showViewer = ref(false);
const viewingOcr = ref(null);
const total = ref(0);
const currentPage = ref(1);
const perPage = 15;
const fileInput = ref(null);
const selected = ref([]);
const bulkDeleting = ref(false);

const statusOpts = [
    { label: 'Pending', value: 'pending' }, { label: 'Processing', value: 'processing' },
    { label: 'Completed', value: 'completed' }, { label: 'Failed', value: 'failed' },
    { label: 'Reviewed', value: 'reviewed' },
];

function onFileSelect(e) { pendingFiles.value = Array.from(e.target.files); }
function onDrop(e) { pendingFiles.value = Array.from(e.dataTransfer.files).filter(f => ['image/jpeg','image/png','application/pdf'].includes(f.type)); }
function formatSize(bytes) { return bytes < 1024 * 1024 ? `${(bytes / 1024).toFixed(0)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`; }

function cancelUpload() {
    showUpload.value = false;
    pendingFiles.value = [];
    uploadProgress.value = {};
}

async function uploadFiles() {
    uploading.value = true;
    let failed = 0;
    for (let i = 0; i < pendingFiles.value.length; i++) {
        const fd = new FormData();
        fd.append('file', pendingFiles.value[i]);
        uploadProgress.value[i] = 0;
        try {
            await axios.post('/ocr/upload', fd, {
                onUploadProgress: p => { uploadProgress.value[i] = Math.round((p.loaded / p.total) * 100); },
            });
            uploadProgress.value[i] = 100;
        } catch (err) {
            failed++;
            const msg = err?.response?.data?.message || err?.message || 'Upload failed';
            toast.add({ severity: 'error', summary: pendingFiles.value[i].name, detail: msg, life: 5000 });
        }
    }
    uploading.value = false;
    const uploaded = pendingFiles.value.length - failed;
    pendingFiles.value = [];
    uploadProgress.value = {};
    showUpload.value = false;
    if (uploaded > 0) {
        toast.add({
            severity: 'success',
            summary: 'Uploaded',
            detail: `${uploaded} file(s) queued. Run Scan to extract fields.`,
        });
    }
    loadOcr();
}

function extractedTd(ocr) {
    const fields = ocr?.corrected_fields || ocr?.extracted_fields || {};
    return fields.td_number || null;
}

async function loadOcr() {
    loading.value = true;
    try {
        const res = await axios.get('/ocr', {
            params: {
                status: filterStatus.value,
                search: search.value || undefined,
                page: currentPage.value,
                per_page: perPage,
            },
        });
        ocrResults.value = res.data.data;
        total.value = res.data.total;
        // Drop selections that are no longer on the current page.
        if (selected.value.length) {
            const visibleIds = new Set(ocrResults.value.map(r => r.id));
            selected.value = selected.value.filter(r => visibleIds.has(r.id));
        }
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Load failed', detail: err?.response?.data?.message || 'Could not load OCR queue.' });
    } finally { loading.value = false; }
}

function onFilter() {
    currentPage.value = 1;
    loadOcr();
}

function onSearch() {
    currentPage.value = 1;
    loadOcr();
}

async function scanOcr(ocr) {
    scanningId.value = ocr.id;
    try {
        await axios.post(`/ocr/${ocr.id}/scan`);
        toast.add({ severity: 'success', summary: 'Scanned', detail: 'OCR scan completed.' });
        loadOcr();
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Scan failed', detail: err?.response?.data?.message || 'Could not run OCR scan.' });
    } finally { scanningId.value = null; }
}

async function batchScan() {
    batchLoading.value = true;
    try {
        const pending = ocrResults.value.filter(o => o.status === 'pending').map(o => o.id);
        if (!pending.length) {
            toast.add({ severity: 'info', summary: 'Info', detail: 'No pending items on this page.' });
            return;
        }
        const { data } = await axios.post('/ocr/batch-scan', { ocr_result_ids: pending });
        toast.add({ severity: 'success', summary: 'Batch Scan', detail: `Processed ${data.processed} of ${pending.length} items.` });
        loadOcr();
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Batch failed', detail: err?.response?.data?.message || 'Batch scan failed.' });
    } finally {
        batchLoading.value = false;
    }
}

function confirmDelete(ocr) {
    confirm.require({
        message: `Delete OCR record #${ocr.id}? This will remove the uploaded file.`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => deleteOcr(ocr),
    });
}

async function deleteOcr(ocr) {
    deletingId.value = ocr.id;
    try {
        await axios.delete(`/ocr/${ocr.id}`);
        toast.add({ severity: 'success', summary: 'Deleted', detail: 'OCR record removed.' });
        loadOcr();
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Delete failed', detail: err?.response?.data?.message || 'Could not delete record.' });
    } finally {
        deletingId.value = null;
    }
}

function confirmBulkDelete() {
    const count = selected.value.length;
    if (!count) return;
    confirm.require({
        message: `Delete ${count} OCR record${count > 1 ? 's' : ''}? This will remove the uploaded files as well.`,
        header: 'Confirm Bulk Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        accept: bulkDeleteSelected,
    });
}

async function bulkDeleteSelected() {
    if (!selected.value.length) return;
    bulkDeleting.value = true;
    try {
        const ids = selected.value.map(r => r.id);
        const { data } = await axios.post('/ocr/bulk-delete', { ids });
        toast.add({
            severity: 'success',
            summary: 'Deleted',
            detail: data?.message || `${ids.length} record(s) deleted.`,
        });
        selected.value = [];
        loadOcr();
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Bulk delete failed',
            detail: err?.response?.data?.message || 'Could not delete selected records.',
        });
    } finally {
        bulkDeleting.value = false;
    }
}

function viewOcr(ocr) { viewingOcr.value = ocr; showViewer.value = true; }

function displayFilename(ocr) {
    return ocr?.original_filename || ocr?.source_file?.split('/').pop() || 'Unknown file';
}

function fileName(ocr) {
    return displayFilename(ocr);
}

function displayFields(ocr) {
    return ocr?.corrected_fields || ocr?.extracted_fields || {};
}

function fieldCount(ocr) {
    return Object.keys(displayFields(ocr)).length;
}

function fieldLabel(key) {
    const labels = {
        td_number: 'TD Number',
        arp_number: 'ARP Number',
        lot_number: 'Lot Number',
        owner_name: 'Owner Name',
        land_area: 'Land Area (sq.m.)',
        market_value: 'Market Value',
        assessed_value: 'Assessed Value',
    };
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function formatFieldValue(key, val) {
    if (['market_value', 'assessed_value'].includes(key) && !isNaN(val)) {
        return '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    }
    if (key === 'land_area' && !isNaN(val)) {
        return Number(val).toLocaleString('en-PH') + ' sq.m.';
    }
    return val;
}

function confidenceBarClass(score) {
    if (score >= 80) return 'bg-green-500';
    if (score >= 55) return 'bg-amber-500';
    return 'bg-red-500';
}

function confidenceTextClass(score) {
    if (score >= 80) return 'text-green-600 dark:text-green-400';
    if (score >= 55) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
}

async function scanFromModal() {
    if (!viewingOcr.value) return;
    scanningId.value = viewingOcr.value.id;
    try {
        const { data } = await axios.post(`/ocr/${viewingOcr.value.id}/scan`);
        viewingOcr.value = data;
        toast.add({ severity: 'success', summary: 'Scanned', detail: 'OCR scan completed.' });
        loadOcr();
    } finally {
        scanningId.value = null;
    }
}

function ocrStatusSeverity(s) {
    return { pending: 'secondary', processing: 'warn', completed: 'success', failed: 'danger', reviewed: 'info' }[s] || 'secondary';
}

onMounted(loadOcr);
</script>

<style>
.ocr-viewer-dialog .p-dialog-header-actions { display: none; }
</style>

