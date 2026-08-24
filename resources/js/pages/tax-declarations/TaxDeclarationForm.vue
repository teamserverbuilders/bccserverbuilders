<template>
    <div class="space-y-6 max-w-full overflow-x-hidden">
        <form @submit.prevent="handleSubmit" novalidate class="overflow-x-hidden space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <RouterLink to="/tax-declarations">
                        <button type="button" class="h-8 w-8 inline-flex items-center justify-center rounded-md border border-[#1a3557] text-[#1a3557] hover:bg-[#1a3557] hover:text-white dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-800 transition-colors">
                            <i class="pi pi-arrow-left text-sm"></i>
                        </button>
                    </RouterLink>
                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold text-[#1a3557] dark:text-zinc-50">
                            {{ isEdit ? 'Edit Tax Declaration' : 'New Tax Declaration' }}
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">
                            {{ isEdit ? `TD# ${td?.td_number}` : 'Tax Declaration of Real Property' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:shrink-0 pl-11 sm:pl-0">
                    <button type="submit" :disabled="saving"
                        class="h-9 px-4 rounded-md bg-[#1a3557] hover:bg-[#1e4880] text-white text-sm font-medium disabled:opacity-50 inline-flex items-center justify-center gap-1.5 transition-colors shadow-sm">
                        <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <i v-else class="pi pi-save text-sm"></i>
                        {{ isEdit ? 'Update Record' : 'Save Declaration' }}
                    </button>
                    <RouterLink to="/tax-declarations">
                        <button type="button" class="h-9 px-4 rounded-md border border-[#1a3557] text-[#1a3557] dark:border-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            Cancel
                        </button>
                    </RouterLink>
                </div>
            </div>

            <div :class="isEdit ? '' : 'grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_280px] gap-4'">
                <!-- ─── Main Form (aligned to official TD sheet) ─── -->
                <div class="min-w-0 space-y-5">

                    <div class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-sm space-y-4 overflow-x-hidden">
                        <h2 class="text-center text-sm font-bold uppercase tracking-wide text-[#1a3557] dark:text-zinc-50">
                            Tax Declaration of Real Property
                        </h2>

                        <!-- TD No. / PIN -->
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-slate-300 dark:border-slate-600 w-1/2 p-0">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">TD No. <span class="text-red-500">*</span></span>
                                            <InputText v-model="form.td_number" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('td_number')}" required />
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 dark:border-slate-600 w-1/2 p-0">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Property Identification No.</span>
                                            <InputText v-model="form.property_index_number" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('property_index_number')}" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <small v-if="errors.td_number" class="text-red-500 text-xs">{{ errors.td_number[0] }}</small>

                        <!-- Owner search -->
                        <div>
                            <label class="form-label">Search Existing Owner</label>
                            <AutoComplete v-model="ownerSearch" :suggestions="ownerSuggestions" optionLabel="owner_name"
                                @complete="searchOwners" @item-select="onOwnerSelect"
                                placeholder="Type name to search…" class="w-full" :forceSelection="false" />
                        </div>

                        <!-- Owner -->
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0" colspan="1" style="width:70%">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Owner <span class="text-red-500">*</span></span>
                                            <InputText v-model="ownerForm.owner_name" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('owner_name')}" required />
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:30%">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">TIN</span>
                                            <InputText v-model="form.owner_tin" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('owner_tin')}" />
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Address <span class="text-red-500">*</span></span>
                                            <InputText v-model="ownerForm.address" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('owner_address')}" required />
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Telephone No.</span>
                                            <InputText v-model="form.owner_telephone" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('owner_telephone')}" />
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
                                            <InputText v-model="form.administrator_name" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('administrator_name')}" />
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:30%">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">TIN</span>
                                            <InputText v-model="form.administrator_tin" class="!w-full !border-0 !rounded-none !shadow-none" />
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Address</span>
                                            <InputText v-model="form.administrator_address" class="!w-full !border-0 !rounded-none !shadow-none" />
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Telephone No.</span>
                                            <InputText v-model="form.administrator_telephone" class="!w-full !border-0 !rounded-none !shadow-none" />
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
                                    <InputText v-model="form.property_street" class="!w-full !border-0 !rounded-none !shadow-none !text-sm" :class="{'!bg-green-50': ocr.highlights.includes('property_street')}" />
                                    <p class="text-[10px] text-center text-slate-400 mt-0.5">(Number and Street)</p>
                                </div>
                                <div class="border-r border-slate-300 dark:border-slate-600 p-1">
                                    <Select v-model="form.barangay_id" :options="filteredBarangays" optionLabel="name" optionValue="id"
                                        class="!w-full !border-0" showClear :class="{'!bg-green-50': ocr.highlights.includes('barangay_id')}" />
                                    <p class="text-[10px] text-center text-slate-400 mt-0.5">(Barangay/District)</p>
                                </div>
                                <div class="p-1">
                                    <Select v-model="form.municipality_id" :options="municipalityOptions" optionLabel="label" optionValue="id"
                                        class="!w-full !border-0" @change="loadBarangays" showClear :class="{'!bg-green-50': ocr.highlights.includes('municipality_id')}" />
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
                                        <td class="p-0"><InputText v-model="form.oct_tct_cloa_no" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('oct_tct_cloa_no')}" /></td>
                                    </tr>
                                    <tr class="border border-slate-300 dark:border-slate-600">
                                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">CCT</td>
                                        <td class="p-0"><InputText v-model="form.cct" class="!w-full !border-0 !rounded-none !shadow-none" /></td>
                                    </tr>
                                    <tr class="border border-slate-300 dark:border-slate-600">
                                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">Dated</td>
                                        <td class="p-0"><DatePicker v-model="form.title_date" class="w-full" dateFormat="mm/dd/yy" showIcon inputClass="!border-0 !rounded-none !shadow-none" /></td>
                                    </tr>
                                </tbody>
                            </table>
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="border border-slate-300 dark:border-slate-600">
                                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-28">Survey No.</td>
                                        <td class="p-0"><InputText v-model="form.survey_number" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('survey_number')}" /></td>
                                    </tr>
                                    <tr class="border border-slate-300 dark:border-slate-600">
                                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">Lot No.</td>
                                        <td class="p-0"><InputText v-model="form.lot_number" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('lot_number')}" /></td>
                                    </tr>
                                    <tr class="border border-slate-300 dark:border-slate-600">
                                        <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">Block No.</td>
                                        <td class="p-0"><InputText v-model="form.block_number" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('block_number')}" /></td>
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
                                            <td class="p-0"><InputText v-model="form.boundary_north" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('boundary_north')}" /></td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">East</td>
                                            <td class="p-0"><InputText v-model="form.boundary_east" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('boundary_east')}" /></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <table class="w-full border-collapse text-sm">
                                    <tbody>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 w-16">South</td>
                                            <td class="p-0"><InputText v-model="form.boundary_south" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('boundary_south')}" /></td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600">West</td>
                                            <td class="p-0"><InputText v-model="form.boundary_west" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('boundary_west')}" /></td>
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
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" value="Land" v-model="form.kind_of_property" class="w-4 h-4 rounded border-slate-300 text-[#1a3557] focus:ring-[#1a3557]" />
                                        <span class="font-medium">LAND</span>
                                    </label>
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="checkbox" value="Building" v-model="form.kind_of_property" class="w-4 h-4 mt-1 rounded border-slate-300 text-[#1a3557] focus:ring-[#1a3557]" />
                                        <div class="flex-1 space-y-1">
                                            <span class="font-medium">BUILDING</span>
                                            <div class="grid grid-cols-2 gap-2" v-if="form.kind_of_property.includes('Building')">
                                                <div>
                                                    <span class="text-[10px] text-slate-400">No. of Storeys</span>
                                                    <InputNumber v-model="form.no_of_storeys" class="w-full" :min="0" />
                                                </div>
                                                <div>
                                                    <span class="text-[10px] text-slate-400">Brief Description</span>
                                                    <InputText v-model="form.building_description" class="w-full" />
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="space-y-2">
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="checkbox" value="Machinery" v-model="form.kind_of_property" class="w-4 h-4 mt-1 rounded border-slate-300 text-[#1a3557] focus:ring-[#1a3557]" />
                                        <div class="flex-1 space-y-1">
                                            <span class="font-medium">MACHINERY</span>
                                            <div v-if="form.kind_of_property.includes('Machinery')">
                                                <span class="text-[10px] text-slate-400">Brief Description</span>
                                                <InputText v-model="form.machinery_description" class="w-full" />
                                            </div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="checkbox" value="Others" v-model="form.kind_of_property" class="w-4 h-4 mt-1 rounded border-slate-300 text-[#1a3557] focus:ring-[#1a3557]" />
                                        <div class="flex-1 space-y-1">
                                            <span class="font-medium">Others</span>
                                            <div v-if="form.kind_of_property.includes('Others')">
                                                <span class="text-[10px] text-slate-400">Specify</span>
                                                <InputText v-model="form.others_specify" class="w-full" />
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Dual Valuation / Assessment Table (official TD layout) -->
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
                                    <tr v-for="(kind, i) in assessmentRows" :key="kind.classification">
                                        <!-- Left valuation cells -->
                                        <td class="border border-slate-400 dark:border-slate-500 p-0 align-middle">
                                            <Select
                                                v-if="valuationRows[i]"
                                                v-model="valuationRows[i].classification_id"
                                                :options="classifications"
                                                optionLabel="name"
                                                optionValue="id"
                                                class="!w-full !border-0"
                                                showClear
                                                @change="syncValuationToForm"
                                            />
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 p-0 align-middle">
                                            <InputNumber
                                                v-if="valuationRows[i]"
                                                v-model="valuationRows[i].area"
                                                class="cell-number"
                                                inputClass="text-right"
                                                :minFractionDigits="2"
                                                :maxFractionDigits="6"
                                                @update:modelValue="syncValuationToForm"
                                            />
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 p-0 align-middle">
                                            <InputNumber
                                                v-if="valuationRows[i]"
                                                v-model="valuationRows[i].base_market_value"
                                                class="cell-number"
                                                inputClass="text-right"
                                                :minFractionDigits="2"
                                                @update:modelValue="syncValuationToForm"
                                            />
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 p-0 align-middle border-r-2">
                                            <InputText
                                                v-if="valuationRows[i]"
                                                v-model="valuationRows[i].actual_use"
                                                class="cell-input"
                                                @update:modelValue="syncValuationToForm"
                                            />
                                        </td>
                                        <!-- Right assessment cells -->
                                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-1 font-semibold uppercase text-[9px] tracking-wide truncate" :title="kind.classification">
                                            {{ kind.classification }}
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 p-0">
                                            <InputNumber
                                                v-model="kind.adjusted_market_value"
                                                class="cell-number"
                                                inputClass="text-right"
                                                :minFractionDigits="2"
                                                @update:modelValue="recalcAssessmentRow(kind)"
                                            />
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 p-0">
                                            <InputNumber
                                                v-model="kind.assessment_level"
                                                class="cell-number"
                                                inputClass="text-right"
                                                @update:modelValue="recalcAssessmentRow(kind)"
                                            />
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 p-0">
                                            <InputNumber
                                                v-model="kind.assessed_value"
                                                class="cell-number"
                                                inputClass="text-right"
                                                :minFractionDigits="2"
                                                @update:modelValue="syncAssessmentToForm"
                                            />
                                        </td>
                                    </tr>

                                    <!-- TOTAL row — aligned across both sides -->
                                    <tr class="font-bold bg-slate-50 dark:bg-slate-800/50">
                                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 uppercase">Total :</td>
                                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 text-right underline decoration-2 underline-offset-2">
                                            {{ formatNum(valuationTotals.area, 6) }}
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 text-right underline decoration-2 underline-offset-2">
                                            {{ formatNum(valuationTotals.base_market_value, 2) }}
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 border-r-2"></td>
                                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 uppercase">Total :</td>
                                        <td class="border border-slate-400 dark:border-slate-500 px-2 py-2 text-right underline decoration-2 underline-offset-2">
                                            {{ formatNum(assessmentTotals.adjusted_market_value, 2) }}
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 px-1 py-2 text-right text-[10px] font-semibold whitespace-nowrap">
                                            Rounded :
                                        </td>
                                        <td class="border border-slate-400 dark:border-slate-500 p-0">
                                            <InputNumber
                                                v-model="form.rounded_assessed_value"
                                                class="cell-number"
                                                inputClass="text-right font-bold underline decoration-2 underline-offset-2"
                                                :minFractionDigits="2"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="text-[10px] text-slate-400 mt-1.5">
                                Left side: enter classification / area / base market value / actual use (one row per entry).
                                Right side: fill the matching classification kind — assessed value = adjusted MV × assm’t level ÷ 100; rounded total auto-fills.
                            </p>
                        </div>

                        <!-- Total Assessed Value in words -->
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr class="border border-slate-300 dark:border-slate-600">
                                    <td class="px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium border-r border-slate-300 dark:border-slate-600 whitespace-nowrap w-44">Total Assessed Value</td>
                                    <td class="p-0">
                                        <InputText v-model="form.assessed_value_words" class="!w-full !border-0 !rounded-none !shadow-none uppercase"
                                            :class="{'!bg-green-50': ocr.highlights.includes('assessed_value_words')}"
                                            placeholder="Amount in words" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Taxability & Effectivity -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="border border-slate-300 dark:border-slate-600 p-3 flex items-center gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="taxable" v-model="form.taxability" class="w-4 h-4 text-[#1a3557] focus:ring-[#1a3557]" />
                                    <span class="text-sm font-medium">TAXABLE</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="exempt" v-model="form.taxability" class="w-4 h-4 text-[#1a3557] focus:ring-[#1a3557]" />
                                    <span class="text-sm font-medium">EXEMPT</span>
                                </label>
                            </div>
                            <div class="border border-slate-300 dark:border-slate-600 p-3">
                                <p class="text-[10px] font-bold uppercase text-slate-400 mb-2">Effectivity of Assessment/Reassessment</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <Select v-model="form.effectivity_quarter" :options="['1st','2nd','3rd','4th']" class="w-full" showClear placeholder="Quarter"
                                        :class="{'border-green-500': ocr.highlights.includes('effectivity_quarter')}" />
                                    <InputText v-model="form.effectivity_year" class="w-full" placeholder="Year"
                                        :class="{'border-green-500': ocr.highlights.includes('effectivity_year')}" />
                                </div>
                            </div>
                        </div>

                        <!-- Approved By -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-0 border border-slate-300 dark:border-slate-600">
                            <div class="md:col-span-2 border-b md:border-b-0 md:border-r border-slate-300 dark:border-slate-600 p-2">
                                <p class="text-[10px] font-bold uppercase text-slate-400 mb-1">Approved By</p>
                                <InputText v-model="form.approved_by_name" class="!w-full !border-0 !rounded-none !shadow-none"
                                    :class="{'!bg-green-50': ocr.highlights.includes('approved_by_name')}" placeholder="Name & Title" />
                            </div>
                            <div class="p-2">
                                <p class="text-[10px] font-bold uppercase text-slate-400 mb-1">Date</p>
                                <DatePicker v-model="form.date_issued" class="w-full" dateFormat="mm/dd/yy" showIcon inputClass="!border-0 !rounded-none !shadow-none" />
                            </div>
                        </div>

                        <!-- Cancels previous TD -->
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:34%">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 text-xs font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Cancels TD No.</span>
                                            <InputText v-model="form.previous_td_number" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('previous_td_number')}" />
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:33%">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 text-xs font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Owner</span>
                                            <InputText v-model="form.previous_owner" class="!w-full !border-0 !rounded-none !shadow-none" :class="{'!bg-green-50': ocr.highlights.includes('previous_owner')}" />
                                        </div>
                                    </td>
                                    <td class="border border-slate-300 dark:border-slate-600 p-0" style="width:33%">
                                        <div class="flex items-center">
                                            <span class="px-2 py-1.5 text-xs font-medium whitespace-nowrap bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600">Previous A.V.</span>
                                            <InputNumber v-model="form.previous_av" class="!w-full" inputClass="!border-0 !rounded-none !shadow-none" mode="currency" currency="PHP" locale="en-PH"
                                                :class="{'border-green-500': ocr.highlights.includes('previous_av')}" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 px-1 py-1.5">
                            These fields are for the printed TD form. After saving, use
                            <span class="font-medium">Transfer Ownership</span> on the declaration view to record ownership history when the property changes hands.
                        </p>

                        <!-- Memoranda -->
                        <div class="border border-slate-300 dark:border-slate-600 p-3">
                            <p class="text-xs font-bold uppercase text-slate-600 dark:text-slate-300 mb-2">Memoranda</p>
                            <Textarea v-model="form.memoranda" class="w-full !border-0 !shadow-none" rows="3"
                                :class="{'!bg-green-50': ocr.highlights.includes('memoranda')}"
                                placeholder="e.g. Revised Pursuant to Sec 219 of R.A. 7160" />
                        </div>

                        <div>
                            <label class="form-label">Remarks / Notes</label>
                            <Textarea v-model="form.remarks" class="w-full mt-1" rows="2" />
                        </div>
                    </div>
                </div>

                <!-- ─── Right Sidebar (create only — OCR / copy tools) ─── -->
                <div v-if="!isEdit" class="space-y-3 lg:w-[280px] lg:shrink-0">
                    <!-- OCR Tool -->
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm">
                        <h3 class="text-xs font-bold text-[#1a3557] dark:text-zinc-50 mb-2 flex items-center gap-1.5">
                            <i class="pi pi-camera text-violet-500 text-sm"></i> Tools
                        </h3>

                        <label class="flex items-center gap-2 cursor-pointer select-none group">
                            <div @click="toggleUseTool" :class="['w-3.5 h-3.5 rounded border-2 flex items-center justify-center shrink-0 transition-colors',
                                useTool ? 'bg-violet-600 border-violet-600' : 'border-zinc-300 dark:border-zinc-600 group-hover:border-violet-400']">
                                <i v-if="useTool" class="pi pi-check text-white" style="font-size:8px"></i>
                            </div>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Use OCR Tool</span>
                        </label>

                        <Transition name="slide-down">
                            <div v-if="useTool" class="mt-3 space-y-3">
                                <p class="text-xs text-zinc-500">Upload one or more scanned TD pages. OCR merges extracted fields automatically.</p>

                                <!-- File upload -->
                                <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg p-3 text-center cursor-pointer hover:border-violet-400 transition-colors"
                                    @click="$refs.ocrFile.click()" @dragover.prevent @drop.prevent="onOcrDrop">
                                    <i class="pi pi-cloud-upload text-xl text-slate-400 mb-1 block"></i>
                                    <p class="text-xs text-slate-500">Drop files or click to upload</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Images / PDF · multiple allowed</p>
                                </div>
                                <input ref="ocrFile" type="file" accept="image/*,.pdf" class="hidden" multiple @change="onOcrFileSelect" />

                                <button type="button" @click="openWebcam"
                                    class="w-full h-8 rounded-md border border-violet-300 dark:border-violet-700 text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/30 text-xs font-medium flex items-center justify-center gap-1.5 transition-colors">
                                    <i class="pi pi-camera text-[11px]"></i>
                                    Use Webcam
                                </button>

                                <div v-if="ocr.files.length" class="space-y-1.5 max-h-36 overflow-y-auto">
                                    <div v-for="(f, i) in ocr.files" :key="f.name + '-' + i"
                                        class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-800 rounded text-xs">
                                        <i class="pi pi-file text-violet-500 shrink-0"></i>
                                        <span class="truncate flex-1" :title="f.name">{{ f.name }}</span>
                                        <button type="button" @click="removeOcrFile(i)" class="text-slate-400 hover:text-red-500 shrink-0">
                                            <i class="pi pi-times text-[10px]"></i>
                                        </button>
                                    </div>
                                    <button type="button" @click="clearOcrFiles" class="text-[10px] text-slate-400 hover:text-red-500">Clear all</button>
                                </div>

                                <button v-if="ocr.files.length && !ocr.scanning" type="button" @click="runOcr"
                                    class="w-full h-8 rounded-md bg-violet-600 hover:bg-violet-700 text-white text-xs font-medium flex items-center justify-center gap-1.5 transition-colors">
                                    <i class="pi pi-camera text-[11px]"></i>
                                    Scan {{ ocr.files.length }} Document{{ ocr.files.length > 1 ? 's' : '' }}
                                </button>

                                <div v-if="ocr.scanning" class="flex items-center gap-2 text-xs text-violet-600">
                                    <span class="w-3.5 h-3.5 border-2 border-violet-600 border-t-transparent rounded-full animate-spin"></span>
                                    {{ ocr.scanProgress || 'Processing OCR…' }}
                                </div>

                                <!-- OCR Results -->
                                <div v-if="ocr.result" class="space-y-2 p-3 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg">
                                    <div class="flex items-center gap-2 text-xs font-bold text-green-700 dark:text-green-400">
                                        <i class="pi pi-check-circle"></i> OCR Complete — {{ ocr.confidence }}% confidence
                                    </div>
                                    <p v-if="ocr.filesScanned" class="text-[10px] text-green-600 dark:text-green-500">{{ ocr.filesScanned }} file(s) merged</p>
                                    <button type="button" @click="showReviewModal = true"
                                        class="w-full h-7 rounded bg-[#1a3557] hover:bg-[#1e4880] text-white text-xs font-medium flex items-center justify-center gap-1.5 transition-colors">
                                        <i class="pi pi-eye text-[10px]"></i> Review &amp; Match
                                    </button>
                                    <button type="button" @click="applyOcrFields"
                                        class="w-full h-7 rounded bg-green-600 hover:bg-green-700 text-white text-xs font-medium flex items-center justify-center gap-1.5 transition-colors">
                                        <i class="pi pi-download text-[10px]"></i> Apply Directly
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Reference TD Tool -->
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm">
                        <h3 class="text-xs font-bold text-[#1a3557] dark:text-zinc-50 mb-2 flex items-center gap-1.5">
                            <i class="pi pi-copy text-blue-500 text-sm"></i> Existing TD in the OCR Scanner
                        </h3>
                        <AutoComplete v-model="toolTdSearch" :suggestions="toolTdSuggestions" optionLabel="td_number"
                            @complete="searchTdNumbers" @item-select="onToolTdSelect"
                            placeholder="Search TD number, owner, filename…" class="w-full text-xs" :loading="toolLoading" forceSelection dropdown>
                            <template #option="{ option }">
                                <div class="flex flex-col py-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#1a3557] dark:text-blue-400">{{ option.td_number }}</span>
                                        <span v-if="option.confidence_score" class="text-[9px] px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 font-semibold">{{ Number(option.confidence_score).toFixed(0) }}%</span>
                                    </div>
                                    <span v-if="option.owner_name" class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ option.owner_name }}</span>
                                    <span v-if="option.original_filename" class="text-[9px] text-slate-400 truncate">{{ option.original_filename }}</span>
                                </div>
                            </template>
                        </AutoComplete>
                        <p class="text-[10px] text-slate-400 mt-1.5 leading-snug">Fills the form from a document previously scanned in the OCR Management page.</p>
                        <div v-if="toolApplied" class="flex items-center gap-1.5 mt-2 p-1.5 rounded-md bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 text-[10px] text-blue-700 dark:text-blue-400">
                            <i class="pi pi-check-circle shrink-0"></i>
                            <span class="truncate">Copied from <strong>{{ toolAppliedTd }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- ─── Review & Match Modal ─────────────────────────────────────── -->
        <Dialog v-model:visible="showReviewModal" modal :closable="true" :dismissableMask="true"
            :style="{ width: '95vw', maxWidth: '1400px' }" :contentStyle="{ padding: 0 }">
            <template #header>
                <div class="flex items-center gap-3 w-full">
                    <div class="w-8 h-8 rounded-md bg-[#1a3557] flex items-center justify-center">
                        <i class="pi pi-eye text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[#1a3557] dark:text-slate-100">Review & Match</h3>
                        <p class="text-xs text-slate-500">Compare extracted data with source document. Edit any mismatched fields before applying.</p>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 h-[75vh]">
                <!-- Left: Document Preview -->
                <div class="border-r border-slate-200 dark:border-slate-700 flex flex-col">
                    <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                        <i class="pi pi-file-pdf text-red-500 text-sm"></i>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Source Document{{ ocr.previews.length > 1 ? 's' : '' }}</span>
                        <span class="ml-auto text-[10px] text-slate-400">{{ ocr.files.length }} file(s)</span>
                    </div>
                    <div class="flex-1 overflow-auto p-4 bg-slate-100 dark:bg-slate-900 space-y-3">
                        <div v-for="(url, i) in ocr.previews" :key="i" class="flex flex-col items-center gap-1">
                            <p class="text-[10px] text-slate-400 self-start">{{ ocr.files[i]?.name || `Page ${i + 1}` }}</p>
                            <img :src="url" class="max-w-full h-auto rounded shadow-md border border-slate-300 dark:border-slate-700" />
                        </div>
                        <div v-if="!ocr.previews.length" class="flex flex-col items-center justify-center h-full text-slate-400">
                            <i class="pi pi-image text-4xl mb-2"></i>
                            <p class="text-xs">No preview available</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Editable Extracted Fields -->
                <div class="flex flex-col">
                    <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                        <i class="pi pi-list text-[#b8860b] text-sm"></i>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Extracted Data</span>
                        <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-400 font-bold">{{ ocr.confidence }}% confidence</span>
                    </div>
                    <div class="flex-1 overflow-auto p-4 space-y-3">
                        <div v-for="(value, key) in reviewFields" :key="key" class="flex items-start gap-3">
                            <div class="w-40 shrink-0 pt-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ formatReviewLabel(key) }}</span>
                            </div>
                            <div class="flex-1">
                                <InputText v-if="!isArrayField(key)" v-model="reviewFields[key]" class="w-full text-sm" size="small" />
                                <div v-else class="flex items-center gap-3 flex-wrap">
                                    <label v-for="opt in propertyKinds" :key="opt" class="flex items-center gap-1.5 text-xs">
                                        <input type="checkbox" :value="opt" v-model="reviewFields[key]"
                                            class="w-3.5 h-3.5 rounded border-slate-300 text-[#1a3557]" />
                                        {{ opt }}
                                    </label>
                                </div>
                            </div>
                            <button type="button" @click="delete reviewFields[key]" class="shrink-0 w-6 h-6 mt-1 flex items-center justify-center rounded text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors" title="Remove field">
                                <i class="pi pi-times text-[10px]"></i>
                            </button>
                        </div>

                        <div v-if="!Object.keys(reviewFields).length" class="py-8 text-center text-slate-400">
                            <i class="pi pi-info-circle text-2xl mb-2 block"></i>
                            <p class="text-xs">No fields extracted. Check the document quality.</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center gap-2">
                        <button type="button" @click="showReviewModal = false"
                            class="h-8 px-4 rounded-md border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Cancel
                        </button>
                        <div class="flex-1"></div>
                        <span class="text-[10px] text-slate-400 mr-2">{{ Object.keys(reviewFields).length }} fields</span>
                        <button type="button" @click="applyReviewedFields"
                            class="h-8 px-5 rounded-md bg-green-600 hover:bg-green-700 text-white text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm">
                            <i class="pi pi-check text-[10px]"></i> Apply to Form
                        </button>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ─── Webcam Capture Modal ─────────────────────────────────────── -->
        <Dialog v-model:visible="showWebcam" modal :closable="true" :dismissableMask="false"
            :style="{ width: '95vw', maxWidth: '1200px' }" :contentStyle="{ padding: 0 }" @hide="closeWebcam">
            <template #header>
                <div class="flex items-center gap-3 w-full">
                    <div class="w-8 h-8 rounded-md bg-violet-600 flex items-center justify-center">
                        <i class="pi pi-camera text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[#1a3557] dark:text-slate-100">Scan with Webcam</h3>
                        <p class="text-xs text-slate-500">Center the TD document (portrait) in frame, then capture. It will be scanned automatically.</p>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 h-[75vh]">
                <!-- Left: Extracted Data -->
                <div class="border-r border-slate-200 dark:border-slate-700 flex flex-col order-2 lg:order-1">
                    <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                        <i class="pi pi-list text-[#b8860b] text-sm"></i>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Extracted Data</span>
                        <span v-if="ocr.result" class="ml-auto text-[10px] px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-400 font-bold">{{ ocr.confidence }}% confidence</span>
                    </div>

                    <div class="flex-1 overflow-auto p-4 space-y-3">
                        <div v-if="ocr.scanning" class="flex flex-col items-center justify-center h-full text-violet-500">
                            <span class="w-6 h-6 border-2 border-violet-500 border-t-transparent rounded-full animate-spin mb-2"></span>
                            <p class="text-xs">{{ ocr.scanProgress || 'Scanning document…' }}</p>
                        </div>

                        <template v-else-if="Object.keys(reviewFields).length">
                            <div v-for="(value, key) in reviewFields" :key="key" class="flex items-start gap-3">
                                <div class="w-32 shrink-0 pt-1.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ formatReviewLabel(key) }}</span>
                                </div>
                                <div class="flex-1">
                                    <InputText v-if="!isArrayField(key)" v-model="reviewFields[key]" class="w-full text-sm" size="small" />
                                    <div v-else class="flex items-center gap-3 flex-wrap">
                                        <label v-for="opt in propertyKinds" :key="opt" class="flex items-center gap-1.5 text-xs">
                                            <input type="checkbox" :value="opt" v-model="reviewFields[key]"
                                                class="w-3.5 h-3.5 rounded border-slate-300 text-[#1a3557]" />
                                            {{ opt }}
                                        </label>
                                    </div>
                                </div>
                                <button type="button" @click="delete reviewFields[key]" class="shrink-0 w-6 h-6 mt-1 flex items-center justify-center rounded text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors" title="Remove field">
                                    <i class="pi pi-times text-[10px]"></i>
                                </button>
                            </div>
                        </template>

                        <div v-else class="flex flex-col items-center justify-center h-full text-center text-slate-400">
                            <i class="pi pi-file-edit text-3xl mb-2"></i>
                            <p class="text-xs">Capture a photo on the right to extract data</p>
                        </div>
                    </div>

                    <div v-if="Object.keys(reviewFields).length" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center gap-2">
                        <span class="text-[10px] text-slate-400">{{ Object.keys(reviewFields).length }} fields</span>
                        <div class="flex-1"></div>
                        <button type="button" @click="applyWebcamFields"
                            class="h-8 px-4 rounded-md bg-green-600 hover:bg-green-700 text-white text-xs font-bold flex items-center gap-1.5 transition-colors shadow-sm">
                            <i class="pi pi-check text-[10px]"></i> Apply to Form
                        </button>
                    </div>
                </div>

                <!-- Right: Portrait Capture -->
                <div class="flex flex-col order-1 lg:order-2 bg-slate-100 dark:bg-slate-900">
                    <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                        <i class="pi pi-video text-violet-500 text-sm"></i>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Camera</span>
                        <select v-if="webcamDevices.length > 1" v-model="selectedDeviceId" @change="switchWebcamDevice(selectedDeviceId)"
                            class="ml-auto max-w-[55%] h-7 text-[11px] rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-1.5 text-slate-700 dark:text-slate-200">
                            <option v-for="d in webcamDevices" :key="d.deviceId" :value="d.deviceId">
                                {{ d.label || 'Camera' }}
                            </option>
                        </select>
                    </div>

                    <div class="flex-1 flex flex-col items-center justify-center gap-3 p-4 min-h-0 overflow-hidden">
                        <div class="w-full flex flex-col items-center gap-3" style="max-width: min(100%, calc(62vh * 3 / 4));">
                            <div class="relative bg-black rounded-lg overflow-hidden shadow-md w-full" style="aspect-ratio: 3 / 4;">
                                <video v-show="!webcamError && !webcamCapturedPreview" ref="webcamVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                <img v-if="webcamCapturedPreview" :src="webcamCapturedPreview" class="w-full h-full object-cover" />

                                <div v-if="webcamStarting" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-white bg-black/40">
                                    <span class="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    <span class="text-xs">Starting camera…</span>
                                </div>

                                <div v-if="webcamError" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center px-6">
                                    <i class="pi pi-video text-3xl text-red-400"></i>
                                    <p class="text-xs text-red-300">{{ webcamError }}</p>
                                    <button type="button" @click="startWebcamStream(selectedDeviceId)"
                                        class="mt-1 h-7 px-3 rounded-md bg-white/10 hover:bg-white/20 text-white text-[11px] font-medium transition-colors">
                                        Try Again
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 w-full">
                                <button v-if="webcamCapturedPreview" type="button" @click="retakePhoto" :disabled="ocr.scanning"
                                    class="flex-1 h-9 rounded-md border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                                    <i class="pi pi-refresh text-[11px] mr-1.5"></i> Retake
                                </button>
                                <button v-else type="button" @click="capturePhoto" :disabled="!webcamStream || webcamStarting || webcamCapturing"
                                    class="flex-1 h-9 rounded-md bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-sm disabled:opacity-50">
                                    <span v-if="webcamCapturing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    <i v-else class="pi pi-camera text-[11px]"></i>
                                    {{ webcamCapturing ? 'Capturing…' : 'Capture & Scan' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center gap-2 w-full">
                    <button type="button" @click="closeWebcam"
                        class="h-9 px-4 rounded-md border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Close
                    </button>
                </div>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import AutoComplete from 'primevue/autocomplete';
import Dialog from 'primevue/dialog';
import axios from 'axios';

const route  = useRoute();
const router = useRouter();
const toast  = useToast();

const isEdit = computed(() => !!route.params.id);
const td     = ref(null);
const saving = ref(false);
const errors = ref({});

const municipalities    = ref([]);
const barangays         = ref([]);
const classifications   = ref([]);
const ownerSuggestions  = ref([]);
const ownerSearch       = ref('');
const selectedOwnerId   = ref(null);

const propertyKinds = ['Land', 'Building', 'Machinery', 'Others'];
const classificationKinds = ['Residential', 'Agricultural', 'Commercial', 'Industrial', 'Mineral', 'Special', 'Timber/Forest', 'Improvements'];

function emptyValuationRow() {
    return { classification_id: null, area: null, base_market_value: null, actual_use: '' };
}

const valuationRows = ref(
    Array.from({ length: classificationKinds.length }, () => emptyValuationRow())
);
const assessmentRows = ref(
    classificationKinds.map((classification) => ({
        classification,
        adjusted_market_value: null,
        assessment_level: null,
        assessed_value: null,
    }))
);

const valuationTotals = computed(() => ({
    area: valuationRows.value.reduce((s, r) => s + (Number(r.area) || 0), 0),
    base_market_value: valuationRows.value.reduce((s, r) => s + (Number(r.base_market_value) || 0), 0),
}));

const assessmentTotals = computed(() => ({
    adjusted_market_value: assessmentRows.value.reduce((s, r) => s + (Number(r.adjusted_market_value) || 0), 0),
    assessed_value: assessmentRows.value.reduce((s, r) => s + (Number(r.assessed_value) || 0), 0),
}));

function formatNum(val, digits = 2) {
    if (!val && val !== 0) return '';
    return Number(val).toLocaleString('en-PH', {
        minimumFractionDigits: digits === 0 ? 0 : Math.min(digits, 2),
        maximumFractionDigits: digits,
    });
}

function ensureValuationRowCount() {
    const target = classificationKinds.length;
    while (valuationRows.value.length < target) {
        valuationRows.value.push(emptyValuationRow());
    }
    if (valuationRows.value.length > target) {
        valuationRows.value = valuationRows.value.slice(0, target);
    }
}

function syncValuationToForm() {
    ensureValuationRowCount();
    const first = valuationRows.value.find((r) => r.classification_id || r.area || r.base_market_value || r.actual_use)
        || valuationRows.value[0];
    form.classification_id = first?.classification_id ?? null;
    form.actual_use = first?.actual_use || '';
    form.land_area = valuationTotals.value.area || null;
    form.base_market_value = valuationTotals.value.base_market_value || null;
    form.market_value = form.base_market_value;
    form.valuation_rows = valuationRows.value;
}

function recalcAssessmentRow(row) {
    if (row.adjusted_market_value != null && row.assessment_level != null) {
        row.assessed_value = Math.round(Number(row.adjusted_market_value) * Number(row.assessment_level)) / 100;
    }
    syncAssessmentToForm();
}

function syncAssessmentToForm() {
    const filled = assessmentRows.value.find((r) => r.adjusted_market_value != null || r.assessed_value != null);
    if (filled) {
        form.current_use = filled.classification;
        form.adjusted_market_value = assessmentTotals.value.adjusted_market_value || filled.adjusted_market_value;
        form.assessment_level = filled.assessment_level;
        form.assessed_value = assessmentTotals.value.assessed_value || filled.assessed_value;
    } else {
        form.adjusted_market_value = assessmentTotals.value.adjusted_market_value || null;
        form.assessed_value = assessmentTotals.value.assessed_value || null;
    }
    const raw = assessmentTotals.value.assessed_value;
    form.rounded_assessed_value = raw ? Math.round(raw / 10) * 10 : null;
    form.assessment_rows = assessmentRows.value;
}

function loadValuationFromRecord(data) {
    if (Array.isArray(data.valuation_rows) && data.valuation_rows.length) {
        valuationRows.value = data.valuation_rows.map((r) => ({ ...emptyValuationRow(), ...r }));
    } else if (data.classification_id || data.land_area || data.base_market_value || data.actual_use) {
        valuationRows.value = [{
            classification_id: data.classification_id ?? null,
            area: data.land_area != null ? Number(data.land_area) : null,
            base_market_value: data.base_market_value != null ? Number(data.base_market_value) : null,
            actual_use: data.actual_use || '',
        }];
    } else {
        valuationRows.value = [emptyValuationRow()];
    }
    ensureValuationRowCount();
}

function loadAssessmentFromRecord(data) {
    const rows = Array.isArray(data.assessment_rows) && data.assessment_rows.length
        ? data.assessment_rows
        : [];
    assessmentRows.value = classificationKinds.map((classification) => {
        const found = rows.find((r) => r.classification === classification) || {};
        const isMatch = data.current_use
            && String(data.current_use).toLowerCase() === classification.toLowerCase();
        return {
            classification,
            adjusted_market_value: found.adjusted_market_value != null
                ? Number(found.adjusted_market_value)
                : (isMatch && data.adjusted_market_value != null ? Number(data.adjusted_market_value) : null),
            assessment_level: found.assessment_level != null
                ? Number(found.assessment_level)
                : (isMatch && data.assessment_level != null ? Number(data.assessment_level) : null),
            assessed_value: found.assessed_value != null
                ? Number(found.assessed_value)
                : (isMatch && data.assessed_value != null ? Number(data.assessed_value) : null),
        };
    });
}

// ── Form state ────────────────────────────────────────────────────────────────
const form = reactive({
    td_number: '',
    property_index_number: '',
    // Owner (stored on TD)
    owner_tin: '',
    owner_address: '',
    owner_telephone: '',
    // Administrator
    administrator_name: '',
    administrator_tin: '',
    administrator_address: '',
    administrator_telephone: '',
    // Location
    property_street: '',
    municipality_id: null,
    barangay_id: null,
    // Title/Survey
    oct_tct_cloa_no: '',
    survey_number: '',
    cct: '',
    lot_number: '',
    block_number: '',
    title_date: null,
    // Boundaries
    boundary_north: '',
    boundary_east: '',
    boundary_south: '',
    boundary_west: '',
    // Kind of property
    kind_of_property: [],
    no_of_storeys: null,
    building_description: '',
    machinery_description: '',
    others_specify: '',
    // Classification & Values
    classification_id: null,
    actual_use: '',
    current_use: null,
    land_area: null,
    building_area: null,
    base_market_value: null,
    market_value: null,
    adjusted_market_value: null,
    assessment_level: null,
    assessed_value: null,
    rounded_assessed_value: null,
    assessed_value_words: '',
    valuation_rows: null,
    assessment_rows: null,
    // Taxability & Effectivity
    taxability: 'taxable',
    effectivity_quarter: null,
    effectivity_year: '',
    effectivity_date: null,
    // Approval
    approved_by_name: '',
    date_issued: null,
    // Previous TD
    previous_td_number: '',
    previous_owner: '',
    previous_av: null,
    // Notes
    memoranda: '',
    remarks: '',
});

const ownerForm = reactive({
    owner_name: '', co_owner_name: '', tin: '', sex: null,
    civil_status: null, birth_date: null, address: '', contact_number: '', email: '',
});

// ── OCR Tool ──────────────────────────────────────────────────────────────────
const useTool = ref(false);
const ocr = reactive({
    files: [],
    previews: [],
    scanning: false,
    scanProgress: '',
    result: null,
    confidence: 0,
    filesScanned: 0,
    highlights: [],
});
const showReviewModal = ref(false);
const reviewFields = reactive({});

function toggleUseTool() { useTool.value = !useTool.value; }

function onOcrFileSelect(e) {
    addOcrFiles(e.target.files);
    e.target.value = '';
}
function onOcrDrop(e) {
    addOcrFiles(e.dataTransfer.files);
}

function addOcrFiles(fileList) {
    const incoming = Array.from(fileList || []).filter((f) =>
        /^(image\/|application\/pdf)/i.test(f.type) || /\.(jpe?g|png|gif|webp|pdf)$/i.test(f.name)
    );
    for (const file of incoming) {
        const dup = ocr.files.some((f) => f.name === file.name && f.size === file.size);
        if (dup) continue;
        ocr.files.push(file);
        ocr.previews.push(URL.createObjectURL(file));
    }
}

function removeOcrFile(index) {
    if (ocr.previews[index]) URL.revokeObjectURL(ocr.previews[index]);
    ocr.files.splice(index, 1);
    ocr.previews.splice(index, 1);
}

function clearOcrFiles() {
    ocr.previews.forEach((url) => URL.revokeObjectURL(url));
    ocr.files = [];
    ocr.previews = [];
    ocr.result = null;
    ocr.confidence = 0;
    ocr.filesScanned = 0;
}

// ── Webcam Capture ───────────────────────────────────────────────────────────
const showWebcam = ref(false);
const webcamVideo = ref(null);
const webcamStream = ref(null);
const webcamDevices = ref([]);
const selectedDeviceId = ref(null);
const webcamStarting = ref(false);
const webcamCapturing = ref(false);
const webcamError = ref('');
const webcamCapturedPreview = ref(null);
const WEBCAM_PORTRAIT_ASPECT = 3 / 4; // width / height — matches the on-screen portrait viewfinder

async function openWebcam() {
    if (!navigator.mediaDevices?.getUserMedia) {
        toast.error('Camera Unavailable', 'This browser does not support webcam capture. Try Chrome or Edge, and make sure the page is served over HTTPS or localhost.');
        return;
    }
    useTool.value = true;
    showWebcam.value = true;
    await nextTick();
    await startWebcamStream(selectedDeviceId.value);
}

async function startWebcamStream(deviceId = null) {
    webcamError.value = '';
    webcamStarting.value = true;
    stopWebcamStream();
    try {
        const constraints = {
            video: deviceId
                ? { deviceId: { exact: deviceId } }
                : { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
            audio: false,
        };
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        webcamStream.value = stream;
        await nextTick();
        if (webcamVideo.value) {
            webcamVideo.value.srcObject = stream;
            await webcamVideo.value.play().catch(() => {});
        }
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            webcamDevices.value = devices.filter((d) => d.kind === 'videoinput');
        } catch { /* enumerateDevices can fail on some browsers without impacting capture */ }
        const activeId = stream.getVideoTracks()[0]?.getSettings()?.deviceId;
        if (activeId) selectedDeviceId.value = activeId;
    } catch (err) {
        webcamStream.value = null;
        if (err?.name === 'NotAllowedError' || err?.name === 'PermissionDeniedError') {
            webcamError.value = 'Camera access was denied. Allow camera permission for this site and try again.';
        } else if (err?.name === 'NotFoundError' || err?.name === 'DevicesNotFoundError') {
            webcamError.value = 'No webcam was found on this computer.';
        } else if (err?.name === 'NotReadableError') {
            webcamError.value = 'The webcam is already in use by another application.';
        } else {
            webcamError.value = 'Could not access the webcam.';
        }
    } finally {
        webcamStarting.value = false;
    }
}

function stopWebcamStream() {
    if (webcamStream.value) {
        webcamStream.value.getTracks().forEach((t) => t.stop());
        webcamStream.value = null;
    }
    if (webcamVideo.value) webcamVideo.value.srcObject = null;
}

function switchWebcamDevice(deviceId) {
    startWebcamStream(deviceId);
}

function clearWebcamPreview() {
    if (webcamCapturedPreview.value) URL.revokeObjectURL(webcamCapturedPreview.value);
    webcamCapturedPreview.value = null;
}

function closeWebcam() {
    stopWebcamStream();
    clearWebcamPreview();
    showWebcam.value = false;
    webcamError.value = '';
}

function retakePhoto() {
    clearWebcamPreview();
}

async function capturePhoto() {
    if (!webcamVideo.value || !webcamStream.value) return;
    webcamCapturing.value = true;
    try {
        const video = webcamVideo.value;
        const vw = video.videoWidth || 1280;
        const vh = video.videoHeight || 960;

        // Crop the (usually landscape) webcam feed to the portrait aspect ratio
        // shown in the viewfinder, so the captured file matches a TD document's shape.
        let cropW = vh * WEBCAM_PORTRAIT_ASPECT;
        let cropH = vh;
        if (cropW > vw) {
            cropW = vw;
            cropH = vw / WEBCAM_PORTRAIT_ASPECT;
        }
        const sx = (vw - cropW) / 2;
        const sy = (vh - cropH) / 2;

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(cropW);
        canvas.height = Math.round(cropH);
        canvas.getContext('2d').drawImage(video, sx, sy, cropW, cropH, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));
        if (!blob) throw new Error('Capture produced no image data.');

        clearWebcamPreview();
        webcamCapturedPreview.value = URL.createObjectURL(blob);

        const file = new File([blob], `webcam-scan-${Date.now()}.jpg`, { type: 'image/jpeg' });
        addOcrFiles([file]);
        await runOcr();
    } catch (err) {
        toast.error('Capture Failed', 'Could not capture a photo from the webcam. Please try again.');
    } finally {
        webcamCapturing.value = false;
    }
}

function applyWebcamFields() {
    if (!Object.keys(reviewFields).length) return;
    ocr.result = JSON.parse(JSON.stringify(reviewFields));
    applyOcrFields();
    closeWebcam();
    toast.success('Applied', 'Extracted fields have been applied to the form.');
}

onBeforeUnmount(() => {
    stopWebcamStream();
    clearWebcamPreview();
});

function mergeOcrFields(target, source) {
    for (const [key, val] of Object.entries(source || {})) {
        if (val == null || val === '') continue;
        if (Array.isArray(val)) {
            const prev = Array.isArray(target[key]) ? target[key] : [];
            target[key] = [...new Set([...prev, ...val])];
            continue;
        }
        if (target[key] == null || target[key] === '') {
            target[key] = val;
        }
    }
    return target;
}

async function runOcr() {
    if (!ocr.files.length) return;
    ocr.scanning = true;
    ocr.scanProgress = '';
    try {
        const merged = {};
        const scores = [];
        for (let i = 0; i < ocr.files.length; i++) {
            const file = ocr.files[i];
            ocr.scanProgress = `Scanning ${i + 1} of ${ocr.files.length}: ${file.name}`;
            const fd = new FormData();
            fd.append('file', file);
            const { data: uploadRes } = await axios.post('ocr/upload', fd);
            const { data: scanRes } = await axios.post(`ocr/${uploadRes.id}/scan`);
            mergeOcrFields(merged, scanRes.extracted_fields || {});
            if (scanRes.confidence_score != null) scores.push(Number(scanRes.confidence_score));
        }
        ocr.result = merged;
        ocr.confidence = scores.length
            ? Math.round(scores.reduce((a, b) => a + b, 0) / scores.length)
            : 0;
        ocr.filesScanned = ocr.files.length;
        Object.keys(reviewFields).forEach((k) => delete reviewFields[k]);
        Object.assign(reviewFields, JSON.parse(JSON.stringify(ocr.result)));

        if (Object.keys(merged).length === 0) {
            toast.warn('No Fields Detected', 'This does not look like a Tax Declaration document. Make sure the actual TD form is in frame, well-lit, and try again.');
        } else {
            toast.success('OCR Complete', `${ocr.filesScanned} file(s) · Confidence: ${ocr.confidence}%`);
        }
    } catch (err) {
        toast.error('OCR Failed', err.response?.data?.message || 'Scan error.');
    } finally {
        ocr.scanning = false;
        ocr.scanProgress = '';
    }
}

// ── Review & Match helpers ────────────────────────────────────────────────────
const reviewLabelMap = {
    td_number: 'TD No.', property_identification_no: 'Property ID No.',
    owner_name: 'Owner', tin: 'TIN', telephone: 'Telephone No.',
    address: 'Address', administrator: 'Administrator',
    location_street: 'Location (Street)', barangay: 'Barangay', municipality: 'Municipality', province: 'Province',
    survey_no: 'Survey No.', lot_no: 'Lot No.', block_no: 'Block No.',
    boundary_north: 'Boundary North', boundary_south: 'Boundary South',
    boundary_east: 'Boundary East', boundary_west: 'Boundary West',
    kind_of_property: 'Kind of Property', classification: 'Classification', actual_use: 'Actual Use',
    area: 'Area (Ha/Sq.M.)', base_market_value: 'Base Market Value',
    market_value: 'Market Value', adjusted_market_value: 'Adjusted Market Value',
    assessment_level: 'Assessment Level (%)', assessed_value: 'Assessed Value',
    rounded_assessed_value: 'Rounded Assessed Value',
    assessed_value_words: 'Assessed Value (Words)', taxability: 'Taxable/Exempt',
    effectivity_quarter: 'Effectivity Quarter', effectivity_year: 'Effectivity Year',
    approved_by: 'Approved By', approval_date: 'Approval Date',
    previous_td: 'Cancels TD No.', previous_owner: 'Previous Owner',
    previous_av: 'Previous A.V.', memoranda: 'Memoranda',
};

function formatReviewLabel(key) {
    return reviewLabelMap[key] || key.replace(/_/g, ' ');
}

function isArrayField(key) {
    return key === 'kind_of_property' && Array.isArray(reviewFields[key]);
}

function applyReviewedFields() {
    ocr.result = JSON.parse(JSON.stringify(reviewFields));
    applyOcrFields();
    showReviewModal.value = false;
}

async function resolveMunicipalityFromOcr(name, province) {
    if (!name) return null;
    const existing = findMunicipality(name);
    if (existing) return existing;

    try {
        const { data } = await axios.post('settings/municipalities/resolve', {
            name,
            province: province || null,
        });
        if (!municipalities.value.some(m => m.id === data.id)) {
            municipalities.value.push(data);
        }
        return data;
    } catch {
        return null;
    }
}

// ── Location dropdown matching ────────────────────────────────────────────────
function normalizeLocationName(str) {
    return String(str || '')
        .toLowerCase()
        .replace(/^(barangay|brgy\.?|district|municipality|mun\.?|city of|municipality of|province of|prov\.?)\s+/gi, '')
        .replace(/[^\w\sñ]/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function namesMatch(a, b) {
    const na = normalizeLocationName(a);
    const nb = normalizeLocationName(b);
    if (!na || !nb || na.length < 2 || nb.length < 2) return false;
    if (na === nb) return true;
    if (na.includes(nb) || nb.includes(na)) return true;
    // Word overlap: "San Vicente" matches "San Vicente Pob"
    const wordsA = na.split(' ').filter(w => w.length > 2);
    const wordsB = nb.split(' ').filter(w => w.length > 2);
    if (wordsA.length && wordsB.length && wordsA.every(w => nb.includes(w))) return true;
    return false;
}

function findMunicipality(name) {
    if (!name) return null;
    return municipalities.value.find(m => namesMatch(m.name, name)) || null;
}

function findBarangay(name, municipalityId = null) {
    if (!name) return null;
    const pool = municipalityId
        ? barangays.value.filter(b => b.municipality_id === municipalityId)
        : barangays.value;
    return pool.find(b => namesMatch(b.name, name)) || null;
}

function parseLocationSegments(text) {
    if (!text) return [];
    return text.split(',').map(s => s.trim()).filter(Boolean);
}

function splitMunicipalityProvince(text) {
    if (!text || !String(text).includes(',')) {
        return { municipality: text || null, province: null };
    }
    const parts = String(text).split(',').map(s => s.trim()).filter(Boolean);
    return {
        municipality: parts[0] || null,
        province: parts.slice(1).join(', ') || null,
    };
}

function buildLocationSearchText(r) {
    return [r.location_street, r.barangay, r.municipality, r.province]
        .filter(Boolean)
        .join(' ');
}

function findBarangayInText(text, municipalityId = null) {
    if (!text) return null;
    const normalizedText = normalizeLocationName(text);
    const pool = municipalityId
        ? barangays.value.filter(b => b.municipality_id === municipalityId)
        : barangays.value;
    const sorted = [...pool].sort(
        (a, b) => normalizeLocationName(b.name).length - normalizeLocationName(a.name).length
    );
    return sorted.find(b => {
        const nb = normalizeLocationName(b.name);
        return nb.length >= 2 && normalizedText.includes(nb);
    }) || null;
}

async function applyLocationDropdowns(r, highlights) {
    const locationText = buildLocationSearchText(r);
    const segments = parseLocationSegments(r.location_street);
    const munParts = splitMunicipalityProvince(r.municipality);
    const barangayCandidate = r.barangay || null;
    const municipalityCandidate = munParts.municipality || (segments.length >= 2 ? segments[1] : null);
    const provinceCandidate = munParts.province || r.province || null;

    let matchedBrgy = findBarangay(barangayCandidate);
    let matchedMun = findMunicipality(municipalityCandidate);

    // Match saved barangay names inside full location text (handles "San Vicente Bato, Camarines Sur")
    if (!matchedBrgy) {
        matchedBrgy = findBarangayInText(locationText, matchedMun?.id ?? null);
    }
    if (!matchedBrgy) {
        matchedBrgy = findBarangayInText(locationText);
    }

    // Prefer OCR municipality over the barangay's linked municipality
    if (!matchedMun && municipalityCandidate) {
        matchedMun = await resolveMunicipalityFromOcr(municipalityCandidate, provinceCandidate);
    }

    if (!matchedMun && !municipalityCandidate && matchedBrgy?.municipality_id) {
        matchedMun = municipalities.value.find(m => m.id === matchedBrgy.municipality_id) || null;
    }

    // If barangay not matched but municipality is, search within that municipality
    if (!matchedBrgy && barangayCandidate && matchedMun) {
        matchedBrgy = findBarangay(barangayCandidate, matchedMun.id);
    }

    // Try all saved barangays against location text segments
    if (!matchedBrgy && segments.length) {
        for (const seg of segments) {
            const hit = findBarangay(seg, matchedMun?.id ?? null);
            if (hit) { matchedBrgy = hit; break; }
        }
    }

    // Try matching municipality from any segment
    if (!matchedMun && segments.length) {
        for (const seg of segments) {
            const hit = findMunicipality(seg);
            if (hit) { matchedMun = hit; break; }
        }
    }

    if (matchedMun) {
        form.municipality_id = matchedMun.id;
        highlights.push('municipality_id');
    }

    if (matchedBrgy) {
        form.barangay_id = matchedBrgy.id;
        highlights.push('barangay_id');
    }

    // Street: use location_street unless it looks like "Barangay, Municipality, Province".
    // When barangay/municipality/province are recognized, strip them from location_street so
    // only the actual street portion (if any) remains — the form has separate barangay + municipality dropdowns.
    if (r.location_street) {
        const looksLikeFullLocation = segments.length >= 2 && (matchedBrgy || matchedMun);
        if (!looksLikeFullLocation) {
            form.property_street = r.location_street;
            highlights.push('property_street');
        } else {
            const stripTokens = [
                matchedBrgy?.name,
                barangayCandidate,
                matchedMun?.name,
                municipalityCandidate,
                provinceCandidate,
                r.province,
            ].filter(Boolean);

            let streetOnly = String(r.location_street);
            for (const tok of stripTokens) {
                streetOnly = streetOnly.replace(new RegExp(String(tok).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'ig'), '');
            }
            streetOnly = streetOnly.replace(/[,\s]+/g, ' ').trim().replace(/^,\s*|,\s*$/g, '');

            if (streetOnly && streetOnly.length >= 2) {
                form.property_street = streetOnly;
                highlights.push('property_street');
            } else {
                form.property_street = '';
            }
        }
    }
}

async function applyOcrFields() {
    if (!ocr.result) return;
    const r = normalizeOcrResult(ocr.result);
    const highlights = [];

    function setField(formKey, val) {
        if (val == null || (typeof val === 'string' && val.trim() === '')) return;
        form[formKey] = typeof val === 'string' ? val.trim() : val;
        if (!highlights.includes(formKey)) highlights.push(formKey);
    }
    function setNumeric(formKey, val) {
        if (val == null || val === '') return;
        const num = parseOcrNumber(val);
        if (num != null) {
            form[formKey] = num;
            if (!highlights.includes(formKey)) highlights.push(formKey);
        }
    }

    // TD & Property ID
    setField('td_number', r.td_number);
    setField('property_index_number', r.property_identification_no || r.property_index_number || r.pin);

    // Owner
    if (r.owner_name) {
        let name = String(r.owner_name).replace(/\s+/g, ' ').trim()
            .replace(/\s*(?:Taxab[a-z]{0,10}|Taxable|Exempt|Address|T\.?I\.?N\.?).*$/i, '')
            .replace(/\b(?:Taxab[a-z]{0,10}|Taxable|Exempt)\b/gi, '')
            .trim();
        if (name) {
            ownerForm.owner_name = name;
            selectedOwnerId.value = null;
            highlights.push('owner_name');
        }
    }
    setField('owner_tin', r.tin || r.owner_tin);
    setField('owner_telephone', r.telephone || r.owner_telephone || r.tel);
    if (r.address || r.owner_address) {
        const addr = String(r.address || r.owner_address).trim();
        if (addr && !/^(PROPERTY|LOCATION|PROPERTY\s+LOCATION)$/i.test(addr)) {
            ownerForm.address = addr;
            setField('owner_address', addr);
            highlights.push('owner_address');
        }
    }

    // Administrator
    setField('administrator_name', r.administrator || r.administrator_name);
    setField('administrator_tin', r.administrator_tin);
    setField('administrator_address', r.administrator_address);
    setField('administrator_telephone', r.administrator_telephone);

    // Location dropdowns (also handles property_street conditionally — do NOT setField after,
    // otherwise the full "Street Barangay, Municipality Province" string overrides the parsed street)
    await applyLocationDropdowns(r, highlights);
    if (!form.property_street && (r.property_street || r.street)) {
        setField('property_street', r.property_street || r.street);
    }

    // Title / Survey
    setField('oct_tct_cloa_no', r.oct_tct_cloa || r.oct_tct_cloa_no || r.tct);
    setField('survey_number', r.survey_no || r.survey_number);
    setField('cct', r.cct);
    setField('lot_number', r.lot_no || r.lot_number);
    setField('block_number', r.block_no || r.block_number);
    if (r.title_date || r.dated) {
        const d = parseMaybeDate(r.title_date || r.dated);
        if (d) { form.title_date = d; highlights.push('title_date'); }
    }

    // Boundaries
    setField('boundary_north', r.boundary_north || r.north);
    setField('boundary_south', r.boundary_south || r.south);
    setField('boundary_east', r.boundary_east || r.east);
    setField('boundary_west', r.boundary_west || r.west);

    // Kind of Property
    const kinds = normalizeKindOfProperty(r.kind_of_property);
    if (kinds.length) {
        form.kind_of_property = kinds;
        highlights.push('kind_of_property');
    }
    setNumeric('no_of_storeys', r.no_of_storeys || r.storeys);
    setField('building_description', r.building_description);
    setField('machinery_description', r.machinery_description);
    setField('others_specify', r.others_specify || r.others);

    // Classification & values → form + dual tables
    const matchedCls = matchClassification(r.classification || r.classification_kind);
    const matchedKind = matchClassificationKind(r.classification || r.current_use || r.classification_kind);
    if (matchedCls) {
        form.classification_id = matchedCls.id;
        highlights.push('classification_id');
    }
    if (matchedKind) {
        form.current_use = matchedKind;
        highlights.push('current_use');
    }

    setField('actual_use', r.actual_use);
    setNumeric('land_area', r.area || r.land_area);
    setNumeric('base_market_value', r.base_market_value);
    setNumeric('market_value', r.market_value || r.base_market_value);
    setNumeric('adjusted_market_value', r.adjusted_market_value);
    setNumeric('assessment_level', r.assessment_level);
    setNumeric('assessed_value', r.assessed_value);
    setNumeric('rounded_assessed_value', r.rounded_assessed_value || r.rounded);
    setField('assessed_value_words', r.assessed_value_words);

    // Fill left valuation table
    ensureValuationRowCount();
    valuationRows.value[0] = {
        classification_id: matchedCls?.id ?? form.classification_id ?? null,
        area: parseOcrNumber(r.area || r.land_area) ?? form.land_area,
        base_market_value: parseOcrNumber(r.base_market_value) ?? form.base_market_value,
        actual_use: (r.actual_use || form.actual_use || '').trim(),
    };
    // Clear other left rows so totals stay clean
    for (let i = 1; i < valuationRows.value.length; i++) {
        valuationRows.value[i] = emptyValuationRow();
    }
    syncValuationToForm();
    highlights.push('land_area', 'base_market_value', 'actual_use');

    // Fill right assessment table for matching kind
    const kindKey = matchedKind
        || matchClassificationKind(matchedCls?.name)
        || 'Agricultural';
    assessmentRows.value = classificationKinds.map((classification) => {
        const isMatch = classification.toLowerCase() === String(kindKey).toLowerCase();
        return {
            classification,
            adjusted_market_value: isMatch ? (parseOcrNumber(r.adjusted_market_value) ?? form.adjusted_market_value) : null,
            assessment_level: isMatch ? (parseOcrNumber(r.assessment_level) ?? form.assessment_level) : null,
            assessed_value: isMatch ? (parseOcrNumber(r.assessed_value) ?? form.assessed_value) : null,
        };
    });
    const filledRow = assessmentRows.value.find((row) =>
        row.classification.toLowerCase() === String(kindKey).toLowerCase()
    );
    if (filledRow) {
        if (filledRow.assessed_value == null && filledRow.adjusted_market_value != null && filledRow.assessment_level != null) {
            recalcAssessmentRow(filledRow);
        } else {
            syncAssessmentToForm();
        }
        highlights.push('adjusted_market_value', 'assessment_level', 'assessed_value');
    }

    // Prefer OCR rounded value when present (syncAssessmentToForm may have recomputed it)
    const ocrRounded = parseOcrNumber(r.rounded_assessed_value || r.rounded);
    if (ocrRounded != null) {
        form.rounded_assessed_value = ocrRounded;
        highlights.push('rounded_assessed_value');
    }

    // Taxability
    if (r.taxability) {
        const t = String(r.taxability).toLowerCase();
        form.taxability = t.includes('exempt') ? 'exempt' : 'taxable';
        highlights.push('taxability');
    }

    // Effectivity — only from OCR effectivity fields (not approval dates)
    if (r.effectivity_quarter) {
        const q = String(r.effectivity_quarter).toLowerCase().replace(/quarter|qtr\.?/g, '').trim();
        const map = {
            '1': '1st', '1st': '1st', 'fst': '1st', 'ist': '1st', 'i': '1st', 'l': '1st', 'f': '1st',
            '2': '2nd', '2nd': '2nd', '3': '3rd', '3rd': '3rd', '4': '4th', '4th': '4th',
        };
        if (map[q]) setField('effectivity_quarter', map[q]);
    }
    const effYearRaw = String(r.effectivity_year || '').replace(/\D/g, '');
    if (effYearRaw.length === 4) {
        const y = parseInt(effYearRaw, 10);
        const maxY = new Date().getFullYear() + 1;
        if (y >= 1990 && y <= maxY) setField('effectivity_year', String(y));
    }

    // Approval
    setField('approved_by_name', r.approved_by || r.approved_by_name);
    if (r.approval_date || r.date_issued) {
        const d = parseMaybeDate(r.approval_date || r.date_issued);
        if (d) { form.date_issued = d; highlights.push('date_issued'); }
    }

    // Previous TD
    setField('previous_td_number', r.previous_td || r.previous_td_number);
    {
        let prevOwner = String(r.previous_owner || '').replace(/\s+/g, ' ').trim();
        prevOwner = prevOwner
            .replace(/\s*(?:Taxab[a-z]{0,10}|Taxable|Exempt|Previous\s+(?:Assessed\s+Value|A\.?V\.?)|Tax\s+Effectivity).*$/i, '')
            .replace(/\b(?:Taxab[a-z]{0,10}|Taxable|Exempt)\b/gi, '')
            .replace(/\s+/g, ' ')
            .replace(/[,\s.]+$/g, '')
            .trim();
        if (prevOwner) setField('previous_owner', prevOwner);
    }
    setNumeric('previous_av', r.previous_av);

    // Memoranda — prefer "Revised Pursuant…" and strip References/Posting bleed
    if (r.memoranda) {
        const raw = String(r.memoranda).trim();
        const pursuant = raw.match(/Revised\s+Pursuant\s+to\s+Sec\.?\s*\d+[A-Za-z]?\s+of\s+R\.?A\.?\s*\d+/i);
        if (pursuant) {
            setField('memoranda', pursuant[0].replace(/\s+/g, ' ').trim());
        } else if (!/REFERENCES?\s*&?\s*(?:AND\s+)?POSTING|REFERENCES?\s+AND|Posting\s+Report|Previous\s+Record|Clerk\s+Initial/i.test(raw)) {
            setField('memoranda', raw);
        } else {
            const cleaned = raw
                .replace(/\bREFERENCES?\s*&?\s*(?:AND\s+)?(?:POSTING\s+SUMMARY)?\b[\s\S]*$/i, '')
                .replace(/\bREFERENCES?\s+AND\b[\s\S]*$/i, '')
                .replace(/\bPosting\s+Report\b[\s\S]*$/i, '')
                .trim();
            if (cleaned.length > 2) setField('memoranda', cleaned);
        }
    }
    setField('remarks', r.remarks);

    ocr.highlights = [...new Set(highlights.filter(Boolean))];
    toast.success('Applied', `${ocr.highlights.length} fields auto-filled from OCR — including valuation tables.`);
}

function normalizeOcrResult(raw) {
    const r = { ...(raw || {}) };
    // Common alias normalization (TD + FAAS shared OCR keys)
    if (!r.td_number && (r.td_no || r.arp_no || r.arp_number)) {
        r.td_number = r.td_no || r.arp_no || r.arp_number;
    }
    if (!r.property_identification_no && r.pin) r.property_identification_no = r.pin;
    if (!r.oct_tct_cloa && r.oct_tct_kot_no) r.oct_tct_cloa = r.oct_tct_kot_no;
    if (!r.lot_no && r.cad_pls_lot_no) r.lot_no = r.cad_pls_lot_no;
    if (!r.area && r.land_area) r.area = r.land_area;
    if (!r.classification && r.classification_kind) r.classification = r.classification_kind;
    if (!r.adjusted_market_value && r.adjusted_mv) r.adjusted_market_value = r.adjusted_mv;
    if (!r.assessment_level && (r.assmt_level || r.assessment_rate)) {
        r.assessment_level = r.assmt_level || r.assessment_rate;
    }
    return r;
}

function parseOcrNumber(val) {
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
        // 5.280.00 → 5280.00 (OCR thousand separators)
        const pos = s.lastIndexOf('.');
        s = s.slice(0, pos).replace(/\./g, '') + '.' + s.slice(pos + 1);
    } else {
        s = s.replace(/[^0-9.\-]/g, '');
    }
    const num = parseFloat(s);
    return Number.isNaN(num) ? null : num;
}

function normalizeKindOfProperty(val) {
    if (!val) return [];
    const list = Array.isArray(val) ? val : String(val).split(/[,;/|]+/);
    const map = {
        land: 'Land', building: 'Building', machinery: 'Machinery',
        others: 'Others', other: 'Others', improvement: 'Others', improvements: 'Others',
    };
    const out = [];
    for (const item of list) {
        const key = String(item).trim().toLowerCase();
        const mapped = map[key] || propertyKinds.find((k) => k.toLowerCase() === key);
        if (mapped && !out.includes(mapped)) out.push(mapped);
    }
    return out;
}

function matchClassification(name) {
    if (!name) return null;
    const n = String(name).toLowerCase().replace(/[\/\-_]/g, ' ').trim();
    return classifications.value.find((c) => {
        const cn = c.name.toLowerCase().replace(/[\/\-_]/g, ' ');
        return cn === n || cn.includes(n) || n.includes(cn);
    }) || null;
}

function matchClassificationKind(name) {
    if (!name) return null;
    const n = String(name).toLowerCase().replace(/[\/\-_]/g, ' ').trim();
    return classificationKinds.find((k) => {
        const kn = k.toLowerCase().replace(/[\/\-_]/g, ' ');
        return kn === n || kn.includes(n) || n.includes(kn.split(' ')[0]);
    }) || null;
}

// ── Reference TD Tool ─────────────────────────────────────────────────────────
const toolTdSearch      = ref('');
const toolTdSuggestions = ref([]);
const toolLoading       = ref(false);
const toolApplied       = ref(false);
const toolAppliedTd     = ref('');

async function searchTdNumbers(event) {
    toolLoading.value = true;
    try {
        const { data } = await axios.get('ocr/extracted-td-numbers', {
            params: { q: event.query || '', limit: 25 },
        });
        toolTdSuggestions.value = Array.isArray(data) ? data : [];
    } catch {
        toolTdSuggestions.value = [];
    } finally { toolLoading.value = false; }
}

async function onToolTdSelect(event) {
    const selected = event.value;
    if (!selected?.fields || !selected.td_number) return;
    toolLoading.value = true;
    try {
        // Feed the OCR-extracted fields through the same pipeline used by the
        // in-form OCR tool so date/number/dropdown normalization is identical.
        ocr.result = JSON.parse(JSON.stringify(selected.fields));
        ocr.confidence = Number(selected.confidence_score || 0);
        ocr.filesScanned = 1;
        Object.keys(reviewFields).forEach((k) => delete reviewFields[k]);
        Object.assign(reviewFields, JSON.parse(JSON.stringify(selected.fields)));

        await applyOcrFields();

        toolApplied.value = true;
        toolAppliedTd.value = selected.td_number;
        toast.success(
            'Form pre-filled',
            `Data from OCR record for TD# ${selected.td_number} loaded into the form.`
        );
    } catch (err) {
        toast.error('Error', err?.response?.data?.message || 'Could not load the selected OCR record.');
    } finally { toolLoading.value = false; }
}

function parseMaybeDate(val) {
    if (!val) return null;
    const d = new Date(val);
    return Number.isNaN(d.getTime()) ? null : d;
}

function fillFromExisting(data) {
    // Copy TD number and all scalar form fields
    const skip = ['id', 'created_at', 'updated_at', 'deleted_at', 'qr_code', 'version', 'owner_id', 'created_by', 'updated_by', 'approved_by', 'approved_at', 'is_locked', 'locked_by', 'locked_at', 'status'];
    for (const key of Object.keys(form)) {
        if (skip.includes(key)) continue;
        if (data[key] !== undefined && data[key] !== null) {
            form[key] = data[key];
        }
    }

    // Explicitly set TD number (user expects it filled; they can edit before save)
    if (data.td_number) form.td_number = data.td_number;

    // Dates need Date objects for DatePicker
    form.title_date = parseMaybeDate(data.title_date);
    form.date_issued = parseMaybeDate(data.date_issued);
    form.effectivity_date = parseMaybeDate(data.effectivity_date);

    // Numeric casts for InputNumber
    const numericKeys = [
        'land_area', 'building_area', 'base_market_value', 'market_value',
        'adjusted_market_value', 'assessment_level', 'assessed_value',
        'rounded_assessed_value', 'previous_av', 'no_of_storeys',
    ];
    for (const key of numericKeys) {
        if (data[key] != null && data[key] !== '') form[key] = Number(data[key]);
    }

    // Kind of property array
    if (Array.isArray(data.kind_of_property)) {
        form.kind_of_property = [...data.kind_of_property];
    }

    // Owner block — treat as new owner entry (clear selectedOwnerId so a new owner can be created, or keep link)
    if (data.owner) {
        selectedOwnerId.value = data.owner_id ?? null;
        Object.assign(ownerForm, {
            owner_name: data.owner.owner_name ?? '',
            co_owner_name: data.owner.co_owner_name ?? '',
            tin: data.owner.tin ?? data.owner_tin ?? '',
            address: data.owner.address ?? data.owner_address ?? '',
            contact_number: data.owner.contact_number ?? data.owner_telephone ?? '',
            email: data.owner.email ?? '',
        });
        ownerSearch.value = data.owner;
        if (!form.owner_tin && (data.owner.tin || data.owner_tin)) {
            form.owner_tin = data.owner.tin || data.owner_tin;
        }
        if (!form.owner_telephone && (data.owner.contact_number || data.owner_telephone)) {
            form.owner_telephone = data.owner.contact_number || data.owner_telephone;
        }
        if (!form.owner_address && (data.owner.address || data.owner_address)) {
            form.owner_address = data.owner.address || data.owner_address;
        }
    }

    // Valuation + assessment tables (the dual grid)
    loadValuationFromRecord(data);
    loadAssessmentFromRecord(data);
    if (data.rounded_assessed_value != null) {
        form.rounded_assessed_value = Number(data.rounded_assessed_value);
    } else {
        syncAssessmentToForm();
    }

    // If source has no valuation_rows JSON, still seed left table from flat fields
    const hasValData = valuationRows.value.some((r) => r.classification_id || r.area || r.base_market_value || r.actual_use);
    if (!hasValData && (data.classification_id || data.land_area || data.base_market_value || data.actual_use)) {
        valuationRows.value[0] = {
            classification_id: data.classification_id ?? null,
            area: data.land_area != null ? Number(data.land_area) : null,
            base_market_value: data.base_market_value != null ? Number(data.base_market_value) : null,
            actual_use: data.actual_use || '',
        };
        ensureValuationRowCount();
        syncValuationToForm();
    }

    // Seed right-side assessment row from flat fields when assessment_rows empty
    const hasAssessData = assessmentRows.value.some((r) => r.adjusted_market_value != null || r.assessed_value != null);
    if (!hasAssessData && (data.adjusted_market_value != null || data.assessed_value != null)) {
        const kindName = data.current_use
            || classifications.value.find((c) => c.id === data.classification_id)?.name
            || 'Agricultural';
        const row = assessmentRows.value.find((r) =>
            r.classification.toLowerCase() === String(kindName).toLowerCase()
        ) || assessmentRows.value.find((r) => r.classification === 'Agricultural');
        if (row) {
            row.adjusted_market_value = data.adjusted_market_value != null ? Number(data.adjusted_market_value) : null;
            row.assessment_level = data.assessment_level != null ? Number(data.assessment_level) : null;
            row.assessed_value = data.assessed_value != null ? Number(data.assessed_value) : null;
            if (row.assessed_value == null && row.adjusted_market_value != null && row.assessment_level != null) {
                recalcAssessmentRow(row);
            } else {
                syncAssessmentToForm();
            }
        }
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const municipalityOptions = computed(() =>
    municipalities.value.map(m => ({
        ...m,
        label: m.province ? `${m.name}, ${m.province}` : m.name,
    }))
);

const filteredBarangays = computed(() => {
    const pool = form.municipality_id
        ? barangays.value.filter(b => b.municipality_id === form.municipality_id)
        : barangays.value;

    if (form.barangay_id) {
        const selected = barangays.value.find(b => b.id === form.barangay_id);
        if (selected && !pool.some(b => b.id === selected.id)) {
            return [selected, ...pool];
        }
    }

    return pool;
});

function loadBarangays() {
    if (!form.municipality_id) {
        form.barangay_id = null;
        return;
    }
    if (form.barangay_id) {
        const stillValid = barangays.value.some(
            b => b.id === form.barangay_id && b.municipality_id === form.municipality_id
        );
        if (!stillValid) form.barangay_id = null;
    }
}

function calcAssessed() {
    if (form.adjusted_market_value && form.assessment_level) {
        form.assessed_value = (form.adjusted_market_value * form.assessment_level) / 100;
    }
}

async function searchOwners(event) {
    const { data } = await axios.get('property-owners', { params: { search: event.query } });
    ownerSuggestions.value = data.data;
}

function onOwnerSelect(event) {
    const owner = event.value;
    selectedOwnerId.value = owner.id;
    Object.assign(ownerForm, {
        owner_name: owner.owner_name, co_owner_name: owner.co_owner_name,
        tin: owner.tin, sex: owner.sex, civil_status: owner.civil_status,
        address: owner.address, contact_number: owner.contact_number, email: owner.email,
    });
    form.owner_tin = owner.tin || '';
    form.owner_telephone = owner.contact_number || '';
}

function validateForm() {
    const issues = [];
    if (!form.td_number?.trim()) issues.push('TD Number is required');
    if (!ownerForm.owner_name?.trim()) issues.push('Owner name is required');
    if (!ownerForm.address?.trim() && !selectedOwnerId.value) {
        issues.push('Owner address is required (or select an existing owner)');
    }

    if (issues.length) {
        toast.error('Cannot save yet', issues.join('\n'));
        return false;
    }
    return true;
}

async function handleSubmit() {
    if (!validateForm()) return;

    saving.value = true;
    errors.value = {};
    const loadingId = toast.loading('Saving declaration…', 'Please wait');
    try {
        syncValuationToForm();
        syncAssessmentToForm();

        let ownerId = selectedOwnerId.value;
        if (!ownerId) {
            const ownerPayload = {
                ...ownerForm,
                address: ownerForm.address?.trim() || ownerForm.owner_name?.trim() || 'N/A',
            };
            const ownerRes = await axios.post('property-owners', ownerPayload);
            ownerId = ownerRes.data.id;
        }

        const payload = { ...form, owner_id: ownerId };
        if (form.effectivity_date) payload.effectivity_date = new Date(form.effectivity_date).toISOString().split('T')[0];
        if (form.date_issued) payload.date_issued = new Date(form.date_issued).toISOString().split('T')[0];
        if (form.title_date) payload.title_date = new Date(form.title_date).toISOString().split('T')[0];
        if (!payload.owner_address && ownerForm.address) payload.owner_address = ownerForm.address;

        if (isEdit.value) {
            await axios.put(`tax-declarations/${route.params.id}`, payload);
            toast.dismiss(loadingId);
            toast.success('Updated', 'Tax declaration updated.');
            router.push('/tax-declarations');
        } else {
            const res = await axios.post('tax-declarations', payload);
            toast.dismiss(loadingId);
            toast.success('Saved', 'Tax declaration registered successfully.');
            router.push(`/tax-declarations/${res.data.id}`);
        }
    } catch (err) {
        toast.dismiss(loadingId);
        errors.value = toast.apiError(err, 'Save failed') || {};
        console.error('Save declaration failed:', err?.response?.data || err);
    } finally { saving.value = false; }
}

onMounted(async () => {
    const [brRes, clsRes, munRes] = await Promise.all([
        axios.get('settings/barangays'),
        axios.get('settings/classifications'),
        axios.get('settings/municipalities'),
    ]);
    municipalities.value  = munRes.data ?? [];
    barangays.value       = brRes.data;
    classifications.value = clsRes.data;

    if (isEdit.value) {
        const { data } = await axios.get(`tax-declarations/${route.params.id}`);
        td.value = data;
        for (const key of Object.keys(form)) {
            if (data[key] !== undefined) form[key] = data[key];
        }
        if (data.owner) {
            selectedOwnerId.value = data.owner_id;
            Object.assign(ownerForm, data.owner);
            ownerSearch.value = data.owner;
        }
        loadValuationFromRecord(data);
        loadAssessmentFromRecord(data);
        if (data.rounded_assessed_value != null) {
            form.rounded_assessed_value = Number(data.rounded_assessed_value);
        }
    }
});
</script>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; transform: translateY(-4px); }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; max-height: 500px; }

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
    padding: 0.25rem 0.35rem;
    font-size: 0.65rem;
    background: transparent;
}
:deep(.cell-number .p-inputnumber) {
    width: 100%;
}
:deep(.p-autocomplete),
:deep(.p-autocomplete-input) {
    width: 100% !important;
    max-width: 100%;
    font-size: 0.75rem;
}
</style>
