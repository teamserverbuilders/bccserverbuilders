<template>
    <div class="land-map-page flex flex-col gap-3 h-[calc(100vh-7.5rem)] min-h-[520px]">
        <!-- Page header -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 shrink-0">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Land Mapping</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Draw land boundaries on OpenStreetMap and save them to a tax declaration
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 shrink-0">
                <Select
                    v-model="selectedTdId"
                    :options="tdOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select tax declaration"
                    filter
                    showClear
                    class="w-full sm:w-72"
                    :loading="loadingTds"
                    @change="onTdChange"
                />
                <Button
                    icon="pi pi-refresh"
                    outlined
                    size="small"
                    v-tooltip="'Reload boundary'"
                    :disabled="!selectedTdId"
                    :loading="loadingLand"
                    @click="loadLand"
                />
            </div>
        </div>

        <!-- Status strip -->
        <div
            v-if="selectedTd"
            class="shrink-0 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2"
        >
            <span>
                <span class="text-slate-400">TD</span>
                <RouterLink :to="`/tax-declarations/${selectedTd.value}`" class="ml-1 font-semibold text-blue-600 hover:underline">
                    {{ selectedTd.td_number }}
                </RouterLink>
            </span>
            <span v-if="selectedTd.owner">
                <span class="text-slate-400">Owner</span>
                <span class="ml-1 font-medium">{{ selectedTd.owner }}</span>
            </span>
            <span v-if="landMeta.updated_at" class="text-slate-400">
                Last saved {{ formatDate(landMeta.updated_at) }}
            </span>
            <span v-else class="text-amber-600">No saved boundary yet — draw and save</span>
        </div>
        <div
            v-else
            class="shrink-0 text-xs text-slate-500 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2"
        >
            Select a tax declaration to load or save a land boundary.
        </div>

        <!-- Full map workspace -->
        <div class="flex-1 min-h-0">
            <LandMapper
                ref="mapperRef"
                v-model="landData"
                :editable="!!selectedTdId"
                :locate-on-mount="true"
                @save="onSave"
                @cancel="onCancel"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import Select from 'primevue/select';
import LandMapper from '@/components/LandMapper.vue';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const toast = useToast();

const mapperRef = ref(null);
const tdOptions = ref([]);
const loadingTds = ref(false);
const loadingLand = ref(false);
const selectedTdId = ref(null);
const landData = ref(null);
const landMeta = ref({ updated_at: null, area: null });

const selectedTd = computed(() => tdOptions.value.find((t) => t.value === selectedTdId.value) || null);

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function loadTdOptions() {
    loadingTds.value = true;
    try {
        const res = await axios.get('tax-declarations', { params: { per_page: 500 } });
        const rows = res.data.data || res.data || [];
        tdOptions.value = rows.map((td) => ({
            value: td.id,
            td_number: td.td_number,
            owner: td.owner?.owner_name || '',
            label: `${td.td_number}${td.owner?.owner_name ? ` — ${td.owner.owner_name}` : ''}`,
        }));
    } catch (err) {
        toast.apiError(err, 'Failed to load tax declarations');
        tdOptions.value = [];
    } finally {
        loadingTds.value = false;
    }
}

async function loadLand() {
    if (!selectedTdId.value) {
        landData.value = null;
        landMeta.value = { updated_at: null, area: null };
        mapperRef.value?.clear?.();
        return;
    }

    loadingLand.value = true;
    try {
        const { data } = await axios.get(`gis/land/${selectedTdId.value}`);
        landMeta.value = {
            updated_at: data.updated_at || data.created_at,
            area: data.area,
        };

        if (data.coordinates?.length) {
            landData.value = {
                coordinates: data.coordinates,
                latitude: data.latitude,
                longitude: data.longitude,
                area: data.area,
                perimeter: data.perimeter,
                created_at: data.created_at,
            };
            // Ensure mapper redraws even if v-model watch skipped
            mapperRef.value?.setPolygonFromCoordinates?.(data.coordinates);
            mapperRef.value?.markClean?.();
        } else {
            landData.value = null;
            mapperRef.value?.clear?.();
            // Center on saved pin if available
            if (data.latitude && data.longitude && mapperRef.value) {
                // LandMapper doesn't expose setView — invalidate is enough; user can locate
            }
        }
    } catch (err) {
        toast.apiError(err, 'Failed to load land boundary');
    } finally {
        loadingLand.value = false;
        setTimeout(() => mapperRef.value?.invalidateSize?.(), 100);
    }
}

async function onTdChange() {
    await loadLand();
}

async function onSave(payload) {
    if (!selectedTdId.value) {
        toast.warn('Required', 'Select a tax declaration first.');
        return;
    }

    mapperRef.value?.setSaving?.(true);
    try {
        const body = {
            tax_declaration_id: selectedTdId.value,
            coordinates: payload.coordinates,
            latitude: payload.latitude,
            longitude: payload.longitude,
            area: payload.area,
            perimeter: payload.perimeter,
        };
        const { data } = await axios.post('gis/land', body);
        landData.value = {
            coordinates: data.coordinates,
            latitude: data.latitude,
            longitude: data.longitude,
            area: data.area,
            perimeter: data.perimeter,
            created_at: data.created_at,
        };
        landMeta.value = {
            updated_at: data.updated_at || data.created_at,
            area: data.area,
        };
        mapperRef.value?.markClean?.();
        toast.success('Saved', `Land boundary saved (${Number(data.area).toLocaleString()} m²).`);
    } catch (err) {
        toast.apiError(err, 'Failed to save land boundary');
    } finally {
        mapperRef.value?.setSaving?.(false);
    }
}

function onCancel() {
    // Reloads from last saved state via LandMapper cancel; refresh meta display
    if (selectedTdId.value) loadLand();
}

onMounted(async () => {
    await loadTdOptions();

    const qTd = route.query.td_id || route.query.td;
    if (qTd) {
        selectedTdId.value = Number(qTd);
        await loadLand();
    }
});
</script>
