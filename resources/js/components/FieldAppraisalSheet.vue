<template>
    <div v-if="appraisal" class="space-y-4">
        <div class="bg-[#1a3557] text-white px-4 py-3 text-center rounded-t-lg">
            <p class="text-[10px] uppercase tracking-[0.2em] opacity-80">Field Appraisal and Assessment Sheet</p>
            <h2 class="text-base font-bold tracking-wide mt-0.5">{{ appraisal.appraisal_no || 'FAAS' }}</h2>
            <p class="text-xs opacity-90 mt-1">
                {{ formTemplateLabel }} · {{ appraisal.owner_name || appraisal.tax_declaration?.owner?.owner_name || '—' }}
            </p>
        </div>

        <div class="inline-flex rounded-md border border-[#1a3557] overflow-hidden">
            <button type="button" class="px-4 py-1.5 text-sm font-semibold transition-colors"
                :class="page === 'front' ? 'bg-[#1a3557] text-white' : 'bg-white dark:bg-slate-900 text-[#1a3557]'"
                @click="page = 'front'">Front Page</button>
            <button type="button" class="px-4 py-1.5 text-sm font-semibold border-l border-[#1a3557] transition-colors"
                :class="page === 'back' ? 'bg-[#1a3557] text-white' : 'bg-white dark:bg-slate-900 text-[#1a3557]'"
                @click="page = 'back'">Back Page</button>
        </div>

        <!-- Identity -->
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-4">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Owner</p>
                    <p>{{ appraisal.owner_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Owner Address</p>
                    <p>{{ appraisal.owner_address || '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">T.I.N.</p>
                    <p>{{ appraisal.owner_tin || '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Tel No.</p>
                    <p>{{ appraisal.owner_telephone || '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Administrator</p>
                    <p>{{ appraisal.administrator_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Linked TD</p>
                    <p>{{ appraisal.tax_declaration?.td_number || '—' }}</p>
                </div>
                <template v-if="appraisal.form_template === 'form_2'">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Update Code</p>
                        <p>{{ appraisal.update_code || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">P.I.N.</p>
                        <p>{{ appraisal.pin || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">A.R.P. No.</p>
                        <p>{{ appraisal.arp_no || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">OCT/TCT/KOT</p>
                        <p>{{ appraisal.oct_tct_kot_no || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Survey No.</p>
                        <p>{{ appraisal.survey_no || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 mb-0.5">Cad/PLS Lot No.</p>
                        <p>{{ appraisal.cad_pls_lot_no || '—' }}</p>
                    </div>
                </template>
            </div>
        </div>

        <template v-if="page === 'front'">
            <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-xs font-bold uppercase text-center text-[#1a3557] mb-2">Property Location</h3>
                    <table class="w-full border-collapse text-sm">
                        <tbody>
                            <tr v-for="row in locationRows" :key="row.label" class="border border-slate-300 dark:border-slate-600">
                                <td class="w-32 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium border-r">{{ row.label }}</td>
                                <td class="px-2 py-1.5">{{ row.value || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <h3 class="text-xs font-bold uppercase text-center text-[#1a3557] mt-4 mb-2">Boundaries</h3>
                    <table class="w-full border-collapse text-sm">
                        <tbody>
                            <tr v-for="row in boundaryRows" :key="row.label" class="border border-slate-300 dark:border-slate-600">
                                <td class="w-20 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium border-r">{{ row.label }}</td>
                                <td class="px-2 py-1.5">{{ row.value || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase text-center text-[#1a3557] mb-2">Land Sketch</h3>
                    <div class="min-h-[220px] border border-slate-300 dark:border-slate-600 flex items-center justify-center bg-slate-50 dark:bg-slate-800/40">
                        <img v-if="appraisal.land_sketch" :src="`/storage/${appraisal.land_sketch}`" alt="Land sketch" class="max-h-[280px] object-contain p-2" />
                        <p v-else class="text-xs text-slate-400">No land sketch</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 overflow-hidden">
                <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xs font-bold uppercase text-center text-[#1a3557]">Land Appraisal</h3>
                </div>
                <div class="p-3 overflow-x-auto">
                    <table class="w-full min-w-[640px] border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800">
                                <th class="border border-slate-300 px-2 py-1.5">Classification Kind</th>
                                <th class="border border-slate-300 px-2 py-1.5">Sub Class</th>
                                <th class="border border-slate-300 px-2 py-1.5">Actual Use</th>
                                <th class="border border-slate-300 px-2 py-1.5">Area</th>
                                <th class="border border-slate-300 px-2 py-1.5">Unit Value</th>
                                <th class="border border-slate-300 px-2 py-1.5">Base Market Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in landRows" :key="'l'+i">
                                <td class="border border-slate-300 px-2 py-1">{{ row.classification_kind || '—' }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-center">{{ row.sub_class || '—' }}</td>
                                <td class="border border-slate-300 px-2 py-1">{{ row.actual_use || '—' }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.area, 6) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.unit_value) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.base_market_value) }}</td>
                            </tr>
                            <tr v-if="!landRows.length">
                                <td colspan="6" class="border border-slate-300 px-2 py-3 text-center text-slate-400">No land rows</td>
                            </tr>
                            <tr class="font-semibold bg-slate-50 dark:bg-slate-800/60">
                                <td colspan="3" class="border border-slate-300 px-2 py-1.5 text-center">Total</td>
                                <td class="border border-slate-300 px-2 py-1.5 text-right">{{ fmt(landTotals.area, 6) }}</td>
                                <td class="border border-slate-300"></td>
                                <td class="border border-slate-300 px-2 py-1.5 text-right">{{ fmt(landTotals.bmv) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 overflow-hidden">
                <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b">
                    <h3 class="text-xs font-bold uppercase text-[#1a3557]">Plants and Trees Appraisal</h3>
                </div>
                <div class="p-3 overflow-x-auto">
                    <table class="w-full min-w-[720px] border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800">
                                <th class="border border-slate-300 px-2 py-1.5">Kind</th>
                                <th class="border border-slate-300 px-2 py-1.5">Prod Class</th>
                                <th class="border border-slate-300 px-2 py-1.5">Area</th>
                                <th class="border border-slate-300 px-2 py-1.5">Non FB</th>
                                <th class="border border-slate-300 px-2 py-1.5">FB</th>
                                <th class="border border-slate-300 px-2 py-1.5">Total</th>
                                <th class="border border-slate-300 px-2 py-1.5">Unit Value</th>
                                <th class="border border-slate-300 px-2 py-1.5">Base Market Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in plantRows" :key="'p'+i">
                                <td class="border border-slate-300 px-2 py-1">{{ row.kind || '—' }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-center">{{ row.prod_class || '—' }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.area_planted, 6) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.non_fb, 0) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.fb, 0) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.total, 0) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.unit_value) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.base_market_value) }}</td>
                            </tr>
                            <tr v-if="!plantRows.length">
                                <td colspan="8" class="border border-slate-300 px-2 py-3 text-center text-slate-400">No plant rows</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-4">
                    <h3 class="text-xs font-bold uppercase text-center text-[#1a3557] mb-2">Value Adjustment Factors</h3>
                    <table class="w-full border-collapse text-xs">
                        <tbody>
                            <tr class="border border-slate-300"><td class="px-2 py-1.5 border-r">Base Market Value</td><td class="px-2 py-1.5 text-right">100%</td></tr>
                            <tr class="border border-slate-300"><td class="px-2 py-1.5 border-r">[a] Along road</td><td class="px-2 py-1.5 text-right">{{ adjPct(appraisal.adj_along_road) }}</td></tr>
                            <tr class="border border-slate-300"><td class="px-2 py-1.5 border-r">[b] kms weather road</td><td class="px-2 py-1.5 text-right">{{ adjPct(appraisal.adj_kms_weather_road) }}</td></tr>
                            <tr class="border border-slate-300"><td class="px-2 py-1.5 border-r">[c] kms to market</td><td class="px-2 py-1.5 text-right">{{ adjPct(appraisal.adj_kms_to_market) }}</td></tr>
                            <tr class="border border-slate-300 font-semibold"><td class="px-2 py-1.5 border-r">Total Adjustments</td><td class="px-2 py-1.5 text-right">{{ appraisal.adj_total_adjustments ?? 0 }}%</td></tr>
                            <tr class="border border-slate-300 font-bold bg-slate-50 dark:bg-slate-800"><td class="px-2 py-1.5 border-r">Total Percentage</td><td class="px-2 py-1.5 text-right">{{ appraisal.adj_total_percentage ?? 100 }}%</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-4 overflow-x-auto">
                    <h3 class="text-xs font-bold uppercase text-center text-[#1a3557] mb-2">Property Assessment</h3>
                    <table class="w-full min-w-[400px] border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800">
                                <th class="border border-slate-300 px-2 py-1.5">Classification</th>
                                <th class="border border-slate-300 px-2 py-1.5">Adjusted MV</th>
                                <th class="border border-slate-300 px-2 py-1.5">Assm't %</th>
                                <th class="border border-slate-300 px-2 py-1.5">Assessed Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in assessmentRows" :key="'a'+i">
                                <td class="border border-slate-300 px-2 py-1">{{ row.classification || '—' }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.adjusted_market_value) }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ row.assessment_level ?? '—' }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right">{{ fmt(row.assessed_value) }}</td>
                            </tr>
                            <tr v-if="!assessmentRows.length">
                                <td colspan="4" class="border border-slate-300 px-2 py-3 text-center text-slate-400">No assessment rows</td>
                            </tr>
                            <tr class="font-semibold bg-slate-50 dark:bg-slate-800/60">
                                <td class="border border-slate-300 px-2 py-1.5 text-center">Total</td>
                                <td class="border border-slate-300 px-2 py-1.5 text-right">{{ fmt(assessTotals.adj) }}</td>
                                <td class="border border-slate-300 px-2 py-1.5 text-center text-[10px]">Rounded</td>
                                <td class="border border-slate-300 px-2 py-1.5 text-right">{{ fmt(appraisal.rounded_assessed_value || assessTotals.av) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 overflow-hidden">
                <table class="w-full border-collapse text-sm">
                    <tbody>
                        <tr>
                            <td class="border border-slate-300 px-3 py-2 w-1/2">
                                <span class="text-slate-500 font-medium">Previous Owner:</span>
                                <span class="ml-2">{{ appraisal.previous_owner || '—' }}</span>
                            </td>
                            <td class="border border-slate-300 px-3 py-2">
                                <span class="text-slate-500 font-medium">Taxability:</span>
                                <span class="ml-2 capitalize">{{ appraisal.taxability || '—' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="border border-slate-300 px-3 py-2">
                                <span class="text-slate-500 font-medium">Previous A.V.:</span>
                                <span class="ml-2">₱{{ fmt(appraisal.previous_assessed_value) }}</span>
                            </td>
                            <td class="border border-slate-300 px-3 py-2">
                                <span class="text-slate-500 font-medium">Effectivity:</span>
                                <span class="ml-2">{{ [appraisal.effectivity_year, appraisal.effectivity_quarter].filter(Boolean).join(' ') || '—' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-4">
                <h3 class="text-xs font-bold uppercase text-[#1a3557] mb-2">Memoranda</h3>
                <p class="text-sm whitespace-pre-wrap text-slate-600 dark:text-slate-300">{{ appraisal.memoranda || '—' }}</p>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    appraisal: { type: Object, default: null },
});

const page = ref('front');

const formTemplateLabel = computed(() => ({
    form_1: 'Form 1 — FAAS (Sample)',
    form_2: 'Form 2 — FAAS (Sample)',
}[props.appraisal?.form_template] || 'Form 1 — FAAS (Sample)'));

const locationRows = computed(() => [
    { label: 'Number & Street', value: props.appraisal?.property_street },
    { label: 'Barangay', value: props.appraisal?.property_barangay },
    { label: 'Municipality', value: props.appraisal?.property_municipality },
    { label: 'Province', value: props.appraisal?.property_province },
]);

const boundaryRows = computed(() => [
    { label: 'North', value: props.appraisal?.boundary_north },
    { label: 'East', value: props.appraisal?.boundary_east },
    { label: 'South', value: props.appraisal?.boundary_south },
    { label: 'West', value: props.appraisal?.boundary_west },
]);

const landRows = computed(() => props.appraisal?.land_rows || props.appraisal?.landRows || []);
const plantRows = computed(() => props.appraisal?.plant_rows || props.appraisal?.plantRows || []);
const assessmentRows = computed(() => props.appraisal?.assessment_rows || props.appraisal?.assessmentRows || []);

const landTotals = computed(() => ({
    area: landRows.value.reduce((s, r) => s + (Number(r.area) || 0), 0),
    bmv: landRows.value.reduce((s, r) => s + (Number(r.base_market_value) || 0), 0),
}));

const assessTotals = computed(() => ({
    adj: assessmentRows.value.reduce((s, r) => s + (Number(r.adjusted_market_value) || 0), 0),
    av: assessmentRows.value.reduce((s, r) => s + (Number(r.assessed_value) || 0), 0),
}));

function fmt(val, digits = 2) {
    if (val == null || val === '') return '—';
    return Number(val).toLocaleString('en-PH', { minimumFractionDigits: digits, maximumFractionDigits: digits });
}

function adjPct(val) {
    if (val == null || val === '') return '—';
    return `${val}%`;
}
</script>
