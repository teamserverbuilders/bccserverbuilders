<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <RouterLink to="/field-appraisals">
                    <button class="h-8 w-8 inline-flex items-center justify-center rounded-md border border-[#1a3557] text-[#1a3557] hover:bg-[#1a3557] hover:text-white transition-colors">
                        <i class="pi pi-arrow-left text-sm"></i>
                    </button>
                </RouterLink>
                <div>
                    <h1 class="text-xl font-semibold text-[#1a3557] dark:text-slate-50">
                        {{ isEdit ? 'Edit Field Appraisal' : 'New Field Appraisal' }}
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ isEdit ? `Appraisal #${form.appraisal_no}` : 'Field Appraisal and Assessment Sheet [Land / Plant and Trees]' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:min-w-[220px]">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">Form Template</label>
                <Select
                    v-model="form.form_template"
                    :options="formTemplateOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                    placeholder="Select form"
                />
            </div>
        </div>

        <!-- Front / Back tabs -->
        <div v-if="form.form_template === 'form_1' || form.form_template === 'form_2'" class="inline-flex rounded-md border border-[#1a3557] overflow-hidden">
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

        <form @submit.prevent="handleSubmit">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Main form -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Dynamic sheet title (changes with Form Template + Front/Back) -->
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm text-center">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-[#1a3557] dark:text-slate-100">
                            {{ sheetTitle }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ sheetSubtitle }}
                        </p>
                    </div>

                    <!-- Basic Info (always visible) -->
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-4 flex items-center gap-2">
                            <i class="pi pi-clipboard text-[#b8860b]"></i> Appraisal Information
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">ARP No. <span class="text-red-500">*</span></label>
                                <InputText v-model="form.appraisal_no" class="w-full" required />
                                <small v-if="errors.appraisal_no" class="text-red-500 text-xs">{{ errors.appraisal_no[0] }}</small>
                            </div>
                            <div>
                                <label class="form-label">Link to Tax Declaration</label>
                                <AutoComplete v-model="tdSearch" :suggestions="tdSuggestions" optionLabel="td_number"
                                    @complete="searchTd" @item-select="onTdSelect" placeholder="Search TD#…"
                                    class="w-full" :forceSelection="false" dropdown />
                            </div>
                            <div>
                                <label class="form-label">Inspection Date</label>
                                <DatePicker v-model="form.inspection_date" class="w-full" dateFormat="mm/dd/yy" showIcon />
                            </div>
                            <div>
                                <label class="form-label">Inspection Location</label>
                                <InputText v-model="form.inspection_location" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- ════════════ FORM 1 LAYOUT ════════════ -->
                    <template v-if="form.form_template === 'form_1'">

                    <!-- ════════════ FRONT PAGE ════════════ -->
                    <template v-if="activePage === 'front'">

                        <!-- Owner / Administrator (FAAS) -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-medium bg-slate-50 dark:bg-slate-800 whitespace-nowrap w-36">Owner</td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0" colspan="3">
                                            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-300 dark:divide-slate-600">
                                                <InputText v-model="form2Identity.owner_name" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="Owner name" />
                                                <div class="flex items-stretch min-w-0">
                                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Address</span>
                                                    <InputText v-model="form2Identity.owner_address" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="Owner address" />
                                                </div>
                                                <div class="flex items-stretch min-w-0">
                                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">T.I.N.</span>
                                                    <InputText v-model="form2Identity.owner_tin" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="TIN" />
                                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-x border-slate-300 dark:border-slate-600 whitespace-nowrap">Tel No.</span>
                                                    <InputText v-model="form2Identity.owner_telephone" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="Tel" />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-medium bg-slate-50 dark:bg-slate-800 whitespace-nowrap leading-tight">Administrator</td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0" colspan="3">
                                            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-300 dark:divide-slate-600">
                                                <InputText v-model="form2Identity.administrator_name" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="Administrator / Occupant" />
                                                <div class="flex items-stretch min-w-0">
                                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">Address</span>
                                                    <InputText v-model="form2Identity.administrator_address" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="Address" />
                                                </div>
                                                <div class="flex items-stretch min-w-0">
                                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-r border-slate-300 dark:border-slate-600 whitespace-nowrap">T.I.N.</span>
                                                    <InputText v-model="form2Identity.administrator_tin" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="TIN" />
                                                    <span class="px-2 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-x border-slate-300 dark:border-slate-600 whitespace-nowrap">Tel No.</span>
                                                    <InputText v-model="form2Identity.administrator_telephone" class="!w-full !border-0 !rounded-none !shadow-none" placeholder="Tel" />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Property Location, Boundaries & Land Sketch -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                <!-- Left: Location + Boundaries -->
                                <div class="space-y-5">
                                    <div>
                                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                            Property Location
                                        </h3>
                                        <table class="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr v-for="row in locationFields" :key="row.key" class="border border-slate-300 dark:border-slate-600">
                                                    <td class="w-36 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium text-slate-600 dark:text-slate-300 border-r border-slate-300 dark:border-slate-600">
                                                        {{ row.label }}
                                                    </td>
                                                    <td class="p-0">
                                                        <InputText v-model="propertyLocation[row.key]" class="!w-full !border-0 !rounded-none !shadow-none" />
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                            Property Boundaries
                                        </h3>
                                        <table class="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr v-for="row in boundaryFields" :key="row.key" class="border border-slate-300 dark:border-slate-600">
                                                    <td class="w-20 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 font-medium text-slate-600 dark:text-slate-300 border-r border-slate-300 dark:border-slate-600">
                                                        {{ row.label }}
                                                    </td>
                                                    <td class="p-0">
                                                        <InputText v-model="propertyBoundaries[row.key]" class="!w-full !border-0 !rounded-none !shadow-none" />
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Right: Land Sketch -->
                                <div class="flex flex-col">
                                    <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                        Land Sketch
                                    </h3>
                                    <div
                                        class="flex-1 min-h-[260px] border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-md bg-slate-50 dark:bg-slate-800/40 relative overflow-hidden flex flex-col items-center justify-center"
                                        @dragover.prevent
                                        @drop.prevent="onSketchDrop"
                                    >
                                        <img
                                            v-if="sketchPreviewUrl"
                                            :src="sketchPreviewUrl"
                                            alt="Land sketch"
                                            class="absolute inset-0 w-full h-full object-contain p-2 bg-white dark:bg-slate-900"
                                        />
                                        <div v-else class="text-center px-4 py-8 z-10">
                                            <i class="pi pi-image text-3xl text-slate-300 dark:text-slate-600 mb-2"></i>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Upload or drop a sketch image</p>
                                            <p class="text-[10px] text-slate-400 mt-1">JPG, PNG · max 10MB</p>
                                        </div>
                                        <div class="absolute bottom-2 right-2 flex gap-1 z-10">
                                            <button type="button"
                                                class="h-8 px-3 rounded-md bg-[#1a3557] text-white text-xs font-medium hover:bg-[#1e4880] inline-flex items-center gap-1.5"
                                                :disabled="uploadingSketch"
                                                @click="$refs.sketchInput.click()">
                                                <span v-if="uploadingSketch" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                                <i v-else class="pi pi-upload text-[10px]"></i>
                                                {{ sketchPreviewUrl ? 'Replace' : 'Upload' }}
                                            </button>
                                            <button v-if="sketchPreviewUrl" type="button"
                                                class="h-8 w-8 rounded-md border border-red-300 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 inline-flex items-center justify-center"
                                                :disabled="uploadingSketch"
                                                title="Remove sketch"
                                                @click="removeSketch">
                                                <i class="pi pi-trash text-xs"></i>
                                            </button>
                                        </div>
                                        <input ref="sketchInput" type="file" accept="image/*" class="hidden" @change="onSketchSelect" />
                                    </div>
                                    <p v-if="!isEdit && pendingSketchFile" class="text-[10px] text-amber-600 mt-1.5">
                                        Sketch selected — it will upload when you save the appraisal.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- LAND APPRAISAL -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
                            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                Land Appraisal
                            </h3>
                            <table class="w-full min-w-[720px] border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800">
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[16%]">Classification Kind</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[10%]">Sub Class</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[22%]">Actual Use</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[14%]">Area<br><span class="font-normal">(Ha or Sq. M)</span></th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[14%]">Unit Value</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[16%]">Base Market Value</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1 py-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in landRows" :key="'land-' + i">
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputText v-model="row.classification_kind" class="cell-input" placeholder="e.g. Agricultural" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputText v-model="row.sub_class" class="cell-input text-center" placeholder="e.g. 3rd" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputText v-model="row.actual_use" class="cell-input" placeholder="e.g. Riceland, Irrigated" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputNumber v-model="row.area" class="cell-number" inputClass="text-right" :minFractionDigits="2" :maxFractionDigits="6" @update:modelValue="recalcLandRow(row)" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputNumber v-model="row.unit_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" @update:modelValue="recalcLandRow(row)" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputNumber v-model="row.base_market_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 text-center">
                                            <button type="button" class="text-slate-400 hover:text-red-500 p-1" :disabled="landRows.length <= 1" @click="removeLandRow(i)">
                                                <i class="pi pi-times text-[10px]"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                                        <td colspan="3" class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center text-slate-700 dark:text-slate-200">Total:</td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right text-slate-800 dark:text-slate-100">{{ formatNum(landTotals.area, 6) }}</td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2"></td>
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right text-slate-800 dark:text-slate-100">{{ formatNum(landTotals.base_market_value, 2) }}</td>
                                        <td class="border border-slate-300 dark:border-slate-600"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="mt-2 text-xs text-[#1a3557] dark:text-blue-400 hover:underline font-medium inline-flex items-center gap-1" @click="addLandRow">
                                <i class="pi pi-plus text-[10px]"></i> Add land row
                            </button>
                        </div>

                        <!-- PLANTS AND TREES APPRAISAL -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
                            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 uppercase tracking-wide">
                                Plants and Trees Appraisal
                            </h3>
                            <table class="w-full min-w-[860px] border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800">
                                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[20%]">Kind of Plants And/or Trees</th>
                                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[9%]">Prod Class</th>
                                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[12%]">Area Planted<br><span class="font-normal">(Hectares)</span></th>
                                        <th colspan="3" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200">Number of Plants/Trees</th>
                                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[12%]">Unit Value</th>
                                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold text-slate-700 dark:text-slate-200 w-[14%]">Base Market Value</th>
                                        <th rowspan="2" class="border border-slate-300 dark:border-slate-600 px-1 py-2 w-8"></th>
                                    </tr>
                                    <tr class="bg-slate-50 dark:bg-slate-800">
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-1.5 font-semibold text-slate-700 dark:text-slate-200 w-[8%]">Non FB</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-1.5 font-semibold text-slate-700 dark:text-slate-200 w-[8%]">FB</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-1.5 font-semibold text-slate-700 dark:text-slate-200 w-[8%]">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in plantRows" :key="'plant-' + i">
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="row.kind" class="cell-input" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputText v-model="row.prod_class" class="cell-input text-center" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.area_planted" class="cell-number" inputClass="text-right" :minFractionDigits="2" :maxFractionDigits="6" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.non_fb" class="cell-number" inputClass="text-right" :min="0" @update:modelValue="recalcPlantRow(row)" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.fb" class="cell-number" inputClass="text-right" :min="0" @update:modelValue="recalcPlantRow(row)" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.total" class="cell-number" inputClass="text-right" :min="0" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.unit_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" @update:modelValue="recalcPlantRow(row)" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0"><InputNumber v-model="row.base_market_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" /></td>
                                        <td class="border border-slate-300 dark:border-slate-600 text-center">
                                            <button type="button" class="text-slate-400 hover:text-red-500 p-1" :disabled="plantRows.length <= 1" @click="removePlantRow(i)">
                                                <i class="pi pi-times text-[10px]"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-slate-700 dark:text-slate-200">Total:</td>
                                        <td class="border border-slate-300 dark:border-slate-600"></td>
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
                            <button type="button" class="mt-2 text-xs text-[#1a3557] dark:text-blue-400 hover:underline font-medium inline-flex items-center gap-1" @click="addPlantRow">
                                <i class="pi pi-plus text-[10px]"></i> Add plants/trees row
                            </button>
                        </div>
                    </template>

                    <!-- ════════════ BACK PAGE ════════════ -->
                    <template v-else>

                        <!-- Value Adjustment + Property Assessment -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

                            <!-- VALUE ADJUSTMENT FACTORS -->
                            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                                <h3 class="text-xs font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                    Value Adjustment Factors for Agricultural Lands
                                </h3>
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
                                            <td class="px-2 py-1 border-r border-slate-300 dark:border-slate-600">[ a ] Along road / no road frontage</td>
                                            <td class="p-0 w-28">
                                                <InputNumber v-model="adjustments.along_road" class="cell-number" inputClass="text-right" @update:modelValue="recalcAdjustments" />
                                            </td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1 border-r border-slate-300 dark:border-slate-600">[ b ] kms. to all weather road</td>
                                            <td class="p-0">
                                                <InputNumber v-model="adjustments.kms_weather_road" class="cell-number" inputClass="text-right" @update:modelValue="recalcAdjustments" />
                                            </td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600">
                                            <td class="px-2 py-1 border-r border-slate-300 dark:border-slate-600">[ c ] kms. to market (Pob.)</td>
                                            <td class="p-0">
                                                <InputNumber v-model="adjustments.kms_to_market" class="cell-number" inputClass="text-right" @update:modelValue="recalcAdjustments" />
                                            </td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600 font-semibold">
                                            <td class="px-2 py-1.5 border-r border-slate-300 dark:border-slate-600">Total Adjustments</td>
                                            <td class="px-2 py-1.5 text-right">{{ adjustments.total_adjustments }}%</td>
                                        </tr>
                                        <tr class="border border-slate-300 dark:border-slate-600 font-bold bg-slate-50 dark:bg-slate-800/60">
                                            <td class="px-2 py-2 border-r border-slate-300 dark:border-slate-600 uppercase">Total Percentage Adjustment</td>
                                            <td class="px-2 py-2 text-right">{{ adjustments.total_percentage }}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="mt-2 text-xs text-[#1a3557] dark:text-blue-400 hover:underline font-medium" @click="applyAdjustmentToAssessment">
                                    Apply % to land total → Agricultural adjusted MV
                                </button>
                            </div>

                            <!-- PROPERTY ASSESSMENT -->
                            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm overflow-x-auto">
                                <h3 class="text-xs font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                    Property Assessment
                                </h3>
                                <table class="w-full min-w-[420px] border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 dark:bg-slate-800">
                                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Classification (Kind)</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Adjusted Market Value</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Assm't Level (%)</th>
                                            <th class="border border-slate-300 dark:border-slate-600 px-1.5 py-2 font-semibold">Assessed Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in assessmentRows" :key="row.classification">
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-1 font-medium">{{ row.classification }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 p-0">
                                                <InputNumber v-model="row.adjusted_market_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" @update:modelValue="recalcAssessed(row)" />
                                            </td>
                                            <td class="border border-slate-300 dark:border-slate-600 p-0">
                                                <InputNumber v-model="row.assessment_level" class="cell-number" inputClass="text-right" @update:modelValue="recalcAssessed(row)" />
                                            </td>
                                            <td class="border border-slate-300 dark:border-slate-600 p-0">
                                                <InputNumber v-model="row.assessed_value" class="cell-number" inputClass="text-right" :minFractionDigits="2" />
                                            </td>
                                        </tr>
                                        <tr class="bg-slate-50 dark:bg-slate-800/60 font-semibold">
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center">Total</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-right">{{ formatNum(assessmentTotals.adjusted_market_value, 2) }}</td>
                                            <td class="border border-slate-300 dark:border-slate-600 px-2 py-2 text-center text-[10px] font-normal leading-tight">
                                                Rounded<br>Assessed Value
                                            </td>
                                            <td class="border border-slate-300 dark:border-slate-600 p-0">
                                                <InputNumber v-model="roundedAssessedValue" class="cell-number" inputClass="text-right font-semibold" :minFractionDigits="2" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="mt-2 text-xs text-[#1a3557] dark:text-blue-400 hover:underline font-medium" @click="syncComputedValues">
                                    Sync totals to computed market / assessed value
                                </button>
                            </div>
                        </div>

                        <!-- Previous Owner / Taxability -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr>
                                        <td class="border border-slate-300 dark:border-slate-600 w-1/2 p-0">
                                            <div class="flex items-center">
                                                <span class="px-3 py-2 font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap border-r border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800">Previous Owner:</span>
                                                <InputText v-model="backMeta.previous_owner" class="!w-full !border-0 !rounded-none !shadow-none" />
                                            </div>
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 w-1/2 p-0">
                                            <div class="flex items-center">
                                                <span class="px-3 py-2 font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap border-r border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800">Taxability:</span>
                                                <Select v-model="backMeta.taxability" :options="['Taxable','Exempt']" class="!w-full !border-0" />
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <div class="flex items-center">
                                                <span class="px-3 py-2 font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap border-r border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800">Previous Assessed Value:</span>
                                                <InputNumber v-model="backMeta.previous_assessed_value" class="!w-full" inputClass="!border-0 !rounded-none !shadow-none" mode="currency" currency="PHP" locale="en-PH" />
                                            </div>
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <div class="flex items-center gap-2 px-2">
                                                <span class="py-2 font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap bg-slate-50 dark:bg-slate-800 px-2 -ml-2 border-r border-slate-300 dark:border-slate-600">Effectivity:</span>
                                                <InputText v-model="backMeta.effectivity_year" class="!w-24 !border-0 !rounded-none !shadow-none" placeholder="Year" />
                                                <Select v-model="backMeta.effectivity_quarter" :options="['1st Qtr','2nd Qtr','3rd Qtr','4th Qtr']" class="!flex-1 !border-0" placeholder="Quarter" showClear />
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Signatures -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div v-for="sig in signatureFields" :key="sig.key" class="border border-slate-300 dark:border-slate-600 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[#1a3557] dark:text-slate-200 mb-3">{{ sig.label }}</p>
                                    <div class="space-y-2">
                                        <div>
                                            <label class="form-label">Name</label>
                                            <InputText v-model="signatures[sig.key].name" class="w-full" />
                                        </div>
                                        <div>
                                            <label class="form-label">Title / Position</label>
                                            <InputText v-model="signatures[sig.key].title" class="w-full" :placeholder="sig.placeholder" />
                                        </div>
                                        <div>
                                            <label class="form-label">Date</label>
                                            <DatePicker v-model="signatures[sig.key].date" class="w-full" dateFormat="mm/dd/yy" showIcon />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Memoranda -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 uppercase tracking-wide">Memoranda</h3>
                            <Textarea v-model="backMeta.memoranda" class="w-full" rows="4" placeholder="e.g. Revised Pursuant to Sec 219 of R.A. 7160" />
                        </div>

                        <!-- References and Posting Summary -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
                            <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 text-center uppercase tracking-wide">
                                References and Posting Summary
                            </h3>
                            <table class="w-full min-w-[700px] border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800">
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold w-[14%]">Reference</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold w-[22%]">Previous Record</th>
                                        <th colspan="2" class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold">Posting Report</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-2 font-semibold w-[18%]">Post Inspection</th>
                                    </tr>
                                    <tr class="bg-slate-50 dark:bg-slate-800">
                                        <th class="border border-slate-300 dark:border-slate-600"></th>
                                        <th class="border border-slate-300 dark:border-slate-600"></th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-1 font-semibold w-[16%]">Date</th>
                                        <th class="border border-slate-300 dark:border-slate-600 px-2 py-1 font-semibold w-[14%]">Clerk Initial</th>
                                        <th class="border border-slate-300 dark:border-slate-600"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in referenceRows" :key="row.key">
                                        <td class="border border-slate-300 dark:border-slate-600 px-2 py-1.5 font-medium whitespace-nowrap">{{ row.label }}</td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputText v-model="references[row.key]" class="cell-input" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <DatePicker v-model="posting[row.key].date" class="w-full" dateFormat="mm/dd/yy" showIcon inputClass="!border-0 !rounded-none !shadow-none !text-xs" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputText v-model="posting[row.key].clerk_initial" class="cell-input text-center" />
                                        </td>
                                        <td class="border border-slate-300 dark:border-slate-600 p-0">
                                            <InputText v-model="posting[row.key].post_inspection" class="cell-input" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Remarks -->
                        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                            <label class="form-label">Remarks / Findings</label>
                            <Textarea v-model="form.remarks" class="w-full mt-1" rows="3" placeholder="Any observations during inspection…" />
                        </div>
                    </template>

                    </template>

                    <!-- ════════════ FORM 2 — Sample FAAS ════════════ -->
                    <FieldAppraisalForm2Sheet
                        v-else-if="form.form_template === 'form_2'"
                        :active-page="activePage"
                        :identity="form2Identity"
                        :property-location="propertyLocation"
                        :property-boundaries="propertyBoundaries"
                        :land-rows="landRows"
                        :plant-rows="plantRows"
                        :land-totals="landTotals"
                        :plant-totals="plantTotals"
                        :adjustments="adjustments"
                        :assessment-rows="assessmentRows"
                        :assessment-totals="assessmentTotals"
                        :back-meta="backMeta"
                        :signatures="signatures"
                        :conforme="conforme"
                        :references="references"
                        :posting="posting"
                        v-model:remarks="form.remarks"
                        :sketch-preview-url="sketchPreviewUrl"
                        :uploading-sketch="uploadingSketch"
                        @add-land="addLandRow"
                        @remove-land="removeLandRow"
                        @recalc-land="recalcLandRow"
                        @add-plant="addPlantRow"
                        @remove-plant="removePlantRow"
                        @recalc-plant="recalcPlantRow"
                        @recalc-adj="recalcAdjustments"
                        @apply-adj="applyAdjustmentToAssessment"
                        @recalc-assessed="recalcAssessed"
                        @sync-computed="syncComputedValues"
                        @sketch-select="onSketchSelect"
                        @sketch-drop="onSketchDrop"
                        @sketch-remove="removeSketch"
                    />
                </div>

                <!-- Right sidebar -->
                <div class="space-y-5">
                    <!-- OCR Tool -->
                    <div v-if="!isEdit" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3 flex items-center gap-2">
                            <i class="pi pi-camera text-violet-500"></i> Tools
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
                                <p class="text-xs text-zinc-500">Upload one or more scanned FAAS pages (front/back). OCR merges extracted fields automatically.</p>

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

                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-[#1a3557] dark:text-slate-200 mb-3">Status</h3>
                        <Select v-model="form.status" :options="statusOpts" optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4 space-y-2 shadow-sm">
                        <button type="submit" :disabled="saving"
                            class="w-full h-9 rounded-md bg-[#1a3557] hover:bg-[#1e4880] text-white text-sm font-medium disabled:opacity-50 flex items-center justify-center gap-2 transition-colors shadow-sm">
                            <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <i v-else class="pi pi-save text-sm"></i>
                            {{ isEdit ? 'Update Appraisal' : 'Save Appraisal' }}
                        </button>
                        <RouterLink to="/field-appraisals">
                            <button type="button" class="w-full h-9 rounded-md border border-[#1a3557] text-[#1a3557] dark:border-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                Cancel
                            </button>
                        </RouterLink>
                    </div>
                </div>
            </div>
        </form>

        <!-- Review & Match Modal -->
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
                                <InputText v-model="reviewFields[key]" class="w-full text-sm" size="small" />
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

        <!-- Webcam Capture Modal -->
        <Dialog v-model:visible="showWebcam" modal :closable="true" :dismissableMask="false"
            :style="{ width: '95vw', maxWidth: '1200px' }" :contentStyle="{ padding: 0 }" @hide="closeWebcam">
            <template #header>
                <div class="flex items-center gap-3 w-full">
                    <div class="w-8 h-8 rounded-md bg-violet-600 flex items-center justify-center">
                        <i class="pi pi-camera text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[#1a3557] dark:text-slate-100">Scan with Webcam</h3>
                        <p class="text-xs text-slate-500">Center the FAAS document (portrait) in frame, then capture. It will be scanned automatically.</p>
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
                                    <InputText v-model="reviewFields[key]" class="w-full text-sm" size="small" />
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
import FieldAppraisalForm2Sheet from '@/pages/field-appraisals/FieldAppraisalForm2Sheet.vue';
import axios from 'axios';

const route  = useRoute();
const router = useRouter();
const toast  = useToast();

const isEdit = computed(() => !!route.params.id);
const saving = ref(false);
const errors = ref({});
const activePage = ref('front');

const sheetTitle = computed(() => {
    if (form.form_template === 'form_2') {
        return 'Field Appraisal and Assessment Sheet (FAAS)';
    }
    return 'Field Appraisal and Assessment Sheet';
});

const sheetSubtitle = computed(() => {
    const page = activePage.value === 'back' ? 'Back Page' : 'Front Page';
    if (form.form_template === 'form_2') {
        return `(Land / Plants and Trees) — ${page}`;
    }
    return `[Land / Plant and Trees] — ${page}`;
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
        // shown in the viewfinder, so the captured file matches a FAAS document's shape.
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

const reviewLabelMap = {
    document_type: 'Document Type',
    td_number: 'TD / ARP No.', arp_number: 'A.R.P. No.', arp_no: 'A.R.P. No.',
    property_identification_no: 'P.I.N.', pin: 'P.I.N.', update_code: 'Update Code',
    owner_name: 'Owner', address: 'Owner Address', owner_address: 'Owner Address',
    owner_tin: 'Owner T.I.N.', tin: 'Owner T.I.N.', owner_telephone: 'Owner Tel No.', telephone: 'Owner Tel No.',
    administrator: 'Administrator', administrator_name: 'Administrator', administrator_address: 'Administrator Address',
    administrator_tin: 'Administrator T.I.N.', administrator_telephone: 'Administrator Tel No.',
    location_street: 'No./Street', barangay: 'Barangay', municipality: 'Municipality', province: 'Province',
    survey_no: 'Survey No.', lot_no: 'Lot No.', cad_pls_lot_no: 'Cad/PLS Lot No.',
    oct_tct_cloa: 'OCT/TCT/CLOA', oct_tct_kot_no: 'OCT/TCT/KOT No.',
    boundary_north: 'Boundary NE/North', boundary_south: 'Boundary SW/South',
    boundary_east: 'Boundary SE/East', boundary_west: 'Boundary NW/West',
    classification: 'Classification', sub_class: 'Sub-Class', actual_use: 'Actual Use',
    area: 'Area', unit_value: 'Unit Value', base_market_value: 'Base Market Value',
    adjusted_market_value: 'Adjusted Market Value',
    assessment_level: 'Assessment Level (%)', assessed_value: 'Assessed Value',
    rounded_assessed_value: 'Rounded Assessed Value',
    plant_kind: 'Plant/Tree Kind', plant_prod_class: 'Plant Prod Class',
    plant_area: 'Plant Area', plant_non_fb: 'Non Fruit Bearing', plant_fb: 'Fruit Bearing',
    plant_total: 'Plant Total', plant_unit_value: 'Plant Unit Value',
    plant_base_market_value: 'Plant Base Market Value',
    adj_along_road: 'Adj. Along Road %', adj_kms_weather_road: 'Adj. Weather Road %',
    adj_kms_to_market: 'Adj. To Market %', adj_total_adjustments: 'Total Adjustments %',
    adj_total_percentage: 'Total % Adjustment',
    taxability: 'Taxable/Exempt',
    effectivity: 'Effectivity / Tax Effectivity',
    effectivity_quarter: 'Effectivity Quarter',
    effectivity_year: 'Effectivity Year',
    tax_effectivity_year: 'Tax Effectivity Year',
    tax_effectivity_quarter: 'Tax Effectivity Quarter',
    approved_by: 'Approved By', assessed_by: 'Assessed By',
    previous_owner: 'Previous Owner', previous_av: 'Previous A.V.', memoranda: 'Memoranda',
    conforme_name: 'Conforme', conforme_ctc_no: 'CTC No.', conforme_issued_at: 'Issued At',
};

function formatReviewLabel(key) {
    return reviewLabelMap[key] || String(key).replace(/_/g, ' ');
}

function applyReviewedFields() {
    ocr.result = JSON.parse(JSON.stringify(reviewFields));
    applyOcrFields();
    showReviewModal.value = false;
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
        const pos = s.lastIndexOf('.');
        s = s.slice(0, pos).replace(/\./g, '') + '.' + s.slice(pos + 1);
    } else {
        s = s.replace(/[^0-9.\-]/g, '');
    }
    const num = parseFloat(s);
    return Number.isNaN(num) ? null : num;
}

/** Reject OCR memoranda that is actually the Reference & Posting table. */
function sanitizeOcrMemoranda(val) {
    if (val == null) return null;
    let text = String(val).trim();
    if (!text) return null;
    // Prefer the common FAAS memo line if present inside a noisy capture
    const pursuant = text.match(/Revised\s+Pursuant\s+to\s+Sec\.?\s*\d+[A-Za-z]?\s+of\s+R\.?A\.?\s*\d+/i);
    if (pursuant) return pursuant[0].replace(/\s+/g, ' ').trim();
    if (/REFERENCES?\s*&?\s*(?:AND\s+)?POSTING|REFERENCES?\s+AND|Posting\s+Report|Previous\s+Record|Clerk\s+Initial|A\.?R\.?\s*Page/i.test(text)
        && !/pursuant|revised|sec\.?\s*\d|R\.?A\.?\s*\d/i.test(text)) {
        return null;
    }
    text = text
        .replace(/\bREFERENCES?\s*&?\s*(?:AND\s+)?(?:POSTING\s+SUMMARY)?\b[\s\S]*$/i, '')
        .replace(/\bREFERENCES?\s+AND\b[\s\S]*$/i, '')
        .replace(/\bPosting\s+Report\b[\s\S]*$/i, '')
        .trim();
    return text.length > 2 ? text : null;
}

/** Strip Taxability / border artifacts / label bleed from person-name OCR text. */
function sanitizeOcrPersonName(val) {
    if (val == null) return null;
    let text = String(val).replace(/[\|\/\\_]+/g, ' ').replace(/\s+/g, ' ').trim();
    if (!text) return null;
    text = text
        .replace(/\s*(?:Taxab[a-z]{0,10}|Taxable|Exempt|Previous\s+(?:Assessed\s+Value|A\.?V\.?)|Tax\s+Effectivity|Address|T\.?I\.?N\.?|Telephone|Tel\.?\s*No\.?).*$/i, '')
        .replace(/\b(?:Taxab[a-z]{0,10}|Taxable|Exempt)\b/gi, '')
        .replace(/\s+(TIN|Telephone|Tel|Address|Administrator|Beneficial)\b.*$/i, '')
        .replace(/\s+/g, ' ')
        .replace(/[\s|\/\\_,.-]+$/g, '')
        .trim();
    return text.length > 1 ? text : null;
}

/** @deprecated use sanitizeOcrPersonName */
function sanitizeOcrPreviousOwner(val) {
    return sanitizeOcrPersonName(val);
}

function normalizeFaOcr(raw) {
    const r = { ...(raw || {}) };
    if (!r.arp_no && (r.arp_number || r.td_number)) r.arp_no = r.arp_number || r.td_number;
    if (!r.pin && r.property_identification_no) r.pin = r.property_identification_no;
    if (!r.oct_tct_kot_no && r.oct_tct_cloa) r.oct_tct_kot_no = r.oct_tct_cloa;
    if (!r.cad_pls_lot_no && r.lot_no) r.cad_pls_lot_no = r.lot_no;
    if (!r.owner_address && r.address) r.owner_address = r.address;
    if (!r.administrator_name && r.administrator) r.administrator_name = r.administrator;
    return r;
}

function applyOcrFields() {
    if (!ocr.result) return;
    const r = normalizeFaOcr(ocr.result);
    const highlights = [];

    const setStr = (obj, key, val) => {
        if (val == null || String(val).trim() === '') return;
        obj[key] = String(val).trim();
        highlights.push(key);
    };

    // Appraisal no. from ARP/TD when empty
    if (!form.appraisal_no && (r.arp_no || r.td_number)) {
        form.appraisal_no = String(r.arp_no || r.td_number).trim();
        highlights.push('appraisal_no');
    }

    // Form 2 identity / header (also useful when switching templates)
    const updateCode = (r.update_code || '').trim();
    if (updateCode.length >= 2 && !/^A$/i.test(updateCode) && !/^(ARP|PIN|OCT|TCT)$/i.test(updateCode)) {
        setStr(form2Identity, 'update_code', updateCode);
    }
    setStr(form2Identity, 'arp_no', r.arp_no || r.arp_number || r.td_number);
    setStr(form2Identity, 'pin', r.pin || r.property_identification_no);
    setStr(form2Identity, 'oct_tct_kot_no', r.oct_tct_kot_no || r.oct_tct_cloa);
    setStr(form2Identity, 'survey_no', r.survey_no || r.survey_number);
    setStr(form2Identity, 'cad_pls_lot_no', r.cad_pls_lot_no || r.lot_no || r.lot_number);
    setStr(form2Identity, 'owner_name', sanitizeOcrPersonName(r.owner_name));
    const ownerAddr = (r.owner_address || r.address || '').trim();
    if (ownerAddr && !/^(PROPERTY|LOCATION|PROPERTY\s+LOCATION)$/i.test(ownerAddr)) {
        setStr(form2Identity, 'owner_address', ownerAddr);
    }
    setStr(form2Identity, 'owner_tin', r.owner_tin || r.tin);
    setStr(form2Identity, 'owner_telephone', r.owner_telephone || r.telephone);
    setStr(form2Identity, 'administrator_name', sanitizeOcrPersonName(r.administrator_name || r.administrator));
    const adminAddr = (r.administrator_address || '').trim();
    if (adminAddr && !/^(PROPERTY|LOCATION|PROPERTY\s+LOCATION)$/i.test(adminAddr)) {
        setStr(form2Identity, 'administrator_address', adminAddr);
    }
    setStr(form2Identity, 'administrator_tin', r.administrator_tin);
    setStr(form2Identity, 'administrator_telephone', r.administrator_telephone);

    // Location (Form 1 + Form 2)
    setStr(propertyLocation, 'street', r.location_street || r.property_street || r.street);
    setStr(propertyLocation, 'barangay', r.barangay);
    setStr(propertyLocation, 'municipality', r.municipality);
    {
        let prov = String(r.province || '').replace(/\s+/g, ' ').trim();
        const fixes = {
            CAMARI: 'CAMARINES SUR',
            CAMARINE: 'CAMARINES SUR',
            'CAMARINES S': 'CAMARINES SUR',
            'CAMARINES SU': 'CAMARINES SUR',
            'CAMARINES N': 'CAMARINES NORTE',
            'CAMARINES NO': 'CAMARINES NORTE',
            'CAMARINES NOR': 'CAMARINES NORTE',
            'CAMARINES NORT': 'CAMARINES NORTE',
        };
        const key = prov.toUpperCase();
        if (fixes[key]) prov = fixes[key];
        setStr(propertyLocation, 'province', prov || null);
    }
    // If municipality still empty, parse from owner address "Brgy, Mun, Province"
    if (!propertyLocation.municipality) {
        const src = form2Identity.owner_address || ownerAddr || '';
        const parts = src.split(',').map((p) => p.trim()).filter(Boolean);
        if (parts.length >= 3) {
            if (!propertyLocation.barangay) setStr(propertyLocation, 'barangay', parts[0]);
            setStr(propertyLocation, 'municipality', parts[1]);
            if (!propertyLocation.province) setStr(propertyLocation, 'province', parts.slice(2).join(', '));
        } else if (parts.length === 2 && !propertyLocation.barangay) {
            setStr(propertyLocation, 'barangay', parts[0]);
            setStr(propertyLocation, 'municipality', parts[1]);
        }
    }

    // Boundaries — N/E/S/W and NE/SE/SW/NW aliases
    setStr(propertyBoundaries, 'north', r.boundary_north || r.boundary_ne || r.north || r.ne);
    setStr(propertyBoundaries, 'east', r.boundary_east || r.boundary_se || r.east || r.se);
    setStr(propertyBoundaries, 'south', r.boundary_south || r.boundary_sw || r.south || r.sw);
    setStr(propertyBoundaries, 'west', r.boundary_west || r.boundary_nw || r.west || r.nw);

    // Land appraisal row (both templates)
    const area = parseOcrNumber(r.area || r.land_area);
    const bmv = parseOcrNumber(r.base_market_value);
    const unitVal = parseOcrNumber(r.unit_value);
    const actualUse = (r.actual_use || '').trim();
    const classification = (r.classification || '').trim();
    const subClass = (r.sub_class || '').trim();
    if (classification || actualUse || area != null || bmv != null || unitVal != null || subClass) {
        landRows.value[0] = {
            ...emptyLandRow(),
            classification_kind: classification || landRows.value[0]?.classification_kind || '',
            sub_class: subClass || landRows.value[0]?.sub_class || '',
            actual_use: actualUse || landRows.value[0]?.actual_use || '',
            area: area ?? landRows.value[0]?.area ?? null,
            unit_value: unitVal ?? landRows.value[0]?.unit_value ?? null,
            base_market_value: bmv ?? landRows.value[0]?.base_market_value ?? null,
        };
        if (landRows.value[0].unit_value == null && area != null && bmv != null && area > 0) {
            landRows.value[0].unit_value = Math.round((bmv / area) * 100) / 100;
        } else if (landRows.value[0].base_market_value == null && area != null && unitVal != null) {
            landRows.value[0].base_market_value = Math.round(area * unitVal * 100) / 100;
        }
        highlights.push('land_appraisal');
    }

    // Plants / trees row
    const plantKind = (r.plant_kind || '').trim();
    if (plantKind || r.plant_area || r.plant_base_market_value) {
        plantRows.value[0] = {
            ...emptyPlantRow(),
            kind: plantKind || plantRows.value[0]?.kind || '',
            prod_class: (r.plant_prod_class || '').trim() || plantRows.value[0]?.prod_class || '',
            area_planted: parseOcrNumber(r.plant_area) ?? plantRows.value[0]?.area_planted ?? null,
            non_fb: parseOcrNumber(r.plant_non_fb) ?? plantRows.value[0]?.non_fb ?? null,
            fb: parseOcrNumber(r.plant_fb) ?? plantRows.value[0]?.fb ?? null,
            total: parseOcrNumber(r.plant_total) ?? plantRows.value[0]?.total ?? null,
            unit_value: parseOcrNumber(r.plant_unit_value) ?? plantRows.value[0]?.unit_value ?? null,
            base_market_value: parseOcrNumber(r.plant_base_market_value) ?? plantRows.value[0]?.base_market_value ?? null,
        };
        if (plantRows.value[0].total == null) {
            const n = Number(plantRows.value[0].non_fb) || 0;
            const f = Number(plantRows.value[0].fb) || 0;
            if (n || f) plantRows.value[0].total = n + f;
        }
        highlights.push('plant_appraisal');
    }

    // Value adjustments (Form 1 back page — Agricultural Lands)
    const along = parseOcrNumber(r.adj_along_road);
    const weather = parseOcrNumber(r.adj_kms_weather_road);
    const market = parseOcrNumber(r.adj_kms_to_market);
    let adjApplied = false;
    if (along != null) { adjustments.along_road = along; adjApplied = true; }
    if (weather != null) { adjustments.kms_weather_road = weather; adjApplied = true; }
    if (market != null) { adjustments.kms_to_market = market; adjApplied = true; }
    if (adjApplied) {
        recalcAdjustments();
        highlights.push('adjustments');
    }
    const totAdj = parseOcrNumber(r.adj_total_adjustments);
    const totPct = parseOcrNumber(r.adj_total_percentage);
    // Prefer recomputed totals from a/b/c; fall back to OCR totals when factors missing
    if (!adjApplied) {
        if (totAdj != null) adjustments.total_adjustments = totAdj;
        if (totPct != null) adjustments.total_percentage = totPct;
        if (totAdj != null || totPct != null) highlights.push('adjustments');
    } else if (totPct != null && Math.abs(totPct - adjustments.total_percentage) <= 1) {
        // Keep OCR total when it matches recomputed (±1 for rounding)
        adjustments.total_percentage = totPct;
    }

    // Assessment row for matching classification
    const adjMv = parseOcrNumber(r.adjusted_market_value);
    const assmt = parseOcrNumber(r.assessment_level);
    const av = parseOcrNumber(r.assessed_value);
    const rounded = parseOcrNumber(r.rounded_assessed_value);
    if (adjMv != null || assmt != null || av != null || classification) {
        let kindName = classification || 'Agricultural';
        if (/timber|forest/i.test(kindName)) kindName = 'Timber/Forest';
        if (/plant/i.test(kindName)) kindName = 'Plant and Trees';
        const row = assessmentRows.value.find((row) =>
            row.classification.toLowerCase() === String(kindName).toLowerCase()
        ) || assessmentRows.value.find((row) => row.classification === 'Agricultural')
          || assessmentRows.value[0];
        if (row) {
            if (adjMv != null) row.adjusted_market_value = adjMv;
            if (assmt != null) row.assessment_level = assmt;
            if (av != null) row.assessed_value = av;
            else if (row.adjusted_market_value != null && row.assessment_level != null) {
                recalcAssessed(row);
            }
            highlights.push('assessment');
        }
        if (av != null) form.computed_assessed_value = av;
        if (rounded != null) roundedAssessedValue.value = rounded;
        else if (av != null) roundedAssessedValue.value = Math.round(av / 10) * 10;
        if (adjMv != null) form.computed_market_value = adjMv;
        else if (bmv != null) form.computed_market_value = bmv;
    }

    // Back meta
    const prevOwner = sanitizeOcrPersonName(r.previous_owner);
    setStr(backMeta, 'previous_owner', prevOwner);
    const prevAv = parseOcrNumber(r.previous_av);
    if (prevAv != null) { backMeta.previous_assessed_value = prevAv; highlights.push('previous_av'); }
    if (r.taxability) {
        const t = String(r.taxability).toLowerCase();
        backMeta.taxability = t.includes('exempt') ? 'Exempt' : 'Taxable';
        highlights.push('taxability');
    }
    // Effectivity (Form 1) / Tax Effectivity (Form 2)
    let effYear = r.effectivity_year || r.tax_effectivity_year || '';
    let effQtr = r.effectivity_quarter || r.tax_effectivity_quarter || '';
    const combinedEff = String(r.effectivity || r.tax_effectivity || '').trim();
    if ((!effYear || !effQtr) && combinedEff) {
        let cm = combinedEff.match(/((?:19|20)\d{2})\s*[,\/\-]?\s*([1-4]|I|l)\s*(?:st|nd|rd|th)?/i);
        if (cm) {
            if (!effYear) effYear = cm[1];
            if (!effQtr) effQtr = cm[2];
        } else {
            cm = combinedEff.match(/([1-4]|I|l)\s*(?:st|nd|rd|th)?\s*(?:qtr|quarter)?\s*[,\/\-]?\s*((?:19|20)\d{2})/i);
            if (cm) {
                if (!effQtr) effQtr = cm[1];
                if (!effYear) effYear = cm[2];
            }
        }
    }
    const effYearRaw = String(effYear || '').replace(/\D/g, '');
    if (effYearRaw.length === 4) {
        const y = parseInt(effYearRaw, 10);
        const maxY = new Date().getFullYear() + 1;
        if (y >= 1990 && y <= maxY) {
            backMeta.effectivity_year = String(y);
            highlights.push('effectivity_year');
        }
    }
    if (effQtr) {
        const q = String(effQtr).replace(/quarter|qtr\.?/ig, '').trim();
        const map = {
            '1': '1st Qtr', '1st': '1st Qtr', 'fst': '1st Qtr', 'ist': '1st Qtr', 'i': '1st Qtr', 'l': '1st Qtr', 'f': '1st Qtr',
            '2': '2nd Qtr', '2nd': '2nd Qtr',
            '3': '3rd Qtr', '3rd': '3rd Qtr',
            '4': '4th Qtr', '4th': '4th Qtr',
        };
        const mapped = map[q.toLowerCase()] || (String(effQtr).includes('Qtr') ? String(effQtr).trim() : null);
        if (mapped && /^(1st|2nd|3rd|4th) Qtr$/i.test(mapped)) {
            backMeta.effectivity_quarter = mapped;
            highlights.push('effectivity_quarter');
        }
    }
    setStr(backMeta, 'memoranda', sanitizeOcrMemoranda(r.memoranda));

    // Signatures / conforme
    if (r.assessed_by) {
        signatures.assessed_by.name = String(r.assessed_by).trim();
        highlights.push('assessed_by');
    }
    if (r.approved_by) {
        signatures.approved.name = String(r.approved_by).trim();
        highlights.push('approved_by');
    }
    setStr(conforme, 'name', r.conforme_name);
    setStr(conforme, 'ctc_no', r.conforme_ctc_no);
    setStr(conforme, 'issued_at', r.conforme_issued_at);
    if (r.conforme_dated || r.approval_date) {
        const d = parseDate(r.conforme_dated || r.approval_date);
        if (d) { conforme.dated = d; highlights.push('conforme_dated'); }
    }

    // Reference posting
    if (form2Identity.pin) references.pin = form2Identity.pin;
    if (form2Identity.arp_no) references.arp_no = form2Identity.arp_no;

    // Inspection location hint
    if (r.barangay || r.municipality) {
        const parts = [r.barangay, r.municipality, r.province].filter(Boolean);
        if (parts.length && !form.inspection_location) {
            form.inspection_location = parts.join(', ');
            highlights.push('inspection_location');
        }
    }

    ocr.highlights = [...new Set(highlights)];
    toast.success('Applied', `${ocr.highlights.length} fields auto-filled from OCR for ${form.form_template === 'form_2' ? 'Form 2' : 'Form 1'}.`);
}

const tdSearch      = ref('');
const tdSuggestions = ref([]);
const photoList     = ref([]);
const landSketchPath = ref(null);
const pendingSketchFile = ref(null);
const pendingSketchPreview = ref(null);
const uploadingSketch = ref(false);

const sketchPreviewUrl = computed(() => {
    if (pendingSketchPreview.value) return pendingSketchPreview.value;
    if (landSketchPath.value) return `/storage/${landSketchPath.value}`;
    return null;
});

const statusOpts = [
    { label: 'Draft', value: 'draft' },
    { label: 'Inspected', value: 'inspected' },
    { label: 'Computed', value: 'computed' },
    { label: 'Approved', value: 'approved' },
    { label: 'Revision', value: 'revision' },
];

const locationFields = [
    { key: 'street', label: 'No./Street' },
    { key: 'barangay', label: 'Barangay' },
    { key: 'municipality', label: 'Municipality' },
    { key: 'province', label: 'Province' },
];

const boundaryFields = [
    { key: 'north', label: 'North' },
    { key: 'east', label: 'East' },
    { key: 'south', label: 'South' },
    { key: 'west', label: 'West' },
];

const assessmentKinds = [
    'Agricultural', 'Residential', 'Commercial', 'Industrial',
    'Mineral', 'Special', 'Timber/Forest', 'Plant and Trees',
];

const signatureFields = [
    { key: 'appraised_by', label: 'Appraised By', placeholder: 'e.g. Assessment Clerk II' },
    { key: 'assessed_by', label: 'Assessed By', placeholder: 'e.g. Assessment Clerk II' },
    { key: 'recommending', label: 'Recommending Approval', placeholder: 'e.g. Municipal Assessor' },
    { key: 'approved', label: 'Approved', placeholder: 'e.g. OIC-Provincial Assessor' },
];

const referenceRows = [
    { key: 'pin', label: 'PIN' },
    { key: 'arp_no', label: 'ARP No.' },
    { key: 'ar_page_no', label: 'AR Page No.' },
];

const formTemplateOptions = [
    { label: 'Form 1 — FAAS (Sample)', value: 'form_1' },
    { label: 'Form 2 — FAAS (Sample)', value: 'form_2' },
];

const form = reactive({
    appraisal_no: '',
    form_template: 'form_1',
    tax_declaration_id: null,
    inspection_date: null,
    inspection_location: '',
    latitude: null,
    longitude: null,
    computed_market_value: null,
    computed_assessed_value: null,
    remarks: '',
    status: 'draft',
});

const form2Identity = reactive({
    update_code: '',
    pin: '',
    arp_no: '',
    oct_tct_kot_no: '',
    survey_no: '',
    cad_pls_lot_no: '',
    owner_name: '',
    owner_address: '',
    owner_tin: '',
    owner_telephone: '',
    administrator_name: '',
    administrator_address: '',
    administrator_tin: '',
    administrator_telephone: '',
});

const conforme = reactive({
    name: '',
    ctc_no: '',
    dated: null,
    issued_at: '',
});

const propertyLocation = reactive({
    street: '', barangay: '', municipality: '', province: '',
});

const propertyBoundaries = reactive({
    north: '', east: '', south: '', west: '',
});

const adjustments = reactive({
    along_road: null,
    kms_weather_road: null,
    kms_to_market: null,
    total_adjustments: 0,
    total_percentage: 100,
});

const assessmentRows = ref(
    assessmentKinds.map((classification) => ({
        classification,
        adjusted_market_value: null,
        assessment_level: null,
        assessed_value: null,
    }))
);

const roundedAssessedValue = ref(null);

const backMeta = reactive({
    previous_owner: '',
    previous_assessed_value: null,
    taxability: 'Taxable',
    effectivity_year: '',
    effectivity_quarter: null,
    memoranda: '',
});

const signatures = reactive({
    appraised_by: { name: '', title: '', date: null },
    assessed_by: { name: '', title: '', date: null },
    recommending: { name: '', title: '', date: null },
    approved: { name: '', title: '', date: null },
});

const references = reactive({
    pin: '',
    arp_no: '',
    ar_page_no: '',
});

const posting = reactive({
    pin: { date: null, clerk_initial: '', post_inspection: '' },
    arp_no: { date: null, clerk_initial: '', post_inspection: '' },
    ar_page_no: { date: null, clerk_initial: '', post_inspection: '' },
});

function emptyLandRow() {
    return {
        classification_kind: '',
        sub_class: '',
        actual_use: '',
        area: null,
        unit_value: null,
        base_market_value: null,
    };
}

function emptyPlantRow() {
    return {
        kind: '',
        prod_class: '',
        area_planted: null,
        non_fb: null,
        fb: null,
        total: null,
        unit_value: null,
        base_market_value: null,
    };
}

const landRows  = ref([emptyLandRow()]);
const plantRows = ref([emptyPlantRow()]);

const landTotals = computed(() => ({
    area: landRows.value.reduce((s, r) => s + (Number(r.area) || 0), 0),
    base_market_value: landRows.value.reduce((s, r) => s + (Number(r.base_market_value) || 0), 0),
}));

const plantTotals = computed(() => ({
    area_planted: plantRows.value.reduce((s, r) => s + (Number(r.area_planted) || 0), 0),
    non_fb: plantRows.value.reduce((s, r) => s + (Number(r.non_fb) || 0), 0),
    fb: plantRows.value.reduce((s, r) => s + (Number(r.fb) || 0), 0),
    total: plantRows.value.reduce((s, r) => s + (Number(r.total) || 0), 0),
    base_market_value: plantRows.value.reduce((s, r) => s + (Number(r.base_market_value) || 0), 0),
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

function recalcLandRow(row) {
    if (row.area != null && row.unit_value != null) {
        row.base_market_value = Math.round(Number(row.area) * Number(row.unit_value) * 100) / 100;
    }
}

function recalcPlantRow(row) {
    const nonFb = Number(row.non_fb) || 0;
    const fb = Number(row.fb) || 0;
    if (row.non_fb != null || row.fb != null) {
        row.total = nonFb + fb;
    }
    if (row.total != null && row.unit_value != null) {
        row.base_market_value = Math.round(Number(row.total) * Number(row.unit_value) * 100) / 100;
    }
}

function recalcAdjustments() {
    const total =
        (Number(adjustments.along_road) || 0) +
        (Number(adjustments.kms_weather_road) || 0) +
        (Number(adjustments.kms_to_market) || 0);
    adjustments.total_adjustments = total;
    adjustments.total_percentage = 100 + total;
}

function recalcAssessed(row) {
    if (row.adjusted_market_value != null && row.assessment_level != null) {
        row.assessed_value = Math.round(Number(row.adjusted_market_value) * Number(row.assessment_level)) / 100;
    }
    const raw = assessmentTotals.value.assessed_value;
    roundedAssessedValue.value = raw ? Math.round(raw / 10) * 10 : null;
}

function applyAdjustmentToAssessment() {
    recalcAdjustments();
    const base = landTotals.value.base_market_value;
    const pct = adjustments.total_percentage / 100;
    const agri = assessmentRows.value.find((r) => r.classification === 'Agricultural');
    if (agri && base) {
        agri.adjusted_market_value = Math.round(base * pct * 100) / 100;
        if (!agri.assessment_level) agri.assessment_level = 20;
        recalcAssessed(agri);
    }
    const plants = assessmentRows.value.find((r) => r.classification === 'Plant and Trees');
    if (plants && plantTotals.value.base_market_value) {
        plants.adjusted_market_value = plantTotals.value.base_market_value;
        recalcAssessed(plants);
    }
}

function syncComputedValues() {
    form.computed_market_value = Math.round(assessmentTotals.value.adjusted_market_value * 100) / 100;
    form.computed_assessed_value = roundedAssessedValue.value ?? Math.round(assessmentTotals.value.assessed_value * 100) / 100;
}

function addLandRow() {
    landRows.value.push(emptyLandRow());
}

function removeLandRow(i) {
    if (landRows.value.length > 1) landRows.value.splice(i, 1);
}

function addPlantRow() {
    plantRows.value.push(emptyPlantRow());
}

function removePlantRow(i) {
    if (plantRows.value.length > 1) plantRows.value.splice(i, 1);
}

async function searchTd(event) {
    const { data } = await axios.get('tax-declarations', { params: { search: event.query, per_page: 10 } });
    tdSuggestions.value = data.data ?? data;
}

function onTdSelect(event) {
    form.tax_declaration_id = event.value.id;
    const td = event.value;
    if (td.property_street) propertyLocation.street = td.property_street;
    if (td.barangay?.name) propertyLocation.barangay = td.barangay.name;
    if (td.municipality?.name) propertyLocation.municipality = td.municipality.name;
    if (td.boundary_north) propertyBoundaries.north = td.boundary_north;
    if (td.boundary_east) propertyBoundaries.east = td.boundary_east;
    if (td.boundary_south) propertyBoundaries.south = td.boundary_south;
    if (td.boundary_west) propertyBoundaries.west = td.boundary_west;
    if (td.property_index_number) {
        references.pin = td.property_index_number;
        form2Identity.pin = td.property_index_number;
    }
    if (td.td_number) {
        references.arp_no = td.td_number;
        form2Identity.arp_no = td.td_number;
    }
    if (td.oct_tct_cloa_no) form2Identity.oct_tct_kot_no = td.oct_tct_cloa_no;
    if (td.survey_number) form2Identity.survey_no = td.survey_number;
    if (td.lot_number) form2Identity.cad_pls_lot_no = td.lot_number;
    if (td.owner?.owner_name) form2Identity.owner_name = td.owner.owner_name;
    if (td.owner_address || td.owner?.address) form2Identity.owner_address = td.owner_address || td.owner?.address || '';
    if (td.administrator_name) form2Identity.administrator_name = td.administrator_name;
    if (td.administrator_address) form2Identity.administrator_address = td.administrator_address;
    if (td.owner?.owner_name) backMeta.previous_owner = td.owner.owner_name;
    if (td.previous_owner) backMeta.previous_owner = td.previous_owner;
    if (td.previous_av != null) backMeta.previous_assessed_value = Number(td.previous_av);
    if (td.taxability) backMeta.taxability = td.taxability === 'exempt' ? 'Exempt' : 'Taxable';
    if (td.effectivity_year) backMeta.effectivity_year = td.effectivity_year;
    if (td.effectivity_quarter) {
        const q = String(td.effectivity_quarter);
        backMeta.effectivity_quarter = q.includes('Qtr') ? q : `${q} Qtr`;
    }
}

function parseDate(val) {
    if (!val) return null;
    const d = new Date(val);
    return Number.isNaN(d.getTime()) ? null : d;
}

function toDateStr(val) {
    if (!val) return null;
    return new Date(val).toISOString().split('T')[0];
}

function numOrNull(val) {
    return val == null || val === '' ? null : Number(val);
}

function buildPayload() {
    recalcAdjustments();
    return {
        appraisal_no: form.appraisal_no,
        form_template: form.form_template || 'form_1',
        tax_declaration_id: form.tax_declaration_id,
        inspection_date: toDateStr(form.inspection_date),
        inspection_location: form.inspection_location,
        latitude: form.latitude,
        longitude: form.longitude,
        computed_market_value: form.computed_market_value,
        computed_assessed_value: form.computed_assessed_value,
        remarks: form.remarks,
        status: form.status,

        update_code: form2Identity.update_code,
        pin: form2Identity.pin,
        arp_no: form2Identity.arp_no,
        oct_tct_kot_no: form2Identity.oct_tct_kot_no,
        survey_no: form2Identity.survey_no,
        cad_pls_lot_no: form2Identity.cad_pls_lot_no,
        owner_name: form2Identity.owner_name,
        owner_address: form2Identity.owner_address,
        owner_tin: form2Identity.owner_tin,
        owner_telephone: form2Identity.owner_telephone,
        administrator_name: form2Identity.administrator_name,
        administrator_address: form2Identity.administrator_address,
        administrator_tin: form2Identity.administrator_tin,
        administrator_telephone: form2Identity.administrator_telephone,

        property_street: propertyLocation.street,
        property_barangay: propertyLocation.barangay,
        property_municipality: propertyLocation.municipality,
        property_province: propertyLocation.province,

        boundary_north: propertyBoundaries.north,
        boundary_east: propertyBoundaries.east,
        boundary_south: propertyBoundaries.south,
        boundary_west: propertyBoundaries.west,

        land_total_area: landTotals.value.area || null,
        land_total_base_market_value: landTotals.value.base_market_value || null,
        plant_total_area: plantTotals.value.area_planted || null,
        plant_total_non_fb: plantTotals.value.non_fb || null,
        plant_total_fb: plantTotals.value.fb || null,
        plant_total_count: plantTotals.value.total || null,
        plant_total_base_market_value: plantTotals.value.base_market_value || null,

        adj_along_road: adjustments.along_road,
        adj_kms_weather_road: adjustments.kms_weather_road,
        adj_kms_to_market: adjustments.kms_to_market,
        adj_total_adjustments: adjustments.total_adjustments,
        adj_total_percentage: adjustments.total_percentage,

        total_adjusted_market_value: assessmentTotals.value.adjusted_market_value || null,
        rounded_assessed_value: roundedAssessedValue.value,

        previous_owner: backMeta.previous_owner,
        previous_assessed_value: backMeta.previous_assessed_value,
        taxability: backMeta.taxability,
        effectivity_year: backMeta.effectivity_year,
        effectivity_quarter: backMeta.effectivity_quarter,

        appraised_by_name: signatures.appraised_by.name,
        appraised_by_title: signatures.appraised_by.title,
        appraised_by_date: toDateStr(signatures.appraised_by.date),
        assessed_by_name: signatures.assessed_by.name,
        assessed_by_title: signatures.assessed_by.title,
        assessed_by_date: toDateStr(signatures.assessed_by.date),
        recommending_name: signatures.recommending.name,
        recommending_title: signatures.recommending.title,
        recommending_date: toDateStr(signatures.recommending.date),
        approved_by_name: signatures.approved.name,
        approved_by_title: signatures.approved.title,
        approved_by_date: toDateStr(signatures.approved.date),

        conforme_name: conforme.name,
        conforme_ctc_no: conforme.ctc_no,
        conforme_dated: toDateStr(conforme.dated),
        conforme_issued_at: conforme.issued_at,

        memoranda: backMeta.memoranda,
        ref_pin: references.pin,
        ref_arp_no: references.arp_no,
        ref_ar_page_no: references.ar_page_no,

        posting_pin_date: toDateStr(posting.pin.date),
        posting_pin_clerk: posting.pin.clerk_initial,
        posting_pin_inspection: posting.pin.post_inspection,
        posting_arp_date: toDateStr(posting.arp_no.date),
        posting_arp_clerk: posting.arp_no.clerk_initial,
        posting_arp_inspection: posting.arp_no.post_inspection,
        posting_ar_page_date: toDateStr(posting.ar_page_no.date),
        posting_ar_page_clerk: posting.ar_page_no.clerk_initial,
        posting_ar_page_inspection: posting.ar_page_no.post_inspection,

        land_rows: landRows.value,
        plant_rows: plantRows.value,
        assessment_rows: assessmentRows.value,
    };
}

function loadFromRecord(data) {
    Object.assign(form, {
        appraisal_no: data.appraisal_no,
        form_template: data.form_template || 'form_1',
        tax_declaration_id: data.tax_declaration_id,
        inspection_date: parseDate(data.inspection_date),
        inspection_location: data.inspection_location || '',
        latitude: numOrNull(data.latitude),
        longitude: numOrNull(data.longitude),
        computed_market_value: numOrNull(data.computed_market_value),
        computed_assessed_value: numOrNull(data.computed_assessed_value),
        remarks: data.remarks || '',
        status: data.status || 'draft',
    });

    Object.assign(form2Identity, {
        update_code: data.update_code || '',
        pin: data.pin || '',
        arp_no: data.arp_no || '',
        oct_tct_kot_no: data.oct_tct_kot_no || '',
        survey_no: data.survey_no || '',
        cad_pls_lot_no: data.cad_pls_lot_no || '',
        owner_name: data.owner_name || '',
        owner_address: data.owner_address || '',
        owner_tin: data.owner_tin || '',
        owner_telephone: data.owner_telephone || '',
        administrator_name: data.administrator_name || '',
        administrator_address: data.administrator_address || '',
        administrator_tin: data.administrator_tin || '',
        administrator_telephone: data.administrator_telephone || '',
    });

    Object.assign(conforme, {
        name: data.conforme_name || '',
        ctc_no: data.conforme_ctc_no || '',
        dated: parseDate(data.conforme_dated),
        issued_at: data.conforme_issued_at || '',
    });

    // Prefer dedicated columns; fall back to legacy JSON
    const loc = data.land_details?.location || {};
    propertyLocation.street = data.property_street || loc.street || '';
    propertyLocation.barangay = data.property_barangay || loc.barangay || '';
    propertyLocation.municipality = data.property_municipality || loc.municipality || '';
    propertyLocation.province = data.property_province || loc.province || '';

    const b = data.land_details?.boundaries || {};
    propertyBoundaries.north = data.boundary_north || b.north || '';
    propertyBoundaries.east = data.boundary_east || b.east || '';
    propertyBoundaries.south = data.boundary_south || b.south || '';
    propertyBoundaries.west = data.boundary_west || b.west || '';

    if (Array.isArray(data.land_rows) && data.land_rows.length) {
        landRows.value = data.land_rows.map((r) => ({ ...emptyLandRow(), ...r, area: numOrNull(r.area), unit_value: numOrNull(r.unit_value), base_market_value: numOrNull(r.base_market_value) }));
    } else if (Array.isArray(data.land_details?.rows) && data.land_details.rows.length) {
        landRows.value = data.land_details.rows.map((r) => ({ ...emptyLandRow(), ...r }));
    }

    if (Array.isArray(data.plant_rows) && data.plant_rows.length) {
        plantRows.value = data.plant_rows.map((r) => ({
            ...emptyPlantRow(),
            ...r,
            area_planted: numOrNull(r.area_planted),
            non_fb: numOrNull(r.non_fb),
            fb: numOrNull(r.fb),
            total: numOrNull(r.total),
            unit_value: numOrNull(r.unit_value),
            base_market_value: numOrNull(r.base_market_value),
        }));
    } else if (Array.isArray(data.improvement_details?.rows) && data.improvement_details.rows.length) {
        plantRows.value = data.improvement_details.rows.map((r) => ({ ...emptyPlantRow(), ...r }));
    }

    adjustments.along_road = numOrNull(data.adj_along_road ?? data.computation?.adjustments?.along_road);
    adjustments.kms_weather_road = numOrNull(data.adj_kms_weather_road ?? data.computation?.adjustments?.kms_weather_road);
    adjustments.kms_to_market = numOrNull(data.adj_kms_to_market ?? data.computation?.adjustments?.kms_to_market);
    recalcAdjustments();

    const aRows = Array.isArray(data.assessment_rows) && data.assessment_rows.length
        ? data.assessment_rows
        : (data.computation?.assessment_rows || []);
    assessmentRows.value = assessmentKinds.map((classification) => {
        const found = aRows.find((r) => r.classification === classification) || {};
        return {
            classification,
            adjusted_market_value: numOrNull(found.adjusted_market_value),
            assessment_level: numOrNull(found.assessment_level),
            assessed_value: numOrNull(found.assessed_value),
        };
    });

    roundedAssessedValue.value = numOrNull(data.rounded_assessed_value ?? data.computation?.rounded_assessed_value);

    const meta = data.computation?.back_meta || {};
    backMeta.previous_owner = data.previous_owner || meta.previous_owner || '';
    backMeta.previous_assessed_value = numOrNull(data.previous_assessed_value ?? meta.previous_assessed_value);
    backMeta.taxability = data.taxability || meta.taxability || 'Taxable';
    backMeta.effectivity_year = data.effectivity_year || meta.effectivity_year || '';
    backMeta.effectivity_quarter = data.effectivity_quarter || meta.effectivity_quarter || null;
    backMeta.memoranda = data.memoranda || meta.memoranda || '';

    const sig = data.computation?.signatures || {};
    signatures.appraised_by = {
        name: data.appraised_by_name || sig.appraised_by?.name || '',
        title: data.appraised_by_title || sig.appraised_by?.title || '',
        date: parseDate(data.appraised_by_date || sig.appraised_by?.date),
    };
    signatures.assessed_by = {
        name: data.assessed_by_name || sig.assessed_by?.name || '',
        title: data.assessed_by_title || sig.assessed_by?.title || '',
        date: parseDate(data.assessed_by_date || sig.assessed_by?.date),
    };
    signatures.recommending = {
        name: data.recommending_name || sig.recommending?.name || '',
        title: data.recommending_title || sig.recommending?.title || '',
        date: parseDate(data.recommending_date || sig.recommending?.date),
    };
    signatures.approved = {
        name: data.approved_by_name || sig.approved?.name || '',
        title: data.approved_by_title || sig.approved?.title || '',
        date: parseDate(data.approved_by_date || sig.approved?.date),
    };

    const refs = data.computation?.references || {};
    references.pin = data.ref_pin || refs.pin || '';
    references.arp_no = data.ref_arp_no || refs.arp_no || '';
    references.ar_page_no = data.ref_ar_page_no || refs.ar_page_no || '';

    const post = data.computation?.posting || {};
    posting.pin = {
        date: parseDate(data.posting_pin_date || post.pin?.date),
        clerk_initial: data.posting_pin_clerk || post.pin?.clerk_initial || '',
        post_inspection: data.posting_pin_inspection || post.pin?.post_inspection || '',
    };
    posting.arp_no = {
        date: parseDate(data.posting_arp_date || post.arp_no?.date),
        clerk_initial: data.posting_arp_clerk || post.arp_no?.clerk_initial || '',
        post_inspection: data.posting_arp_inspection || post.arp_no?.post_inspection || '',
    };
    posting.ar_page_no = {
        date: parseDate(data.posting_ar_page_date || post.ar_page_no?.date),
        clerk_initial: data.posting_ar_page_clerk || post.ar_page_no?.clerk_initial || '',
        post_inspection: data.posting_ar_page_inspection || post.ar_page_no?.post_inspection || '',
    };

    if (data.photos) photoList.value = data.photos;
    if (data.tax_declaration) tdSearch.value = data.tax_declaration;
}

function onSketchSelect(e) {
    const file = e.target.files?.[0];
    if (file) applySketchFile(file);
    e.target.value = '';
}

function onSketchDrop(e) {
    const file = e.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) applySketchFile(file);
}

async function applySketchFile(file) {
    if (pendingSketchPreview.value) URL.revokeObjectURL(pendingSketchPreview.value);

    if (isEdit.value) {
        uploadingSketch.value = true;
        try {
            const fd = new FormData();
            fd.append('sketch', file);
            const { data } = await axios.post(`field-appraisals/${route.params.id}/sketch`, fd);
            landSketchPath.value = data.land_sketch;
            pendingSketchFile.value = null;
            pendingSketchPreview.value = null;
            toast.success('Uploaded', 'Land sketch saved.');
        } catch {
            toast.error('Error', 'Failed to upload sketch.');
        } finally {
            uploadingSketch.value = false;
        }
        return;
    }

    pendingSketchFile.value = file;
    pendingSketchPreview.value = URL.createObjectURL(file);
}

async function removeSketch() {
    if (isEdit.value && landSketchPath.value) {
        uploadingSketch.value = true;
        try {
            await axios.delete(`field-appraisals/${route.params.id}/sketch`);
            landSketchPath.value = null;
            toast.success('Removed', 'Land sketch removed.');
        } catch {
            toast.error('Error', 'Failed to remove sketch.');
        } finally {
            uploadingSketch.value = false;
        }
    }
    if (pendingSketchPreview.value) URL.revokeObjectURL(pendingSketchPreview.value);
    pendingSketchFile.value = null;
    pendingSketchPreview.value = null;
    landSketchPath.value = null;
}

async function uploadPendingSketch(appraisalId) {
    if (!pendingSketchFile.value) return;
    const fd = new FormData();
    fd.append('sketch', pendingSketchFile.value);
    const { data } = await axios.post(`field-appraisals/${appraisalId}/sketch`, fd);
    landSketchPath.value = data.land_sketch;
    if (pendingSketchPreview.value) URL.revokeObjectURL(pendingSketchPreview.value);
    pendingSketchFile.value = null;
    pendingSketchPreview.value = null;
}

async function handleSubmit() {
    saving.value = true;
    errors.value = {};
    try {
        const payload = buildPayload();

        if (isEdit.value) {
            await axios.put(`field-appraisals/${route.params.id}`, payload);
            toast.success('Updated', 'Field appraisal updated.');
        } else {
            const { data } = await axios.post('field-appraisals', payload);
            if (pendingSketchFile.value) {
                await uploadPendingSketch(data.id);
            }
            toast.success('Created', 'Field appraisal saved.');
            router.push(`/field-appraisals/${data.id}`);
            return;
        }
        router.push('/field-appraisals');
    } catch (err) {
        errors.value = err.response?.data?.errors || {};
        toast.error('Error', err.response?.data?.message || 'Please check the form.');
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    if (isEdit.value) {
        const { data } = await axios.get(`field-appraisals/${route.params.id}`);
        loadFromRecord(data);
        landSketchPath.value = data.land_sketch || null;
    }
});
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

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; transform: translateY(-4px); }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; max-height: 500px; }
</style>
