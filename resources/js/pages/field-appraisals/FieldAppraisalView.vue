<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <RouterLink to="/field-appraisals">
                    <button class="h-8 w-8 inline-flex items-center justify-center rounded-md border border-[#1a3557] text-[#1a3557] hover:bg-[#1a3557] hover:text-white transition-colors">
                        <i class="pi pi-arrow-left text-sm"></i>
                    </button>
                </RouterLink>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold text-[#1a3557] dark:text-slate-50">{{ appraisal?.appraisal_no }}</h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ formTemplateLabel(appraisal?.form_template) }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Field Appraisal and Assessment Sheet [Land / Plant and Trees]</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Tag :value="appraisal?.status" :severity="statusSeverity(appraisal?.status)" />
                <RouterLink :to="`/field-appraisals/${route.params.id}/edit`">
                    <Button label="Edit" icon="pi pi-pencil" outlined size="small" />
                </RouterLink>
                <Button v-if="appraisal?.status === 'computed'" label="Approve" icon="pi pi-check" size="small" :loading="approving" @click="approve" />
            </div>
        </div>

        <!-- Front / Back tabs -->
        <div class="inline-flex rounded-md border border-[#1a3557] overflow-hidden">
            <button type="button"
                class="px-5 py-2 text-sm font-semibold transition-colors"
                :class="activePage === 'front' ? 'bg-[#1a3557] text-white' : 'bg-white dark:bg-slate-900 text-[#1a3557] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'"
                @click="activePage = 'front'">
                Front Page
            </button>
            <button type="button"
                class="px-5 py-2 text-sm font-semibold border-l border-[#1a3557] transition-colors"
                :class="activePage === 'back' ? 'bg-[#1a3557] text-white' : 'bg-white dark:bg-slate-900 text-[#1a3557] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'"
                @click="activePage = 'back'">
                Back Page
            </button>
        </div>

        <div v-if="loading" class="space-y-4">
            <div v-for="i in 4" :key="i" class="h-32 bg-slate-100 dark:bg-slate-800 rounded-lg animate-pulse"></div>
        </div>

        <template v-else-if="appraisal">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 space-y-5">

                    <!-- Appraisal Info -->
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <i class="pi pi-clipboard text-[#1a3557] dark:text-blue-400 text-sm"></i>
                            <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Appraisal Details</span>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-x-6 gap-y-3">
                            <InfoRow label="ARP No." :value="appraisal.appraisal_no" />
                            <InfoRow label="Form Template" :value="formTemplateLabel(appraisal.form_template)" />
                            <InfoRow label="Inspection Date" :value="appraisal.inspection_date ? new Date(appraisal.inspection_date).toLocaleDateString() : '—'" />
                            <InfoRow label="Location" :value="appraisal.inspection_location || '—'" />
                            <InfoRow label="Assessor" :value="appraisal.assessor?.name || '—'" />
                            <InfoRow label="Linked TD" :value="appraisal.tax_declaration?.td_number || '—'" link
                                :to="appraisal.tax_declaration ? `/tax-declarations/${appraisal.tax_declaration.id}` : null" />
                            <InfoRow label="Owner" :value="appraisal.owner_name || appraisal.tax_declaration?.owner?.owner_name || '—'" />
                            <InfoRow label="Owner Address" :value="appraisal.owner_address || appraisal.tax_declaration?.owner_address || appraisal.tax_declaration?.owner?.address || '—'" />
                            <InfoRow label="Owner T.I.N." :value="appraisal.owner_tin || '—'" />
                            <InfoRow label="Owner Tel No." :value="appraisal.owner_telephone || '—'" />
                            <InfoRow label="Administrator" :value="appraisal.administrator_name || '—'" />
                            <InfoRow label="Administrator Address" :value="appraisal.administrator_address || '—'" />
                            <template v-if="appraisal.form_template === 'form_2'">
                                <InfoRow label="Update Code" :value="appraisal.update_code || '—'" />
                                <InfoRow label="P.I.N." :value="appraisal.pin || '—'" />
                                <InfoRow label="A.R.P. No." :value="appraisal.arp_no || '—'" />
                                <InfoRow label="OCT/TCT/KOT No." :value="appraisal.oct_tct_kot_no || '—'" />
                                <InfoRow label="Survey No." :value="appraisal.survey_no || '—'" />
                                <InfoRow label="Cad/PLS Lot No." :value="appraisal.cad_pls_lot_no || '—'" />
                            </template>
                        </div>
                    </div>

                    <!-- FRONT -->
                    <template v-if="activePage === 'front'">
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-5">
                                <div class="space-y-5">
                                    <div>
                                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Property Location</h3>
                                        <table class="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr v-for="row in locationRows" :key="row.label" class="border border-slate-300 dark:border-slate-600">
                                                    <td class="w-36 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium text-slate-600 dark:text-slate-300 border-r border-slate-300 dark:border-slate-600">{{ row.label }}</td>
                                                    <td class="px-2 py-1.5 text-slate-800 dark:text-slate-200">{{ row.value || '—' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Property Boundaries</h3>
                                        <table class="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr v-for="row in boundaryRows" :key="row.label" class="border border-slate-300 dark:border-slate-600">
                                                    <td class="w-20 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium text-slate-600 dark:text-slate-300 border-r border-slate-300 dark:border-slate-600">{{ row.label }}</td>
                                                    <td class="px-2 py-1.5 text-slate-800 dark:text-slate-200">{{ row.value || '—' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Land Sketch</h3>
                                    <div class="flex-1 min-h-[260px] border border-slate-300 dark:border-slate-600 rounded-md bg-slate-50 dark:bg-slate-800/40 overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="appraisal.land_sketch"
                                            :src="`/storage/${appraisal.land_sketch}`"
                                            alt="Land sketch"
                                            class="w-full h-full object-contain p-2 bg-white dark:bg-slate-900"
                                        />
                                        <p v-else class="text-xs text-slate-400">No land sketch uploaded.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 text-center uppercase tracking-wide">Land Appraisal</h3>
                            </div>
                            <div class="p-4 overflow-x-auto">
                                <table class="w-full min-w-[680px] border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 dark:bg-slate-800">
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Classification Kind</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Sub Class</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Actual Use</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Area (Ha or Sq. M)</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Unit Value</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Base Market Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in landRows" :key="'l-' + i">
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5">{{ row.classification_kind || '—' }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-center">{{ row.sub_class || '—' }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5">{{ row.actual_use || '—' }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.area, 6) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.unit_value, 2) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.base_market_value, 2) }}</td>
                                        </tr>
                                        <tr v-if="!landRows.length">
                                            <td colspan="6" class="border border-slate-300 dark:border-slate-600 px-2 py-4 text-center text-slate-400">No land appraisal rows.</td>
                                        </tr>
                                        <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                                            <td colspan="3" class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center">Total:</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(landTotals.area, 6) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600"></td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(landTotals.base_market_value, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 uppercase tracking-wide">Plants and Trees Appraisal</h3>
                            </div>
                            <div class="p-4 overflow-x-auto">
                                <table class="w-full min-w-[800px] border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 dark:bg-slate-800">
                                            <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Kind of Plants And/or Trees</th>
                                            <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Prod Class</th>
                                            <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Area Planted (Hectares)</th>
                                            <th colspan="3" class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Number of Plants/Trees</th>
                                            <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Unit Value</th>
                                            <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Base Market Value</th>
                                        </tr>
                                        <tr class="bg-slate-50 dark:bg-slate-800">
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 font-semibold">Non FB</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 font-semibold">FB</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 font-semibold">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in plantRows" :key="'p-' + i">
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5">{{ row.kind || '—' }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-center">{{ row.prod_class || '—' }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.area_planted, 6) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.non_fb, 0) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.fb, 0) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.total, 0) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.unit_value, 2) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.base_market_value, 2) }}</td>
                                        </tr>
                                        <tr v-if="!plantRows.length">
                                            <td colspan="8" class="border border-slate-300 dark:border-slate-600 px-2 py-4 text-center text-slate-400">No plants/trees appraisal rows.</td>
                                        </tr>
                                        <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2">Total:</td>
                                            <td class="border border-slate-300 dark:border-slate-600"></td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.area_planted, 6) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.non_fb, 0) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.fb, 0) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.total, 0) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600"></td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(plantTotals.base_market_value, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- BACK -->
                    <template v-else>
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                                <h3 class="text-xs font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                    Value Adjustment Factors for Agricultural Lands
                                </h3>
                                <table class="w-full border-collapse text-xs">
                                    <tbody>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1.5 font-medium border-r border-slate-300 dark:border-slate-600">Base Market Value</td>
                                            <td class="px-2 py-1.5 text-right">100%</td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50">
                                            <td colspan="2" class="px-2 py-1.5 font-semibold">Adjustments:</td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1.5 border-r border-slate-300 dark:border-slate-600">[ a ] Along road / no road frontage</td>
                                            <td class="px-2 py-1.5 text-right">{{ formatAdj(adj.along_road) }}</td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1.5 border-r border-slate-300 dark:border-slate-600">[ b ] kms. to all weather road</td>
                                            <td class="px-2 py-1.5 text-right">{{ formatAdj(adj.kms_weather_road) }}</td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1.5 border-r border-slate-300 dark:border-slate-600">[ c ] kms. to market (Pob.)</td>
                                            <td class="px-2 py-1.5 text-right">{{ formatAdj(adj.kms_to_market) }}</td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600 font-semibold">
                                            <td class="px-2 py-1.5 border-r border-slate-300 dark:border-slate-600">Total Adjustments</td>
                                            <td class="px-2 py-1.5 text-right">{{ adj.total_adjustments ?? 0 }}%</td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600 font-bold bg-slate-50 dark:bg-slate-800/60">
                                            <td class="px-2 py-2 border-r border-slate-300 dark:border-slate-600 uppercase">Total Percentage Adjustment</td>
                                            <td class="px-2 py-2 text-right">{{ adj.total_percentage ?? 100 }}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm overflow-x-auto">
                                <h3 class="text-xs font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">Property Assessment</h3>
                                <table class="w-full min-w-[420px] border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 dark:bg-slate-800">
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Classification (Kind)</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Adjusted Market Value</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Assm't Level (%)</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Assessed Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in assessmentRows" :key="row.classification">
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 font-medium">{{ row.classification }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.adjusted_market_value, 2) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ row.assessment_level != null ? row.assessment_level : '—' }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-right">{{ formatNum(row.assessed_value, 2) }}</td>
                                        </tr>
                                        <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center">Total</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(assessmentTotals.adjusted_market_value, 2) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center text-[10px] font-normal">Rounded Assessed Value</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(a.rounded_assessed_value ?? computation?.rounded_assessed_value ?? assessmentTotals.assessed_value, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr>
                                        <td class="border border-slate-300 dark:border-slate-600 px-3 py-2 w-1/2">
                                            <span class="font-medium text-slate-500">Previous Owner:</span>
                                            <span class="ml-2 text-slate-800 dark:text-slate-200">{{ backMeta.previous_owner || '—' }}</span>
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-3 py-2 w-1/2">
                                            <span class="font-medium text-slate-500">Taxability:</span>
                                            <span class="ml-2 text-slate-800 dark:text-slate-200">{{ backMeta.taxability || '—' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 dark:border-slate-600 px-3 py-2">
                                            <span class="font-medium text-slate-500">Previous Assessed Value:</span>
                                            <span class="ml-2 text-slate-800 dark:text-slate-200">{{ formatCurrency(backMeta.previous_assessed_value) }}</span>
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-3 py-2">
                                            <span class="font-medium text-slate-500">Effectivity:</span>
                                            <span class="ml-2 text-slate-800 dark:text-slate-200">
                                                {{ [backMeta.effectivity_year, backMeta.effectivity_quarter].filter(Boolean).join(' ') || '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div v-for="sig in signatureDisplay" :key="sig.label" class="border border-slate-300 dark:border-slate-600 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#1a3557] dark:text-slate-200 mb-2">{{ sig.label }}</p>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ sig.name || '—' }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ sig.title || '—' }}</p>
                                    <p class="text-xs text-slate-400 mt-2">{{ sig.date || '—' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-2 uppercase tracking-wide">Memoranda</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{{ backMeta.memoranda || '—' }}</p>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
                            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">References and Posting Summary</h3>
                            <table class="w-full min-w-[700px] border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800">
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Reference</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Previous Record</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Date</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Clerk Initial</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Post Inspection</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in referenceDisplay" :key="row.label">
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 font-medium">{{ row.label }}</td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5">{{ row.value || '—' }}</td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5">{{ row.date || '—' }}</td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-center">{{ row.clerk || '—' }}</td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5">{{ row.inspection || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                <!-- Sidebar -->
                <div class="space-y-5">
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <i class="pi pi-calculator text-amber-600 text-sm"></i>
                            <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Computation of Value</span>
                        </div>
                        <div class="p-5 grid grid-cols-1 gap-y-3">
                            <InfoRow label="Total Market Value" :value="formatCurrency(appraisal.computed_market_value)" bold />
                            <InfoRow label="Total Assessed Value" :value="formatCurrency(appraisal.computed_assessed_value)" bold />
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-red-500 text-sm"></i>
                                <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">GIS Location</span>
                            </div>
                            <Button size="small" outlined icon="pi pi-map" label="Open Map"
                                @click="$router.push('/gis?fa=' + route.params.id)" />
                        </div>
                        <div class="p-4">
                            <div v-if="mapPin">
                                <MiniPropertyMap
                                    :lat="mapPin.lat"
                                    :lng="mapPin.lng"
                                    :label="mapPin.label"
                                />
                                <p class="text-sm font-medium text-[#1a3557] dark:text-slate-100 mt-3">{{ mapPin.label }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                                    {{ mapPin.lat.toFixed(6) }}, {{ mapPin.lng.toFixed(6) }}
                                </p>
                                <p class="text-xs text-slate-400 mt-1">Source: GIS pin</p>
                                <a :href="`https://www.google.com/maps?q=${mapPin.lat},${mapPin.lng}`" target="_blank"
                                   class="inline-flex items-center gap-1.5 text-xs text-[#1a3557] dark:text-blue-400 hover:underline mt-2 font-medium">
                                    <i class="pi pi-external-link text-[10px]"></i> Open in Google Maps
                                </a>
                            </div>
                            <div v-else class="text-center py-4">
                                <p class="text-sm text-slate-500 dark:text-slate-400">No location coordinates available</p>
                                <p class="text-xs text-slate-400 mt-1">Set a GIS pin on the map for this appraisal.</p>
                                <Button label="Set Location" icon="pi pi-map-marker" size="small" class="mt-3"
                                    @click="$router.push('/gis?fa=' + route.params.id)" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-images text-violet-500 text-sm"></i>
                                <span class="text-sm font-bold text-[#1a3557] dark:text-slate-200">Photos</span>
                            </div>
                            <Button icon="pi pi-upload" size="small" text rounded @click="$refs.photoInput.click()" />
                        </div>
                        <input ref="photoInput" type="file" multiple accept="image/*" class="hidden" @change="uploadPhotos" />
                        <div class="p-4">
                            <div v-if="(appraisal.photos || []).length" class="grid grid-cols-3 gap-2">
                                <div v-for="(p, i) in appraisal.photos" :key="i" class="aspect-square rounded-md overflow-hidden border border-slate-200 dark:border-slate-700">
                                    <img :src="`/storage/${p.path}`" class="w-full h-full object-cover" />
                                </div>
                            </div>
                            <p v-else class="text-xs text-slate-400 text-center py-4">No photos uploaded.</p>
                        </div>
                    </div>

                    <div v-if="appraisal.remarks" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-2">Remarks</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{{ appraisal.remarks }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import MiniPropertyMap from '@/components/MiniPropertyMap.vue';
import axios from 'axios';

const InfoRow = (props) => {
    const valueEl = props.link && props.to
        ? h(RouterLink, { to: props.to, class: 'text-[#1a3557] dark:text-blue-400 hover:underline font-semibold text-sm' }, () => props.value)
        : h('p', { class: `text-sm ${props.bold ? 'font-bold text-[#1a3557] dark:text-slate-100' : 'text-slate-800 dark:text-slate-200'}` }, props.value);
    return h('div', {}, [
        h('span', { class: 'text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5' }, props.label),
        valueEl,
    ]);
};
InfoRow.props = ['label', 'value', 'bold', 'link', 'to'];

const route     = useRoute();
const toast     = useToast();
const appraisal = ref(null);
const loading   = ref(true);
const approving = ref(false);
const activePage = ref('front');

const mapPin = computed(() => {
    const lat = Number(appraisal.value?.latitude);
    const lng = Number(appraisal.value?.longitude);
    if (!appraisal.value?.latitude || !appraisal.value?.longitude || Number.isNaN(lat) || Number.isNaN(lng)) {
        return null;
    }
    return {
        lat,
        lng,
        label: appraisal.value.appraisal_no || 'Field Appraisal',
        source: 'GIS pin',
    };
});

const a = computed(() => appraisal.value || {});
const computation = computed(() => a.value.computation || {});

const adj = computed(() => ({
    along_road: a.value.adj_along_road ?? computation.value.adjustments?.along_road,
    kms_weather_road: a.value.adj_kms_weather_road ?? computation.value.adjustments?.kms_weather_road,
    kms_to_market: a.value.adj_kms_to_market ?? computation.value.adjustments?.kms_to_market,
    total_adjustments: a.value.adj_total_adjustments ?? computation.value.adjustments?.total_adjustments ?? 0,
    total_percentage: a.value.adj_total_percentage ?? computation.value.adjustments?.total_percentage ?? 100,
}));

const backMeta = computed(() => ({
    previous_owner: a.value.previous_owner || computation.value.back_meta?.previous_owner || '',
    previous_assessed_value: a.value.previous_assessed_value ?? computation.value.back_meta?.previous_assessed_value,
    taxability: a.value.taxability || computation.value.back_meta?.taxability || '',
    effectivity_year: a.value.effectivity_year || computation.value.back_meta?.effectivity_year || '',
    effectivity_quarter: a.value.effectivity_quarter || computation.value.back_meta?.effectivity_quarter || '',
    memoranda: a.value.memoranda || computation.value.back_meta?.memoranda || '',
}));

const landRows = computed(() => {
    if (Array.isArray(a.value.land_rows) && a.value.land_rows.length) return a.value.land_rows;
    const ld = a.value.land_details;
    if (Array.isArray(ld?.rows) && ld.rows.length) return ld.rows;
    return [];
});

const plantRows = computed(() => {
    if (Array.isArray(a.value.plant_rows) && a.value.plant_rows.length) {
        return a.value.plant_rows.filter((r) => r.kind || r.prod_class || r.area_planted || r.total || r.base_market_value);
    }
    const id = a.value.improvement_details;
    if (Array.isArray(id?.rows)) return id.rows.filter((r) => r.kind || r.prod_class || r.area_planted || r.total || r.base_market_value);
    return [];
});

const locationRows = computed(() => {
    const loc = a.value.land_details?.location || {};
    return [
        { label: 'No./Street', value: a.value.property_street || loc.street },
        { label: 'Barangay', value: a.value.property_barangay || loc.barangay },
        { label: 'Municipality', value: a.value.property_municipality || loc.municipality },
        { label: 'Province', value: a.value.property_province || loc.province },
    ];
});

const boundaryRows = computed(() => {
    const b = a.value.land_details?.boundaries || {};
    return [
        { label: 'North', value: a.value.boundary_north || b.north },
        { label: 'East', value: a.value.boundary_east || b.east },
        { label: 'South', value: a.value.boundary_south || b.south },
        { label: 'West', value: a.value.boundary_west || b.west },
    ];
});

const landTotals = computed(() => ({
    area: a.value.land_total_area ?? landRows.value.reduce((s, r) => s + (Number(r.area) || 0), 0),
    base_market_value: a.value.land_total_base_market_value ?? landRows.value.reduce((s, r) => s + (Number(r.base_market_value) || 0), 0),
}));

const plantTotals = computed(() => ({
    area_planted: a.value.plant_total_area ?? plantRows.value.reduce((s, r) => s + (Number(r.area_planted) || 0), 0),
    non_fb: a.value.plant_total_non_fb ?? plantRows.value.reduce((s, r) => s + (Number(r.non_fb) || 0), 0),
    fb: a.value.plant_total_fb ?? plantRows.value.reduce((s, r) => s + (Number(r.fb) || 0), 0),
    total: a.value.plant_total_count ?? plantRows.value.reduce((s, r) => s + (Number(r.total) || 0), 0),
    base_market_value: a.value.plant_total_base_market_value ?? plantRows.value.reduce((s, r) => s + (Number(r.base_market_value) || 0), 0),
}));

const assessmentKinds = [
    'Agricultural', 'Residential', 'Commercial', 'Industrial',
    'Mineral', 'Special', 'Timber/Forest', 'Plant and Trees',
];

const assessmentRows = computed(() => {
    const rows = (Array.isArray(a.value.assessment_rows) && a.value.assessment_rows.length)
        ? a.value.assessment_rows
        : (computation.value.assessment_rows || []);
    return assessmentKinds.map((classification) => {
        const found = rows.find((r) => r.classification === classification) || {};
        return {
            classification,
            adjusted_market_value: found.adjusted_market_value ?? null,
            assessment_level: found.assessment_level ?? null,
            assessed_value: found.assessed_value ?? null,
        };
    });
});

const assessmentTotals = computed(() => ({
    adjusted_market_value: a.value.total_adjusted_market_value
        ?? assessmentRows.value.reduce((s, r) => s + (Number(r.adjusted_market_value) || 0), 0),
    assessed_value: assessmentRows.value.reduce((s, r) => s + (Number(r.assessed_value) || 0), 0),
}));

const signatureDisplay = computed(() => {
    const s = computation.value.signatures || {};
    const fmt = (d) => (d ? new Date(d).toLocaleDateString() : '');
    return [
        {
            label: 'Appraised By',
            name: a.value.appraised_by_name || s.appraised_by?.name,
            title: a.value.appraised_by_title || s.appraised_by?.title,
            date: fmt(a.value.appraised_by_date || s.appraised_by?.date),
        },
        {
            label: 'Assessed By',
            name: a.value.assessed_by_name || s.assessed_by?.name,
            title: a.value.assessed_by_title || s.assessed_by?.title,
            date: fmt(a.value.assessed_by_date || s.assessed_by?.date),
        },
        {
            label: 'Recommending Approval',
            name: a.value.recommending_name || s.recommending?.name,
            title: a.value.recommending_title || s.recommending?.title,
            date: fmt(a.value.recommending_date || s.recommending?.date),
        },
        {
            label: 'Approved',
            name: a.value.approved_by_name || s.approved?.name,
            title: a.value.approved_by_title || s.approved?.title,
            date: fmt(a.value.approved_by_date || s.approved?.date),
        },
    ];
});

const referenceDisplay = computed(() => {
    const refs = computation.value.references || {};
    const post = computation.value.posting || {};
    const fmt = (d) => (d ? new Date(d).toLocaleDateString() : '');
    return [
        {
            label: 'PIN',
            value: a.value.ref_pin || refs.pin,
            date: fmt(a.value.posting_pin_date || post.pin?.date),
            clerk: a.value.posting_pin_clerk || post.pin?.clerk_initial,
            inspection: a.value.posting_pin_inspection || post.pin?.post_inspection,
        },
        {
            label: 'ARP No.',
            value: a.value.ref_arp_no || refs.arp_no,
            date: fmt(a.value.posting_arp_date || post.arp_no?.date),
            clerk: a.value.posting_arp_clerk || post.arp_no?.clerk_initial,
            inspection: a.value.posting_arp_inspection || post.arp_no?.post_inspection,
        },
        {
            label: 'AR Page No.',
            value: a.value.ref_ar_page_no || refs.ar_page_no,
            date: fmt(a.value.posting_ar_page_date || post.ar_page_no?.date),
            clerk: a.value.posting_ar_page_clerk || post.ar_page_no?.clerk_initial,
            inspection: a.value.posting_ar_page_inspection || post.ar_page_no?.post_inspection,
        },
    ];
});

function statusSeverity(s) {
    return { draft: 'secondary', inspected: 'info', computed: 'warn', approved: 'success', revision: 'danger' }[s] || 'secondary';
}

function formTemplateLabel(template) {
    return { form_1: 'Form 1 — FAAS (Sample)', form_2: 'Form 2 — FAAS (Sample)' }[template] || 'Form 1 — FAAS (Sample)';
}

function formatCurrency(val) {
    if (val == null || val === '') return '—';
    return '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function formatNum(val, digits = 2) {
    if (val == null || val === '') return '—';
    return Number(val).toLocaleString('en-PH', {
        minimumFractionDigits: digits === 0 ? 0 : Math.min(digits, 2),
        maximumFractionDigits: digits,
    });
}

function formatAdj(val) {
    if (val == null || val === '') return '—';
    return Number(val);
}

async function approve() {
    approving.value = true;
    try {
        const { data } = await axios.post(`field-appraisals/${route.params.id}/approve`);
        appraisal.value = data;
        toast.success('Approved', 'Field appraisal approved and values synced to TD.');
    } catch (err) {
        toast.error('Error', err.response?.data?.message || 'Approval failed.');
    } finally { approving.value = false; }
}

async function uploadPhotos(e) {
    const files = e.target.files;
    if (!files.length) return;
    const fd = new FormData();
    for (const f of files) fd.append('photos[]', f);
    try {
        const { data } = await axios.post(`field-appraisals/${route.params.id}/photos`, fd);
        appraisal.value.photos = data.photos;
        toast.success('Uploaded', 'Photos added.');
    } catch { toast.error('Error', 'Upload failed.'); }
}

onMounted(async () => {
    try {
        const { data } = await axios.get(`field-appraisals/${route.params.id}`);
        appraisal.value = data;
    } finally { loading.value = false; }
});
</script>
