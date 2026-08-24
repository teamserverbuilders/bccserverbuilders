<template>
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Reports</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Generate and export system reports</p>
            </div>
        </div>

        <!-- Report Tabs -->
        <TabView v-model:activeIndex="activeTab">
            <TabPanel header="Property Report">
                <div class="space-y-4 pt-4">
                    <!-- Filters -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Select v-model="filters.barangay_id" :options="barangays" optionLabel="name" optionValue="id" placeholder="Barangay" showClear />
                        <Select v-model="filters.classification_id" :options="classifications" optionLabel="name" optionValue="id" placeholder="Classification" showClear />
                        <Select v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value" placeholder="Status" showClear />
                        <div class="flex gap-2">
                            <Button label="Generate" icon="pi pi-search" :loading="loading" @click="generateReport(1)" class="flex-1" />
                            <Button icon="pi pi-file-pdf" outlined v-tooltip.top="'Export PDF'" :loading="exporting" @click="exportPdf" />
                        </div>
                    </div>

                    <DataTable :value="reportData" :loading="loading" class="p-datatable-sm" striped-rows :paginator="false">
                        <template #empty>
                            <div class="text-center py-10">
                                <i class="pi pi-inbox text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-gray-500">No records match the selected filters.</p>
                            </div>
                        </template>
                        <Column field="td_number" header="TD Number">
                            <template #body="{ data }"><span class="text-blue-600 font-medium text-sm">{{ data.td_number }}</span></template>
                        </Column>
                        <Column field="owner.owner_name" header="Owner">
                            <template #body="{ data }">{{ data.owner?.owner_name || '—' }}</template>
                        </Column>
                        <Column field="classification.name" header="Classification">
                            <template #body="{ data }"><Tag v-if="data.classification?.name" :value="data.classification.name" class="text-xs" /><span v-else class="text-gray-400 text-xs">—</span></template>
                        </Column>
                        <Column field="barangay.name" header="Barangay">
                            <template #body="{ data }">{{ data.barangay?.name || '—' }}</template>
                        </Column>
                        <Column field="market_value" header="Market Value">
                            <template #body="{ data }">₱{{ data.market_value ? Number(data.market_value).toLocaleString() : '—' }}</template>
                        </Column>
                        <Column field="assessed_value" header="Assessed Value">
                            <template #body="{ data }"><strong>₱{{ data.assessed_value ? Number(data.assessed_value).toLocaleString() : '—' }}</strong></template>
                        </Column>
                        <Column field="status" header="Status">
                            <template #body="{ data }"><Tag :value="data.status?.replace(/_/g,' ')" class="text-xs capitalize" /></template>
                        </Column>
                    </DataTable>

                    <div v-if="reportTotal" class="flex justify-end px-1">
                        <Paginator :rows="reportPerPage" :totalRecords="reportTotal" :first="(reportPage - 1) * reportPerPage"
                            @page="e => generateReport(e.page + 1)"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                            currentPageReportTemplate="{first}–{last} of {totalRecords}" />
                    </div>
                </div>
            </TabPanel>

            <TabPanel header="Assessment Report">
                <div class="space-y-4 pt-4">
                    <div class="flex justify-end gap-2">
                        <Button icon="pi pi-refresh" outlined size="small" :loading="assessmentLoading" v-tooltip.top="'Refresh'" @click="loadAssessment" />
                        <Button icon="pi pi-file-pdf" outlined size="small" :loading="exporting" v-tooltip.top="'Export PDF'" @click="exportPdf" />
                    </div>

                    <div v-if="assessmentLoading && !assessmentData" class="text-center py-10 text-gray-400">
                        <i class="pi pi-spin pi-spinner text-2xl block mb-2"></i>Loading assessment data…
                    </div>

                    <template v-else-if="assessmentData">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                                <p class="text-sm text-blue-600 dark:text-blue-400">Total Market Value</p>
                                <p class="text-3xl font-bold text-blue-700 dark:text-blue-300">₱{{ Number(assessmentData.total_market_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6">
                                <p class="text-sm text-green-600 dark:text-green-400">Total Assessed Value</p>
                                <p class="text-3xl font-bold text-green-700 dark:text-green-300">₱{{ Number(assessmentData.total_assessed_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                        </div>
                        <DataTable :value="assessmentData.by_classification" class="p-datatable-sm" striped-rows>
                            <template #empty>
                                <div class="text-center py-8 text-gray-400">No approved records found.</div>
                            </template>
                            <Column field="classification.name" header="Classification">
                                <template #body="{ data }">{{ data.classification?.name || '—' }}</template>
                            </Column>
                            <Column field="count" header="Count" />
                            <Column field="total_market" header="Total Market Value">
                                <template #body="{ data }">₱{{ Number(data.total_market || 0).toLocaleString() }}</template>
                            </Column>
                            <Column field="total_assessed" header="Total Assessed Value">
                                <template #body="{ data }"><strong>₱{{ Number(data.total_assessed || 0).toLocaleString() }}</strong></template>
                            </Column>
                        </DataTable>
                    </template>

                    <div v-else class="text-center py-10 text-gray-400">
                        Could not load assessment data. <a href="#" class="text-blue-600" @click.prevent="loadAssessment">Try again</a>.
                    </div>
                </div>
            </TabPanel>

            <TabPanel header="OCR Accuracy">
                <div class="space-y-4 pt-4">
                    <div class="flex justify-end">
                        <Button icon="pi pi-refresh" outlined size="small" :loading="ocrLoading" v-tooltip.top="'Refresh'" @click="loadOcrAccuracy" />
                    </div>

                    <div v-if="ocrLoading && !ocrData" class="text-center py-10 text-gray-400">
                        <i class="pi pi-spin pi-spinner text-2xl block mb-2"></i>Loading OCR statistics…
                    </div>

                    <div v-else-if="ocrData" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <StatCard title="Total Scanned" :value="ocrData.total_scanned" icon="pi-camera" color="blue" />
                        <StatCard title="Completed" :value="ocrData.completed" icon="pi-check-circle" color="green" />
                        <StatCard title="Failed" :value="ocrData.failed" icon="pi-times-circle" color="red" />
                        <StatCard title="Avg. Confidence" :value="`${(ocrData.average_confidence || 0).toFixed(1)}%`" icon="pi-chart-line" color="purple" />
                    </div>

                    <div v-else class="text-center py-10 text-gray-400">
                        Could not load OCR statistics. <a href="#" class="text-blue-600" @click.prevent="loadOcrAccuracy">Try again</a>.
                    </div>
                </div>
            </TabPanel>

            <TabPanel header="Audit Report">
                <div class="space-y-4 pt-4">
                    <div class="flex justify-end gap-2">
                        <Button icon="pi pi-refresh" outlined size="small" :loading="auditLoading" v-tooltip.top="'Refresh'" @click="loadAudit(1)" />
                        <Button icon="pi pi-file-pdf" outlined size="small" :loading="exporting" v-tooltip.top="'Export PDF'" @click="exportPdf" />
                    </div>

                    <DataTable :value="auditLogs" :loading="auditLoading" class="p-datatable-sm" striped-rows :paginator="false">
                        <template #empty>
                            <div class="text-center py-10 text-gray-400">No audit log entries found.</div>
                        </template>
                        <Column field="user.name" header="User">
                            <template #body="{ data }">{{ data.user?.name || 'System' }}</template>
                        </Column>
                        <Column field="event" header="Event">
                            <template #body="{ data }"><span class="text-xs font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ data.event }}</span></template>
                        </Column>
                        <Column field="ip_address" header="IP Address">
                            <template #body="{ data }">{{ data.ip_address || '—' }}</template>
                        </Column>
                        <Column header="Date">
                            <template #body="{ data }"><span class="text-xs text-gray-500">{{ new Date(data.created_at).toLocaleString() }}</span></template>
                        </Column>
                    </DataTable>

                    <div v-if="auditTotal" class="flex justify-end px-1">
                        <Paginator :rows="auditPerPage" :totalRecords="auditTotal" :first="(auditPage - 1) * auditPerPage"
                            @page="e => loadAudit(e.page + 1)"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                            currentPageReportTemplate="{first}–{last} of {totalRecords}" />
                    </div>
                </div>
            </TabPanel>
        </TabView>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import StatCard from '@/components/StatCard.vue';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const toast = useToast();

const activeTab = ref(0);
// Tab index -> PDF export "type" understood by the backend. OCR Accuracy (index 2) has no PDF export.
const exportTypes = ['property', 'assessment', null, 'audit'];

const barangays = ref([]);
const classifications = ref([]);

const reportData = ref([]);
const reportTotal = ref(0);
const reportPage = ref(1);
const reportPerPage = 15;
const loading = ref(false);

const assessmentData = ref(null);
const assessmentLoading = ref(false);

const ocrData = ref(null);
const ocrLoading = ref(false);

const auditLogs = ref([]);
const auditTotal = ref(0);
const auditPage = ref(1);
const auditPerPage = 15;
const auditLoading = ref(false);

const exporting = ref(false);

const filters = ref({ barangay_id: null, classification_id: null, status: null });

const statuses = [
    { label: 'Draft', value: 'draft' },
    { label: 'OCR Processing', value: 'ocr_processing' },
    { label: 'OCR Review', value: 'ocr_review' },
    { label: 'Encoder Review', value: 'encoder_review' },
    { label: 'Assessor Verification', value: 'assessor_verification' },
    { label: 'Supervisor Review', value: 'supervisor_review' },
    { label: 'Approved', value: 'approved' },
    { label: 'Released', value: 'released' },
    { label: 'Returned', value: 'returned' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Archived', value: 'archived' },
];

async function loadLookups() {
    try {
        const [br, cls] = await Promise.all([
            axios.get('/settings/barangays'),
            axios.get('/settings/classifications'),
        ]);
        barangays.value = br.data;
        classifications.value = cls.data;
    } catch (err) {
        toast.add({ severity: 'warn', summary: 'Filters unavailable', detail: 'Could not load barangay/classification filter options.' });
    }
}

async function generateReport(page = 1) {
    loading.value = true;
    try {
        const res = await axios.get('/reports/property', { params: { ...filters.value, page, per_page: reportPerPage } });
        reportData.value = res.data.data;
        reportTotal.value = res.data.total;
        reportPage.value = res.data.current_page || page;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Report failed', detail: err?.response?.data?.message || 'Could not generate the property report.' });
    } finally { loading.value = false; }
}

async function loadAssessment() {
    assessmentLoading.value = true;
    try {
        const res = await axios.get('/reports/assessment');
        assessmentData.value = res.data;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Load failed', detail: 'Could not load the assessment report.' });
    } finally { assessmentLoading.value = false; }
}

async function loadOcrAccuracy() {
    ocrLoading.value = true;
    try {
        const res = await axios.get('/reports/ocr-accuracy');
        ocrData.value = res.data;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Load failed', detail: 'Could not load OCR accuracy statistics.' });
    } finally { ocrLoading.value = false; }
}

async function loadAudit(page = 1) {
    auditLoading.value = true;
    try {
        const res = await axios.get('/reports/audit', { params: { page, per_page: auditPerPage } });
        auditLogs.value = res.data.data;
        auditTotal.value = res.data.total;
        auditPage.value = res.data.current_page || page;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Load failed', detail: 'Could not load the audit report.' });
    } finally { auditLoading.value = false; }
}

async function exportPdf() {
    const type = exportTypes[activeTab.value];
    if (!type) return;

    exporting.value = true;
    try {
        const res = await axios.get('/reports/export-pdf', { params: { ...filters.value, type }, responseType: 'blob' });
        const url = URL.createObjectURL(res.data);
        window.open(url, '_blank');
    } catch (err) {
        let message = 'Could not generate the PDF report.';
        const data = err?.response?.data;
        if (data instanceof Blob) {
            try {
                message = JSON.parse(await data.text())?.message || message;
            } catch { /* not JSON, keep default message */ }
        } else if (data?.message) {
            message = data.message;
        }
        toast.add({ severity: 'error', summary: 'Export failed', detail: message });
    } finally { exporting.value = false; }
}

onMounted(() => {
    loadLookups();
    generateReport(1);
    loadAssessment();
    loadOcrAccuracy();
    loadAudit(1);
});
</script>
