<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-lock text-blue-600 text-2xl"></i>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Forgot Password?</h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Contact your system administrator to reset your password.</p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <i class="pi pi-info-circle mr-2"></i>
                        Please contact your department's System Administrator or IT Officer to reset your account password.
                    </p>
                </div>

                <RouterLink to="/login">
                    <Button label="Back to Login" icon="pi pi-arrow-left" outlined class="w-full" />
                </RouterLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import Button from 'primevue/button';
</script>
