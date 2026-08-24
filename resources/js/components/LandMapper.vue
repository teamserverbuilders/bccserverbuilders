<template>
    <div class="land-mapper relative w-full h-full min-h-[420px] rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900">
        <!-- Floating toolbar -->
        <div class="absolute top-3 left-3 right-3 z-[1000] flex flex-col gap-2 pointer-events-none">
            <div class="flex flex-col sm:flex-row gap-2 pointer-events-auto">
                <div class="flex-1 flex items-center gap-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur rounded-lg shadow-md border border-slate-200 dark:border-slate-700 p-1">
                    <InputText
                        v-model="searchQuery"
                        placeholder="Search location (barangay, city, address)…"
                        class="flex-1 !border-0 !shadow-none bg-transparent text-sm"
                        @keyup.enter="searchLocation"
                    />
                    <Button icon="pi pi-search" size="small" text rounded :loading="searching" @click="searchLocation" v-tooltip="'Search'" />
                </div>

                <div class="flex flex-wrap items-center gap-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur rounded-lg shadow-md border border-slate-200 dark:border-slate-700 p-1">
                    <Button icon="pi pi-map-marker" size="small" text rounded v-tooltip="'Locate Me'" :loading="locating" @click="locateMe" />
                    <Button
                        icon="pi pi-pencil"
                        size="small"
                        :text="!drawingActive"
                        :severity="drawingActive ? 'warn' : undefined"
                        rounded
                        v-tooltip="'Draw Polygon'"
                        :disabled="!editable"
                        @click="startDraw"
                    />
                    <Button
                        icon="pi pi-file-edit"
                        size="small"
                        :text="!editingActive"
                        :severity="editingActive ? 'info' : undefined"
                        rounded
                        v-tooltip="'Edit Polygon'"
                        :disabled="!editable || !hasPolygon"
                        @click="toggleEdit"
                    />
                    <Button
                        icon="pi pi-undo"
                        size="small"
                        text
                        rounded
                        v-tooltip="'Undo last point'"
                        :disabled="!drawingActive"
                        @click="undoLastVertex"
                    />
                    <Button
                        icon="pi pi-trash"
                        size="small"
                        text
                        rounded
                        severity="danger"
                        v-tooltip="'Delete Polygon'"
                        :disabled="!editable || !hasPolygon"
                        @click="deletePolygon"
                    />
                    <div class="w-px h-6 bg-slate-200 dark:bg-slate-700 mx-0.5 hidden sm:block"></div>
                    <Button
                        label="Save Land"
                        icon="pi pi-save"
                        size="small"
                        class="!text-xs"
                        :loading="saving"
                        :disabled="!editable || !hasPolygon || !dirty"
                        @click="emitSave"
                    />
                    <Button
                        label="Cancel"
                        icon="pi pi-times"
                        size="small"
                        outlined
                        class="!text-xs"
                        :disabled="!editable || (!dirty && !drawingActive && !editingActive)"
                        @click="cancelChanges"
                    />
                </div>
            </div>
            <p
                v-if="drawingActive || editingActive"
                class="pointer-events-none text-[11px] text-slate-600 dark:text-slate-300 bg-white/90 dark:bg-slate-900/90 rounded-md px-2 py-1 shadow border border-slate-200 dark:border-slate-700 w-fit"
            >
                <template v-if="drawingActive">
                    Click to add points · Undo / Backspace removes last point · Finish on first point
                </template>
                <template v-else>
                    Drag a vertex to move · Click a vertex to remove it (keep at least 3)
                </template>
            </p>
        </div>

        <div ref="mapEl" class="absolute inset-0 z-0"></div>

        <!-- Measurement panel -->
        <div class="absolute bottom-3 left-3 right-3 lg:right-auto z-[1000] flex flex-col gap-2 pointer-events-none max-w-full lg:max-w-md">
            <div class="pointer-events-auto bg-white/95 dark:bg-slate-900/95 backdrop-blur rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-3 py-2 bg-[#1a3557] text-white flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide">Measurements</span>
                    <span v-if="stats.vertexCount" class="text-[10px] opacity-80">{{ stats.vertexCount }} sides</span>
                </div>
                <div class="p-3 space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Area</p>
                            <p class="text-lg font-bold text-[#1a3557] dark:text-amber-400 leading-tight">
                                {{ areaSqmLabel }}
                            </p>
                            <p v-if="stats.area != null && stats.area >= 100" class="text-[11px] text-slate-500 mt-0.5">
                                {{ areaHaLabel }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Perimeter</p>
                            <p class="text-lg font-bold text-slate-800 dark:text-slate-100 leading-tight">
                                {{ perimeterLabel }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Total boundary length</p>
                        </div>
                    </div>

                    <div v-if="stats.sides.length" class="border-t border-slate-100 dark:border-slate-700 pt-2">
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1.5">Side lengths</p>
                        <div class="max-h-28 overflow-y-auto space-y-1 pr-1">
                            <div
                                v-for="side in stats.sides"
                                :key="side.index"
                                class="flex items-center justify-between text-xs gap-2"
                            >
                                <span class="text-slate-500">Side {{ side.index }}</span>
                                <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ formatMeters(side.length) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-700 pt-2">
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-0.5">Centroid</p>
                        <p class="text-xs font-mono text-slate-700 dark:text-slate-300">
                            <template v-if="stats.centroid">
                                {{ stats.centroid.lat.toFixed(6) }}, {{ stats.centroid.lng.toFixed(6) }}
                            </template>
                            <template v-else-if="mouseCoords">
                                Cursor: {{ mouseCoords.lat.toFixed(6) }}, {{ mouseCoords.lng.toFixed(6) }}
                            </template>
                            <template v-else>Draw a polygon to measure</template>
                        </p>
                        <p v-if="dirty" class="text-[10px] text-amber-600 mt-1">Unsaved changes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * LandMapper — Leaflet + Leaflet.draw land boundary editor with live measurements.
 */
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-draw';
import 'leaflet-draw/dist/leaflet.draw.css';
import * as turf from '@turf/turf';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { useToast } from '@/composables/useToast';

if (typeof window !== 'undefined') {
    window.L = L;
}

const props = defineProps({
    modelValue: { type: Object, default: null },
    editable: { type: Boolean, default: true },
    center: { type: Array, default: () => [13.4569, 123.3756] },
    zoom: { type: Number, default: 15 },
    locateOnMount: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'save', 'change', 'cancel', 'ready']);

const toast = useToast();
const mapEl = ref(null);

const searchQuery = ref('');
const searching = ref(false);
const locating = ref(false);
const saving = ref(false);
const drawingActive = ref(false);
const editingActive = ref(false);
const dirty = ref(false);
const hasPolygon = ref(false);
const mouseCoords = ref(null);

const stats = reactive({
    area: null,
    perimeter: null,
    centroid: null,
    vertexCount: 0,
    sides: [],
});

let map = null;
let drawnItems = null;
let measureLayer = null;
let polygonDrawer = null;
let editHandler = null;
let userMarker = null;
let currentPolygon = null;
let drawPreviewHandler = null;

const POLYGON_STYLE = {
    color: '#1a3557',
    weight: 3,
    opacity: 1,
    fillColor: '#3b82f6',
    fillOpacity: 0.25,
};

const SELECTED_STYLE = {
    color: '#b8860b',
    weight: 4,
    opacity: 1,
    fillColor: '#f59e0b',
    fillOpacity: 0.3,
};

const areaSqmLabel = computed(() => {
    if (stats.area == null) return '— m²';
    return `${formatNumber(stats.area)} m²`;
});

const areaHaLabel = computed(() => {
    if (stats.area == null) return '';
    return `${(stats.area / 10000).toFixed(4)} hectares`;
});

const perimeterLabel = computed(() => {
    if (stats.perimeter == null) return '—';
    return formatMeters(stats.perimeter);
});

function formatNumber(n) {
    return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function formatMeters(m) {
    if (m == null) return '—';
    if (m >= 1000) return `${(m / 1000).toFixed(3)} km`;
    return `${formatNumber(m)} m`;
}

function closeRing(coords) {
    if (!coords?.length) return [];
    const ring = coords.map(([lat, lng]) => [Number(lat), Number(lng)]);
    const [fLat, fLng] = ring[0];
    const [lLat, lLng] = ring[ring.length - 1];
    if (fLat !== lLat || fLng !== lLng) {
        ring.push([fLat, fLng]);
    }
    return ring;
}

function layerToCoordinates(layer) {
    const latlngs = layer.getLatLngs()?.[0] || layer.getLatLngs() || [];
    return closeRing(latlngs.map((ll) => [ll.lat, ll.lng]));
}

function latLngsToOpenCoords(latlngs) {
    if (!latlngs?.length) return [];
    return latlngs.map((ll) => [ll.lat, ll.lng]);
}

function distanceMeters(a, b) {
    return turf.distance(
        turf.point([a[1], a[0]]),
        turf.point([b[1], b[0]]),
        { units: 'meters' },
    );
}

function computeSideLengths(coordinates) {
    const ring = closeRing(coordinates);
    if (ring.length < 2) return [];
    const sides = [];
    for (let i = 0; i < ring.length - 1; i += 1) {
        sides.push({
            index: i + 1,
            length: distanceMeters(ring[i], ring[i + 1]),
            mid: [
                (ring[i][0] + ring[i + 1][0]) / 2,
                (ring[i][1] + ring[i + 1][1]) / 2,
            ],
        });
    }
    return sides;
}

function coordinatesToTurfPolygon(coordinates) {
    const ring = closeRing(coordinates).map(([lat, lng]) => [lng, lat]);
    return turf.polygon([ring]);
}

function clearMeasureLabels() {
    if (measureLayer) measureLayer.clearLayers();
}

const VERTEX_ICON = () => new L.DivIcon({
    iconSize: new L.Point(12, 12),
    iconAnchor: new L.Point(6, 6),
    className: 'leaflet-div-icon leaflet-editing-icon land-vertex-icon',
});

const VERTEX_TOUCH_ICON = () => new L.DivIcon({
    iconSize: new L.Point(16, 16),
    iconAnchor: new L.Point(8, 8),
    className: 'leaflet-div-icon leaflet-editing-icon leaflet-touch-icon land-vertex-icon',
});

/**
 * Leaflet.draw guide dashes are absolute-positioned pixels — they stick after zoom.
 * Clear them from the overlay pane whenever zoom changes or drawing stops.
 */
function clearDrawGuides() {
    try {
        polygonDrawer?._clearGuides?.();
    } catch {
        // ignore
    }
    if (!map) return;
    const pane = map.getPanes()?.overlayPane;
    if (!pane) return;
    pane.querySelectorAll('.leaflet-draw-guides').forEach((el) => {
        while (el.firstChild) el.removeChild(el.firstChild);
    });
    // Orphan mouse markers left by draw handler
    pane.querySelectorAll('.leaflet-mouse-marker').forEach((el) => {
        el.remove();
    });
}

function makeLabelIcon(html, className = 'land-measure-label') {
    return L.divIcon({
        className,
        html: `<div class="land-measure-pill">${html}</div>`,
        iconSize: [1, 1],
        iconAnchor: [0, 0],
    });
}

/**
 * Scale on-map measurement text with zoom so labels shrink when zoomed out
 * and grow when zoomed in for close editing.
 */
function measureScaleForZoom(zoom) {
    // Reference: zoom 16 ≈ scale 1
    // zoom 12 ≈ 0.4, zoom 14 ≈ 0.65, zoom 18 ≈ 1.25, zoom 22 ≈ 1.7
    const scale = 0.28 + ((zoom - 11) * 0.14);
    return Math.min(1.75, Math.max(0.32, scale));
}

function applyMeasureZoomScale() {
    if (!map || !mapEl.value) return;
    const scale = measureScaleForZoom(map.getZoom());
    mapEl.value.style.setProperty('--measure-scale', String(scale));

    // Soft-hide area label when very zoomed out (still readable in side panel)
    const showAreaOnMap = map.getZoom() >= 13;
    mapEl.value.classList.toggle('measure-hide-area', !showAreaOnMap);
}

function refreshMeasureLabels(coordinates) {
    clearMeasureLabels();
    if (!map || !measureLayer || !coordinates?.length) return;

    applyMeasureZoomScale();
    const ring = closeRing(coordinates);

    // Open polyline (still drawing)
    if (ring.length < 4) {
        const open = coordinates;
        for (let i = 0; i < open.length - 1; i += 1) {
            const len = distanceMeters(open[i], open[i + 1]);
            const mid = [
                (open[i][0] + open[i + 1][0]) / 2,
                (open[i][1] + open[i + 1][1]) / 2,
            ];
            L.marker(mid, {
                icon: makeLabelIcon(formatMeters(len), 'land-measure-label land-side-label'),
                interactive: false,
                keyboard: false,
            }).addTo(measureLayer);
        }
        return;
    }

    computeSideLengths(ring).forEach((side) => {
        L.marker(side.mid, {
            icon: makeLabelIcon(formatMeters(side.length), 'land-measure-label land-side-label'),
            interactive: false,
            keyboard: false,
        }).addTo(measureLayer);
    });

    if (stats.centroid && stats.area != null) {
        const html = `<div class="land-area-text"><strong>${formatNumber(stats.area)} m²</strong><span>${formatMeters(stats.perimeter)} around</span></div>`;
        L.marker([stats.centroid.lat, stats.centroid.lng], {
            icon: makeLabelIcon(html, 'land-measure-label land-area-label'),
            interactive: false,
            keyboard: false,
        }).addTo(measureLayer);
    }
}

function recomputeStatsFromCoordinates(coordinates, { closed = true } = {}) {
    if (!coordinates?.length) {
        stats.area = null;
        stats.perimeter = null;
        stats.centroid = null;
        stats.vertexCount = 0;
        stats.sides = [];
        clearMeasureLabels();
        return;
    }

    // Live draw (not closed yet): side lengths only
    if (!closed || coordinates.length < 3) {
        const sides = [];
        for (let i = 0; i < coordinates.length - 1; i += 1) {
            sides.push({
                index: i + 1,
                length: distanceMeters(coordinates[i], coordinates[i + 1]),
                mid: [
                    (coordinates[i][0] + coordinates[i + 1][0]) / 2,
                    (coordinates[i][1] + coordinates[i + 1][1]) / 2,
                ],
            });
        }
        stats.sides = sides;
        stats.perimeter = sides.reduce((sum, s) => sum + s.length, 0);
        stats.area = null;
        stats.centroid = null;
        stats.vertexCount = Math.max(0, coordinates.length - 1);
        refreshMeasureLabels(coordinates);
        return;
    }

    const ring = closeRing(coordinates);
    stats.vertexCount = Math.max(0, ring.length - 1);
    stats.sides = computeSideLengths(ring);

    try {
        const poly = coordinatesToTurfPolygon(ring);
        stats.area = turf.area(poly);
        stats.perimeter = turf.length(poly, { units: 'meters' });
        const c = turf.centroid(poly);
        const [lng, lat] = c.geometry.coordinates;
        stats.centroid = { lat, lng };
    } catch {
        stats.area = null;
        stats.perimeter = stats.sides.reduce((sum, s) => sum + s.length, 0);
        stats.centroid = null;
    }

    refreshMeasureLabels(ring);
}

function recomputeStats(layer) {
    if (!layer) {
        recomputeStatsFromCoordinates([]);
        return;
    }
    recomputeStatsFromCoordinates(layerToCoordinates(layer), { closed: true });
}

function buildPayload() {
    if (!currentPolygon) return null;
    const coordinates = layerToCoordinates(currentPolygon);
    recomputeStats(currentPolygon);

    return {
        coordinates,
        latitude: stats.centroid?.lat ?? coordinates[0][0],
        longitude: stats.centroid?.lng ?? coordinates[0][1],
        area: Number((stats.area ?? 0).toFixed(4)),
        perimeter: stats.perimeter != null ? Number(stats.perimeter.toFixed(4)) : null,
        created_at: new Date().toISOString(),
    };
}

function clearDrawnLayers() {
    if (drawnItems) drawnItems.clearLayers();
    clearMeasureLabels();
    currentPolygon = null;
    hasPolygon.value = false;
    recomputeStatsFromCoordinates([]);
}

function setPolygonFromCoordinates(coordinates) {
    if (!map || !drawnItems) return;
    clearDrawnLayers();
    const ring = closeRing(coordinates);
    if (ring.length < 4) return;

    currentPolygon = L.polygon(ring.map(([lat, lng]) => L.latLng(lat, lng)), { ...SELECTED_STYLE });
    drawnItems.addLayer(currentPolygon);
    hasPolygon.value = true;
    recomputeStats(currentPolygon);
    map.fitBounds(currentPolygon.getBounds(), { padding: [40, 40], maxZoom: 20 });
}

function stopDrawTools() {
    clearDrawGuides();
    if (polygonDrawer) {
        try {
            polygonDrawer.disable();
        } catch {
            // ignore
        }
        polygonDrawer = null;
    }
    if (editHandler) {
        try {
            editHandler.disable();
        } catch {
            // ignore
        }
        editHandler = null;
    }
    if (map && drawPreviewHandler) {
        map.off(L.Draw.Event.DRAWVERTEX, drawPreviewHandler);
        drawPreviewHandler = null;
    }
    clearDrawGuides();
    drawingActive.value = false;
    editingActive.value = false;
}

function onDrawVertexPreview(cursorLatLng = null) {
    clearDrawGuides();
    try {
        const markers = polygonDrawer?._markers || [];
        const latlngs = markers.map((m) => m.getLatLng());
        if (!latlngs.length) {
            recomputeStatsFromCoordinates([]);
            return;
        }

        const coords = latLngsToOpenCoords(latlngs);

        // Rubber-band: include cursor so range updates before the next click
        if (cursorLatLng) {
            coords.push([cursorLatLng.lat, cursorLatLng.lng]);
        }

        if (coords.length >= 2) {
            recomputeStatsFromCoordinates(coords, { closed: false });
        } else {
            recomputeStatsFromCoordinates([]);
        }
    } catch {
        // ignore mid-draw preview errors
    }
}

function undoLastVertex() {
    if (!polygonDrawer || !drawingActive.value) return;
    try {
        if (typeof polygonDrawer.deleteLastVertex === 'function') {
            polygonDrawer.deleteLastVertex();
        }
        clearDrawGuides();
        onDrawVertexPreview(
            mouseCoords.value
                ? L.latLng(mouseCoords.value.lat, mouseCoords.value.lng)
                : null,
        );
    } catch {
        toast.warn('Undo', 'No point left to remove.');
    }
}

function onDrawKeydown(e) {
    if (!drawingActive.value && !editingActive.value) return;
    if (e.key === 'Backspace' || e.key === 'Delete') {
        // Avoid deleting browser history / form fields
        if (e.target && ['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
        e.preventDefault();
        if (drawingActive.value) undoLastVertex();
    }
    if (e.key === 'Escape') {
        e.preventDefault();
        cancelChanges();
    }
}

function startDraw() {
    if (!map || !props.editable) return;
    stopDrawTools();
    clearDrawnLayers();
    dirty.value = true;

    polygonDrawer = new L.Draw.Polygon(map, {
        allowIntersection: false,
        showArea: false, // we show our own measurements
        metric: true,
        feet: false,
        showLength: false,
        guidelineDistance: 24,
        icon: VERTEX_ICON(),
        touchIcon: VERTEX_TOUCH_ICON(),
        shapeOptions: { ...POLYGON_STYLE },
    });
    polygonDrawer.enable();
    drawingActive.value = true;

    drawPreviewHandler = () => onDrawVertexPreview(mouseCoords.value ? L.latLng(mouseCoords.value.lat, mouseCoords.value.lng) : null);
    map.on(L.Draw.Event.DRAWVERTEX, drawPreviewHandler);
}

function toggleEdit() {
    if (!map || !currentPolygon || !props.editable) return;

    if (editingActive.value) {
        stopDrawTools();
        currentPolygon.setStyle(SELECTED_STYLE);
        recomputeStats(currentPolygon);
        return;
    }

    stopDrawTools();
    currentPolygon.setStyle(SELECTED_STYLE);

    // Smaller vertex handles for edit mode
    if (L.Edit?.PolyVerticesEdit) {
        L.Edit.PolyVerticesEdit.mergeOptions({
            icon: VERTEX_ICON(),
            touchIcon: VERTEX_TOUCH_ICON(),
        });
    }

    editHandler = new L.EditToolbar.Edit(map, {
        featureGroup: drawnItems,
        selectedPathOptions: {
            maintainColor: false,
            color: '#b8860b',
            weight: 4,
        },
    });
    editHandler.enable();
    editingActive.value = true;

    currentPolygon.on('edit', () => {
        dirty.value = true;
        recomputeStats(currentPolygon);
    });
    currentPolygon.on('editdrag', () => {
        dirty.value = true;
        recomputeStats(currentPolygon);
    });

    // After edit enables, attach contextmenu on vertex markers for remove
    setTimeout(() => bindVertexRemoveHints(), 50);
}

/**
 * Leaflet.draw already removes a vertex on click (if > 3 remain).
 * Also allow right-click, and show a cursor hint.
 */
function bindVertexRemoveHints() {
    if (!map) return;
    map.eachLayer((layer) => {
        if (layer instanceof L.Marker && layer.options?.icon?.options?.className?.includes('leaflet-editing-icon')) {
            layer.off('contextmenu');
            layer.on('contextmenu', (e) => {
                L.DomEvent.preventDefault(e);
                // Simulate click — Edit.PolyVerticesEdit removes on click
                layer.fire('click');
                dirty.value = true;
                if (currentPolygon) recomputeStats(currentPolygon);
            });
            if (!layer.getTooltip()) {
                layer.bindTooltip('Click to remove · drag to move', {
                    direction: 'top',
                    opacity: 0.9,
                    offset: [0, -6],
                });
            }
        }
    });
}

function deletePolygon() {
    if (!props.editable) return;
    stopDrawTools();
    clearDrawnLayers();
    dirty.value = true;
    emit('change', null);
}

function cancelChanges() {
    stopDrawTools();
    const initial = props.modelValue?.coordinates || props.modelValue?.boundary_polygon?.coordinates;
    if (initial?.length) {
        setPolygonFromCoordinates(initial);
        dirty.value = false;
    } else {
        clearDrawnLayers();
        dirty.value = false;
    }
    emit('cancel');
}

function emitSave() {
    const payload = buildPayload();
    if (!payload) {
        toast.warn('No polygon', 'Draw a land boundary before saving.');
        return;
    }
    stopDrawTools();
    if (currentPolygon) currentPolygon.setStyle(SELECTED_STYLE);
    dirty.value = false;
    emit('update:modelValue', payload);
    emit('save', payload);
}

async function locateMe() {
    if (!map || !navigator.geolocation) {
        toast.warn('Unavailable', 'Geolocation is not supported in this browser.');
        return;
    }
    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const { latitude, longitude } = pos.coords;
            map.setView([latitude, longitude], Math.max(map.getZoom(), 18));
            if (userMarker) {
                userMarker.setLatLng([latitude, longitude]);
            } else {
                userMarker = L.circleMarker([latitude, longitude], {
                    radius: 8,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#2563eb',
                    fillOpacity: 1,
                }).addTo(map).bindPopup('Your location');
            }
            locating.value = false;
        },
        () => {
            locating.value = false;
            toast.error('Location', 'Could not get your current location. Check browser permissions.');
        },
        { enableHighAccuracy: true, timeout: 12000 },
    );
}

async function searchLocation() {
    const q = searchQuery.value.trim();
    if (!q || !map) return;
    searching.value = true;
    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(q)}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!data?.length) {
            toast.warn('Not found', 'No matching location. Try a different search.');
            return;
        }
        const { lat, lon, display_name } = data[0];
        map.setView([Number(lat), Number(lon)], 16);
        L.popup().setLatLng([Number(lat), Number(lon)]).setContent(display_name).openOn(map);
    } catch {
        toast.error('Search failed', 'Unable to reach the geocoding service.');
    } finally {
        searching.value = false;
    }
}

