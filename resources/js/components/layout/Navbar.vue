<template>
    <!-- Government header bar -->
    <header :class="['shrink-0', showUserMenu ? 'z-50' : 'z-30']">
        <!-- Top accent strip -->
        <div class="h-[3px] bg-gradient-to-r from-[#1a3557] via-[#b8860b] to-[#1a3557]"></div>

        <!-- Main navbar -->
        <div class="h-14 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center px-4 gap-3 shadow-sm">

            <!-- Sidebar toggle -->
            <button
                @click="$emit('toggle')"
                class="h-8 w-8 inline-flex items-center justify-center rounded-md text-[#1a3557] dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <i class="pi pi-bars text-[14px]"></i>
            </button>

            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700"></div>

            <!-- Breadcrumb -->
            <div class="hidden md:flex items-center gap-1.5 text-sm">
                <span class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-medium">TDMS</span>
                <i class="pi pi-chevron-right text-[9px] text-slate-300"></i>
                <span class="font-semibold text-[#1a3557] dark:text-slate-200 text-sm">{{ pageTitle }}</span>
            </div>

            <div class="flex-1"></div>

            <!-- Global search -->
            <GlobalSearch />

            <!-- Tools -->
            <div class="flex items-center gap-1">
                <RouterLink
                    to="/ocr"
                    class="h-8 inline-flex items-center gap-1.5 rounded-md px-2.5 text-xs font-medium transition-colors"
                    :class="isToolActive('/ocr')
                        ? 'bg-[#1a3557]/10 text-[#1a3557] dark:bg-slate-800 dark:text-blue-300'
                        : 'text-slate-500 hover:bg-slate-100 hover:text-[#1a3557] dark:hover:bg-slate-800 dark:hover:text-slate-300'"
                    title="OCR Scanner"
                >
                    <i class="pi pi-camera text-[13px]"></i>
                    <span class="hidden lg:inline">OCR Scanner</span>
                </RouterLink>
                <RouterLink
                    to="/gis"
                    class="h-8 inline-flex items-center gap-1.5 rounded-md px-2.5 text-xs font-medium transition-colors"
                    :class="isToolActive('/gis')
                        ? 'bg-[#1a3557]/10 text-[#1a3557] dark:bg-slate-800 dark:text-blue-300'
                        : 'text-slate-500 hover:bg-slate-100 hover:text-[#1a3557] dark:hover:bg-slate-800 dark:hover:text-slate-300'"
                    title="GIS Map"
                >
                    <i class="pi pi-map text-[13px]"></i>
                    <span class="hidden lg:inline">GIS Map</span>
                </RouterLink>
                <RouterLink
                    to="/land-map"
                    class="h-8 inline-flex items-center gap-1.5 rounded-md px-2.5 text-xs font-medium transition-colors"
                    :class="isToolActive('/land-map')
                        ? 'bg-[#1a3557]/10 text-[#1a3557] dark:bg-slate-800 dark:text-blue-300'
                        : 'text-slate-500 hover:bg-slate-100 hover:text-[#1a3557] dark:hover:bg-slate-800 dark:hover:text-slate-300'"
                    title="Land Mapping"
                >
                    <i class="pi pi-sitemap text-[13px]"></i>
                    <span class="hidden lg:inline">Land Map</span>
                </RouterLink>
            </div>

            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700"></div>

            <!-- User menu -->
            <div class="relative">
                <button
                    @click="showUserMenu = !showUserMenu"
                    class="flex items-center gap-2 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 px-2 py-1 transition-colors"
                >
                    <div class="w-7 h-7 rounded-full bg-[#1a3557] flex items-center justify-center text-white text-xs font-bold shrink-0 uppercase ring-2 ring-[#b8860b]">
                        {{ initials }}
                    </div>
                    <div class="hidden md:block text-left">
                        <p class="text-xs font-semibold text-[#1a3557] dark:text-slate-200 leading-tight">{{ authStore.user?.name?.split(' ')[0] }}</p>
                        <p class="text-[10px] text-slate-400 leading-tight">{{ authStore.user?.roles?.[0]?.name }}</p>
                    </div>
                    <i class="pi pi-chevron-down text-[10px] text-slate-400"></i>
                </button>

                <Transition name="dropdown">
                    <div v-if="showUserMenu" @click.stop
                        class="absolute right-0 top-11 w-56 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl z-50 overflow-hidden">
                        <!-- User info header -->
                        <div class="px-4 py-3 bg-[#1a3557] text-white">
                            <p class="text-sm font-semibold truncate">{{ authStore.user?.name }}</p>
                            <p class="text-xs text-blue-200 truncate">{{ authStore.user?.email }}</p>
                        </div>
                        <div class="py-1">
                            <RouterLink to="/profile"
                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                                @click="showUserMenu = false">
                                <i class="pi pi-user text-[#1a3557] dark:text-blue-400 text-sm"></i> My Profile
                            </RouterLink>
                            <RouterLink to="/settings"
                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                                @click="showUserMenu = false">
                                <i class="pi pi-cog text-[#1a3557] dark:text-blue-400 text-sm"></i> Settings
                            </RouterLink>
                            <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                            <button type="button" @click.stop="handleLogout"
                                class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30">
                                <i class="pi pi-sign-out text-sm"></i> Log out
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </header>

    <div v-if="showUserMenu"
        @click="showUserMenu = false"
        class="fixed inset-0 z-40">
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import GlobalSearch from '@/components/layout/GlobalSearch.vue';

defineProps({ collapsed: { type: Boolean, default: false } });
defineEmits(['toggle']);

const route    = useRoute();
const router   = useRouter();
const authStore = useAuthStore();

const showUserMenu   = ref(false);

const initials = computed(() => {
    const name = authStore.user?.name || '';
    return name.split(' ').map(w => w[0]).slice(0, 2).join('');
});

function isToolActive(path) {
    return route.path === path || route.path.startsWith(`${path}/`);
}
const routeTitles = {
    dashboard: 'Dashboard', 'tax-declarations': 'Tax Declarations',
    'td-create': 'New Tax Declaration', 'td-show': 'Declaration Details',
    'td-edit': 'Edit Declaration', 'td-pdf': 'Export PDF', workflow: 'Workflow Management',
    'field-appraisals': 'Field Appraisals', 'fa-create': 'New Field Appraisal',
    'fa-show': 'Appraisal Details', 'fa-edit': 'Edit Appraisal', 'fa-pdf': 'Export PDF',
    'property-owners': 'Property Owners', properties: 'Property Owners',
    'property-improvements': 'Property Improvements',
    'property-locations': 'Property Locations',
    gis: 'GIS Map', 'land-map': 'Land Mapping', ocr: 'OCR Scanner', 'ocr-review': 'OCR Review',
    documents: 'Supporting Documents', archive: 'Archive', reports: 'Reports',
    'ownership-history': 'Ownership History',
    users: 'User Management',
    roles: 'Roles & Permissions', audit: 'Audit Trail',
    settings: 'System Settings', profile: 'My Profile',
    search: 'Search Results',
};

const pageTitle = computed(() => routeTitles[route.name] || 'TDMS');

async function handleLogout() {
    showUserMenu.value = false;
    await authStore.logout();
    router.push({ name: 'login' });
}
</script>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to      { opacity: 0; transform: translateY(-6px) scale(0.97); }
</style>
