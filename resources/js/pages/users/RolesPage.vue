<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Roles & Permissions</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Define access levels and assign function permissions for each role
                </p>
            </div>
            <Button label="New Role" icon="pi pi-plus" @click="openRoleDialog" />
        </div>

        <!-- Roles table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-slate-50/60 dark:bg-slate-800/40">
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield text-[#1a3557] dark:text-blue-300 text-sm"></i>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Roles</h2>
                    <Tag :value="`${roles.length}`" severity="secondary" class="text-[10px]" />
                </div>
                <span class="text-[11px] text-gray-400 hidden sm:inline">
                    Protected admin roles cannot be deleted
                </span>
            </div>

            <DataTable
                :value="roles"
                :loading="loading"
                dataKey="id"
                selectionMode="single"
                v-model:selection="selectedRole"
                class="p-datatable-sm"
                striped-rows
                paginator
                :rows="10"
                :rowsPerPageOptions="[5, 10, 25]"
                @row-select="onRoleSelect"
            >
                <template #empty>
                    <div class="text-center py-12 text-gray-400">
                        <i class="pi pi-inbox text-3xl mb-2 block opacity-40"></i>
                        <p class="text-sm">No roles yet. Create one to get started.</p>
                    </div>
                </template>

                <Column field="name" header="Role" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span
                                class="h-8 w-8 rounded-lg shrink-0 flex items-center justify-center text-white text-xs"
                                :class="data.is_protected ? 'bg-[#1a3557]' : 'bg-slate-500'"
                            >
                                <i :class="data.is_protected ? 'pi pi-verified' : 'pi pi-user'" class="text-[11px]"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ data.name }}</p>
                                <p v-if="data.is_protected" class="text-[10px] text-amber-600 dark:text-amber-400">System protected</p>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="Permissions" sortable sortField="permissions_count">
                    <template #body="{ data }">
                        <Tag
                            :value="`${data.permissions?.length || 0} assigned`"
                            :severity="(data.permissions?.length || 0) ? 'info' : 'secondary'"
                            class="text-xs"
                        />
                    </template>
                </Column>

                <Column field="users_count" header="Users" sortable>
                    <template #body="{ data }">
                        <span class="text-sm text-gray-700 dark:text-gray-300 tabular-nums">
                            {{ data.users_count ?? 0 }}
                        </span>
                    </template>
                </Column>

                <Column header="Status">
                    <template #body="{ data }">
                        <Tag
                            v-if="data.is_protected"
                            value="Protected"
                            severity="warn"
                            icon="pi pi-lock"
                            class="text-xs"
                        />
                        <Tag
                            v-else
                            value="Custom"
                            severity="success"
                            class="text-xs"
                        />
                    </template>
                </Column>

                <Column header="Actions" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex items-center gap-0.5">
                            <Button
                                icon="pi pi-cog"
                                size="small"
                                text
                                rounded
                                v-tooltip.top="'Manage permissions'"
                                @click.stop="selectRole(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                text
                                rounded
                                severity="danger"
                                :disabled="data.is_protected"
                                v-tooltip.top="data.is_protected ? 'Admin roles cannot be deleted' : 'Delete role'"
                                @click.stop="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Permissions panel -->
        <div
            v-if="selectedRole"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
        >
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-[#1a3557]/[0.03] dark:bg-slate-800/50">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                            {{ selectedRole.name }}
                        </h3>
                        <Tag
                            v-if="selectedRole.is_protected"
                            value="Protected"
                            severity="warn"
                            class="text-[10px]"
                        />
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ selectedPermissions.length }} of {{ totalPermissionCount }} permissions selected
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button
                        label="Close"
                        icon="pi pi-times"
                        outlined
                        size="small"
                        severity="secondary"
                        @click="selectedRole = null"
                    />
                    <Button
                        label="Save Permissions"
                        icon="pi pi-save"
                        size="small"
                        :loading="saving"
                        @click="savePermissions"
                    />
                </div>
            </div>

            <div class="p-5 space-y-5 max-h-[calc(100vh-320px)] overflow-y-auto">
                <div
                    v-for="(perms, group) in groupedPermissions"
                    :key="group"
                    class="rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden"
                >
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-gray-100 dark:border-gray-700">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ formatGroup(group) }}
                            <span class="ml-1.5 font-normal text-gray-400 normal-case tracking-normal">
                                ({{ countSelectedInGroup(perms) }}/{{ perms.length }})
                            </span>
                        </h4>
                        <div class="flex items-center gap-3">
                            <button type="button" class="text-[11px] text-[#1a3557] dark:text-blue-400 hover:underline" @click="selectAll(perms)">
                                Select all
                            </button>
                            <button type="button" class="text-[11px] text-gray-500 hover:underline" @click="deselectAll(perms)">
                                Clear
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1 p-2">
                        <label
                            v-for="perm in perms"
                            :key="perm.id"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                        >
                            <Checkbox v-model="selectedPermissions" :value="perm.name" :binary="false" />
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">
                                {{ formatPermissionAction(perm.name) }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty hint when no role selected -->
        <div
            v-else
            class="bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-200 dark:border-gray-700 px-6 py-10 text-center text-gray-400"
        >
            <i class="pi pi-sliders-h text-3xl mb-2 block opacity-40"></i>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Select a role to manage its permissions</p>
            <p class="text-xs mt-1">Click a row or use the gear icon in Actions</p>
        </div>

        <!-- New Role dialog -->
        <Dialog
            v-model:visible="showDialog"
            header="Create New Role"
            :modal="true"
            class="w-full max-w-md"
            @hide="newRoleName = ''"
        >
            <div class="space-y-4 pt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Create a custom role, then assign permissions from the table below.
                </p>
                <div>
                    <label class="form-label">Role name <span class="text-red-500">*</span></label>
                    <InputText
                        v-model="newRoleName"
                        class="w-full"
                        placeholder="e.g. Field Inspector"
                        @keyup.enter="createRole"
                    />
                </div>
                <div class="flex gap-2 pt-1">
                    <Button label="Create Role" icon="pi pi-check" class="flex-1" :loading="creating" :disabled="!newRoleName.trim()" @click="createRole" />
                    <Button label="Cancel" outlined class="flex-1" @click="showDialog = false" />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useToast } from '@/composables/useToast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import axios from 'axios';

