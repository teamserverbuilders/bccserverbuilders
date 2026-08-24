<template>
    <div class="space-y-5">
        <!-- ════════════ FRONT PAGE ════════════ -->
        <template v-if="activePage === 'front'">

        <!-- Header identity -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <table class="w-full border-collapse text-sm">
                <tbody>
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-600 p-0 w-1/2">
                            <div class="flex items-stretch">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-36">Update Code</span>
                                <InputText v-model="identity.update_code" class="!w-full !border-0 !rounded-none !shadow-none" />
                            </div>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0 w-1/2">
                            <div class="flex items-stretch">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-36">A.R.P. No.</span>
                                <InputText v-model="identity.arp_no" class="!w-full !border-0 !rounded-none !shadow-none" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                            <div class="flex items-stretch">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-36">P.I.N.</span>
                                <InputText v-model="identity.pin" class="!w-full !border-0 !rounded-none !shadow-none" />
                            </div>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                            <div class="flex items-stretch">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-36">OCT/TCT/KOT No.</span>
                                <InputText v-model="identity.oct_tct_kot_no" class="!w-full !border-0 !rounded-none !shadow-none" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                            <div class="flex items-stretch">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-36">Survey No.</span>
                                <InputText v-model="identity.survey_no" class="!w-full !border-0 !rounded-none !shadow-none" />
                            </div>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                            <div class="flex items-stretch">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-36">Cad/PLS Lot No.</span>
                                <InputText v-model="identity.cad_pls_lot_no" class="!w-full !border-0 !rounded-none !shadow-none" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Owner / Administrator -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <table class="w-full border-collapse text-sm">
                <tbody>
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-medium bg-slate-50 dark:bg-slate-800 whitespace-nowrap w-40">Owner</td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0" colspan="3">
                            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-300 dark:divide-slate-600">
                                <InputText v-model="identity.owner_name" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="Owner name" />
                                <div class="flex items-stretch min-w-0">
                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Address</span>
                                    <InputText v-model="identity.owner_address" class="!w-full !border-0 !rounded-none !shadow-none" />
                                </div>
                                <div class="flex items-stretch min-w-0">
                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">T.I.N.</span>
                                    <InputText v-model="identity.owner_tin" class="!w-full !border-0 !rounded-none !shadow-none" />
                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-x border-slate-300 dark:border-slate-600 whitespace-nowrap">Tel No.</span>
                                    <InputText v-model="identity.owner_telephone" class="!w-full !border-0 !rounded-none !shadow-none" />
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-medium bg-slate-50 dark:bg-slate-800 whitespace-nowrap leading-tight">Administrator / Occupant</td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0" colspan="3">
                            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-300 dark:divide-slate-600">
                                <InputText v-model="identity.administrator_name" class="!w-full !border-0 !rounded-none !shadow-none" />
                                <div class="flex items-stretch min-w-0">
                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Address</span>
                                    <InputText v-model="identity.administrator_address" class="!w-full !border-0 !rounded-none !shadow-none" />
                                </div>
                                <div class="flex items-stretch min-w-0">
                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">T.I.N.</span>
                                    <InputText v-model="identity.administrator_tin" class="!w-full !border-0 !rounded-none !shadow-none" />
                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-x border-slate-300 dark:border-slate-600 whitespace-nowrap">Tel No.</span>
                                    <InputText v-model="identity.administrator_telephone" class="!w-full !border-0 !rounded-none !shadow-none" />
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Property Location + Sketch + Boundaries (NE/SE/SW/NW) -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="space-y-5">
                    <div>
                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Property Location</h3>
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr v-for="row in locationFields" :key="row.key" class="border border-slate-300 dark:border-slate-600">
                                    <td class="w-36 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">{{ row.label }}</td>
                                    <td class="p-0"><InputText v-model="propertyLocation[row.key]" class="!w-full !border-0 !rounded-none !shadow-none" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Property Boundaries</h3>
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr v-for="row in boundaryFields" :key="row.key" class="border border-slate-300 dark:border-slate-600">
                                    <td class="w-16 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">{{ row.label }}</td>
                                    <td class="p-0"><InputText v-model="propertyBoundaries[row.key]" class="!w-full !border-0 !rounded-none !shadow-none" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col">
                    <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Land Sketch</h3>
                    <div
                        class="flex-1 min-h-[260px] border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-md bg-slate-50 dark:bg-slate-800/40 relative overflow-hidden flex flex-col items-center justify-center"
                        @dragover.prevent
                        @drop.prevent="$emit('sketch-drop', $event)"
                    >
                        <img v-if="sketchPreviewUrl" :src="sketchPreviewUrl" alt="Land sketch" class="absolute inset-0 w-full h-full object-contain p-2 bg-white dark:bg-slate-900" />
                        <div v-else class="text-center px-4 py-8 z-10">
                            <i class="pi pi-image text-3xl text-slate-300 dark:text-slate-600 mb-2"></i>
                            <p class="text-xs text-slate-500">Upload or drop a sketch image</p>
                        </div>
                        <div class="absolute bottom-2 right-2 flex gap-1 z-10">
                            <button type="button" class="h-8 px-3 rounded-md bg-[#1a3557] text-white text-xs font-medium" :disabled="uploadingSketch" @click="$refs.sketchInput.click()">
                                {{ sketchPreviewUrl ? 'Replace' : 'Upload' }}
                            </button>
                            <button v-if="sketchPreviewUrl" type="button" class="h-8 w-8 rounded-md border border-red-300 text-red-500" @click="$emit('sketch-remove')">
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                        <input ref="sketchInput" type="file" accept="image/*" class="hidden" @change="$emit('sketch-select', $event)" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Land Appraisal -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Land Appraisal</h3>
            <table class="w-full min-w-[720px] border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800">
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Classification Kind</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Sub-Class</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Actual Use</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Area (Ha. or Sq. M.)</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Unit Value</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Base Market Value</th>
                        <th class="border border-slate-300 dark:border-slate-600 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in landRows" :key="'f2-land-' + i">
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="row.classification_kind" class="cell-input" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="row.sub_class" class="cell-input text-center" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="row.actual_use" class="cell-input" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.area" class="cell-number" inputClass="text-right" :minFractionDigits="2" :maxFractionDigits="6" @update:modelValue="$emit('recalc-land', row)" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.unit_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" @update:modelValue="$emit('recalc-land', row)" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.base_market_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 text-center">
                            <button type="button" class="text-slate-400 hover:text-red-500 p-1" :disabled="landRows.length <= 1" @click="$emit('remove-land', i)"><i class="pi pi-times text-[10px]"></i></button>
                        </td>
                    </tr>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                        <td colspan="3" class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center">TOTAL</td>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(landTotals.area, 6) }}</td>
                        <td class="border border-slate-300 dark:border-slate-600"></td>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(landTotals.base_market_value, 2) }}</td>
                        <td class="border border-slate-300 dark:border-slate-600"></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="mt-2 text-xs text-[#1a3557] hover:underline font-medium" @click="$emit('add-land')"><i class="pi pi-plus text-[10px]"></i> Add land row</button>
        </div>

        <!-- Plants and Trees -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 uppercase tracking-wide">Plants and Trees Appraisal</h3>
            <table class="w-full min-w-[860px] border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800">
                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Kinds of Plants and/or Trees</th>
                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Product Class</th>
                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Area Planted<br><span class="font-normal">Ha. or Sq. M. (Optional)</span></th>
                        <th colspan="3" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Total Numbers</th>
                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Unit Price</th>
                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Base Market Value</th>
                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 w-8"></th>
                    </tr>
                    <tr class="bg-slate-50 dark:bg-slate-800">
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-1.5 font-semibold">N-Fruit Bearing</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-1.5 font-semibold">Fruit Bearing</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-1.5 font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in plantRows" :key="'f2-plant-' + i">
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="row.kind" class="cell-input" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="row.prod_class" class="cell-input text-center" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.area_planted" class="cell-number" inputClass="text-right" :minFractionDigits="2" :maxFractionDigits="6" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.non_fb" class="cell-number" inputClass="text-right" :min="0" @update:modelValue="$emit('recalc-plant', row)" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.fb" class="cell-number" inputClass="text-right" :min="0" @update:modelValue="$emit('recalc-plant', row)" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.total" class="cell-number" inputClass="text-right" :min="0" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.unit_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" @update:modelValue="$emit('recalc-plant', row)" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.base_market_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 text-center">
                            <button type="button" class="text-slate-400 hover:text-red-500 p-1" :disabled="plantRows.length <= 1" @click="$emit('remove-plant', i)"><i class="pi pi-times text-[10px]"></i></button>
                        </td>
                    </tr>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                        <td colspan="2" class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center">TOTAL</td>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.area_planted, 6) }}</td>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.non_fb, 0) }}</td>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.fb, 0) }}</td>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.total, 0) }}</td>
                        <td class="border border-slate-300 dark:border-slate-600"></td>
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.base_market_value, 2) }}</td>
                        <td class="border border-slate-300 dark:border-slate-600"></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="mt-2 text-xs text-[#1a3557] hover:underline font-medium" @click="$emit('add-plant')"><i class="pi pi-plus text-[10px]"></i> Add plants/trees row</button>
        </div>

        </template>

        <!-- ════════════ BACK PAGE ════════════ -->
        <template v-else>

        <!-- Adjustments + Assessment -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <h3 class="text-xs font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Value Adjustment Factors for Agricultural Lands</h3>
                <table class="w-full border-collapse text-xs">
                    <tbody>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1.5 font-medium border-r border-slate-300 dark:border-slate-600">Base Market Value</td>
                            <td class="px-2 py-1.5 text-right w-24">100%</td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50">
                            <td colspan="2" class="px-2 py-1.5 font-semibold">Adjustments:</td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1 border-r border-slate-300 dark:border-slate-600">Along road / no road outlet</td>
                            <td class="p-0 w-28"><InputNumber v-model="adjustments.along_road" class="cell-number" inputClass="text-right" @update:modelValue="$emit('recalc-adj')" /></td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1 border-r border-slate-300 dark:border-slate-600">Km. to all weather road</td>
                            <td class="p-0"><InputNumber v-model="adjustments.kms_weather_road" class="cell-number" inputClass="text-right" @update:modelValue="$emit('recalc-adj')" /></td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600">
                            <td class="px-2 py-1 border-r border-slate-300 dark:border-slate-600">Km. to market (poblacion)</td>
                            <td class="p-0"><InputNumber v-model="adjustments.kms_to_market" class="cell-number" inputClass="text-right" @update:modelValue="$emit('recalc-adj')" /></td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600 font-semibold">
                            <td class="px-2 py-1.5 border-r border-slate-300 dark:border-slate-600">Total Adjustment</td>
                            <td class="px-2 py-1.5 text-right">{{ adjustments.total_adjustments }}%</td>
                        </tr>
                        <tr class="border border-slate-300 dark:border-slate-600 font-bold bg-slate-50 dark:bg-slate-800/60">
                            <td class="px-2 py-2 border-r border-slate-300 dark:border-slate-600 uppercase">Total Percentage Adjustment</td>
                            <td class="px-2 py-2 text-right">{{ adjustments.total_percentage }}%</td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="mt-2 text-xs text-[#1a3557] hover:underline font-medium" @click="$emit('apply-adj')">Apply % to land total → Agricultural adjusted MV</button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm overflow-x-auto">
                <h3 class="text-xs font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Property Assessment</h3>
                <table class="w-full min-w-[420px] border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800">
                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Classification (Kind)</th>
                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Adjusted Market Value</th>
                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Assm't Level</th>
                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Assessed Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in assessmentRows" :key="'f2-a-' + row.classification">
                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1 font-medium">{{ row.classification }}</td>
                            <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.adjusted_market_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" @update:modelValue="$emit('recalc-assessed', row)" /></td>
                            <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.assessment_level" class="cell-number" inputClass="text-right" @update:modelValue="$emit('recalc-assessed', row)" /></td>
                            <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.assessed_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" /></td>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center">TOTAL</td>
                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(assessmentTotals.adjusted_market_value, 2) }}</td>
                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center text-[10px] font-normal">P</td>
                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(assessmentTotals.assessed_value, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="mt-2 text-xs text-[#1a3557] hover:underline font-medium" @click="$emit('sync-computed')">Sync totals to computed market / assessed value</button>
            </div>
        </div>

        <!-- Previous / Taxability / Effectivity -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <table class="w-full border-collapse text-sm">
                <tbody>
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-600 p-0 w-1/2">
                            <div class="flex items-center">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Previous Owner:</span>
                                <InputText v-model="backMeta.previous_owner" class="!w-full !border-0 !rounded-none !shadow-none" />
                            </div>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0 w-1/2">
                            <div class="flex items-center">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Taxability:</span>
                                <Select v-model="backMeta.taxability" :options="['Taxable','Exempt']" class="!w-full !border-0" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                            <div class="flex items-center">
                                <span class="px-3 py-2 font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Previous Assessed Value:</span>
                                <InputNumber v-model="backMeta.previous_assessed_value" class="!w-full" inputClass="!border-0 !rounded-none !shadow-none" mode="currency" currency="PHP" locale="en-PH" />
                            </div>
                        </td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                            <div class="flex items-center gap-2 px-2">
                                <span class="py-2 font-medium bg-slate-50 dark:bg-slate-800 px-2 -ml-2 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Tax Effectivity:</span>
                                <InputText v-model="backMeta.effectivity_year" class="!w-24 !border-0 !rounded-none !shadow-none" placeholder="Year" />
                                <Select v-model="backMeta.effectivity_quarter" :options="['1st Qtr','2nd Qtr','3rd Qtr','4th Qtr']" class="!flex-1 !border-0" placeholder="Quarter" showClear />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Conforme + Assessed By -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-[#1a3557] dark:text-slate-200 mb-3">Conforme</p>
                <div class="space-y-2">
                    <div>
                        <label class="form-label">By (Name)</label>
                        <InputText v-model="conforme.name" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">CTC No.</label>
                        <InputText v-model="conforme.ctc_no" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Dated</label>
                        <DatePicker v-model="conforme.dated" class="w-full" dateFormat="mm/dd/yy" showIcon />
                    </div>
                    <div>
                        <label class="form-label">Issued at</label>
                        <InputText v-model="conforme.issued_at" class="w-full" />
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-[#1a3557] dark:text-slate-200 mb-3">Assessed By</p>
                <div class="space-y-2">
                    <div>
                        <label class="form-label">Name</label>
                        <InputText v-model="signatures.assessed_by.name" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Title / Position</label>
                        <InputText v-model="signatures.assessed_by.title" class="w-full" placeholder="e.g. Assessment Clerk II" />
                    </div>
                    <div>
                        <label class="form-label">Date</label>
                        <DatePicker v-model="signatures.assessed_by.date" class="w-full" dateFormat="mm/dd/yy" showIcon />
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommending + Approved -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-[#1a3557] dark:text-slate-200 mb-3">Recommending Approval</p>
                <div class="space-y-2">
                    <div>
                        <label class="form-label">Name</label>
                        <InputText v-model="signatures.recommending.name" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Title / Position</label>
                        <InputText v-model="signatures.recommending.title" class="w-full" placeholder="Municipal Assessor" />
                    </div>
                    <div>
                        <label class="form-label">Date</label>
                        <DatePicker v-model="signatures.recommending.date" class="w-full" dateFormat="mm/dd/yy" showIcon />
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-[#1a3557] dark:text-slate-200 mb-3">Approved By</p>
                <div class="space-y-2">
                    <div>
                        <label class="form-label">Name</label>
                        <InputText v-model="signatures.approved.name" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Title / Position</label>
                        <InputText v-model="signatures.approved.title" class="w-full" placeholder="Acting-Provincial Assessor" />
                    </div>
                    <div>
                        <label class="form-label">Date</label>
                        <DatePicker v-model="signatures.approved.date" class="w-full" dateFormat="mm/dd/yy" showIcon />
                    </div>
                </div>
            </div>
        </div>

        <!-- Memoranda -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 uppercase tracking-wide">Memoranda</h3>
            <Textarea v-model="backMeta.memoranda" class="w-full" rows="4" />
        </div>

        <!-- Reference & Posting -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Reference &amp; Posting Summary</h3>
            <table class="w-full min-w-[700px] border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800">
                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Reference</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Previous Record</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Date Posted</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Posting Clerk Initial</th>
                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Post Inspection</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in referenceRows" :key="'f2-ref-' + row.key">
                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 font-medium whitespace-nowrap">{{ row.label }}</td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="references[row.key]" class="cell-input" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><DatePicker v-model="posting[row.key].date" class="w-full" dateFormat="mm/dd/yy" showIcon inputClass="!border-0 !rounded-none !shadow-none !text-xs" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="posting[row.key].clerk_initial" class="cell-input text-center" /></td>
                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="posting[row.key].post_inspection" class="cell-input" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <label class="form-label">Remarks / Findings</label>
            <Textarea v-model="remarks" class="w-full mt-1" rows="3" />
        </div>

        </template>
    </div>
</template>

<script setup>
import { defineModel } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';

const remarks = defineModel('remarks', { type: String, default: '' });

defineProps({
    activePage: { type: String, default: 'front' },
    identity: { type: Object, required: true },
    propertyLocation: { type: Object, required: true },
    propertyBoundaries: { type: Object, required: true },
    landRows: { type: Array, required: true },
    plantRows: { type: Array, required: true },
    landTotals: { type: Object, required: true },
    plantTotals: { type: Object, required: true },
    adjustments: { type: Object, required: true },
    assessmentRows: { type: Array, required: true },
    assessmentTotals: { type: Object, required: true },
    backMeta: { type: Object, required: true },
    signatures: { type: Object, required: true },
    conforme: { type: Object, required: true },
    references: { type: Object, required: true },
    posting: { type: Object, required: true },
    sketchPreviewUrl: { type: String, default: null },
    uploadingSketch: { type: Boolean, default: false },
});

defineEmits([
    'add-land', 'remove-land', 'recalc-land',
    'add-plant', 'remove-plant', 'recalc-plant',
    'recalc-adj', 'apply-adj', 'recalc-assessed', 'sync-computed',
    'sketch-select', 'sketch-drop', 'sketch-remove',
]);

const locationFields = [
    { key: 'street', label: 'No./Street' },
    { key: 'barangay', label: 'Barangay' },
    { key: 'municipality', label: 'Municipality' },
    { key: 'province', label: 'Province of' },
];

const boundaryFields = [
    { key: 'north', label: 'NE' },
    { key: 'east', label: 'SE' },
    { key: 'south', label: 'SW' },
    { key: 'west', label: 'NW' },
];

const referenceRows = [
    { key: 'pin', label: 'P.I.N.' },
    { key: 'arp_no', label: 'A.R.P. No.' },
    { key: 'ar_page_no', label: 'A.R. Page No.' },
];

function formatNum(val, digits = 2) {
    if (val == null || val === '' || Number.isNaN(Number(val))) return '—';
    return Number(val).toLocaleString('en-PH', {
        minimumFractionDigits: digits === 0 ? 0 : Math.min(digits, 2),
        maximumFractionDigits: digits,
    });
}
</script>

<style scoped>
:deep(.cell-input),
:deep(.cell-number) {
    width: 100%;
}
:deep(.cell-input.p-inputtext),
:deep(.cell-number .p-inputnumber-input) {
    width: 100%;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 0.4rem 0.5rem;
    font-size: 0.75rem;
    background: transparent;
}
:deep(.cell-number .p-inputnumber) {
    width: 100%;
}
</style>
