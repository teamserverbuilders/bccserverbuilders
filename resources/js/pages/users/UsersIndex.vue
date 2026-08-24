<template>
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">User Management</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage system users and their accounts</p>
            </div>
            <div class="flex items-center gap-2">
                <RouterLink to="/roles">
                    <Button label="Roles" icon="pi pi-shield" outlined size="small" />
                </RouterLink>
                <Button label="Add User" icon="pi pi-user-plus" @click="openUserDialog()" />
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="stat in userStats" :key="stat.label" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ stat.value }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
                <InputText v-model="search" placeholder="Search users..." class="flex-1 max-w-sm" @keyup.enter="loadUsers" />
                <Select v-model="filterStatus" :options="['active','inactive','suspended']" placeholder="Status" showClear @change="loadUsers" />
            </div>

            <DataTable :value="users" :loading="loading" class="p-datatable-sm" striped-rows>
                <Column header="User" style="min-width: 220px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <img :src="data.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(data.name)}&background=random`"
                                class="w-9 h-9 rounded-full object-cover border-2 border-gray-100 dark:border-gray-700" :alt="data.name" />
                            <div>
                                <p class="font-medium text-sm text-gray-800 dark:text-white">{{ data.name }}</p>
                                <p class="text-xs text-gray-400">{{ data.email }}</p>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column field="department.name" header="Department" />
                <Column field="position.name" header="Position" />
                <Column header="Role">
                    <template #body="{ data }">
                        <Tag :value="data.roles?.[0]?.name || 'No Role'" class="text-xs" />
                    </template>
                </Column>
                <Column field="status" header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'danger'" class="text-xs capitalize" />
                    </template>
                </Column>
                <Column header="Last Login">
                    <template #body="{ data }">
                        <span class="text-xs text-gray-500">{{ data.last_login_at ? new Date(data.last_login_at).toLocaleDateString() : 'Never' }}</span>
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 200px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Button icon="pi pi-pencil" size="small" text rounded @click="openUserDialog(data)" v-tooltip="'Edit'" />
                            <Button icon="pi pi-lock" size="small" text rounded severity="help" @click="openPermissions(data)" v-tooltip="'Permissions'" />
                            <Button icon="pi pi-key" size="small" text rounded severity="secondary" @click="openResetPwd(data)" v-tooltip="'Reset Password'" />
                            <Button :icon="data.status === 'active' ? 'pi pi-ban' : 'pi pi-check'" size="small" text rounded
                                :severity="data.status === 'active' ? 'danger' : 'success'" @click="toggleStatus(data)" v-tooltip="data.status === 'active' ? 'Disable' : 'Enable'" />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                <Paginator :rows="15" :totalRecords="total" :first="(page-1)*15" @page="e => { page = e.page+1; loadUsers(); }"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" />
            </div>
        </div>

        <!-- User Dialog -->
        <Dialog v-model:visible="showDialog" :header="editUser ? 'Edit User' : 'Add User'" :modal="true" class="w-full max-w-lg">
            <form @submit.prevent="saveUser" class="space-y-4 pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Full Name</label>
                        <InputText v-model="form.name" class="w-full" required />
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">Email Address</label>
                        <InputText v-model="form.email" type="email" class="w-full" required />
                    </div>
                    <div v-if="!editUser">
                        <label class="form-label">Password</label>
                        <Password v-model="form.password" class="w-full" toggleMask :feedback="true" inputClass="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Employee ID</label>
                        <InputText v-model="form.employee_id" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Department</label>
                        <Select v-model="form.department_id" :options="departments" optionLabel="name" optionValue="id" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="form-label">Position</label>
                        <Select v-model="form.position_id" :options="positions.filter(p => !form.department_id || p.department_id === form.department_id)" optionLabel="name" optionValue="id" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="form-label">Role</label>
                        <Select v-model="form.role" :options="roles" optionLabel="name" optionValue="name" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <Select v-model="form.status" :options="['active','inactive','suspended']" class="w-full" />
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <Button type="submit" :label="editUser ? 'Update User' : 'Add User'" class="flex-1" :loading="saving" />
                    <Button label="Cancel" outlined class="flex-1" type="button" @click="showDialog = false" />
                </div>
            </form>
        </Dialog>

        <!-- Permissions Dialog -->
        <Dialog
            v-model:visible="showPermDialog"
            :header="`Permissions — ${permUser?.name || ''}`"
            :modal="true"
            class="w-full max-w-3xl"
            :style="{ width: '90vw', maxWidth: '48rem' }"
        >
            <div class="space-y-4 pt-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Choose which functions this user can access. Role sets a template; checkboxes control exact access.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div class="flex-1">
                        <label class="form-label">Role</label>
                        <Select
                            v-model="permForm.role"
                            :options="roles"
                            optionLabel="name"
                            optionValue="name"
                            class="w-full"
                            showClear
                            placeholder="Select role"
                        />
                    </div>
                    <Button
                        label="Apply role template"
                        icon="pi pi-copy"
                        outlined
                        size="small"
                        :disabled="!permForm.role"
                        @click="applyRoleTemplate"
                    />
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 max-h-[50vh] overflow-y-auto">
                    <div v-for="(perms, group) in groupedPermissions" :key="group" class="mb-5 last:mb-0">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                {{ formatGroup(group) }}
                            </h4>
                            <div class="flex gap-2">
                                <button type="button" class="text-xs text-blue-600 hover:underline" @click="selectGroup(perms, true)">All</button>
                                <button type="button" class="text-xs text-gray-500 hover:underline" @click="selectGroup(perms, false)">None</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                            <label
                                v-for="perm in perms"
                                :key="perm.id"
                                class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/60 cursor-pointer"
                            >
                                <Checkbox v-model="permForm.permissions" :inputId="`perm-${perm.id}`" :value="perm.name" />
                                <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ formatAction(perm.name) }}</span>
                            </label>
                        </div>
                    </div>
                    <p v-if="!Object.keys(groupedPermissions).length" class="text-sm text-gray-400 text-center py-6">Loading permissions…</p>
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <span class="text-xs text-gray-500">{{ permForm.permissions.length }} permission(s) selected</span>
                    <div class="flex gap-2">
                        <Button label="Cancel" outlined @click="showPermDialog = false" />
                        <Button label="Save Permissions" icon="pi pi-save" :loading="savingPerms" @click="savePermissions" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Reset Password Dialog -->
        <Dialog v-model:visible="showResetDialog" header="Reset Password" :modal="true" class="w-96">
            <div class="space-y-4 pt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Reset password for <strong>{{ resetUser?.name }}</strong></p>
                <div>
                    <label class="form-label">New Password</label>
                    <Password v-model="newPassword" class="w-full" toggleMask :feedback="true" inputClass="w-full" />
                </div>
                <div class="flex gap-2">
                    <Button label="Reset Password" class="flex-1" :loading="resetting" @click="resetPassword" />
                    <Button label="Cancel" outlined class="flex-1" @click="showResetDialog = false" />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Paginator from 'primevue/paginator';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
import axios from 'axios';

const toast = useToast();
const users = ref([]);
const loading = ref(false);
const total = ref(0);
const page = ref(1);
const search = ref('');
const filterStatus = ref(null);
const departments = ref([]);
const positions = ref([]);
const roles = ref([]);
const groupedPermissions = ref({});
const showDialog = ref(false);
const editUser = ref(null);
const saving = ref(false);
const showResetDialog = ref(false);
const resetUser = ref(null);
const newPassword = ref('');
const resetting = ref(false);

const showPermDialog = ref(false);
const permUser = ref(null);
const savingPerms = ref(false);
const permForm = reactive({ role: null, permissions: [] });

const form = reactive({ name: '', email: '', password: '', employee_id: '', department_id: null, position_id: null, role: null, status: 'active' });

const userStats = computed(() => [
    { label: 'Total Users', value: total.value },
    { label: 'Active', value: users.value.filter(u => u.status === 'active').length },
    { label: 'Inactive', value: users.value.filter(u => u.status === 'inactive').length },
    { label: 'Departments', value: departments.value.length },
]);

async function loadUsers() {
    loading.value = true;
    try {
        const res = await axios.get('/users', { params: { search: search.value, status: filterStatus.value, page: page.value } });
        users.value = res.data.data;
        total.value = res.data.total;
    } finally { loading.value = false; }
}

function openUserDialog(user = null) {
    editUser.value = user;
    Object.assign(form, user ? { ...user, password: '', role: user.roles?.[0]?.name } : { name: '', email: '', password: '', employee_id: '', department_id: null, position_id: null, role: null, status: 'active' });
    showDialog.value = true;
}

async function saveUser() {
    saving.value = true;
    try {
        if (editUser.value) {
            await axios.put(`/users/${editUser.value.id}`, form);
            toast.add({ severity: 'success', summary: 'Updated', detail: 'User updated.' });
        } else {
            await axios.post('/users', form);
            toast.add({ severity: 'success', summary: 'Created', detail: 'User created.' });
        }
        showDialog.value = false;
        loadUsers();
    } finally { saving.value = false; }
}

async function openPermissions(user) {
    permUser.value = user;
    permForm.role = user.roles?.[0]?.name || null;
    permForm.permissions = [];
    showPermDialog.value = true;

    try {
        const res = await axios.get(`/users/${user.id}`);
        const data = res.data;
        permForm.role = data.roles?.[0]?.name || null;
        // Prefer direct permissions; fall back to all (role) permissions
        const direct = data.direct_permission_names || [];
        const all = data.permission_names || [];
        permForm.permissions = [...(direct.length ? direct : all)];
    } catch (err) {
        toast.apiError?.(err, 'Failed to load user permissions') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load permissions.' });
    }
}

function applyRoleTemplate() {
    const role = roles.value.find(r => r.name === permForm.role);
    if (!role) return;
    permForm.permissions = (role.permissions || []).map(p => p.name);
    toast.add({ severity: 'info', summary: 'Template applied', detail: `Loaded ${permForm.permissions.length} permissions from ${role.name}.` });
}

function selectGroup(perms, on) {
    const names = perms.map(p => p.name);
    if (on) {
        names.forEach(n => {
            if (!permForm.permissions.includes(n)) permForm.permissions.push(n);
        });
    } else {
        permForm.permissions = permForm.permissions.filter(p => !names.includes(p));
    }
}

function formatGroup(group) {
    return String(group).replace(/-/g, ' ');
}

function formatAction(name) {
    const parts = String(name).split('.');
    return (parts[1] || parts[0] || name).replace(/-/g, ' ');
}

async function savePermissions() {
    if (!permUser.value) return;
    savingPerms.value = true;
    try {
        await axios.put(`/users/${permUser.value.id}/permissions`, {
            role: permForm.role,
            permissions: permForm.permissions,
        });
        toast.add({ severity: 'success', summary: 'Saved', detail: 'User permissions updated.' });
        showPermDialog.value = false;
        loadUsers();
    } catch (err) {
        toast.apiError?.(err, 'Failed to save permissions') || toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save permissions.' });
    } finally {
        savingPerms.value = false;
    }
}

function openResetPwd(user) { resetUser.value = user; newPassword.value = ''; showResetDialog.value = true; }

async function resetPassword() {
    resetting.value = true;
    try {
        await axios.post(`/users/${resetUser.value.id}/reset-password`, { password: newPassword.value });
        toast.add({ severity: 'success', summary: 'Reset', detail: 'Password reset.' });
        showResetDialog.value = false;
    } finally { resetting.value = false; }
}

async function toggleStatus(user) {
    await axios.post(`/users/${user.id}/toggle-status`);
    toast.add({ severity: 'info', summary: 'Updated', detail: 'User status changed.' });
    loadUsers();
}

onMounted(async () => {
    loadUsers();
    const [dep, pos, rol, perms] = await Promise.all([
        axios.get('/users/departments'),
        axios.get('/users/positions'),
        axios.get('/users/roles'),
        axios.get('/users/permissions'),
    ]);
    departments.value = dep.data;
    positions.value = pos.data;
    roles.value = rol.data;
    groupedPermissions.value = perms.data;
});
</script>