function onDrawCreated(e) {
    stopDrawTools();
    clearDrawGuides();
    clearDrawnLayers();
    currentPolygon = e.layer;
    currentPolygon.setStyle(SELECTED_STYLE);
    drawnItems.addLayer(currentPolygon);
    hasPolygon.value = true;
    dirty.value = true;
    recomputeStats(currentPolygon);
    emit('change', buildPayload());
}

function onDrawEdited() {
    clearDrawGuides();
    if (currentPolygon) {
        dirty.value = true;
        recomputeStats(currentPolygon);
        emit('change', buildPayload());
        setTimeout(() => bindVertexRemoveHints(), 50);
    }
}

function onDrawDeleted() {
    clearDrawGuides();
    currentPolygon = null;
    hasPolygon.value = false;
    dirty.value = true;
    recomputeStatsFromCoordinates([]);
    emit('change', null);
}

function initMap() {
    if (!mapEl.value || map) return;

    // High maxZoom lets users get close for precise vertex placement.
    // OSM tiles are native to z19; beyond that Leaflet upscales tiles.
    map = L.map(mapEl.value, {
        center: props.center,
        zoom: props.zoom,
        minZoom: 5,
        maxZoom: 22,
        zoomControl: true,
        zoomSnap: 0.25,
        zoomDelta: 0.5,
        wheelPxPerZoomLevel: 80,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    L.control.scale({ imperial: false, metric: true }).addTo(map);
    applyMeasureZoomScale();

    drawnItems = new L.FeatureGroup();
    measureLayer = new L.FeatureGroup();
    map.addLayer(drawnItems);
    map.addLayer(measureLayer);

    map.on(L.Draw.Event.CREATED, onDrawCreated);
    map.on(L.Draw.Event.EDITED, onDrawEdited);
    map.on(L.Draw.Event.DELETED, onDrawDeleted);
    map.on('mousemove', (e) => {
        mouseCoords.value = { lat: e.latlng.lat, lng: e.latlng.lng };
        // Live distance to cursor while drawing (no click yet)
        if (drawingActive.value && polygonDrawer) {
            onDrawVertexPreview(e.latlng);
        }
    });

    // Prevent stuck guide dashes after zoom; keep label size in sync
    map.on('zoomstart', () => {
        clearDrawGuides();
    });
    map.on('zoom', () => {
        applyMeasureZoomScale();
    });
    map.on('zoomend', () => {
        clearDrawGuides();
        applyMeasureZoomScale();
        if (currentPolygon && !drawingActive.value) {
            recomputeStats(currentPolygon);
        } else if (drawingActive.value) {
            onDrawVertexPreview(
                mouseCoords.value
                    ? L.latLng(mouseCoords.value.lat, mouseCoords.value.lng)
                    : null,
            );
        }
        if (editingActive.value) {
            setTimeout(() => bindVertexRemoveHints(), 50);
        }
    });
    map.on('movestart', () => {
        clearDrawGuides();
    });

    window.addEventListener('keydown', onDrawKeydown);

    const initial = props.modelValue?.coordinates
        || props.modelValue?.boundary_polygon?.coordinates
        || null;
    if (initial?.length) {
        setPolygonFromCoordinates(initial);
        dirty.value = false;
    }

    setTimeout(() => map?.invalidateSize(), 150);
    emit('ready', map);

    if (props.locateOnMount) {
        locateMe();
    }
}

watch(
    () => props.modelValue,
    (val) => {
        if (!map) return;
        const coords = val?.coordinates || val?.boundary_polygon?.coordinates;
        if (coords?.length) {
            if (drawingActive.value || editingActive.value || dirty.value) return;
            setPolygonFromCoordinates(coords);
            dirty.value = false;
        } else if (!dirty.value) {
            clearDrawnLayers();
        }
    },
    { deep: true },
);

watch(
    () => props.editable,
    (canEdit) => {
        if (!canEdit) stopDrawTools();
    },
);

onMounted(() => initMap());

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onDrawKeydown);
    stopDrawTools();
    clearDrawGuides();
    if (map) {
        map.off();
        map.remove();
        map = null;
    }
    drawnItems = null;
    measureLayer = null;
    currentPolygon = null;
    userMarker = null;
});

