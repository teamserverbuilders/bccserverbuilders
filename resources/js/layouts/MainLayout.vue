<template>
    <div class="flex h-screen bg-slate-100 dark:bg-slate-950 overflow-hidden">
        <!-- Sidebar -->
        <Sidebar :collapsed="collapsed" />

        <!-- Main area expands/contracts with sidebar -->
        <div class="flex-1 flex flex-col overflow-hidden min-w-0 transition-all duration-300">
            <Navbar :collapsed="collapsed" @toggle="collapsed = !collapsed" />

            <main class="flex-1 overflow-y-auto">
                <div :class="route.meta.fullBleed ? 'p-3 md:p-4 w-full h-full' : 'p-4 md:p-6 w-full'">
                    <RouterView v-slot="{ Component }">
                        <Transition name="fade" mode="out-in">
                            <component :is="Component" :key="$route.fullPath" />
                        </Transition>
                    </RouterView>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import Sidebar from '@/components/layout/Sidebar.vue';
import Navbar from '@/components/layout/Navbar.vue';

const collapsed = ref(false);
const route = useRoute();
</script>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.12s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }
</style>
