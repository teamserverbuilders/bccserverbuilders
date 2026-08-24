<template>
    <div class="space-y-6">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">My Profile</h1>

        <!-- Profile Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-6 mb-6">
                <div class="relative">
                    <img :src="authStore.user?.avatar_url" class="w-20 h-20 rounded-2xl object-cover border-4 border-blue-100 dark:border-blue-900/30" :alt="authStore.user?.name" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ authStore.user?.name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">{{ authStore.user?.email }}</p>
                    <Tag :value="authStore.user?.roles?.[0]?.name" class="mt-2 text-xs" />
                </div>
            </div>

            <form @submit.prevent="saveProfile" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Full Name</label>
                        <InputText v-model="profileForm.name" class="w-full" required />
                    </div>
                    <div>
                        <label class="form-label">Contact Number</label>
                        <InputText v-model="profileForm.contact_number" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Department</label>
                        <InputText :value="authStore.user?.department?.name" class="w-full" disabled />
                    </div>
                </div>
                <Button type="submit" label="Update Profile" icon="pi pi-save" :loading="savingProfile" />
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="pi pi-lock text-blue-500"></i> Change Password
            </h3>
            <form @submit.prevent="changePassword" class="space-y-4">
                <div>
                    <label class="form-label">Current Password</label>
                    <Password v-model="pwdForm.current_password" class="w-full" :feedback="false" toggleMask inputClass="w-full" />
                </div>
                <div>
                    <label class="form-label">New Password</label>
                    <Password v-model="pwdForm.password" class="w-full" :feedback="true" toggleMask inputClass="w-full" />
                </div>
                <div>
                    <label class="form-label">Confirm Password</label>
                    <Password v-model="pwdForm.password_confirmation" class="w-full" :feedback="false" toggleMask inputClass="w-full" />
                </div>
                <Message v-if="pwdError" severity="error" :closable="false">{{ pwdError }}</Message>
                <Button type="submit" label="Change Password" icon="pi pi-key" :loading="changingPwd" />
            </form>
        </div>

        <!-- Login History -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="pi pi-history text-indigo-500"></i> Recent Login History
            </h3>
            <DataTable :value="loginHistory" class="p-datatable-sm" striped-rows>
                <Column header="Action"><template #body="{ data }">
                    <Tag :value="data.action" :severity="data.action === 'login' ? 'success' : data.action === 'failed' ? 'danger' : 'secondary'" class="text-xs capitalize" />
                </template></Column>
                <Column field="ip_address" header="IP Address" />
                <Column field="browser" header="Browser" />
                <Column header="Date/Time"><template #body="{ data }">
                    <span class="text-xs text-gray-500">{{ new Date(data.created_at).toLocaleString() }}</span>
                </template></Column>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const toast = useToast();
const authStore = useAuthStore();
const savingProfile = ref(false);
const changingPwd = ref(false);
const pwdError = ref('');
const loginHistory = ref([]);

const profileForm = reactive({ name: authStore.user?.name || '', contact_number: authStore.user?.contact_number || '' });
const pwdForm = reactive({ current_password: '', password: '', password_confirmation: '' });

async function saveProfile() {
    savingProfile.value = true;
    try {
        await authStore.updateProfile(profileForm);
        toast.add({ severity: 'success', summary: 'Saved', detail: 'Profile updated.' });
    } finally { savingProfile.value = false; }
}

async function changePassword() {
    pwdError.value = '';
    changingPwd.value = true;
    try {
        await authStore.changePassword(pwdForm);
        toast.add({ severity: 'success', summary: 'Changed', detail: 'Password changed successfully.' });
        Object.assign(pwdForm, { current_password: '', password: '', password_confirmation: '' });
    } catch (err) {
        pwdError.value = err.response?.data?.message || 'Failed to change password.';
    } finally { changingPwd.value = false; }
}

onMounted(async () => {
    const res = await axios.get('/audit/login-logs', { params: { user_id: authStore.user?.id, per_page: 10 } });
    loginHistory.value = res.data.data?.slice(0, 10) || [];
});
</script>