defineExpose({
    startDraw,
    locateMe,
    getPayload: buildPayload,
    setPolygonFromCoordinates,
    clear: () => {
        stopDrawTools();
        clearDrawnLayers();
        dirty.value = false;
    },
    invalidateSize: () => map?.invalidateSize(),
    setSaving: (v) => { saving.value = !!v; },
    markClean: () => { dirty.value = false; },
});
</script>

<style scoped>
.land-mapper :deep(.leaflet-draw-toolbar) {
    display: none;
}

.land-mapper :deep(.leaflet-control-zoom) {
    margin-top: 70px;
}

@media (max-width: 640px) {
    .land-mapper :deep(.leaflet-control-zoom) {
        margin-top: 120px;
    }
}

/* Vertex handles — readable size, not tiny */
.land-mapper :deep(.leaflet-editing-icon.land-vertex-icon),
.land-mapper :deep(.leaflet-editing-icon) {
    margin-left: -6px !important;
    margin-top: -6px !important;
    width: 12px !important;
    height: 12px !important;
    border-radius: 2px !important;
    border: 2px solid #1a3557 !important;
    background: #fff !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
}

.land-mapper :deep(.leaflet-editing-icon.leaflet-touch-icon) {
    margin-left: -8px !important;
    margin-top: -8px !important;
    width: 16px !important;
    height: 16px !important;
}

