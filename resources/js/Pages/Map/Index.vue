<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const mapContainer = ref(null);
let map = null;
let layers = {};
const loading = ref(true);
const filters = ref({ mikrotik: true, olt: true, odc: true, odp: true, ont: true, fiber: true });

const statusColors = { online: '#22c55e', offline: '#ef4444', los: '#dc2626', dyinggasp: '#f59e0b', unknown: '#6b7280' };
const deviceIcons = {
    mikrotik: { color: '#3b82f6', symbol: 'M', size: 18 },
    olt: { color: '#8b5cf6', symbol: 'O', size: 18 },
    odc: { color: '#6366f1', symbol: 'DC', size: 16 },
    odp: { color: '#0ea5e9', symbol: 'DP', size: 14 },
};

function createIcon(type, status) {
    const cfg = deviceIcons[type];
    if (!cfg) {
        const color = statusColors[status] || statusColors.unknown;
        return L.divIcon({
            className: '',
            html: `<div style="width:12px;height:12px;border-radius:50%;background:${color};border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3)"></div>`,
            iconSize: [12, 12], iconAnchor: [6, 6],
        });
    }
    return L.divIcon({
        className: '',
        html: `<div style="width:${cfg.size * 2}px;height:${cfg.size * 2}px;border-radius:50%;background:${cfg.color};display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:${cfg.size * 0.6}px;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3)">${cfg.symbol}</div>`,
        iconSize: [cfg.size * 2, cfg.size * 2], iconAnchor: [cfg.size, cfg.size],
    });
}

async function loadMapData() {
    loading.value = true;
    try {
        const [elemRes, routeRes] = await Promise.all([
            fetch(route('map.elements')),
            fetch(route('map.fiber-routes')),
        ]);
        const elements = await elemRes.json();
        const fiberRoutes = await routeRes.json();

        // Clear old layers
        Object.values(layers).forEach(lg => lg.clearLayers());

        // Mikrotiks
        layers.mikrotik = L.layerGroup();
        elements.mikrotiks.forEach(d => {
            L.marker([d.lat, d.lng], { icon: createIcon('mikrotik') })
                .bindPopup(`<b>Mikrotik: ${d.name}</b><br>Host: ${d.host}<br>Location: ${d.location || '-'}`)
                .addTo(layers.mikrotik);
        });

        // OLTs
        layers.olt = L.layerGroup();
        elements.olts.forEach(d => {
            L.marker([d.lat, d.lng], { icon: createIcon('olt') })
                .bindPopup(`<b>OLT: ${d.name}</b><br>Vendor: ${d.vendor}<br>Host: ${d.host}`)
                .addTo(layers.olt);
        });

        // ODCs
        layers.odc = L.layerGroup();
        elements.odcs.forEach(d => {
            L.marker([d.lat, d.lng], { icon: createIcon('odc') })
                .bindPopup(`<b>ODC: ${d.name}</b><br>Capacity: ${d.used_ports}/${d.capacity}`)
                .addTo(layers.odc);
        });

        // ODPs
        layers.odp = L.layerGroup();
        elements.odps.forEach(d => {
            L.marker([d.lat, d.lng], { icon: createIcon('odp') })
                .bindPopup(`<b>ODP: ${d.name}</b><br>Capacity: ${d.used_ports}/${d.capacity}`)
                .addTo(layers.odp);
        });

        // ONTs
        layers.ont = L.layerGroup();
        elements.onts.forEach(d => {
            L.marker([d.lat, d.lng], { icon: createIcon(null, d.status) })
                .bindPopup(`<b>ONT: ${d.name || d.serial_number}</b><br>Status: <span style="color:${statusColors[d.status]}">${d.status}</span><br>Rx Power: ${d.rx_power ?? '-'} dBm`)
                .addTo(layers.ont);
        });

        // Fiber routes with animated dashes
        layers.fiber = L.layerGroup();
        fiberRoutes.forEach(r => {
            if (!r.coordinates || r.coordinates.length < 2) return;
            const latlngs = r.coordinates.map(c => [c[0], c[1]]);
            const weight = r.source_type === 'olt' ? 4 : r.source_type === 'odc' ? 3 : 2;
            const polyline = L.polyline(latlngs, {
                color: r.color || '#3388ff', weight, opacity: 0.8,
                dashArray: '10 6', className: 'animated-dash',
            }).bindPopup(`<b>${r.name || 'Fiber Route'}</b><br>${r.source_type} -> ${r.destination_type}`)
              .addTo(layers.fiber);
        });

        // Add all visible layers to map
        Object.entries(filters.value).forEach(([key, visible]) => {
            if (visible && layers[key]) layers[key].addTo(map);
        });
    } catch (e) {
        console.error('Failed to load map data:', e);
    }
    loading.value = false;
}

function toggleLayer(key) {
    filters.value[key] = !filters.value[key];
    if (filters.value[key]) {
        layers[key]?.addTo(map);
    } else {
        layers[key] && map.removeLayer(layers[key]);
    }
}

onMounted(() => {
    map = L.map(mapContainer.value).setView([-7.265, 112.750], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);
    loadMapData();
});

onUnmounted(() => {
    map?.remove();
});
</script>

<template>
    <Head title="Network Map" />
    <AuthenticatedLayout>
        <div class="relative" style="height: calc(100vh - 65px)">
            <!-- Filter Panel -->
            <div class="absolute top-3 right-3 z-[1000] rounded-lg bg-white p-3 shadow-lg">
                <h4 class="mb-2 text-sm font-bold text-gray-700">Layers</h4>
                <label v-for="(label, key) in { mikrotik: 'Mikrotik', olt: 'OLT', odc: 'ODC', odp: 'ODP', ont: 'ONT', fiber: 'Fiber Routes' }"
                    :key="key" class="flex items-center gap-2 text-sm cursor-pointer mb-1">
                    <input type="checkbox" :checked="filters[key]" @change="toggleLayer(key)" class="rounded border-gray-300">
                    {{ label }}
                </label>
            </div>

            <!-- Legend -->
            <div class="absolute bottom-3 left-3 z-[1000] rounded-lg bg-white p-3 shadow-lg">
                <h4 class="mb-2 text-sm font-bold text-gray-700">Legend</h4>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-blue-500"></div> Mikrotik</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-purple-500"></div> OLT</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-indigo-500"></div> ODC</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-sky-500"></div> ODP</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-green-500"></div> ONT Online</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-red-500"></div> ONT Offline/LOS</div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="absolute inset-0 z-[1001] flex items-center justify-center bg-white/60">
                <div class="text-gray-600 font-semibold">Loading map data...</div>
            </div>

            <div ref="mapContainer" class="h-full w-full"></div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@keyframes dash-animation {
    to { stroke-dashoffset: -32; }
}
.animated-dash {
    animation: dash-animation 1s linear infinite;
}
</style>
