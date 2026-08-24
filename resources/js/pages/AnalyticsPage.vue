<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Analytics</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Property statistics and performance metrics</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Annual Growth -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Annual Registration Growth</h3>
                <Bar v-if="annualData" :data="annualData" :options="barOptions" class="max-h-60" />
                <Skeleton v-else height="240px" />
            </div>

            <!-- Classification Pie -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Classification Distribution</h3>
                <Doughnut v-if="classData" :data="classData" :options="doughnutOpts" class="max-h-60" />
                <Skeleton v-else height="240px" />
            </div>

            <!-- OCR Accuracy Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-4">OCR Accuracy Trend</h3>
                <Line v-if="ocrTrend" :data="ocrTrend" :options="lineOptions" class="max-h-60" />
                <Skeleton v-else height="240px" />
            </div>

            <!-- Assessment Value by Barangay -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Records by Barangay</h3>
                <Bar v-if="barangayData" :data="barangayData" :options="hBarOptions" class="max-h-60" />
                <Skeleton v-else height="240px" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, LineElement, PointElement, Title, Tooltip, Legend } from 'chart.js';
import Skeleton from 'primevue/skeleton';
import axios from 'axios';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, LineElement, PointElement, Title, Tooltip, Legend);

const stats = ref(null);
const ocrStats = ref(null);
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const annualData = computed(() => {
    if (!stats.value?.monthly_data) return null;
    const data = Array(12).fill(0);
    stats.value.monthly_data.forEach(m => data[m.month - 1] = m.count);
    return {
        labels: months,
        datasets: [{ label: 'Registrations', data, backgroundColor: 'rgba(59,130,246,0.8)', borderRadius: 6 }],
    };
});

const classData = computed(() => {
    if (!stats.value?.by_classification) return null;
    return {
        labels: stats.value.by_classification.map(c => c.classification?.name || 'Unknown'),
        datasets: [{ data: stats.value.by_classification.map(c => c.count), backgroundColor: stats.value.by_classification.map(c => c.classification?.color || '#3B82F6'), borderWidth: 0 }],
    };
});

const ocrTrend = computed(() => {
    if (!ocrStats.value?.monthly) return null;
    const data = Array(12).fill(0);
    ocrStats.value.monthly.forEach(m => data[m.month - 1] = parseFloat(m.avg_confidence) || 0);
    return {
        labels: months,
        datasets: [{ label: 'Avg. Confidence %', data, borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.4, fill: true }],
    };
});

const barangayData = computed(() => {
    if (!stats.value?.by_barangay) return null;
    const top = stats.value.by_barangay.slice(0, 8);
    return {
        labels: top.map(b => b.barangay?.name || ''),
        datasets: [{ label: 'Records', data: top.map(b => b.count), backgroundColor: 'rgba(99,102,241,0.8)', borderRadius: 6 }],
    };
});

const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } };
const hBarOptions = { ...barOptions, indexAxis: 'y' };
const lineOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } };
const doughnutOpts = { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right' } } };

onMounted(async () => {
    const [s, o] = await Promise.all([axios.get('/dashboard/statistics'), axios.get('/reports/ocr-accuracy')]);
    stats.value = s.data;
    ocrStats.value = o.data;
});
</script>
