<template>
    <div class="space-y-5">

        <!-- Page Header -->
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-1 h-6 bg-[#b8860b] rounded-full"></div>
                    <h1 class="text-xl font-bold text-[#1a3557] dark:text-slate-50">Dashboard</h1>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 ml-3">{{ today }}</p>
            </div>
            <RouterLink to="/tax-declarations/create">
                <button class="inline-flex items-center gap-2 h-9 px-4 rounded-md bg-[#1a3557] hover:bg-[#1e4880] text-white text-sm font-medium transition-colors shadow-sm">
                    <i class="pi pi-plus text-xs"></i> New Declaration
                </button>
            </RouterLink>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="kpi in kpiCards" :key="kpi.label"
                class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="h-1 w-full" :class="kpi.topBar"></div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ kpi.label }}</span>
                        <div :class="['w-8 h-8 rounded flex items-center justify-center', kpi.iconBg]">
                            <i :class="['pi text-sm', kpi.icon, kpi.iconColor]"></i>
                        </div>
                    </div>
                    <div v-if="loading" class="h-8 w-24 bg-slate-100 dark:bg-slate-800 rounded animate-pulse"></div>
                    <div v-else>
                        <p class="text-2xl font-bold text-[#1a3557] dark:text-slate-50">{{ (stats[kpi.key] || 0).toLocaleString() }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ kpi.sub }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classification status strip -->
        <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
            <div v-for="s in statusCards" :key="s.key"
                class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3 shadow-sm">
                <div class="flex items-center gap-2 mb-1.5">
                    <span :class="['w-2 h-2 rounded-full shrink-0', s.dot]"></span>
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{{ s.label }}</span>
                </div>
                <span v-if="loading" class="block h-6 w-10 bg-slate-100 dark:bg-slate-800 rounded animate-pulse"></span>
                <span v-else class="text-xl font-bold text-[#1a3557] dark:text-slate-50">{{ (stats[s.key] || 0).toLocaleString() }}</span>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Monthly Bar Chart -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-chart-bar text-[#1a3557] dark:text-blue-400 text-sm"></i>
                        <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Monthly Registrations</span>
                    </div>
                    <span class="text-xs font-medium text-slate-400 border border-slate-200 dark:border-slate-700 rounded px-2 py-0.5">{{ currentYear }}</span>
                </div>
                <div class="p-5 h-56">
                    <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
                    <div v-else class="h-full bg-slate-50 dark:bg-slate-800/30 rounded animate-pulse"></div>
                </div>
            </div>

            <!-- Doughnut -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <i class="pi pi-chart-pie text-[#1a3557] dark:text-blue-400 text-sm"></i>
                    <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">By Classification</span>
                </div>
                <div class="p-5">
                    <div class="h-36 flex items-center justify-center">
                        <Doughnut v-if="classChartData" :data="classChartData" :options="doughnutOptions" />
                        <div v-else class="w-36 h-36 rounded-full bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div v-for="item in (stats.by_classification || [])" :key="item.classification_id"
                            class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: item.classification?.color ?? '#1a3557' }"></span>
                                <span class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-[110px]">{{ item.classification?.name }}</span>
                            </div>
                            <span class="text-xs font-bold text-[#1a3557] dark:text-slate-200">{{ item.count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Top Barangays -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <i class="pi pi-map-marker text-[#1a3557] dark:text-blue-400 text-sm"></i>
                    <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Top Barangays</span>
                </div>
                <div class="p-4 space-y-3">
                    <div v-for="(b, i) in (stats.by_barangay || []).slice(0, 7)" :key="b.barangay_id"
                        class="flex items-center gap-3">
                        <span class="w-5 h-5 rounded bg-[#1a3557] text-white text-[10px] font-bold flex items-center justify-center shrink-0">{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-700 dark:text-slate-300 truncate">{{ b.barangay?.name }}</span>
                                <span class="text-xs font-bold text-[#1a3557] dark:text-slate-200 ml-2">{{ b.count }}</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-[#1a3557] rounded-full transition-all duration-700"
                                    :style="{ width: `${Math.min((b.count / ((stats.by_barangay?.[0]?.count) || 1)) * 100, 100)}%` }">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats.by_barangay?.length && !loading" class="py-4 text-center text-xs text-slate-400">No data yet</div>
                    <template v-if="loading">
                        <div v-for="i in 5" :key="i" class="h-6 bg-slate-50 dark:bg-slate-800 rounded animate-pulse"></div>
                    </template>
                </div>
            </div>

            <!-- Digitization -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <i class="pi pi-database text-[#1a3557] dark:text-blue-400 text-sm"></i>
                    <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Digitization Progress</span>
                </div>
                <div class="p-5">
                    <div class="flex flex-col items-center">
                        <div class="relative w-28 h-28">
                            <svg class="w-28 h-28 -rotate-90" viewBox="0 0 112 112">
                                <circle cx="56" cy="56" r="48" fill="none" stroke-width="10" class="stroke-slate-100 dark:stroke-slate-800" />
                                <circle cx="56" cy="56" r="48" fill="none" stroke-width="10"
                                    stroke="#1a3557" stroke-linecap="round"
                                    :stroke-dasharray="301.6"
                                    :stroke-dashoffset="301.6 * (1 - digitizationPercent / 100)"
                                    class="transition-all duration-1000" />
                                <circle cx="56" cy="56" r="48" fill="none" stroke-width="3"
                                    stroke="#b8860b" stroke-linecap="round" stroke-dasharray="4 8"
                                    class="opacity-40" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-[#1a3557] dark:text-slate-50">{{ digitizationPercent }}%</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 mt-2 text-center">{{ stats.approved || 0 }} of {{ stats.total || 0 }} records</p>
                    </div>
                    <div class="mt-4 space-y-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                        <div v-for="s in workflowStatuses" :key="s.key" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span :class="['w-2 h-2 rounded-full', s.dot]"></span>
                                <span class="text-xs text-slate-600 dark:text-slate-400">{{ s.label }}</span>
                            </div>
                            <span class="text-xs font-bold text-[#1a3557] dark:text-slate-200">{{ stats[s.key] || 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions + System Status -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm flex flex-col">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <i class="pi pi-bolt text-[#b8860b] text-sm"></i>
                    <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Quick Actions</span>
                </div>
                <div class="p-4 grid grid-cols-2 gap-2">
                    <RouterLink v-for="qa in quickActions" :key="qa.to" :to="qa.to">
                        <div class="flex flex-col items-center gap-1.5 rounded-md border border-slate-200 dark:border-slate-700 p-3 hover:border-[#1a3557] hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition-colors text-center group">
                            <div :class="['w-8 h-8 rounded flex items-center justify-center transition-colors', qa.iconBg, 'group-hover:bg-[#1a3557]']">
                                <i :class="['pi text-sm transition-colors', qa.icon, qa.iconColor, 'group-hover:text-white']"></i>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 group-hover:text-[#1a3557] dark:group-hover:text-slate-200 leading-tight">{{ qa.label }}</span>
                        </div>
                    </RouterLink>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800 px-5 py-3 mt-auto">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">System Status</p>
                    <div class="space-y-1.5">
                        <div v-for="item in systemHealth" :key="item.label" class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span :class="['w-1.5 h-1.5 rounded-full', item.dot]"></span>
                                <span class="text-xs text-slate-500">{{ item.label }}</span>
                            </div>
                            <span :class="['text-[10px] font-semibold px-1.5 py-0.5 rounded', item.badge]">{{ item.status }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale, BarElement,
    ArcElement, Title, Tooltip, Legend,
} from 'chart.js';
import axios from 'axios';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

const loading     = ref(true);
const stats       = ref({});
const today       = computed(() => new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
const currentYear = new Date().getFullYear();

const digitizationPercent = computed(() => {
    if (!stats.value.total) return 0;
    return Math.round(((stats.value.approved || 0) / stats.value.total) * 100);
});

const kpiCards = [
    { label: 'Total Declarations', key: 'total',     icon: 'pi-file-edit', topBar: 'bg-[#1a3557]', iconBg: 'bg-[#1a3557]/10', iconColor: 'text-[#1a3557] dark:text-blue-400', sub: 'All records' },
    { label: 'Approved',           key: 'approved',  icon: 'pi-check-circle', topBar: 'bg-green-500', iconBg: 'bg-green-50 dark:bg-green-950/40', iconColor: 'text-green-600', sub: 'Digitized' },
    { label: "Today's Uploads",    key: 'today_uploads', icon: 'pi-upload', topBar: 'bg-[#b8860b]', iconBg: 'bg-amber-50 dark:bg-amber-950/40', iconColor: 'text-amber-600', sub: 'New today' },
    { label: 'Pending Review',     key: 'pending_verification', icon: 'pi-clock', topBar: 'bg-red-500', iconBg: 'bg-red-50 dark:bg-red-950/40', iconColor: 'text-red-600', sub: 'Needs action' },
];

const statusCards = [
    { label: 'Residential', key: 'residential',  dot: 'bg-blue-500' },
    { label: 'Commercial',  key: 'commercial',   dot: 'bg-sky-500' },
    { label: 'Agricultural',key: 'agricultural', dot: 'bg-green-500' },
    { label: 'Industrial',  key: 'industrial',   dot: 'bg-orange-500' },
    { label: 'Special',     key: 'special',      dot: 'bg-purple-500' },
    { label: 'OCR Queue',   key: 'pending_ocr',  dot: 'bg-amber-500' },
];

const workflowStatuses = [
    { label: 'Draft',            key: 'draft',                dot: 'bg-slate-400' },
    { label: 'OCR Processing',   key: 'pending_ocr',          dot: 'bg-amber-400' },
    { label: 'For Verification', key: 'pending_verification', dot: 'bg-orange-400' },
    { label: 'Approved',         key: 'approved',             dot: 'bg-green-500' },
    { label: 'Archived',         key: 'archived',             dot: 'bg-slate-300' },
];

const quickActions = [
    { label: 'New TD',   to: '/tax-declarations/create', icon: 'pi-plus',      iconBg: 'bg-[#1a3557]/10', iconColor: 'text-[#1a3557]' },
    { label: 'OCR Scan', to: '/ocr',                     icon: 'pi-camera',    iconBg: 'bg-violet-100 dark:bg-violet-950/40', iconColor: 'text-violet-600' },
    { label: 'GIS Map',  to: '/gis',                     icon: 'pi-map',       iconBg: 'bg-green-100 dark:bg-green-950/40', iconColor: 'text-green-600' },
    { label: 'Reports',  to: '/reports',                 icon: 'pi-chart-bar', iconBg: 'bg-amber-100 dark:bg-amber-950/40', iconColor: 'text-amber-600' },
];

const systemHealth = [
    { label: 'Database',    dot: 'bg-green-400', status: 'Online',    badge: 'bg-green-100 text-green-700' },
    { label: 'Storage',     dot: 'bg-blue-400',  status: '45% Used',  badge: 'bg-blue-100 text-blue-700' },
    { label: 'API',         dot: 'bg-green-400', status: 'Healthy',   badge: 'bg-green-100 text-green-700' },
    { label: 'Last Backup', dot: 'bg-green-400', status: 'Today',     badge: 'bg-green-100 text-green-700' },
];

const chartData = computed(() => {
    if (!stats.value.monthly_data?.length) return null;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const data   = Array(12).fill(0);
    stats.value.monthly_data.forEach(m => { data[m.month - 1] = m.count; });
    return {
        labels: months,
        datasets: [{
            label: 'Registrations',
            data,
            backgroundColor: 'rgba(26,53,87,0.15)',
            borderColor: '#1a3557',
            borderWidth: 2,
            borderRadius: 3,
            borderSkipped: false,
        }],
    };
});

const classChartData = computed(() => {
    if (!stats.value.by_classification?.length) return null;
    return {
        labels: stats.value.by_classification.map(c => c.classification?.name || 'Unknown'),
        datasets: [{
            data: stats.value.by_classification.map(c => c.count),
            backgroundColor: stats.value.by_classification.map(c => c.classification?.color || '#1a3557'),
            borderWidth: 0,
            hoverOffset: 4,
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} records` } },
    },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
    },
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: { legend: { display: false } },
};

onMounted(async () => {
    try {
        const { data } = await axios.get('dashboard/statistics');
        stats.value = data;
    } finally {
        loading.value = false;
    }
});
</script>