const toast = useToast();
const confirm = useConfirm();

const roles = ref([]);
const allPermissions = ref({});
const selectedRole = ref(null);
const selectedPermissions = ref([]);
const loading = ref(false);
const saving = ref(false);
const showDialog = ref(false);
const newRoleName = ref('');
const creating = ref(false);
const dirty = ref(false);

const groupedPermissions = computed(() => allPermissions.value);

const totalPermissionCount = computed(() =>
    Object.values(allPermissions.value).reduce((sum, list) => sum + (list?.length || 0), 0)
);

watch(selectedRole, (role) => {
    selectedPermissions.value = role?.permissions?.map(p => p.name) || [];
    dirty.value = false;
});

watch(selectedPermissions, () => {
    if (selectedRole.value) dirty.value = true;
}, { deep: true });

function formatGroup(group) {
    return String(group || '').replace(/-/g, ' ');
}

function formatPermissionAction(name) {
    const part = String(name || '').split('.')[1] || name;
    return part.replace(/_/g, ' ');
}

function countSelectedInGroup(perms) {
    const names = perms.map(p => p.name);
    return selectedPermissions.value.filter(p => names.includes(p)).length;
}

function selectRole(role) {
    selectedRole.value = role;
}

function onRoleSelect(event) {
    selectedRole.value = event.data;
}

async function loadRoles() {
    loading.value = true;
    try {
        const res = await axios.get('/users/roles');
        roles.value = res.data;
        // Keep selection in sync after reload
        if (selectedRole.value) {
            const refreshed = roles.value.find(r => r.id === selectedRole.value.id);
            selectedRole.value = refreshed || null;
        }
    } catch (err) {
        toast.apiError?.(err, 'Failed to load roles') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load roles.' });
    } finally {
        loading.value = false;
    }
}

async function savePermissions() {
    if (!selectedRole.value) return;
    saving.value = true;
    try {
        const { data } = await axios.put(`/users/roles/${selectedRole.value.id}`, {
            permissions: selectedPermissions.value,
        });
        toast.add({ severity: 'success', summary: 'Saved', detail: `Permissions updated for ${data.name}.` });
        dirty.value = false;
        await loadRoles();
    } catch (err) {
        toast.apiError?.(err, 'Failed to save permissions') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save permissions.' });
    } finally {
        saving.value = false;
    }
}

function selectAll(perms) {
    perms.forEach(p => {
        if (!selectedPermissions.value.includes(p.name)) {
            selectedPermissions.value.push(p.name);
        }
    });
}

function deselectAll(perms) {
    const names = perms.map(p => p.name);
    selectedPermissions.value = selectedPermissions.value.filter(p => !names.includes(p));
}

function openRoleDialog() {
    newRoleName.value = '';
    showDialog.value = true;
}

async function createRole() {
    const name = newRoleName.value.trim();
    if (!name) {
        toast.add({ severity: 'warn', summary: 'Required', detail: 'Enter a role name.' });
        return;
    }
    creating.value = true;
    try {
        const { data } = await axios.post('/users/roles', { name });
        toast.add({ severity: 'success', summary: 'Created', detail: `Role "${data.name}" created.` });
        showDialog.value = false;
        await loadRoles();
        selectedRole.value = roles.value.find(r => r.id === data.id) || data;
    } catch (err) {
        toast.apiError?.(err, 'Failed to create role') || toast.add({
            severity: 'error',
            summary: 'Error',
            detail: err.response?.data?.message || 'Failed to create role.',
        });
    } finally {
        creating.value = false;
    }
}

function confirmDelete(role) {
    if (role.is_protected) return;

    const assigned = role.users_count ?? 0;
    if (assigned > 0) {
        confirm.require({
            header: 'Cannot delete role',
            message: `"${role.name}" is assigned to ${assigned} user(s). Reassign those users first, then try again.`,
            icon: 'pi pi-info-circle',
            acceptLabel: 'Got it',
            rejectClass: 'hidden',
            acceptSeverity: 'secondary',
            accept: () => {},
        });
        return;
    }

    confirm.require({
        header: 'Delete role?',
        message: `Delete "${role.name}"? This cannot be undone.`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptSeverity: 'danger',
        accept: async () => {
            try {
                await axios.delete(`/users/roles/${role.id}`);
                toast.add({ severity: 'success', summary: 'Deleted', detail: `Role "${role.name}" removed.` });
                if (selectedRole.value?.id === role.id) selectedRole.value = null;
                await loadRoles();
            } catch (err) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: err.response?.data?.message || 'Failed to delete role.',
                });
            }
        },
    });
}

onMounted(async () => {
    await loadRoles();
    try {
        const res = await axios.get('/users/permissions');
        allPermissions.value = res.data;
    } catch {
        allPermissions.value = {};
    }
});
</script>
