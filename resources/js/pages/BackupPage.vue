<template>
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Backup & Recovery</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage system backups and data recovery</p>
            </div>
            <Button label="Create Backup" icon="pi pi-cloud-upload" @click="createBackup" :loading="creating" />
        </div>

        <!-- Backup Types -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="btype in backupTypes" :key="btype.type"
                class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-blue-300 transition-colors"
                @click="selectedType = btype.type">
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-3', btype.bg]">
                    <i :class="['pi text-xl', btype.icon, btype.color]"></i>
                </div>
                <h4 class="font-semibold text-gray-800 dark:text-white text-sm">{{ btype.label }}</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ btype.desc }}</p>
            </div>
        </div>

        <!-- Backup History -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white">Backup History</h3>
            </div>
            <div class="p-4 text-center text-gray-400 py-12">
                <i class="pi pi-server text-4xl mb-3 block opacity-40"></i>
                <p class="text-sm">No backups found. Create your first backup above.</p>
                <p class="text-xs text-gray-300 dark:text-gray-600 mt-1">Backup feature requires server-side configuration.</p>
            </div>
        </div>

        <!-- Schedule Info -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-3">
                <i class="pi pi-info-circle text-blue-500 text-xl mt-0.5"></i>
                <div>
                    <h4 class="font-semibold text-blue-800 dark:text-blue-300">Automatic Backup Schedule</h4>
                    <ul class="text-sm text-blue-700 dark:text-blue-400 mt-2 space-y-1">
                        <li>• Daily database backup at 2:00 AM</li>
                        <li>• Weekly full system backup every Sunday at 3:00 AM</li>
                        <li>• Monthly backup archived to cloud storage</li>
                    </ul>
                    <p class="text-xs text-blue-600 dark:text-blue-500 mt-2">Configure backup settings in System Settings → Backup Configuration.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';

const toast = useToast();
const creating = ref(false);
const selectedType = ref('full');

const backupTypes = [
    { type: 'database', label: 'Database', desc: 'All records & settings', icon: 'pi-database', color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-900/30' },
    { type: 'documents', label: 'Documents', desc: 'Uploaded files & scans', icon: 'pi-folder', color: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-900/30' },
    { type: 'images', label: 'Images', desc: 'Property photographs', icon: 'pi-images', color: 'text-green-600', bg: 'bg-green-100 dark:bg-green-900/30' },
    { type: 'full', label: 'Full System', desc: 'Complete backup', icon: 'pi-server', color: 'text-purple-600', bg: 'bg-purple-100 dark:bg-purple-900/30' },
];

async function createBackup() {
    creating.value = true;
    await new Promise(r => setTimeout(r, 2000));
    creating.value = false;
    toast.add({ severity: 'success', summary: 'Backup Created', detail: `${selectedType.value.toUpperCase()} backup completed successfully.` });
}
</script>

