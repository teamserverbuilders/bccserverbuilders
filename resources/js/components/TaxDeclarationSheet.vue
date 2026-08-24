<template>
    <div v-if="td" class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-sm space-y-4 overflow-x-hidden">
        <h2 class="text-center text-sm font-bold uppercase tracking-wide text-[#1a3557] dark:text-zinc-50">
            Tax Declaration of Real Property
        </h2>

        <!-- TD No. / PIN -->
        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr>
                    <td class="border border-slate-300 dark:border-slate-600 w-1/2 p-0">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">TD No.</span>
                            <span class="px-2 py-1.5 flex-1 font-semibold">{{ td.td_number || '—' }}</span>
                        </div>
                    </td>
                    <td class="border border-slate-300 dark:border-slate-600 w-1/2 p-0">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Property Identification No.</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.property_index_number || '—' }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Owner -->
        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr>
                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:70%">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Owner</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.owner?.owner_name || '—' }}</span>
                        </div>
                    </td>
                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:30%">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">TIN</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.owner_tin || td.owner?.tin || '—' }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Address</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.owner_address || td.owner?.address || '—' }}</span>
                        </div>
                    </td>
                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Telephone No.</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.owner_telephone || td.owner?.contact_number || '—' }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Administrator -->
        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr>
                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:70%">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Administrator/Beneficial User</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.administrator_name || '—' }}</span>
                        </div>
                    </td>
                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:30%">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">TIN</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.administrator_tin || '—' }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Address</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.administrator_address || '—' }}</span>
                        </div>
                    </td>
                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Telephone No.</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.administrator_telephone || '—' }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Location of Property -->
        <div>
            <p class="text-xs font-bold uppercase text-slate-600 dark:text-slate-300 mb-1">Location of Property</p>
            <div class="grid grid-cols-3 gap-0 border border-slate-300 dark:border-slate-600">
                <div class="border-r border-slate-300 dark:border-slate-600 p-1">
                    <p class="px-1 py-1.5 text-sm min-h-[2rem]">{{ td.property_street || '—' }}</p>
                    <p class="text-[10px] text-center text-slate-400 mt-0.5">(Number and Street)</p>
                </div>
                <div class="border-r border-slate-300 dark:border-slate-600 p-1">
                    <p class="px-1 py-1.5 text-sm min-h-[2rem]">{{ td.barangay?.name || '—' }}</p>
                    <p class="text-[10px] text-center text-slate-400 mt-0.5">(Barangay/District)</p>
                </div>
                <div class="p-1">
                    <p class="px-1 py-1.5 text-sm min-h-[2rem]">{{ municipalityLabel }}</p>
                    <p class="text-[10px] text-center text-slate-400 mt-0.5">(Municipality and Province/City)</p>
                </div>
            </div>
        </div>

        <!-- Title / Survey -->
        <div class="grid grid-cols-2 gap-0">
            <table class="w-full border-collapse text-sm">
                <tbody>
                    <tr class="border border-slate-300 dark:border-slate-600">
                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-36">OCT/TCT/CLOA No.</td>
                        <td class="px-2 py-1">{{ td.oct_tct_cloa_no || '—' }}</td>
                    </tr>
                    <tr class="border border-slate-300 dark:border-slate-600">
                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">CCT</td>
                        <td class="px-2 py-1">{{ td.cct || '—' }}</td>
                    </tr>
                    <tr class="border border-slate-300 dark:border-slate-600">
                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">Dated</td>
                        <td class="px-2 py-1">{{ formatDate(td.title_date) }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="w-full border-collapse text-sm">
                <tbody>
                    <tr class="border border-slate-300 dark:border-slate-600">
                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-28">Survey No.</td>
                        <td class="px-2 py-1">{{ td.survey_number || '—' }}</td>
                    </tr>
                    <tr class="border border-slate-300 dark:border-slate-600">
                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">Lot No.</td>
                        <td class="px-2 py-1">{{ td.lot_number || '—' }}</td>
                    </tr>
                    <tr class="border border-slate-300 dark:border-slate-600">
                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">Block No.</td>
                        <td class="px-2 py-1">{{ td.block_number || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Boundaries -->
        <div>
            <p class="text-xs font-bold uppercase text-slate-600 dark:text-slate-300 mb-1">Boundaries</p>
            <div class="grid grid-cols-2 gap-0">
                <table class="w-full border-collapse text-sm">
                    <tbody>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 w-16">North</td>
                            <td class="px-2 py-1">{{ td.boundary_north || '—' }}</td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">East</td>
                            <td class="px-2 py-1">{{ td.boundary_east || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="w-full border-collapse text-sm">
                    <tbody>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 w-16">South</td>
                            <td class="px-2 py-1">{{ td.boundary_south || '—' }}</td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">West</td>
                            <td class="px-2 py-1">{{ td.boundary_west || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kind of Property Assessed -->
        <div class="border border-slate-300 dark:border-slate-600 p-3">
            <p class="text-xs font-bold uppercase text-slate-600 dark:text-slate-300 mb-2">Kind of Property Assessed</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <i :class="checkedIcon('Land')"></i>
                        <span class="font-medium">LAND</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-start gap-2">
                            <i :class="checkedIcon('Building')" class="mt-0.5"></i>
                            <div class="flex-1">
                                <span class="font-medium">BUILDING</span>
                                <div v-if="hasKind('Building')" class="grid grid-cols-2 gap-2 mt-1 text-xs text-slate-600 dark:text-slate-300">
                                    <div>
                                        <span class="text-[10px] text-slate-400">No. of Storeys</span>
                                        <p>{{ td.no_of_storeys ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400">Brief Description</span>
                                        <p>{{ td.building_description || '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="space-y-1">
                        <div class="flex items-start gap-2">
                            <i :class="checkedIcon('Machinery')" class="mt-0.5"></i>
                            <div class="flex-1">
                                <span class="font-medium">MACHINERY</span>
                                <div v-if="hasKind('Machinery')" class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                                    <span class="text-[10px] text-slate-400">Brief Description</span>
                                    <p>{{ td.machinery_description || '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-start gap-2">
                            <i :class="checkedIcon('Others')" class="mt-0.5"></i>
                            <div class="flex-1">
                                <span class="font-medium">Others</span>
                                <div v-if="hasKind('Others')" class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                                    <span class="text-[10px] text-slate-400">Specify</span>
                                    <p>{{ td.others_specify || '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dual Valuation / Assessment Table -->
        <div class="w-full max-w-full overflow-x-auto">
            <table class="w-full table-fixed border-collapse text-[10px]">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800">
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold uppercase tracking-wide w-[11%]">Classification</th>
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold uppercase tracking-wide w-[10%]">Area<br><span class="font-normal normal-case">(Ha./Sq.M.)</span></th>
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold uppercase tracking-wide w-[12%]">Base Market Value</th>
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold uppercase tracking-wide w-[13%] border-r-2">Actual Use</th>
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold tracking-wide w-[13%]">Classification (Kind)</th>
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold tracking-wide w-[13%]">Adjusted Market Value</th>
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold tracking-wide w-[10%]">Assm't. Level (%)</th>
                        <th class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 font-bold tracking-wide w-[12%]">Assessed Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in displayRows" :key="i">
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1.5">{{ row.leftClass }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 text-right">{{ formatNum(row.area, 6) }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 text-right">{{ formatNum(row.base_mv, 2) }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 border-r-2">{{ row.actual_use || '—' }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1 font-semibold uppercase text-[9px] tracking-wide truncate">{{ row.rightClass }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 text-right">{{ formatNum(row.adj_mv, 2) }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 text-right">{{ row.level ?? '—' }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1.5 text-right">{{ formatNum(row.av, 2) }}</td>
                    </tr>
                    <tr v-if="!displayRows.length">
                        <td colspan="8" class="border border-slate-400 dark:border-slate-500 px-2 py-3 text-center text-slate-400">No valuation rows</td>
                    </tr>
                    <tr class="font-bold bg-slate-50 dark:bg-slate-800/50">
                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 uppercase">Total :</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 text-right underline decoration-2 underline-offset-2">{{ formatNum(totals.area, 6) }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 text-right underline decoration-2 underline-offset-2">{{ formatNum(totals.base_mv, 2) }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 border-r-2"></td>
                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 uppercase">Total :</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 text-right underline decoration-2 underline-offset-2">{{ formatNum(totals.adj_mv, 2) }}</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-2 text-right text-[10px] font-semibold whitespace-nowrap">Rounded :</td>
                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 text-right font-bold underline decoration-2 underline-offset-2">{{ formatNum(td.rounded_assessed_value || td.assessed_value, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total Assessed Value in words -->
        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr class="border border-slate-300 dark:border-slate-600">
                    <td class="px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-44">Total Assessed Value</td>
                    <td class="px-2 py-1.5 uppercase">{{ td.assessed_value_words || '—' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Taxability & Effectivity -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="border border-slate-300 dark:border-slate-600 p-3 flex items-center gap-6">
                <span class="flex items-center gap-2 text-sm font-medium">
                    <i :class="['pi text-sm', td.taxability === 'taxable' ? 'pi-check-circle text-[#1a3557]' : 'pi-circle text-slate-300']"></i>
                    TAXABLE
                </span>
                <span class="flex items-center gap-2 text-sm font-medium">
                    <i :class="['pi text-sm', td.taxability === 'exempt' ? 'pi-check-circle text-[#1a3557]' : 'pi-circle text-slate-300']"></i>
                    EXEMPT
                </span>
            </div>
            <div class="border border-slate-300 dark:border-slate-600 p-3">
                <p class="text-[10px] font-bold uppercase text-slate-400 mb-2">Effectivity of Assessment/Reassessment</p>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <p class="text-[10px] text-slate-400">Quarter</p>
                        <p>{{ td.effectivity_quarter || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400">Year</p>
                        <p>{{ td.effectivity_year || '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approved By -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-0 border border-slate-300 dark:border-slate-600">
            <div class="md:col-span-2 border-b md:border-b-0 md:border-r border-slate-300 dark:border-slate-600 p-2">
                <p class="text-[10px] font-bold uppercase text-slate-400 mb-1">Approved By</p>
                <p class="text-sm">{{ td.approved_by_name || '—' }}</p>
            </div>
            <div class="p-2">
                <p class="text-[10px] font-bold uppercase text-slate-400 mb-1">Date</p>
                <p class="text-sm">{{ formatDate(td.date_issued) }}</p>
            </div>
        </div>

        <!-- Cancels previous TD -->
        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr>
                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:34%">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 text-xs font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Cancels TD No.</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.previous_td_number || '—' }}</span>
                        </div>
                    </td>
                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:33%">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 text-xs font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Owner</span>
                            <span class="px-2 py-1.5 flex-1">{{ td.previous_owner || '—' }}</span>
                        </div>
                    </td>
                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:33%">
                        <div class="flex items-center">
                            <span class="px-2 py-1.5 text-xs font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Previous A.V.</span>
                            <span class="px-2 py-1.5 flex-1">₱{{ formatNum(td.previous_av, 2) }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Memoranda -->
        <div class="border border-slate-300 dark:border-slate-600 p-3">
            <p class="text-xs font-bold uppercase text-slate-600 dark:text-slate-300 mb-2">Memoranda</p>
            <p class="text-sm whitespace-pre-wrap text-slate-700 dark:text-slate-300 min-h-[3rem]">{{ td.memoranda || '—' }}</p>
        </div>

        <div v-if="td.remarks">
            <p class="text-xs font-medium text-slate-500 mb-1">Remarks / Notes</p>
            <p class="text-sm whitespace-pre-wrap text-slate-700 dark:text-slate-300">{{ td.remarks }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    td: { type: Object, default: null },
    classifications: { type: Array, default: () => [] },
});

const municipalityLabel = computed(() => {
    const m = props.td?.municipality;
    if (!m) return '—';
    return [m.name, m.province].filter(Boolean).join(', ');
});

const kindList = computed(() => {
    const k = props.td?.kind_of_property;
    return Array.isArray(k) ? k : [];
});

function hasKind(name) {
    return kindList.value.some(k => String(k).toLowerCase() === name.toLowerCase());
}

function checkedIcon(name) {
    return [
        'pi text-sm',
        hasKind(name) ? 'pi-check-square text-[#1a3557]' : 'pi-stop text-slate-300',
    ];
}

function classNameById(id) {
    if (!id) return null;
    const found = props.classifications.find(c => c.id === id);
    return found?.name || null;
}

const valuationRows = computed(() => props.td?.valuation_rows || []);
const assessmentRows = computed(() => props.td?.assessment_rows || []);

const displayRows = computed(() => {
    const left = valuationRows.value;
    const right = assessmentRows.value;
    const n = Math.max(left.length, right.length);

    if (!n) {
        if (props.td?.assessed_value || props.td?.land_area || props.td?.classification) {
            return [{
                leftClass: props.td?.classification?.name || '—',
                area: props.td?.land_area,
                base_mv: props.td?.base_market_value || props.td?.market_value,
                actual_use: props.td?.actual_use || props.td?.current_use,
                rightClass: props.td?.classification?.name || '—',
                adj_mv: props.td?.adjusted_market_value,
                level: props.td?.assessment_level,
                av: props.td?.assessed_value,
            }];
        }
        return [];
    }

    const rows = [];
    for (let i = 0; i < n; i++) {
        const L = left[i] || {};
        const R = right[i] || {};
        rows.push({
            leftClass: L.classification_name || classNameById(L.classification_id) || L.classification || '—',
            area: L.area,
            base_mv: L.base_market_value,
            actual_use: L.actual_use,
            rightClass: R.classification || '—',
            adj_mv: R.adjusted_market_value,
            level: R.assessment_level,
            av: R.assessed_value,
        });
    }
    return rows;
});

const totals = computed(() => ({
    area: displayRows.value.reduce((s, r) => s + (Number(r.area) || 0), 0),
    base_mv: displayRows.value.reduce((s, r) => s + (Number(r.base_mv) || 0), 0),
    adj_mv: displayRows.value.reduce((s, r) => s + (Number(r.adj_mv) || 0), 0),
}));

function formatNum(val, digits = 2) {
    const n = parseMoney(val);
    if (n == null) return '—';
    return n.toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits });
}

/** Parse PH money strings: 5,280.00 | 5.280,00 | 5,280,00 | 5280 */
function parseMoney(val) {
    if (val == null || val === '') return null;
    if (typeof val === 'number') return Number.isFinite(val) ? val : null;
    let s = String(val).trim().replace(/[₱Php\s]/gi, '');
    if (!s) return null;
    const hasComma = s.includes(',');
    const hasDot = s.includes('.');
    if (hasComma && hasDot) {
        if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
            s = s.replace(/\./g, '').replace(',', '.');
        } else {
            s = s.replace(/,/g, '');
        }
    } else if (hasComma) {
        if ((s.match(/,/g) || []).length > 1) {
            const pos = s.lastIndexOf(',');
            s = s.slice(0, pos).replace(/,/g, '') + '.' + s.slice(pos + 1);
        } else if (/,\d{1,2}$/.test(s)) {
            s = s.replace(',', '.');
        } else {
            s = s.replace(/,/g, '');
        }
    } else if (hasDot && (s.match(/\./g) || []).length > 1) {
        const pos = s.lastIndexOf('.');
        s = s.slice(0, pos).replace(/\./g, '') + '.' + s.slice(pos + 1);
    }
    const n = parseFloat(s);
    return Number.isNaN(n) ? null : n;
}

function formatDate(val) {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('en-PH', { month: '2-digit', day: '2-digit', year: 'numeric' });
}
</script>
