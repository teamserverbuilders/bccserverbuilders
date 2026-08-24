<template>
    <div v-if="td" class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <RouterLink to="/tax-declarations">
                    <Button icon="pi pi-arrow-left" rounded outlined size="small" />
                </RouterLink>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">TD# {{ td.td_number }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <Tag :value="formatStatus(td.status)" :severity="statusSeverity(td.status)" />
                        <Tag v-if="td.is_locked" value="Locked" severity="danger" icon="pi pi-lock" />
                        <span class="text-xs text-gray-400">Version {{ td.version }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <Button
                    type="button"
                    label="PDF"
                    icon="pi pi-file-pdf"
                    outlined
                    size="small"
                    @click="generatePdf"
                />
                <Button
                    type="button"
                    label="Transfer Ownership"
                    icon="pi pi-users"
                    outlined
                    size="small"
                    severity="help"
                    :disabled="isArchivedTd || (td.is_locked && String(td.status || '').toLowerCase() !== 'approved')"
                    v-tooltip="transferTooltip"
                    @click="openTransferDialog"
                />
                <Button
                    v-if="td.is_locked"
                    type="button"
                    label="Unlock"
                    icon="pi pi-lock-open"
                    outlined
                    size="small"
                    severity="warn"
                    :loading="unlockLoading"
                    v-tooltip="'Unlock this record so it can be edited again'"
                    @click="confirmUnlock"
                />
                <Button
                    type="button"
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    v-if="!td.is_locked"
                    @click="$router.push(`/tax-declarations/${td.id}/edit`)"
                />
                <Button
                    type="button"
                    label="Workflow"
                    icon="pi pi-sitemap"
                    iconPos="left"
                    size="small"
                    @click="toggleWorkflowMenu"
                    aria-haspopup="true"
                    aria-controls="td_workflow_menu"
                />
                <Menu ref="workflowMenuRef" id="td_workflow_menu" :model="workflowActionItems" :popup="true" appendTo="body" />
            </div>
        </div>

        <!-- Successor / predecessor banner -->
        <div
            v-if="successorTd"
            class="rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50/70 dark:bg-amber-900/20 px-4 py-3 flex flex-wrap items-center justify-between gap-3"
        >
            <div class="flex items-start gap-2 text-sm text-amber-800 dark:text-amber-200">
                <i class="pi pi-inbox mt-0.5"></i>
                <div>
                    <p class="font-medium">This TD has been cancelled by a transfer of ownership.</p>
                    <p class="text-xs">A new TD was issued and supersedes this one.</p>
                </div>
            </div>
            <RouterLink :to="`/tax-declarations/${successorTd.id || successorTd.new_tax_declaration_id}`">
                <Button
                    :label="`Open new TD ${successorTd.td_number || successorTd.new_td_number}`"
                    icon="pi pi-arrow-right"
                    iconPos="right"
                    size="small"
                    severity="warn"
                />
            </RouterLink>
        </div>

        <div
            v-else-if="issuedFrom"
            class="rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50/70 dark:bg-emerald-900/20 px-4 py-3 flex flex-wrap items-center justify-between gap-3"
        >
            <div class="flex items-start gap-2 text-sm text-emerald-800 dark:text-emerald-200">
                <i class="pi pi-verified mt-0.5"></i>
                <div>
                    <p class="font-medium">Issued via ownership transfer.</p>
                    <p class="text-xs">This TD cancels the previous one and takes effect from the transfer date.</p>
                </div>
            </div>
            <RouterLink v-if="issuedFrom.tax_declaration_id" :to="`/tax-declarations/${issuedFrom.tax_declaration_id}`">
                <Button
                    :label="`View cancelled TD ${issuedFrom.taxDeclaration?.td_number || issuedFrom.tax_declaration?.td_number || td.previous_td_number || ''}`"
                    icon="pi pi-history"
                    size="small"
                    outlined
                    severity="success"
                />
            </RouterLink>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_280px] gap-4">
            <div class="min-w-0 space-y-5">
                <!-- Official TD sheet (same layout as New Declaration) -->
                <TaxDeclarationSheet :td="td" :classifications="classifications" />

                <!-- Ownership History -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4 gap-3">
                        <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="pi pi-users text-emerald-500"></i> Ownership History
                        </h3>
                        <Button
                            v-if="!isArchivedTd && (!td.is_locked || String(td.status || '').toLowerCase() === 'approved')"
                            label="Transfer"
                            icon="pi pi-arrow-right-arrow-left"
                            size="small"
                            text
                            @click="openTransferDialog"
                        />
                    </div>

                    <!-- Current owner -->
                    <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/20 p-4 mb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-1">Current owner</p>
                                <RouterLink
                                    v-if="td.owner_id"
                                    :to="`/property-owners/${td.owner_id}`"
                                    class="text-sm font-semibold text-gray-900 dark:text-white hover:underline"
                                >
                                    {{ currentOwnerName }}
                                </RouterLink>
                                <p v-else class="text-sm font-semibold text-gray-900 dark:text-white">{{ currentOwnerName }}</p>
                                <p v-if="ownerTin" class="text-xs text-gray-500 mt-1">TIN: {{ ownerTin }}</p>
                                <p v-if="ownerAddress" class="text-xs text-gray-500 mt-0.5">{{ ownerAddress }}</p>
                            </div>
                            <Tag value="Current" severity="success" class="shrink-0" />
                        </div>
                    </div>

                    <div v-if="ownershipHistory.length" class="space-y-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Previous owners</p>
                        <div v-for="(row, i) in ownershipHistory" :key="row.id" class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white shrink-0 bg-gray-400 dark:bg-gray-600">
                                    {{ ownershipHistory.length - i }}
                                </div>
                                <div v-if="i < ownershipHistory.length - 1" class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                            </div>
                            <div class="pb-3 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <RouterLink
                                        v-if="row.owner_id"
                                        :to="`/property-owners/${row.owner_id}`"
                                        class="text-sm font-medium text-gray-800 dark:text-white hover:underline"
                                    >
                                        {{ row.owner_name }}
                                    </RouterLink>
                                    <p v-else class="text-sm font-medium text-gray-800 dark:text-white">{{ row.owner_name }}</p>
                                    <Tag value="Previous" severity="secondary" class="text-[10px]" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ formatDate(row.effective_from) }} – {{ formatDate(row.effective_to || row.transfer_date) }}
                                    <span v-if="row.transfer_date"> · Transferred {{ formatDate(row.transfer_date) }}</span>
                                </p>
                                <p v-if="row.transfer_reason" class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ row.transfer_reason }}</p>
                                <p v-if="row.remarks" class="text-xs text-gray-500 italic mt-0.5">{{ row.remarks }}</p>
                                <div class="text-xs text-gray-400 mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                                    <span>{{ row.transferred_by?.name || row.transferredBy?.name || 'System' }}</span>
                                    <span v-if="row.new_td_number || row.new_tax_declaration">·</span>
                                    <span v-if="row.new_td_number || row.new_tax_declaration">
                                        Issued new TD
                                        <RouterLink
                                            v-if="row.new_tax_declaration_id || row.new_tax_declaration?.id"
                                            :to="`/tax-declarations/${row.new_tax_declaration_id || row.new_tax_declaration?.id}`"
                                            class="font-mono text-blue-600 hover:underline"
                                        >
                                            {{ row.new_td_number || row.new_tax_declaration?.td_number }}
                                        </RouterLink>
                                        <span v-else class="font-mono">{{ row.new_td_number }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-gray-400">
                        <i class="pi pi-history text-2xl mb-2 block opacity-40"></i>
                        <p class="text-sm">No previous owners recorded yet</p>
                        <p class="text-xs mt-1">Use Transfer Ownership when the property changes hands.</p>
                    </div>
                </div>

                <!-- Documents -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="pi pi-folder text-purple-500"></i> Supporting Documents
                        </h3>
                        <RouterLink :to="{ path: '/documents', query: { upload: '1', td_id: td.id } }">
                            <Button label="Upload" icon="pi pi-upload" size="small" outlined />
                        </RouterLink>
                    </div>
                    <div v-if="td.documents?.length" class="space-y-2">
                        <div v-for="doc in td.documents" :key="doc.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i :class="['pi text-xl', doc.mime_type?.includes('pdf') ? 'pi-file-pdf text-red-500' : 'pi-file text-blue-500']"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ doc.title }}</p>
                                    <p class="text-xs text-gray-500">{{ doc.document_type?.replace(/_/g, ' ') }} · {{ formatFileSize(doc.file_size) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button icon="pi pi-download" size="small" text rounded @click="downloadDoc(doc)" />
                                <Button icon="pi pi-trash" size="small" text rounded severity="danger" @click="deleteDoc(doc)" />
                            </div>
                        </div>
                        <RouterLink :to="{ path: '/documents', query: { td_id: td.id } }" class="inline-flex text-xs text-blue-600 hover:underline mt-1">
                            View all in Supporting Documents
                        </RouterLink>
                    </div>
                    <div v-else class="text-center py-8 text-gray-400">
                        <i class="pi pi-folder-open text-3xl mb-2 block opacity-40"></i>
                        <p class="text-sm">No documents attached</p>
                        <RouterLink :to="{ path: '/documents', query: { upload: '1', td_id: td.id } }" class="inline-block mt-3">
                            <Button label="Add Document" icon="pi pi-upload" size="small" text />
                        </RouterLink>
                    </div>
                </div>

                <!-- Property Images -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="pi pi-images text-sky-500"></i> Property Images
                        </h3>
                        <Button label="Add Photo" icon="pi pi-camera" size="small" outlined @click="showImgUpload = true" />
                    </div>
                    <div v-if="images.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div v-for="img in images" :key="img.id" class="group relative rounded-lg overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700">
                            <img :src="img.url" class="w-full h-full object-cover" :alt="img.image_type" />
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <Button icon="pi pi-eye" size="small" rounded text class="text-white" @click="previewImg = img; showImgPreview = true" />
                                <Button icon="pi pi-trash" size="small" rounded text class="text-white" @click="deleteImage(img)" />
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1.5">
                                <p class="text-white text-xs capitalize text-center">{{ img.image_type }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-400">
                        <i class="pi pi-images text-3xl mb-2 block opacity-40"></i>
                        <p class="text-sm">No images uploaded</p>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-3 lg:w-[280px] lg:shrink-0">
                <!-- Workflow Timeline -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-3 flex items-center gap-2 text-sm">
                        <i class="pi pi-sitemap text-indigo-500"></i> Workflow History
                    </h3>
                    <div class="space-y-3">
                        <div v-if="!workflowHistory.length" class="text-sm text-gray-400 text-center py-4">No workflow activity yet</div>
                        <div v-for="(wf, i) in workflowHistory" :key="wf.id" class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div :class="['w-6 h-6 rounded-full flex items-center justify-center text-xs text-white shrink-0', i === 0 ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600']">
                                    {{ workflowHistory.length - i }}
                                </div>
                                <div v-if="i < workflowHistory.length - 1" class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                            </div>
                            <div class="pb-3 flex-1">
                                <p class="text-sm font-medium text-gray-800 dark:text-white capitalize">{{ wf.to_status?.replace(/_/g, ' ') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ wf.performed_by?.name || wf.performedBy?.name || 'System' }} · {{ formatDate(wf.created_at) }}</p>
                                <p v-if="wf.remarks" class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">{{ wf.remarks }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GIS Location -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2 text-sm">
                            <i class="pi pi-map-marker text-red-500"></i> GIS Location
                        </h3>
                        <div class="flex items-center gap-1">
                            <Button size="small" text icon="pi pi-sitemap" v-tooltip="'Land Mapping'" @click="$router.push('/land-map?td_id=' + td.id)" />
                            <Button size="small" outlined icon="pi pi-map" label="Map" @click="$router.push('/gis?td=' + td.id)" />
                        </div>
                    </div>
                    <div v-if="mapPin">
                        <MiniPropertyMap
                            :lat="mapPin.lat"
                            :lng="mapPin.lng"
                            :label="mapPin.label"
                        />
                        <p class="text-sm font-medium text-gray-800 dark:text-white mt-3">{{ mapPin.label }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ mapPin.lat.toFixed(6) }}, {{ mapPin.lng.toFixed(6) }}</p>
                        <a :href="`https://www.google.com/maps?q=${mapPin.lat},${mapPin.lng}`" target="_blank"
                           class="inline-flex items-center gap-2 text-xs text-blue-600 hover:underline mt-2">
                            <i class="pi pi-external-link"></i> Open in Google Maps
                        </a>
                    </div>
                    <div v-else class="text-center py-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No location coordinates available</p>
                        <Button label="Set Location" icon="pi pi-map-marker" size="small" class="mt-3" @click="$router.push('/gis?td=' + td.id)" />
                    </div>
                </div>

                <!-- OCR Results -->
                <div v-if="td.ocr_results?.length" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-3 flex items-center gap-2 text-sm">
                        <i class="pi pi-camera text-orange-500"></i> OCR Results
                    </h3>
                    <div v-for="ocr in td.ocr_results" :key="ocr.id" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg mb-2">
                        <div class="flex items-center justify-between">
                            <Tag :value="ocr.status" :severity="ocr.status === 'completed' ? 'success' : 'warn'" class="text-xs" />
                            <span class="text-xs text-gray-500">{{ ocr.confidence_score }}% confidence</span>
                        </div>
                        <div class="mt-2 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                            <div class="bg-green-500 h-1.5 rounded-full" :style="{ width: ocr.confidence_score + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Version History -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-3 flex items-center gap-2 text-sm">
                        <i class="pi pi-history text-teal-500"></i> Version History
                    </h3>
                    <div class="space-y-2">
                        <div v-for="v in td.versions?.slice(0, 5)" :key="v.id" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Version {{ v.version_number }}</p>
                                <p class="text-xs text-gray-500">{{ v.created_by?.name }} · {{ formatDate(v.created_at) }}</p>
                                <p class="text-xs text-gray-400 italic">{{ v.change_summary }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflow Action Dialog -->
        <Dialog v-model:visible="showWorkflowDialog" :header="workflowAction.label" :modal="true" class="w-96">
            <div class="space-y-4 pt-4">
                <div>
                    <label class="form-label">Remarks</label>
                    <Textarea v-model="workflowRemarks" class="w-full" rows="3" :placeholder="`Add remarks for ${workflowAction.label?.toLowerCase()}...`" />
                </div>
                <div class="flex gap-2">
                    <Button :label="workflowAction.label" :icon="workflowAction.icon" class="flex-1" :loading="workflowLoading" @click="submitWorkflow" />
                    <Button label="Cancel" outlined class="flex-1" @click="showWorkflowDialog = false" />
                </div>
            </div>
        </Dialog>

        <!-- Transfer Ownership Dialog (landscape) -->
        <Dialog
            v-model:visible="showTransferDialog"
            header="Transfer Ownership — Issue New TD"
            :modal="true"
            class="w-[96vw] max-w-6xl"
            :contentStyle="{ padding: '0' }"
            @hide="resetTransferForm"
        >
            <div class="flex flex-col">
                <!-- Top banner: what will happen -->
                <div class="border-b border-blue-200 dark:border-blue-800 bg-blue-50/60 dark:bg-blue-900/20 px-5 py-3 text-xs text-blue-800 dark:text-blue-200">
                    <p class="flex items-start gap-2">
                        <i class="pi pi-info-circle mt-0.5"></i>
                        <span>
                            A <strong>new Tax Declaration</strong> will be issued for the new owner. The current TD
                            <strong class="font-mono">{{ td.td_number }}</strong> will be <strong>cancelled (archived)</strong>
                            and referenced as the previous TD on the new record.
                        </span>
                    </p>
                </div>

                <!-- Cancels → Issues summary strip -->
                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-stretch gap-0 border-b border-gray-100 dark:border-gray-700">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/40">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Cancels TD</p>
                        <p class="font-mono text-sm font-semibold text-gray-800 dark:text-white break-all">{{ td.td_number }}</p>
                        <p class="text-xs text-gray-500 mt-1 truncate">{{ currentOwnerName }}</p>
                    </div>
                    <div class="hidden md:flex items-center justify-center px-3 text-gray-400">
                        <i class="pi pi-arrow-right text-lg"></i>
                    </div>
                    <div class="p-4 bg-emerald-50/60 dark:bg-emerald-900/20 border-l border-emerald-200 dark:border-emerald-800">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-1">Issues new TD</p>
                        <p class="font-mono text-sm font-semibold text-emerald-800 dark:text-emerald-200 break-all">
                            {{ transferForm.new_td_number || '—' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1 truncate">{{ transferForm.owner_name || 'New owner' }}</p>
                    </div>
                </div>

                <!-- Landscape two-column body -->
                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 dark:divide-gray-700">
                    <!-- LEFT: New TD identifiers + transfer meta -->
                    <div class="p-5 space-y-4">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1.5">
                                <i class="pi pi-file"></i> New Declaration
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label">New TD No. <span class="text-red-500">*</span></label>
                                    <InputText
                                        v-model="transferForm.new_td_number"
                                        class="w-full"
                                        placeholder="e.g. 2026-05-0001"
                                    />
                                    <p class="text-[11px] text-gray-400 mt-1">Must be unique.</p>
                                </div>
                                <div>
                                    <label class="form-label">New ARP No.</label>
                                    <InputText
                                        v-model="transferForm.new_arp_number"
                                        class="w-full"
                                        :placeholder="td.arp_number || 'Leave blank to reuse'"
                                    />
                                    <p class="text-[11px] text-gray-400 mt-1">Optional — reuses current if empty.</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1.5">
                                <i class="pi pi-calendar"></i> Transfer Details
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label">Transfer date <span class="text-red-500">*</span></label>
                                    <DatePicker v-model="transferForm.transfer_date" class="w-full" dateFormat="yy-mm-dd" showIcon />
                                </div>
                                <div>
                                    <label class="form-label">Reason</label>
                                    <InputText v-model="transferForm.transfer_reason" class="w-full" placeholder="Sale, inheritance, donation…" />
                                </div>
                                <div class="col-span-2">
                                    <label class="form-label">Remarks</label>
                                    <Textarea v-model="transferForm.remarks" class="w-full" rows="3" autoResize />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: New owner -->
                    <div class="p-5 space-y-4 bg-gray-50/40 dark:bg-gray-900/20">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                <i class="pi pi-user-plus"></i> New Owner
                            </p>
                            <div v-if="transferForm.owner_id" class="flex items-center gap-2">
                                <Tag :value="`Linked #${transferForm.owner_id}`" severity="info" class="text-[10px]" />
                                <Button label="Clear" size="small" text @click="clearTransferOwnerSelection" />
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Search existing owner</label>
                            <AutoComplete
                                v-model="transferOwnerSearch"
                                :suggestions="transferOwnerSuggestions"
                                optionLabel="owner_name"
                                placeholder="Type name to search…"
                                class="w-full"
                                :forceSelection="false"
                                @complete="searchTransferOwners"
                                @item-select="onTransferOwnerSelect"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Or enter a new owner below to create one.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="form-label">Owner name <span class="text-red-500">*</span></label>
                                <InputText v-model="transferForm.owner_name" class="w-full" :disabled="!!transferForm.owner_id" />
                            </div>
                            <div>
                                <label class="form-label">TIN</label>
                                <InputText v-model="transferForm.owner_tin" class="w-full" :disabled="!!transferForm.owner_id" />
                            </div>
                            <div>
                                <label class="form-label">Telephone</label>
                                <InputText v-model="transferForm.owner_telephone" class="w-full" :disabled="!!transferForm.owner_id" />
                            </div>
                            <div class="col-span-2">
                                <label class="form-label">Address</label>
                                <Textarea v-model="transferForm.owner_address" class="w-full" rows="2" autoResize :disabled="!!transferForm.owner_id" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer actions -->
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <Button label="Cancel" outlined size="small" @click="showTransferDialog = false" />
                    <Button
                        label="Issue New TD & Transfer"
                        icon="pi pi-check"
                        size="small"
                        severity="success"
                        :loading="transferLoading"
                        @click="submitTransfer"
                    />
                </div>
            </div>
        </Dialog>

        <!-- Image Upload -->
        <Dialog v-model:visible="showImgUpload" header="Add Property Photo" :modal="true" class="w-full max-w-md" @hide="resetImgForm">
            <div class="space-y-4 pt-2">
                <div>
                    <label class="form-label">Photo Type</label>
                    <Select v-model="imgForm.image_type" :options="imageTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="form-label">Caption (optional)</label>
                    <InputText v-model="imgForm.caption" class="w-full" placeholder="Short description" />
                </div>
                <div>
                    <label class="form-label">Photo</label>
                    <div
                        class="border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        @click="imgFileInput?.click()"
                    >
                        <i class="pi pi-camera text-xl text-gray-400 mb-1 block"></i>
                        <p v-if="imgForm.file" class="text-sm font-medium text-gray-800 dark:text-white">{{ imgForm.file.name }}</p>
                        <p v-else class="text-sm text-gray-500">Click to choose an image</p>
                    </div>
                    <input ref="imgFileInput" type="file" accept="image/*" class="hidden" @change="onImgFileSelect" />
                </div>
                <div class="flex gap-2 pt-2">
                    <Button label="Add Photo" icon="pi pi-camera" class="flex-1" :loading="imgUploadLoading" @click="submitImgUpload" />
                    <Button label="Cancel" outlined class="flex-1" @click="showImgUpload = false" />
                </div>
            </div>
        </Dialog>

        <!-- Image Preview -->
        <Dialog v-model:visible="showImgPreview" :modal="true" :pt="{ root: 'bg-black/90' }" class="w-full max-w-3xl">
            <img v-if="previewImg" :src="previewImg.url" class="w-full object-contain max-h-[80vh]" :alt="previewImg.image_type" />
        </Dialog>
    </div>
    <div v-else-if="loading" class="flex items-center justify-center h-64">
        <ProgressSpinner />
    </div>
    <div v-else-if="loadError" class="flex flex-col items-center justify-center h-64 gap-4 text-center px-4">
        <i class="pi pi-exclamation-triangle text-4xl text-amber-500"></i>
        <p class="text-gray-600 dark:text-gray-300">{{ loadError }}</p>
        <RouterLink to="/tax-declarations">
            <Button label="Back to list" icon="pi pi-arrow-left" outlined size="small" />
        </RouterLink>
    </div>
    <div v-else class="flex items-center justify-center h-64">
        <ProgressSpinner />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import AutoComplete from 'primevue/autocomplete';
import DatePicker from 'primevue/datepicker';
import ProgressSpinner from 'primevue/progressspinner';
import TaxDeclarationSheet from '@/components/TaxDeclarationSheet.vue';
import MiniPropertyMap from '@/components/MiniPropertyMap.vue';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const td = ref(null);
const loading = ref(true);
const loadError = ref(null);
const images = ref([]);
const classifications = ref([]);
const showWorkflowDialog = ref(false);
const workflowRemarks = ref('');
const workflowLoading = ref(false);
const workflowAction = ref({ label: '', status: '', icon: '' });
const showImgPreview = ref(false);
const previewImg = ref(null);
const showImgUpload = ref(false);
const imgFileInput = ref(null);
const imgUploadLoading = ref(false);
const imgForm = ref({ image_type: 'additional', caption: '', file: null });
const workflowMenuRef = ref(null);
const showTransferDialog = ref(false);
const transferLoading = ref(false);
const unlockLoading = ref(false);
const transferOwnerSearch = ref('');
const transferOwnerSuggestions = ref([]);
const transferForm = ref({
    new_td_number: '',
    new_arp_number: '',
    owner_id: null,
    owner_name: '',
    owner_tin: '',
    owner_address: '',
    owner_telephone: '',
    transfer_date: new Date(),
    transfer_reason: '',
    remarks: '',
});

const imageTypeOptions = [
    { label: 'Front', value: 'front' },
    { label: 'Rear', value: 'rear' },
    { label: 'Left Side', value: 'left' },
    { label: 'Right Side', value: 'right' },
    { label: 'Road View', value: 'road' },
    { label: 'Landmark', value: 'landmark' },
    { label: 'Aerial', value: 'aerial' },
    { label: 'Additional', value: 'additional' },
];

const workflowActionItems = [
    { label: 'Submit for OCR', icon: 'pi pi-camera', command: () => openWorkflow('ocr_processing', 'Submit for OCR', 'pi pi-camera') },
    { label: 'Send to OCR Review', icon: 'pi pi-eye', command: () => openWorkflow('ocr_review', 'Send to OCR Review', 'pi pi-eye') },
    { label: 'Send to Encoder Review', icon: 'pi pi-pencil', command: () => openWorkflow('encoder_review', 'Send to Encoder Review', 'pi pi-pencil') },
    { label: 'Send to Assessor', icon: 'pi pi-verified', command: () => openWorkflow('assessor_verification', 'Send to Assessor', 'pi pi-verified') },
    { label: 'Send to Supervisor', icon: 'pi pi-user-edit', command: () => openWorkflow('supervisor_review', 'Send to Supervisor', 'pi pi-user-edit') },
    { separator: true },
    { label: 'Approve', icon: 'pi pi-check-circle', command: () => openWorkflow('approved', 'Approve', 'pi pi-check-circle') },
    { label: 'Release', icon: 'pi pi-send', command: () => openWorkflow('released', 'Release', 'pi pi-send') },
    { separator: true },
    { label: 'Return for Correction', icon: 'pi pi-undo', command: () => openWorkflow('returned', 'Return', 'pi pi-undo') },
    { label: 'Reject', icon: 'pi pi-times-circle', command: () => openWorkflow('rejected', 'Reject', 'pi pi-times-circle') },
    { separator: true },
    { label: 'Archive', icon: 'pi pi-inbox', command: () => openWorkflow('archived', 'Archive', 'pi pi-inbox') },
    { label: 'Back to Draft', icon: 'pi pi-file', command: () => openWorkflow('draft', 'Back to Draft', 'pi pi-file') },
];

function toggleWorkflowMenu(event) {
    workflowMenuRef.value?.toggle(event);
}

const workflowHistory = computed(() => td.value?.workflow_history ?? td.value?.workflowHistory ?? []);
const ownershipHistory = computed(() => td.value?.ownership_history ?? td.value?.ownershipHistory ?? []);
const currentOwnerName = computed(() =>
    td.value?.owner?.owner_name || td.value?.owner_name || 'No owner assigned'
);

const isArchivedTd = computed(() => String(td.value?.status || '').toLowerCase() === 'archived');

// Latest history row that produced a successor TD (present when this TD was cancelled by transfer)
const successorTd = computed(() => {
    const row = ownershipHistory.value.find(h => h?.new_tax_declaration_id || h?.new_td_number);
    if (!row) return null;
    const nested = row.new_tax_declaration || row.newTaxDeclaration;
    return {
        id: nested?.id || row.new_tax_declaration_id,
        new_tax_declaration_id: row.new_tax_declaration_id,
        td_number: nested?.td_number || row.new_td_number,
        new_td_number: row.new_td_number,
    };
});

// History row on the previous TD that produced this TD (present when this TD was issued via transfer)
const issuedFrom = computed(() =>
    td.value?.issued_from_history ?? td.value?.issuedFromHistory ?? null
);

const transferTooltip = computed(() => {
    if (isArchivedTd.value) return 'This TD is already cancelled. Open the successor to record further transfers.';
    const locked = td.value?.is_locked && String(td.value?.status || '').toLowerCase() !== 'approved';
    if (locked) return 'Unlock or return this record before transferring';
    return 'Issue a new TD and cancel this one under the new owner';
});

const kindOfPropertyLabel = computed(() => {
    const k = td.value?.kind_of_property;
    if (Array.isArray(k) && k.length) return k.join(', ');
    return null;
});

const municipalityLabel = computed(() => {
    const m = td.value?.municipality;
    if (!m) return null;
    return m.province ? `${m.name}, ${m.province}` : m.name;
});

const locationLabel = computed(() => {
    const parts = [
        td.value?.property_street,
        td.value?.barangay?.name,
        td.value?.municipality?.name,
        td.value?.municipality?.province,
    ].filter(Boolean);
    return parts.length ? parts.join(', ') : null;
});

const ownerTin = computed(() => td.value?.owner?.tin || td.value?.owner_tin || null);
const ownerAddress = computed(() => td.value?.owner?.address || td.value?.owner_address || null);

const marketValueDisplay = computed(() =>
    td.value?.market_value ?? td.value?.base_market_value ?? td.value?.adjusted_market_value ?? null
);

const effectivityLabel = computed(() => {
    const q = td.value?.effectivity_quarter;
    const y = td.value?.effectivity_year;
    if (q && y) return `${q} Quarter, ${y}`;
    if (y) return y;
    return null;
});

const hasBoundaries = computed(() =>
    td.value?.boundary_north || td.value?.boundary_east || td.value?.boundary_south || td.value?.boundary_west
);

const mapPin = computed(() => {
    if (!td.value) return null;

    const gis = td.value.gis_location ?? td.value.gisLocation;
    if (gis?.latitude != null && gis?.longitude != null) {
        const lat = Number(gis.latitude);
        const lng = Number(gis.longitude);
        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
            return {
                lat,
                lng,
                label: locationLabel.value || `TD# ${td.value.td_number}`,
                source: 'GIS pin',
            };
        }
    }

    const loc = td.value.location;
    if (loc?.latitude != null && loc?.longitude != null) {
        const lat = Number(loc.latitude);
        const lng = Number(loc.longitude);
        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
            const parts = [loc.street, loc.barangay, loc.municipality].filter(Boolean);
            return {
                lat,
                lng,
                label: parts.join(', ') || locationLabel.value || `TD# ${td.value.td_number}`,
                source: 'Property location',
            };
        }
    }

    const brgy = td.value.barangay;
    if (brgy?.latitude != null && brgy?.longitude != null) {
        const lat = Number(brgy.latitude);
        const lng = Number(brgy.longitude);
        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
            return {
                lat,
                lng,
                label: [brgy.name, td.value.municipality?.name, td.value.municipality?.province].filter(Boolean).join(', '),
                source: 'Barangay location',
            };
        }
    }

    return null;
});

function openWorkflow(status, label, icon) {
    workflowAction.value = { status, label, icon };
    workflowRemarks.value = '';
    showWorkflowDialog.value = true;
}

function formatTransferDate(value) {
    if (!value) return null;
    if (value instanceof Date) {
        const y = value.getFullYear();
        const m = String(value.getMonth() + 1).padStart(2, '0');
        const d = String(value.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    return String(value).slice(0, 10);
}

function openTransferDialog() {
    const status = String(td.value?.status || '').toLowerCase();
    if (td.value?.is_locked && status !== 'approved') {
        toast.error('Locked', 'This record is locked. Return or unlock it before transferring ownership.');
        return;
    }
    if (status === 'archived') {
        toast.error('Already cancelled', 'This TD is already archived. Open the successor TD to record further transfers.');
        return;
    }
    resetTransferForm();
    showTransferDialog.value = true;
}

function resetTransferForm() {
    transferOwnerSearch.value = '';
    transferOwnerSuggestions.value = [];
    transferForm.value = {
        new_td_number: '',
        new_arp_number: '',
        owner_id: null,
        owner_name: '',
        owner_tin: '',
        owner_address: '',
        owner_telephone: '',
        transfer_date: new Date(),
        transfer_reason: '',
        remarks: '',
    };
}

async function searchTransferOwners(event) {
    try {
        const { data } = await axios.get('property-owners', { params: { search: event.query } });
        transferOwnerSuggestions.value = data.data || [];
    } catch {
        transferOwnerSuggestions.value = [];
    }
}

function onTransferOwnerSelect(event) {
    const owner = event.value;
    if (!owner?.id) return;
    transferForm.value.owner_id = owner.id;
    transferForm.value.owner_name = owner.owner_name || '';
    transferForm.value.owner_tin = owner.tin || '';
    transferForm.value.owner_address = owner.address || '';
    transferForm.value.owner_telephone = owner.contact_number || '';
    transferOwnerSearch.value = owner.owner_name || '';
}

function clearTransferOwnerSelection() {
    transferForm.value.owner_id = null;
    transferOwnerSearch.value = '';
}

async function submitTransfer() {
    if (!transferForm.value.new_td_number?.trim()) {
        toast.error('Missing TD number', 'Enter the new TD number that will be issued.');
        return;
    }
    if (transferForm.value.new_td_number.trim() === (td.value?.td_number || '').trim()) {
        toast.error('Same TD number', 'New TD number must be different from the current TD.');
        return;
    }
    if (!transferForm.value.owner_id) {
        const typedName = typeof transferOwnerSearch.value === 'string'
            ? transferOwnerSearch.value.trim()
            : (transferOwnerSearch.value?.owner_name || '');
        if (!transferForm.value.owner_name?.trim() && typedName) {
            transferForm.value.owner_name = typedName;
        }
    }
    if (!transferForm.value.owner_id && !transferForm.value.owner_name?.trim()) {
        toast.error('Missing owner', 'Select an existing owner or enter a new owner name.');
        return;
    }
    if (!transferForm.value.transfer_date) {
        toast.error('Missing date', 'Transfer date is required.');
        return;
    }
    if (transferForm.value.owner_id && Number(transferForm.value.owner_id) === Number(td.value?.owner_id)) {
        toast.error('Same owner', 'Choose a different owner than the current one.');
        return;
    }

    transferLoading.value = true;
    try {
        const payload = {
            new_td_number: transferForm.value.new_td_number.trim(),
            new_arp_number: transferForm.value.new_arp_number?.trim() || undefined,
            owner_id: transferForm.value.owner_id || undefined,
            owner_name: transferForm.value.owner_name?.trim() || undefined,
            owner_tin: transferForm.value.owner_tin || undefined,
            owner_address: transferForm.value.owner_address || undefined,
            owner_telephone: transferForm.value.owner_telephone || undefined,
            transfer_date: formatTransferDate(transferForm.value.transfer_date),
            transfer_reason: transferForm.value.transfer_reason || undefined,
            remarks: transferForm.value.remarks || undefined,
        };
        const { data } = await axios.post(`tax-declarations/${td.value.id}/transfer-ownership`, payload);
        toast.success('Transferred', `New TD ${payload.new_td_number} issued. Old TD cancelled.`);
        showTransferDialog.value = false;
        const newId = data?.new_tax_declaration_id || data?.new_tax_declaration?.id;
        if (newId) {
            router.push(`/tax-declarations/${newId}`);
        } else {
            await loadTd(true);
        }
    } catch (err) {
        toast.apiError(err, 'Ownership transfer failed');
    } finally {
        transferLoading.value = false;
    }
}

async function submitWorkflow() {
    workflowLoading.value = true;
    try {
        await axios.post(`tax-declarations/${td.value.id}/status`, {
            status: workflowAction.value.status,
            remarks: workflowRemarks.value,
        });
        toast.success('Updated', `Status changed to ${workflowAction.value.label}.`);
        showWorkflowDialog.value = false;
        await loadTd(true);
    } catch (err) {
        toast.apiError(err, 'Workflow update failed');
    } finally {
        workflowLoading.value = false;
    }
}

function confirmUnlock() {
    if (!td.value?.is_locked) return;
    confirm.require({
        header: 'Unlock this record?',
        message: `TD ${td.value.td_number} is locked. Unlocking will allow editing again.`,
        icon: 'pi pi-lock-open',
        acceptLabel: 'Unlock',
        rejectLabel: 'Cancel',
        acceptSeverity: 'warn',
        accept: () => unlockTd(),
    });
}

async function unlockTd() {
    unlockLoading.value = true;
    try {
        const { data } = await axios.post(`tax-declarations/${td.value.id}/unlock`);
        td.value = data;
        toast.success('Unlocked', 'This declaration can now be edited.');
    } catch (err) {
        toast.apiError(err, 'Failed to unlock record');
    } finally {
        unlockLoading.value = false;
    }
}

async function generatePdf() {
    const res = await axios.get(`tax-declarations/${td.value.id}/pdf`, { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    window.open(url, '_blank');
}

async function downloadDoc(doc) {
    const res = await axios.get(`documents/${doc.id}/download`, { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a'); a.href = url; a.download = doc.file_name; a.click();
}

function onImgFileSelect(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    imgForm.value.file = file;
}

function resetImgForm() {
    imgForm.value = { image_type: 'additional', caption: '', file: null };
    if (imgFileInput.value) imgFileInput.value.value = '';
}

async function submitImgUpload() {
    if (!imgForm.value.file) {
        toast.error('Missing photo', 'Please select an image to upload.');
        return;
    }

    imgUploadLoading.value = true;
    try {
        const fd = new FormData();
        fd.append('image', imgForm.value.file);
        fd.append('image_type', imgForm.value.image_type);
        if (imgForm.value.caption?.trim()) {
            fd.append('caption', imgForm.value.caption.trim());
        }
        await axios.post(`tax-declarations/${td.value.id}/images`, fd);
        toast.success('Uploaded', 'Property photo added.');
        showImgUpload.value = false;
        resetImgForm();
        await loadImages();
    } catch (err) {
        toast.apiError(err, 'Photo upload failed');
    } finally {
        imgUploadLoading.value = false;
    }
}

async function deleteDoc(doc) {
    if (!window.confirm(`Remove "${doc.title}"?`)) return;
    try {
        await axios.delete(`documents/${doc.id}`);
        toast.success('Removed', 'Document deleted.');
        await loadTd(true);
    } catch (err) {
        toast.apiError(err, 'Delete failed');
    }
}

async function deleteImage(img) {
    if (!window.confirm('Remove this photo?')) return;
    try {
        await axios.delete(`images/${img.id}`);
        toast.success('Removed', 'Photo deleted.');
        await loadImages();
    } catch (err) {
        toast.apiError(err, 'Delete failed');
    }
}

function formatStatus(status) { return status?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || ''; }
function statusSeverity(s) {
    return { draft: 'secondary', approved: 'success', rejected: 'danger', returned: 'warn', archived: 'secondary' }[s] || 'info';
}
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'; }
function formatMoney(val) {
    if (val === null || val === undefined || val === '') return '—';
    return Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}
function formatFileSize(bytes) {
    if (!bytes) return '';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`;
}

async function loadTd(silent = false) {
    if (!silent) {
        loading.value = true;
        loadError.value = null;
    }
    try {
        const res = await axios.get(`tax-declarations/${route.params.id}`);
        td.value = res.data;
    } catch (err) {
        if (!silent) {
            loadError.value = 'Could not load this tax declaration.';
            toast.apiError(err, 'Failed to load declaration');
        }
    } finally {
        if (!silent) loading.value = false;
    }
}

async function loadImages() {
    try {
        const res = await axios.get(`tax-declarations/${route.params.id}/images`);
        images.value = Array.isArray(res.data) ? res.data : (res.data?.data ?? []);
    } catch {
        images.value = [];
    }
}

async function loadClassifications() {
    try {
        const res = await axios.get('/settings/classifications');
        classifications.value = res.data;
    } catch {
        classifications.value = [];
    }
}

onMounted(() => { loadTd(); loadImages(); loadClassifications(); });
</script>

