<template>
    <div ref="mapEl" class="w-full rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600" :style="{ height: `${height}px` }"></div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    lat: { type: Number, required: true },
    lng: { type: Number, required: true },
    label: { type: String, default: 'Property Location' },
    height: { type: Number, default: 220 },
    zoom: { type: Number, default: 15 },
});

const mapEl = ref(null);
let map = null;
let marker = null;
let L = null;

async function initMap() {
    if (!mapEl.value || !props.lat || !props.lng) return;

    if (!L) {
        L = (await import('leaflet')).default;
        await import('leaflet/dist/leaflet.css');
    }

    if (map) {
        map.remove();
        map = null;
        marker = null;
    }

    map = L.map(mapEl.value, {
        center: [props.lat, props.lng],
        zoom: props.zoom,
        scrollWheelZoom: false,
        attributionControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    marker = L.marker([props.lat, props.lng]).addTo(map);
    if (props.label) {
        marker.bindPopup(props.label).openPopup();
    }

    setTimeout(() => map?.invalidateSize(), 100);
}

watch(() => [props.lat, props.lng, props.label], () => initMap());

onMounted(() => initMap());
onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>
