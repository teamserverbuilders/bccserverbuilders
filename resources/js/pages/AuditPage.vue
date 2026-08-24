<template>
    <div class="space-y-5">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Audit Trail</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">System activity logs and user actions</p>
        </div>

        <TabView>
            <TabPanel header="Audit Logs">
                <div class="space-y-4 pt-4">
                    <div class="flex flex-wrap gap-3">
                        <InputText v-model="filters.event" placeholder="Event type..." size="small" />
                        <DatePicker v-model="filters.date_from" placeholder="From date" size="small" dateFormat="yy-mm-dd" showIcon />
                        <DatePicker v-model="filters.date_to" placeholder="To date" size="small" dateFormat="yy-mm-dd" showIcon />
                        <Button label="Filter" icon="pi pi-filter" size="small" @click="loadAudit" />
                    </div>
                    <DataTable :value="auditLogs" :loading="loading" class="p-datatable-sm" striped-rows paginator :rows="20">
                        <Column field="user.name" header="User"><template #body="{ data }">
                            <span class="text-sm font-medium">{{ data.user?.name || 'System' }}</span>
                        </template></Column>
                        <Column field="event" header="Event"><template #body="{ data }">
                            <span class="text-xs font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">{{ data.event }}</span>
                        </template></Column>
                        <Column header="Record" style="min-width: 150px"><template #body="{ data }">
                            <span class="text-xs text-gray-500">{{ data.auditable_type?.split('\\').pop() }} #{{ data.auditable_id }}</span>
                        </template></Column>
                        <Column field="ip_address" header="IP Address" />
                        <Column header="Date/Time"><template #body="{ data }">
                            <span class="text-xs text-gray-500">{{ new Date(data.created_at).toLocaleString() }}</span>
                        </template></Column>
                        <Column header="Changes"><template #body="{ data }">
                            <Button v-if="data.new_values" icon="pi pi-eye" size="small" text rounded @click="viewChanges(data)" />
                        </template></Column>
                    </DataTable>
                </div>
            </TabPanel>

            <TabPanel header="Login History">
                <div class="space-y-4 pt-4">
                    <DataTable :value="loginLogs" :loading="loginLoading" class="p-datatable-sm" striped-rows paginator :rows="20">
                        <Column field="user.name" header="User" />
                        <Column field="email" header="Email" />
                        <Column field="action" header="Action"><template #body="{ data }">
                            <Tag :value="data.action" :severity="data.action === 'login' ? 'success' : data.action === 'failed' ? 'danger' : 'secondary'" class="text-xs capitalize" />
                        </template></Column>
                        <Column field="ip_address" header="IP Address" />
                        <Column field="browser" header="Browser" />
                        <Column field="operating_system" header="OS" />
                        <Column header="Date/Time"><template #body="{ data }">
                            <span class="text-xs text-gray-500">{{ new Date(data.created_at).toLocaleString() }}</span>
                        </template></Column>
                    </DataTable>
                </div>
            </TabPanel>
        </TabView>

        <!-- Changes Dialog -->
        <Dialog v-model:visible="showChanges" header="Record Changes" :modal="true" class="w-full max-w-2xl">
            <div v-if="viewingChanges" class="space-y-4 pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-sm font-semibold text-red-600 mb-2">Before</h4>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 max-h-60 overflow-y-auto">
                            <pre class="text-xs">{{ JSON.stringify(viewingChanges.old_values, null, 2) }}</pre>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-green-600 mb-2">After</h4>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 max-h-60 overflow-y-auto">
                            <pre class="text-xs">{{ JSON.stringify(viewingChanges.new_values, null, 2) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DatePicker from 'primevue/datepicker';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Dialog from 'primevue/dialog';
import axios from 'axios';

const auditLogs = ref([]);
const loginLogs = ref([]);
const loading = ref(false);
const loginLoading = ref(false);
const showChanges = ref(false);
const viewingChanges = ref(null);
const filters = reactive({ event: '', date_from: null, date_to: null });

function viewChanges(log) { viewingChanges.value = log; showChanges.value = true; }

async function loadAudit() {
    loading.value = true;
    try {
        const res = await axios.get('/audit/logs', { params: filters });
        auditLogs.value = res.data.data;
    } finally { loading.value = false; }
}

async function loadLoginLogs() {
    loginLoading.value = true;
    try {
        const res = await axios.get('/audit/login-logs');
        loginLogs.value = res.data.data;
    } finally { loginLoading.value = false; }
}

onMounted(() => { loadAudit(); loadLoginLogs(); });
</script>