/* Side length chips stay light */
.land-mapper :deep(.land-measure-label) {
    background: transparent !important;
    border: none !important;
}

.land-mapper :deep(.land-measure-pill) {
    transform: translate(-50%, -50%) scale(var(--measure-scale, 1));
    transform-origin: center center;
    white-space: nowrap;
    pointer-events: none;
    font-size: 11px;
    line-height: 1.25;
    padding: 2px 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.92);
    color: #1a3557;
    border: 1px solid rgba(26, 53, 87, 0.25);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
    font-weight: 600;
    text-align: center;
}

/* Area / perimeter — text only; scales with --measure-scale */
.land-mapper :deep(.land-area-label .land-measure-pill) {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    border-radius: 0 !important;
}

.land-mapper.measure-hide-area :deep(.land-area-label) {
    display: none;
}

.land-mapper :deep(.land-area-text) {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    color: #0f2744;
    text-shadow:
        0 0 4px #fff,
        0 0 6px #fff,
        1px 1px 0 #fff,
        -1px -1px 0 #fff,
        1px -1px 0 #fff,
        -1px 1px 0 #fff;
    font-weight: 700;
    line-height: 1.15;
    text-align: center;
}

.land-mapper :deep(.land-area-text strong) {
    font-size: 14px;
    letter-spacing: 0.01em;
}

.land-mapper :deep(.land-area-text span) {
    font-size: 10px;
    font-weight: 600;
    opacity: 0.95;
}
</style>
