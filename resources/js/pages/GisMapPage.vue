<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">GIS Map</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Interactive property map with pin locations</p>
            </div>
            <div class="flex items-center gap-2">
                <RouterLink to="/land-map">
                    <Button label="Land Mapping" icon="pi pi-sitemap" outlined size="small" />
                </RouterLink>
                <Button label="Export" icon="pi pi-download" outlined size="small" />
                <Button label="Add Pin" icon="pi pi-map-marker" size="small" @click="pinMode = !pinMode" :severity="pinMode ? 'danger' : 'primary'" />
            </div>
        </div>

        <!-- Map Container -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Toolbar -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex-wrap">
                <InputText v-model="searchQuery" placeholder="Search address or coordinates..." size="small" class="w-64" @keyup.enter="searchAddress" />
                <Button icon="pi pi-search" size="small" outlined @click="searchAddress" v-tooltip="'Search'" />

                <div class="flex items-center gap-1 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                    <button v-for="view in mapViews" :key="view.id"
                        @click="setMapView(view)"
                        :class="['px-3 py-1.5 text-xs transition-colors', activeView === view.id ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700']">
                        {{ view.label }}
                    </button>
                </div>

                <Select v-model="filterClassification" :options="classificationOptions" optionLabel="name" optionValue="id"
                    placeholder="All Classifications" showClear size="small" class="w-44" @change="filterMarkers" />

                <div class="ml-auto flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span> {{ markers.length }} properties
                    <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                    {{ markers.filter(m => m.boundary_polygon?.coordinates?.length || (Array.isArray(m.boundary_polygon) && m.boundary_polygon.length)).length }} land boundaries
                    <span class="w-2 h-2 bg-[#1a3557] rounded-full"></span> {{ barangayLocations.length }} saved locations
                    <span v-if="pinMode" class="text-orange-600 font-medium animate-pulse">· Pin Mode Active</span>
                </div>
            </div>

            <!-- Map -->
            <div class="relative">
                <div id="gis-map" class="w-full" style="height: calc(100vh - 280px); min-height: 500px;"></div>

                <!-- Loading overlay -->
                <div v-if="mapLoading" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center z-10">
                    <div class="text-center">
                        <ProgressSpinner class="w-10 h-10" />
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Loading map data...</p>
                    </div>
                </div>

                <!-- Coordinate + zoom estimate -->
                <div class="absolute bottom-4 left-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 py-2 text-xs text-gray-600 dark:text-gray-400 z-10 space-y-1.5 min-w-[200px]">
                    <div class="flex items-center gap-1.5">
                        <i class="pi pi-map-marker"></i>
                        <span v-if="mouseCoords">{{ mouseCoords.lat.toFixed(6) }}, {{ mouseCoords.lng.toFixed(6) }}</span>
                        <span v-else>Hover over map</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-1.5">
                        <span class="font-mono">
                            Zoom: <strong class="text-gray-800 dark:text-gray-200">{{ currentZoom.toFixed(1) }}</strong>
                            <span class="text-gray-400 mx-1">·</span>
                            Meters at
                            <strong :class="currentZoom >= metersZoomLevel ? 'text-green-600' : 'text-amber-600'">{{ metersZoomLevel }}</strong>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="shrink-0 text-[10px] uppercase tracking-wide text-gray-400">Show meters ≥</label>
                        <input
                            type="range"
                            min="14"
                            max="22"
                            step="1"
                            v-model.number="metersZoomLevel"
                            class="w-full accent-blue-600"
                            @input="onMetersZoomThresholdChange"
                        />
                        <span class="font-mono font-semibold text-gray-800 dark:text-gray-200 w-5 text-right">{{ metersZoomLevel }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Info Sidebar -->
        <Transition name="slide-right">
            <div v-if="selectedProperty" class="fixed right-4 top-20 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-800 dark:text-white text-sm">Property Details</h3>
                    <Button icon="pi pi-times" size="small" text rounded @click="selectedProperty = null" />
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full shrink-0" :style="{ background: selectedProperty.color }"></div>
                        <Tag :value="selectedProperty.classification" class="text-xs" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">TD Number</p>
                        <p class="text-sm font-bold text-blue-600">{{ selectedProperty.td_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Owner</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ selectedProperty.owner }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Barangay</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedProperty.barangay }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Coordinates</p>
                        <p class="text-xs font-mono text-gray-700 dark:text-gray-300">{{ selectedProperty.lat }}, {{ selectedProperty.lng }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                        <Tag :value="selectedProperty.status?.replace(/_/g,' ')" class="text-xs capitalize" />
                    </div>
                    <div class="flex gap-2 pt-2">
                        <RouterLink :to="`/tax-declarations/${selectedProperty.td_id}`" class="flex-1">
                            <Button label="View Record" icon="pi pi-eye" size="small" class="w-full" />
                        </RouterLink>
                        <a :href="`https://www.google.com/maps?q=${selectedProperty.lat},${selectedProperty.lng}`" target="_blank">
                            <Button icon="pi pi-external-link" size="small" outlined v-tooltip="'Open in Google Maps'" />
                        </a>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- New Pin Dialog -->
        <Dialog v-model:visible="showPinDialog" header="Set Property Location" :modal="true" class="w-96">
            <div class="space-y-4 pt-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <i class="pi pi-info-circle mr-2"></i>
                        Clicked at: <strong>{{ newPin?.lat?.toFixed(6) }}, {{ newPin?.lng?.toFixed(6) }}</strong>
                    </p>
                </div>

                <div v-if="pinTarget === 'fa'">
                    <label class="form-label">Field Appraisal</label>
                    <AutoComplete v-model="faSearch" :suggestions="faSuggestions" optionLabel="appraisal_no" @complete="searchFa"
                        @item-select="onFaSelect" placeholder="Search appraisal no..." class="w-full" />
                    <p v-if="selectedFaId" class="text-xs text-slate-500 mt-1">Pin will be saved to this field appraisal.</p>
                </div>
                <div v-else>
                    <label class="form-label">Link to Tax Declaration</label>
                    <AutoComplete v-model="tdSearch" :suggestions="tdSuggestions" optionLabel="td_number" @complete="searchTd"
                        @item-select="onTdSelect" placeholder="Search TD number..." class="w-full" />
                </div>

                <div class="flex gap-2">
                    <Button label="Save Pin" icon="pi pi-save" class="flex-1" :loading="savingPin" @click="savePin" />
                    <Button label="Cancel" outlined class="flex-1" @click="showPinDialog = false; pinMode = false" />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import * as turf from '@turf/turf';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import AutoComplete from 'primevue/autocomplete';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const route = useRoute();
const toast = useToast();

let L = null;
let map = null;
let markerLayer = null;
let barangayLayer = null;
let measureLayer = null;
/** @type {{ marker: import('leaflet').Marker, a: number[], b: number[] }[]} */
const measureSideMarkers = [];

/** Zoom level at which side meters appear — adjust with the map slider. */
const metersZoomLevel = ref(18);
const currentZoom = ref(12);

const markers = ref([]);
const barangayLocations = ref([]);
const mapLoading = ref(true);
const searchQuery = ref('');
const filterClassification = ref(null);
const classificationOptions = ref([]);
const selectedProperty = ref(null);
const mouseCoords = ref(null);
const pinMode = ref(false);
const showPinDialog = ref(false);
const newPin = ref(null);
const tdSearch = ref('');
const tdSuggestions = ref([]);
const selectedTdId = ref(null);
const faSearch = ref('');
const faSuggestions = ref([]);
const selectedFaId = ref(null);
const pinTarget = ref('td'); // 'td' | 'fa'
const savingPin = ref(false);
const activeView = ref('street');

const mapViews = [
    { id: 'street', label: 'Street', url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png' },
    { id: 'satellite', label: 'Satellite', url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}' },
    { id: 'terrain', label: 'Terrain', url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png' },
];

let tileLayer = null;
const propertyMarkerRefs = [];
const barangayMarkerRefs = [];

function getZoomScale(zoom) {
    if (zoom >= 16) return 1;
    if (zoom >= 14) return 0.82;
    if (zoom >= 12) return 0.65;
    if (zoom >= 10) return 0.5;
    return 0.38;
}

function shouldShowLabel(zoom) {
    // Hide TD/owner text when zoomed out so it doesn't collide with the parcel
    return zoom >= 15;
}

const VIBRANT_PIN_COLORS = [
    '#E11D48', '#2563EB', '#059669', '#7C3AED',
    '#EA580C', '#0891B2', '#DB2777', '#16A34A',
    '#4F46E5', '#D97706', '#0D9488', '#9333EA',
];

function getVibrantColor(seed = '') {
    const key = String(seed);
    let hash = 0;
    for (let i = 0; i < key.length; i++) {
        hash = key.charCodeAt(i) + ((hash << 5) - hash);
    }
    return VIBRANT_PIN_COLORS[Math.abs(hash) % VIBRANT_PIN_COLORS.length];
}

function darkenColor(hex, amount = 0.15) {
    const num = parseInt(hex.replace('#', ''), 16);
    const r = Math.max(0, ((num >> 16) & 0xff) * (1 - amount));
    const g = Math.max(0, ((num >> 8) & 0xff) * (1 - amount));
    const b = Math.max(0, (num & 0xff) * (1 - amount));
    return `#${[r, g, b].map(v => Math.round(v).toString(16).padStart(2, '0')).join('')}`;
}

function createTeardropIcon(color = '#2563EB', baseSize = 32, scale = 1) {
    const size = Math.max(14, Math.round(baseSize * scale));
    const height = Math.round(size * 1.25);
    const gradId = `pin-${color.replace('#', '')}-${size}`;
    const dark = darkenColor(color, 0.2);
    return L.divIcon({
        className: 'tdms-teardrop-marker',
        html: `
            <div class="tdms-pin-wrap" style="width:${size}px;height:${height}px">
                <svg viewBox="0 0 32 40" width="${size}" height="${height}" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="${gradId}" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="${color}"/>
                            <stop offset="100%" stop-color="${dark}"/>
                        </linearGradient>
                    </defs>
                    <path d="M16 0C9.373 0 4 5.373 4 12c0 9 12 24 12 24s12-15 12-24C28 5.373 22.627 0 16 0z"
                        fill="url(#${gradId})" stroke="#ffffff" stroke-width="2.5"/>
                    <circle cx="16" cy="12" r="5" fill="#ffffff" opacity="0.95"/>
                    <circle cx="16" cy="12" r="2.5" fill="${color}"/>
                </svg>
            </div>
        `,
        iconSize: [size, height],
        iconAnchor: [size / 2, height],
        popupAnchor: [0, -height + 4],
    });
}

function createPropertyIcon(color, scale = 1, seed = '', tdNumber = '', owner = '') {
    const vibrant = getVibrantColor(seed || color || 'property');
    const baseSize = 28;
    const size = Math.max(14, Math.round(baseSize * scale));
    const height = Math.round(size * 1.25);
    const zoom = map?.getZoom() ?? 15;
    const showLabel = shouldShowLabel(zoom) && (tdNumber || owner);
    const fontSize = Math.max(9, Math.round(10 * scale));

    const labelHtml = showLabel
        ? `<div class="tdms-pin-label-card tdms-property-label" style="--pin-color:${vibrant};font-size:${fontSize}px">
                <span class="tdms-pin-label-dot"></span>
                <span class="tdms-pin-label-stack">
                    <span class="tdms-pin-label-td">${tdNumber || 'TD'}</span>
                    <span class="tdms-pin-label-owner">${owner || '—'}</span>
                </span>
           </div>`
        : '';

    // Label sits ABOVE the pin — tip is at the bottom of the full icon stack
    const labelHeight = labelHtml ? Math.round(36 * scale) : 0;
    const wrapWidth = Math.max(size, showLabel ? 120 : size);
    const totalHeight = height + labelHeight;
    const gradId = `prop-${vibrant.replace('#', '')}-${size}`;
    const dark = darkenColor(vibrant, 0.2);

    return L.divIcon({
        className: 'tdms-teardrop-marker tdms-property-marker',
        html: `
            <div class="tdms-pin-wrap tdms-property-pin" style="width:${wrapWidth}px;height:${totalHeight}px">
                ${labelHtml}
                <svg viewBox="0 0 32 40" width="${size}" height="${height}" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto">
                    <defs>
                        <linearGradient id="${gradId}" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="${vibrant}"/>
                            <stop offset="100%" stop-color="${dark}"/>
                        </linearGradient>
                    </defs>
                    <path d="M16 0C9.373 0 4 5.373 4 12c0 9 12 24 12 24s12-15 12-24C28 5.373 22.627 0 16 0z"
                        fill="url(#${gradId})" stroke="#ffffff" stroke-width="2.5"/>
                    <circle cx="16" cy="12" r="5" fill="#ffffff" opacity="0.95"/>
                    <circle cx="16" cy="12" r="2.5" fill="${vibrant}"/>
                </svg>
            </div>
        `,
        iconSize: [wrapWidth, totalHeight],
        // Anchor at pin TIP (bottom center) so lat/lng stays on the land at every zoom
        iconAnchor: [wrapWidth / 2, totalHeight],
        popupAnchor: [0, -totalHeight + 8],
    });
}

function createBarangayIcon(name = '', scale = 1) {
    const baseSize = 34;
    const size = Math.max(16, Math.round(baseSize * scale));
    const height = Math.round(size * 1.25);
    const zoom = map?.getZoom() ?? 15;
    const pinColor = getVibrantColor(name || 'barangay');
    const label = (shouldShowLabel(zoom) && name)
        ? `<div class="tdms-pin-label-card" style="--pin-color:${pinColor};font-size:${Math.max(9, Math.round(11 * scale))}px">
                <span class="tdms-pin-label-dot"></span>
                <span class="tdms-pin-label-text">${name}</span>
           </div>`
        : '';
    const labelHeight = label ? Math.round(22 * scale) : 0;
    const gradId = `brgy-${pinColor.replace('#', '')}-${size}`;
    const dark = darkenColor(pinColor, 0.2);
    return L.divIcon({
        className: 'tdms-teardrop-marker tdms-barangay-marker',
        html: `
            <div class="tdms-pin-wrap tdms-barangay-pin" style="width:${Math.max(size, 80)}px">
                <svg viewBox="0 0 32 40" width="${size}" height="${height}" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="${gradId}" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="${pinColor}"/>
                            <stop offset="100%" stop-color="${dark}"/>
                        </linearGradient>
                    </defs>
                    <path d="M16 0C9.373 0 4 5.373 4 12c0 9 12 24 12 24s12-15 12-24C28 5.373 22.627 0 16 0z"
                        fill="url(#${gradId})" stroke="#ffffff" stroke-width="2.5"/>
                    <circle cx="16" cy="12" r="5" fill="#ffffff" opacity="0.95"/>
                    <circle cx="16" cy="12" r="2.5" fill="${pinColor}"/>
                </svg>
                ${label}
            </div>
        `,
        iconSize: [Math.max(size, 80), height + labelHeight],
        iconAnchor: [Math.max(size, 80) / 2, height],
        popupAnchor: [0, -height + 4],
    });
}

function updateMarkerSizes() {
    if (!map) return;
    const scale = getZoomScale(map.getZoom());
    const zoom = map.getZoom();

    propertyMarkerRefs.forEach(({ marker, color, seed, tdNumber, owner }) => {
        marker.setIcon(createPropertyIcon(color, scale, seed, tdNumber, owner));
    });

    barangayMarkerRefs.forEach(({ marker, name }) => {
        marker.setIcon(createBarangayIcon(name, scale));
    });

    applyGisMeasureScale();

    const container = map.getContainer();
    container.classList.toggle('tdms-map-zoom-out', zoom < 13);
    container.classList.toggle('tdms-map-zoom-far', zoom < 11);

    if (zoom < 12) map.closePopup();
}

function formatMeters(m) {
    if (m == null || Number.isNaN(m)) return '—';
    if (m >= 1000) return `${(m / 1000).toFixed(3)} km`;
    return `${Number(m).toLocaleString(undefined, { maximumFractionDigits: 2 })} m`;
}

function closeRing(coords) {
    if (!coords?.length) return [];
    const ring = coords.map(([lat, lng]) => [Number(lat), Number(lng)]);
    const [fLat, fLng] = ring[0];
    const [lLat, lLng] = ring[ring.length - 1];
    if (fLat !== lLat || fLng !== lLng) ring.push([fLat, fLng]);
    return ring;
}

function sideLengths(coords) {
    const ring = closeRing(coords);
    const sides = [];
    for (let i = 0; i < ring.length - 1; i += 1) {
        const a = ring[i];
        const b = ring[i + 1];
        const length = turf.distance(
            turf.point([a[1], a[0]]),
            turf.point([b[1], b[0]]),
            { units: 'meters' },
        );
        sides.push({
            length,
            mid: [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2],
        });
    }
    return sides;
}

function measureScaleForZoom(zoom) {
    if (zoom >= 19) return 1.05;
    if (zoom >= 18) return 0.95;
    if (zoom >= 17) return 0.82;
    if (zoom >= 16) return 0.7;
    return 0.55;
}

function clearMeasureMarkers() {
    if (measureLayer) measureLayer.clearLayers();
    measureSideMarkers.length = 0;
}

function shouldShowSideLabels() {
    return !!map && map.getZoom() >= metersZoomLevel.value;
}

function onMetersZoomThresholdChange() {
    applyGisMeasureScale();
}

/** Show/hide side lengths as zoom changes — all sides together. */
function applyGisMeasureScale() {
    if (!map) return;
    const zoom = map.getZoom();
    currentZoom.value = zoom;
    const el = map.getContainer();
    el.style.setProperty('--gis-measure-scale', String(measureScaleForZoom(zoom)));

    const show = shouldShowSideLabels();
    measureSideMarkers.forEach(({ marker }) => {
        marker.setOpacity(show ? 1 : 0);
        const node = marker.getElement();
        if (node) node.style.visibility = show ? 'visible' : 'hidden';
    });
}

/** Nudge side labels slightly outward so they sit off the edge, not on corners. */
function outwardMidpoint(a, b, ring) {
    const mid = [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2];
    try {
        const poly = turf.polygon([ring.map(([lat, lng]) => [lng, lat])]);
        const c = turf.centroid(poly).geometry.coordinates; // [lng, lat]
        const cx = c[1];
        const cy = c[0];
        const dx = mid[0] - cx;
        const dy = mid[1] - cy;
        const len = Math.hypot(dx, dy) || 1;
        const push = 0.00002;
        return [mid[0] + (dx / len) * push, mid[1] + (dy / len) * push];
    } catch {
        return mid;
    }
}

function addBoundaryMeasurements(coords) {
    if (!measureLayer || !coords?.length || !L) return;

    const ring = closeRing(coords);
    const sides = sideLengths(ring);
    const show = shouldShowSideLabels();

    sides.forEach((side, i) => {
        if (side.length < 0.05) return;
        const a = ring[i];
        const b = ring[i + 1];
        const mid = outwardMidpoint(a, b, ring);
        const icon = L.divIcon({
            className: 'gis-measure-label gis-side-label',
            html: `<div class="gis-measure-pill">${formatMeters(side.length)}</div>`,
            iconSize: [0, 0],
            iconAnchor: [0, 0],
        });
        const marker = L.marker(mid, {
            icon,
            interactive: false,
            keyboard: false,
            opacity: show ? 1 : 0,
        }).addTo(measureLayer);
        measureSideMarkers.push({ marker, a, b });
    });

    applyGisMeasureScale();
}

async function initMap() {
    L = (await import('leaflet')).default;
    await import('leaflet/dist/leaflet.css');

    map = L.map('gis-map', {
        center: [8.0, 125.0],
        zoom: 12,
        zoomControl: true,
        maxZoom: 22,
    });

    tileLayer = L.tileLayer(mapViews[0].url, {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 22,
        maxNativeZoom: 19,
    }).addTo(map);

    markerLayer = L.layerGroup().addTo(map);
    barangayLayer = L.layerGroup().addTo(map);
    measureLayer = L.layerGroup().addTo(map);

    map.on('mousemove', (e) => {
        mouseCoords.value = { lat: e.latlng.lat, lng: e.latlng.lng };
    });

    map.on('click', (e) => {
        if (pinMode.value) {
            newPin.value = { lat: e.latlng.lat, lng: e.latlng.lng };
            showPinDialog.value = true;
        }
    });

    map.on('zoom', applyGisMeasureScale);
    map.on('zoomend', () => {
        updateMarkerSizes();
        applyGisMeasureScale();
    });
    currentZoom.value = map.getZoom();

    await loadProperties();
    await loadBarangayLayer();
    applyGisMeasureScale();
    mapLoading.value = false;
}

async function loadProperties() {
    const res = await axios.get('/gis/map-properties');
    markers.value = res.data;
    renderMarkers(res.data, { fit: true });
}

function extractBoundaryCoords(boundary) {
    if (!boundary) return null;
    if (Array.isArray(boundary?.coordinates) && boundary.coordinates.length >= 3) {
        return boundary.coordinates;
    }
    if (Array.isArray(boundary) && Array.isArray(boundary[0]) && boundary.length >= 3) {
        return boundary;
    }
    return null;
}

/** Prefer polygon centroid so the pin sits inside the land boundary. */
function pinPositionForProperty(p) {
    const coords = extractBoundaryCoords(p.boundary_polygon);
    if (coords?.length) {
        let sumLat = 0;
        let sumLng = 0;
        let n = 0;
        coords.forEach(([lat, lng], i) => {
            // Skip duplicate closing point
            if (i > 0 && lat === coords[0][0] && lng === coords[0][1]) return;
            sumLat += Number(lat);
            sumLng += Number(lng);
            n += 1;
        });
        if (n > 0) {
            return { lat: sumLat / n, lng: sumLng / n };
        }
    }
    if (p.lat != null && p.lng != null) {
        return { lat: Number(p.lat), lng: Number(p.lng) };
    }
    return null;
}

function renderMarkers(data, { fit = false } = {}) {
    markerLayer.clearLayers();
    clearMeasureMarkers();
    propertyMarkerRefs.length = 0;
    const scale = getZoomScale(map?.getZoom() ?? 15);
    const bounds = [];
    applyGisMeasureScale();

    data.forEach((p) => {
        const seed = p.td_number || p.id;
        const pinColor = getVibrantColor(seed);
        const tdNumber = p.td_number || '';
        const owner = p.owner || '';

        // Land boundary polygon (saved from Land Mapping)
        const coords = extractBoundaryCoords(p.boundary_polygon);
        if (coords) {
            const latlngs = coords.map(([lat, lng]) => [Number(lat), Number(lng)]);
            const polygon = L.polygon(latlngs, {
                color: '#b8860b',
                weight: 2.5,
                opacity: 0.95,
                fillColor: pinColor,
                fillOpacity: 0.22,
                className: 'tdms-land-boundary',
            });
            polygon.bindPopup(`
                <div class="tdms-popup">
                    <strong>${tdNumber || 'Property'}</strong><br>
                    <span class="tdms-popup-muted">${owner || '—'}</span><br>
                    <span class="tdms-popup-sub">${p.classification || '—'} · ${p.barangay || '—'}</span>
                    ${p.boundary_polygon?.area != null
                        ? `<br><span class="tdms-popup-sub">Area: ${Number(p.boundary_polygon.area).toLocaleString()} m²</span>`
                        : ''}
                </div>
            `);
            polygon.on('click', () => { selectedProperty.value = p; });
            markerLayer.addLayer(polygon);
            addBoundaryMeasurements(latlngs);
            latlngs.forEach((ll) => bounds.push(ll));
        }

        const pos = pinPositionForProperty(p);
        if (!pos) return;

        const marker = L.marker([pos.lat, pos.lng], {
            icon: createPropertyIcon(p.color, scale, seed, tdNumber, owner),
            zIndexOffset: 400,
        })
            .bindPopup(`
                <div class="tdms-popup">
                    <strong>${tdNumber || 'Property'}</strong><br>
                    <span class="tdms-popup-muted">${owner || '—'}</span><br>
                    <span class="tdms-popup-sub">${p.classification || '—'} · ${p.barangay || '—'}</span>
                    ${p.boundary_polygon?.area != null
                        ? `<br><span class="tdms-popup-sub">Area: ${Number(p.boundary_polygon.area).toLocaleString()} m²</span>`
                        : ''}
                </div>
            `)
            .on('click', () => { selectedProperty.value = p; });

        markerLayer.addLayer(marker);
        propertyMarkerRefs.push({
            marker,
            color: p.color,
            seed,
            tdNumber,
            owner,
        });
        bounds.push([pos.lat, pos.lng]);
    });

    if (fit && bounds.length && !route.query.td && !route.query.fa) {
        try {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 17 });
        } catch {
            // ignore invalid bounds
        }
    }
}

async function loadBarangayLayer() {
    const res = await axios.get('/gis/barangay-layer');
    barangayLocations.value = res.data.filter(b => b.latitude && b.longitude);
    barangayLayer.clearLayers();
    barangayMarkerRefs.length = 0;

    const scale = getZoomScale(map?.getZoom() ?? 15);
    const bounds = [];
    barangayLocations.value.forEach(b => {
        const lat = Number(b.latitude);
        const lng = Number(b.longitude);
        bounds.push([lat, lng]);

        const municipality = b.municipality?.name || '—';
        const marker = L.marker([lat, lng], { icon: createBarangayIcon(b.name, scale) })
            .bindPopup(`
                <div class="tdms-popup">
                    <strong class="tdms-popup-title">${b.name}</strong><br>
                    <span class="tdms-popup-muted">${municipality}</span><br>
                    <span class="tdms-popup-coords">${lat.toFixed(6)}, ${lng.toFixed(6)}</span>
                </div>
            `);
        barangayLayer.addLayer(marker);
        barangayMarkerRefs.push({ marker, name: b.name });
    });

    if (bounds.length === 1) {
        map.setView(bounds[0], 15);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
    }

    updateMarkerSizes();
}

function setMapView(view) {
    activeView.value = view.id;
    if (tileLayer) map.removeLayer(tileLayer);
    tileLayer = L.tileLayer(view.url, {
        attribution: '© OpenStreetMap contributors', maxZoom: 20,
    }).addTo(map);
}

function filterMarkers() {
    if (!filterClassification.value) {
        renderMarkers(markers.value);
    } else {
        renderMarkers(markers.value.filter(m =>
            m.classification === classificationOptions.value.find(c => c.id === filterClassification.value)?.name
        ));
    }
}

async function searchAddress() {
    if (!searchQuery.value) return;
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=1`);
        const data = await res.json();
        if (data.length) {
            map.setView([data[0].lat, data[0].lon], 16);
            L.marker([data[0].lat, data[0].lon], { icon: createBarangayIcon() })
                .addTo(map)
                .bindPopup(data[0].display_name)
                .openPopup();
        } else {
            toast.add({ severity: 'warn', summary: 'Not Found', detail: 'Address not found.' });
        }
    } catch {}
}

async function searchTd(event) {
    const res = await axios.get('/tax-declarations', { params: { search: event.query, per_page: 10 } });
    tdSuggestions.value = res.data.data;
}

function onTdSelect(event) {
    selectedTdId.value = event.value.id;
    pinTarget.value = 'td';
}

async function searchFa(event) {
    const res = await axios.get('/field-appraisals', { params: { search: event.query, per_page: 10 } });
    faSuggestions.value = res.data.data ?? res.data;
}

function onFaSelect(event) {
    selectedFaId.value = event.value.id;
    pinTarget.value = 'fa';
}

async function savePin() {
    if (!newPin.value) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Click the map to set a pin first.' });
        return;
    }

    if (pinTarget.value === 'fa') {
        if (!selectedFaId.value) {
            toast.add({ severity: 'warn', summary: 'Warning', detail: 'Please select a field appraisal.' });
            return;
        }
        savingPin.value = true;
        try {
            await axios.post('/gis', {
                field_appraisal_id: selectedFaId.value,
                latitude: newPin.value.lat,
                longitude: newPin.value.lng,
            });
            toast.add({ severity: 'success', summary: 'Pin Saved', detail: 'Field appraisal location saved.' });
            showPinDialog.value = false;
            pinMode.value = false;
            map.setView([newPin.value.lat, newPin.value.lng], 18);
        } finally {
            savingPin.value = false;
        }
        return;
    }

    if (!selectedTdId.value) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Please select a tax declaration.' });
        return;
    }
    savingPin.value = true;
    try {
        await axios.post('/gis', {
            tax_declaration_id: selectedTdId.value,
            latitude: newPin.value.lat,
            longitude: newPin.value.lng,
        });
        toast.add({ severity: 'success', summary: 'Pin Saved', detail: 'Location saved.' });
        showPinDialog.value = false;
        pinMode.value = false;
        await loadProperties();
    } finally {
        savingPin.value = false;
    }
}

onMounted(async () => {
    const res = await axios.get('/settings/classifications');
    classificationOptions.value = res.data;
    await initMap();

    if (route.query.fa) {
        pinTarget.value = 'fa';
        selectedFaId.value = Number(route.query.fa);
        try {
            const faRes = await axios.get(`/gis/field-appraisals/${route.query.fa}`);
            if (faRes.data) {
                faSearch.value = { id: faRes.data.id, appraisal_no: faRes.data.appraisal_no };
                if (faRes.data.latitude && faRes.data.longitude) {
                    map.setView([Number(faRes.data.latitude), Number(faRes.data.longitude)], 18);
                    L.marker([Number(faRes.data.latitude), Number(faRes.data.longitude)], {
                        icon: createTeardropIcon('#E11D48', 32, 1),
                    }).addTo(map).bindPopup(faRes.data.appraisal_no || 'Field Appraisal').openPopup();
                } else {
                    pinMode.value = true;
                    toast.add({
                        severity: 'info',
                        summary: 'Set Location',
                        detail: 'Pin mode is on — click the map to place this appraisal pin.',
                    });
                }
            }
        } catch {}
    } else if (route.query.td) {
        pinTarget.value = 'td';
        selectedTdId.value = Number(route.query.td);
        const tdRes = await axios.get(`/gis/tax-declarations/${route.query.td}`);
        if (tdRes.data?.latitude) {
            map.setView([tdRes.data.latitude, tdRes.data.longitude], 18);
        }
    }
});

onUnmounted(() => { if (map) map.remove(); });
</script>

<style>
.slide-right-enter-active, .slide-right-leave-active { transition: all 0.25s ease; }
.slide-right-enter-from, .slide-right-leave-to { opacity: 0; transform: translateX(30px); }

:deep(.tdms-teardrop-marker) {
    background: transparent;
    border: none;
}

:deep(.tdms-pin-wrap) {
    filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.4));
    transition: transform 0.2s ease, width 0.2s ease, height 0.2s ease;
}

:deep(.leaflet-marker-icon.tdms-teardrop-marker:hover .tdms-pin-wrap) {
    transform: scale(1.08) translateY(-2px);
}

:deep(.tdms-barangay-pin) {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

:deep(.tdms-property-pin) {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    gap: 4px;
    box-sizing: border-box;
}

:deep(.tdms-pin-label-card) {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    max-width: 160px;
    padding: 4px 10px 4px 7px;
    background: linear-gradient(135deg, #1a3557 0%, #1e4d8c 55%, var(--pin-color, #2563EB) 100%);
    border: 1.5px solid rgba(255, 255, 255, 0.95);
    border-radius: 999px;
    box-shadow: 0 3px 10px rgba(26, 53, 87, 0.35), 0 1px 3px rgba(0, 0, 0, 0.15);
    white-space: nowrap;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

:deep(.tdms-property-label) {
    border-radius: 10px;
    max-width: 170px;
    padding: 5px 10px 5px 7px;
}

:deep(.tdms-pin-label-stack) {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.15;
}

:deep(.tdms-pin-label-td) {
    font-weight: 700;
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    text-overflow: ellipsis;
}

:deep(.tdms-pin-label-owner) {
    font-weight: 500;
    font-size: 0.92em;
    color: rgba(255, 255, 255, 0.9);
    overflow: hidden;
    text-overflow: ellipsis;
}

:deep(.tdms-pin-label-dot) {
    flex-shrink: 0;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--pin-color, #2563EB);
    border: 1.5px solid #ffffff;
    box-shadow: 0 0 4px var(--pin-color, #2563EB);
}

:deep(.tdms-pin-label-text) {
    font-weight: 700;
    letter-spacing: 0.02em;
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.leaflet-marker-icon.tdms-barangay-marker:hover .tdms-pin-label-card),
:deep(.leaflet-marker-icon.tdms-property-marker:hover .tdms-pin-label-card) {
    transform: translateY(-1px);
}

:deep(.tdms-land-boundary) {
    cursor: pointer;
}

/* Side length + area labels on land boundaries */
:deep(.gis-measure-label) {
    background: transparent !important;
    border: none !important;
    width: 0 !important;
    height: 0 !important;
    overflow: visible !important;
}

:deep(.gis-measure-pill) {
    transform: translate(-50%, -50%) scale(var(--gis-measure-scale, 1));
    transform-origin: center center;
    white-space: nowrap;
    pointer-events: none;
    font-size: 11px;
    line-height: 1;
    padding: 3px 7px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.96);
    color: #1a3557;
    border: 1px solid rgba(184, 134, 11, 0.55);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
    font-weight: 700;
    width: max-content;
}

/* Smaller popups when zoomed out */
:deep(.tdms-map-zoom-out .leaflet-popup-content-wrapper) {
    padding: 2px 6px;
    border-radius: 6px;
}

:deep(.tdms-map-zoom-out .leaflet-popup-content) {
    margin: 8px 10px;
    font-size: 11px;
    line-height: 1.3;
}

:deep(.tdms-map-zoom-far .leaflet-popup-content-wrapper) {
    padding: 1px 4px;
}

:deep(.tdms-map-zoom-far .leaflet-popup-content) {
    margin: 6px 8px;
    font-size: 10px;
}

:deep(.tdms-popup-title) {
    color: #1a3557;
}

:deep(.tdms-popup-muted) {
    color: #6b7280;
    font-size: inherit;
}

:deep(.tdms-popup-sub),
:deep(.tdms-popup-coords) {
    font-size: 0.9em;
    color: #64748b;
}

:deep(.tdms-popup-coords) {
    font-family: monospace;
}
</style>

