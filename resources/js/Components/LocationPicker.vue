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
const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
let map = null;
let marker = null;
let searchTimeout = null;

const defaultCenter = [-8.1228, 111.5617];

function searchLocation() {
    clearTimeout(searchTimeout);
    const q = searchQuery.value.trim();
    if (q.length < 3) { searchResults.value = []; return; }
    searching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=5&countrycodes=id`);
            searchResults.value = await res.json();
        } catch { searchResults.value = []; }
        searching.value = false;
    }, 400);
}

function selectResult(result) {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        createMarker([lat, lng]);
    }
    map.setView([lat, lng], 17);
    emit('update:lat', parseFloat(lat.toFixed(7)));
    emit('update:lng', parseFloat(lng.toFixed(7)));
    searchResults.value = [];
    searchQuery.value = result.display_name.split(',').slice(0, 3).join(',');
}

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
    clearTimeout(searchTimeout);
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
            <div class="relative">
                <div class="absolute top-2 left-2 right-12 z-[1000]">
                    <div class="relative">
                        <input v-model="searchQuery" @input="searchLocation" @keydown.enter.prevent
                            type="text" placeholder="Cari lokasi..."
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 pr-8 text-sm shadow-md focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <svg v-if="searching" class="absolute right-2 top-2.5 h-4 w-4 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <svg v-else class="absolute right-2 top-2.5 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <div v-if="searchResults.length" class="mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg">
                        <button v-for="(r, i) in searchResults" :key="i" @click="selectResult(r)" type="button"
                            class="flex w-full items-start gap-2 px-3 py-2 text-left text-sm hover:bg-blue-50 border-b border-gray-100 last:border-0">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <span class="text-gray-700">{{ r.display_name }}</span>
                        </button>
                    </div>
                </div>
                <div ref="mapEl" style="height: 350px; width: 100%;"></div>
            </div>
            <div class="flex items-center justify-between bg-gray-50 px-3 py-2 text-xs text-gray-500">
                <span>Klik pada peta untuk memilih lokasi. Drag marker untuk memindahkan.</span>
                <span v-if="lat && lng" class="font-mono text-gray-700">{{ parseFloat(lat).toFixed(7) }}, {{ parseFloat(lng).toFixed(7) }}</span>
            </div>
        </div>
    </div>
</template>
