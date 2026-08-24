<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="text-center">
            <h1 class="text-9xl font-black text-gray-200 dark:text-gray-700">404</h1>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white -mt-4">Page Not Found</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-2 mb-6">The page you're looking for doesn't exist.</p>
            <RouterLink to="/"><Button label="Back to Dashboard" icon="pi pi-home" /></RouterLink>
        </div>
    </div>
</template>
<script setup>
import { RouterLink } from 'vue-router';
import Button from 'primevue/button';
</script>
