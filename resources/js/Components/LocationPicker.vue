<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    lat: { type: [Number, String], default: null },
    lng: { type: [Number, String], default: null },
    label: { type: String, default: 'Pilih lokasi di peta' },
});

const emit = defineEmits(['update:lat', 'update:lng']);

const mapEl = ref(null);
const expanded = ref(false);
let map = null;
let marker = null;

const defaultCenter = [-8.0503, 111.7068];

function initMap() {
    if (map) return;

    const center = (props.lat && props.lng) ? [parseFloat(props.lat), parseFloat(props.lng)] : defaultCenter;
    const zoom = (props.lat && props.lng) ? 17 : 15;

    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri', maxZoom: 19,
    });
    const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OSM', maxZoom: 19,
    });

    map = L.map(mapEl.value, { layers: [satellite] }).setView(center, zoom);
    L.control.layers({ 'Satelit': satellite, 'Peta Jalan': streets }, null, { position: 'topright' }).addTo(map);

    if (props.lat && props.lng) {
        marker = L.marker(center, { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    map.on('click', onMapClick);

    // Fix map rendering in hidden/collapsed container
    setTimeout(() => map.invalidateSize(), 100);
}

function onMapClick(e) {
    const { lat, lng } = e.latlng;
    setMarker(lat, lng);
    emit('update:lat', parseFloat(lat.toFixed(7)));
    emit('update:lng', parseFloat(lng.toFixed(7)));
}

function onMarkerDrag() {
    const { lat, lng } = marker.getLatLng();
    emit('update:lat', parseFloat(lat.toFixed(7)));
    emit('update:lng', parseFloat(lng.toFixed(7)));
}

function setMarker(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }
}

function toggle() {
    expanded.value = !expanded.value;
    if (expanded.value) {
        setTimeout(() => {
            initMap();
            map.invalidateSize();
        }, 50);
    }
}

// Sync external lat/lng changes to marker
watch(() => [props.lat, props.lng], ([newLat, newLng]) => {
    if (map && marker && newLat && newLng) {
        marker.setLatLng([parseFloat(newLat), parseFloat(newLng)]);
    }
});

onUnmounted(() => {
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div class="col-span-2">
        <button type="button" @click="toggle"
            class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800 mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ expanded ? 'Tutup Peta' : label }}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div v-show="expanded" class="rounded-lg border border-gray-300 overflow-hidden shadow-sm">
            <div ref="mapEl" style="height: 300px; width: 100%;"></div>
            <div class="bg-gray-50 px-3 py-1.5 text-xs text-gray-500">
                Klik pada peta untuk memilih lokasi. Drag marker untuk memindahkan.
            </div>
        </div>
    </div>
</template>
