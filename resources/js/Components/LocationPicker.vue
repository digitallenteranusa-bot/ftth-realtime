<script setup>
import { ref, onUnmounted, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default marker icon issue with bundlers
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

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

const defaultCenter = [-8.1092, 111.6193];

function initMap() {
    if (map) {
        map.invalidateSize();
        return;
    }
    if (!mapEl.value) return;

    const hasCoords = props.lat && props.lng && !isNaN(parseFloat(props.lat)) && !isNaN(parseFloat(props.lng));
    const center = hasCoords ? [parseFloat(props.lat), parseFloat(props.lng)] : defaultCenter;
    const zoom = hasCoords ? 17 : 15;

    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri', maxZoom: 19,
    });
    const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OSM', maxZoom: 19,
    });

    map = L.map(mapEl.value, { layers: [satellite] }).setView(center, zoom);
    L.control.layers({ 'Satelit': satellite, 'Peta Jalan': streets }, null, { position: 'topright' }).addTo(map);

    if (hasCoords) {
        createMarker(center);
    }

    map.on('click', onMapClick);

    // Multiple invalidateSize calls to handle container rendering
    setTimeout(() => { if (map) map.invalidateSize(); }, 100);
    setTimeout(() => { if (map) map.invalidateSize(); }, 300);
    setTimeout(() => { if (map) map.invalidateSize(); }, 500);
}

function createMarker(latlng) {
    const redIcon = L.divIcon({
        className: '',
        html: `<div style="position:relative">
            <div style="width:24px;height:24px;background:#ef4444;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.4);position:absolute;top:-24px;left:-12px"></div>
            <div style="width:8px;height:8px;background:white;border-radius:50%;position:absolute;top:-18px;left:-4px"></div>
        </div>`,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
    });

    marker = L.marker(latlng, { draggable: true, icon: redIcon }).addTo(map);
    marker.on('dragend', onMarkerDrag);
}

function onMapClick(e) {
    const { lat, lng } = e.latlng;
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        createMarker([lat, lng]);
    }
    emit('update:lat', parseFloat(lat.toFixed(7)));
    emit('update:lng', parseFloat(lng.toFixed(7)));
}

function onMarkerDrag() {
    const { lat, lng } = marker.getLatLng();
    emit('update:lat', parseFloat(lat.toFixed(7)));
    emit('update:lng', parseFloat(lng.toFixed(7)));
}

async function toggle() {
    expanded.value = !expanded.value;
    if (expanded.value) {
        await nextTick();
        initMap();
    }
}

// Sync external lat/lng changes (manual input) to marker
watch(() => [props.lat, props.lng], ([newLat, newLng]) => {
    if (!map) return;
    const lat = parseFloat(newLat);
    const lng = parseFloat(newLng);
    if (isNaN(lat) || isNaN(lng)) return;

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        createMarker([lat, lng]);
    }
    map.setView([lat, lng], map.getZoom());
});

onUnmounted(() => {
    if (map) { map.remove(); map = null; }
    marker = null;
});
</script>

<template>
    <div class="col-span-2">
        <button type="button" @click="toggle"
            class="flex items-center gap-2 rounded-md border border-blue-300 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 transition mb-1">
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
            <div ref="mapEl" style="height: 350px; width: 100%;"></div>
            <div class="flex items-center justify-between bg-gray-50 px-3 py-2 text-xs text-gray-500">
                <span>Klik pada peta untuk memilih lokasi. Drag marker untuk memindahkan.</span>
                <span v-if="lat && lng" class="font-mono text-gray-700">{{ parseFloat(lat).toFixed(7) }}, {{ parseFloat(lng).toFixed(7) }}</span>
            </div>
        </div>
    </div>
</template>
