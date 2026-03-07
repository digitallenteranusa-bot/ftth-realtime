<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    mapElements: Object,
    fiberRoutes: Array,
});

const mapContainer = ref(null);
let map = null;
let layers = {};
const filters = ref({ mikrotik: true, olt: true, odc: true, odp: true, ont: true, fiber: true });

const statusColors = { online: '#22c55e', offline: '#ef4444', los: '#dc2626', dyinggasp: '#f59e0b', unknown: '#6b7280' };
const deviceIcons = {
    mikrotik: { color: '#3b82f6', symbol: 'M', size: 18 },
    olt: { color: '#8b5cf6', symbol: 'O', size: 18 },
    odc: { color: '#6366f1', symbol: 'DC', size: 16 },
    odp: { color: '#0ea5e9', symbol: 'DP', size: 14 },
};

// Drawing state
const isDrawing = ref(false);
const drawingPoints = ref([]);
const drawingMode = ref(null); // 'new' or 'edit'
const editingRouteId = ref(null);
let drawingPolyline = null;
let drawingMarkers = [];

// Route form
const routeForm = ref({
    name: '',
    source_type: '',
    source_id: '',
    destination_type: '',
    destination_id: '',
    color: '#3388ff',
    status: 'active',
});
const showRouteForm = ref(false);

function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const toRad = d => d * Math.PI / 180;
    const dLat = toRad(lat2 - lat1), dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function calcRouteDistance(coords) {
    let total = 0;
    for (let i = 1; i < coords.length; i++) {
        total += haversine(coords[i-1][0], coords[i-1][1], coords[i][0], coords[i][1]);
    }
    return total >= 1000 ? (total/1000).toFixed(2) + ' km' : Math.round(total) + ' m';
}

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

function buildLayers() {
    const elements = props.mapElements;

    layers.mikrotik = L.layerGroup();
    elements.mikrotiks.forEach(d => {
        L.marker([d.lat, d.lng], { icon: createIcon('mikrotik') })
            .bindPopup(`<b>Mikrotik: ${d.name}</b><br>Host: ${d.host}<br>Location: ${d.location || '-'}`)
            .addTo(layers.mikrotik);
    });

    layers.olt = L.layerGroup();
    elements.olts.forEach(d => {
        L.marker([d.lat, d.lng], { icon: createIcon('olt') })
            .bindPopup(`<b>OLT: ${d.name}</b><br>Vendor: ${d.vendor}<br>Host: ${d.host}`)
            .addTo(layers.olt);
    });

    layers.odc = L.layerGroup();
    elements.odcs.forEach(d => {
        L.marker([d.lat, d.lng], { icon: createIcon('odc') })
            .bindPopup(`<b>ODC: ${d.name}</b><br>Capacity: ${d.used_ports}/${d.capacity}`)
            .addTo(layers.odc);
    });

    layers.odp = L.layerGroup();
    elements.odps.forEach(d => {
        L.marker([d.lat, d.lng], { icon: createIcon('odp') })
            .bindPopup(`<b>ODP: ${d.name}</b><br>Capacity: ${d.used_ports}/${d.capacity}`)
            .addTo(layers.odp);
    });

    layers.ont = L.layerGroup();
    elements.onts.forEach(d => {
        L.marker([d.lat, d.lng], { icon: createIcon(null, d.status) })
            .bindPopup(`<b>ONT: ${d.name || d.serial_number}</b><br>Status: <span style="color:${statusColors[d.status]}">${d.status}</span><br>Rx Power: ${d.rx_power ?? '-'} dBm`)
            .addTo(layers.ont);
    });

    layers.fiber = L.layerGroup();
    props.fiberRoutes.forEach(r => {
        if (!r.coordinates || r.coordinates.length < 2) return;
        const latlngs = r.coordinates.map(c => [c[0], c[1]]);
        const weight = r.source_type === 'olt' ? 4 : r.source_type === 'odc' ? 3 : 2;
        const dist = calcRouteDistance(r.coordinates);
        const line = L.polyline(latlngs, {
            color: r.color || '#3388ff', weight, opacity: 0.8,
            dashArray: '10 6', className: 'animated-dash',
        });
        line.bindPopup(`
            <b>${r.name || 'Fiber Route'}</b><br>
            ${r.source_type} → ${r.destination_type}<br>
            Jarak: <b>${dist}</b><br>
            <div style="margin-top:6px">
                <button onclick="window.__editRoute(${r.id})" style="background:#3b82f6;color:white;padding:2px 8px;border-radius:4px;border:none;cursor:pointer;font-size:12px;margin-right:4px">Edit Rute</button>
                <button onclick="window.__deleteRoute(${r.id})" style="background:#ef4444;color:white;padding:2px 8px;border-radius:4px;border:none;cursor:pointer;font-size:12px">Hapus</button>
            </div>
        `);
        line.addTo(layers.fiber);
    });

    Object.entries(filters.value).forEach(([key, visible]) => {
        if (visible && layers[key]) layers[key].addTo(map);
    });
}

function toggleLayer(key) {
    filters.value[key] = !filters.value[key];
    if (filters.value[key]) {
        layers[key]?.addTo(map);
    } else {
        layers[key] && map.removeLayer(layers[key]);
    }
}

// --- Drawing functions ---
function startDrawing() {
    isDrawing.value = true;
    drawingMode.value = 'new';
    drawingPoints.value = [];
    showRouteForm.value = false;
    editingRouteId.value = null;
    routeForm.value = { name: '', source_type: '', source_id: '', destination_type: '', destination_id: '', color: '#3388ff', status: 'active' };
    map.getContainer().style.cursor = 'crosshair';
}

function startEditRoute(routeId) {
    const route = props.fiberRoutes.find(r => r.id === routeId);
    if (!route) return;
    map.closePopup();

    isDrawing.value = true;
    drawingMode.value = 'edit';
    editingRouteId.value = routeId;
    drawingPoints.value = route.coordinates.map(c => [c[0], c[1]]);
    routeForm.value = {
        name: route.name || '',
        source_type: route.source_type,
        source_id: route.source_id,
        destination_type: route.destination_type,
        destination_id: route.destination_id,
        color: route.color || '#3388ff',
        status: route.status || 'active',
    };
    showRouteForm.value = false;
    map.getContainer().style.cursor = 'crosshair';
    updateDrawingLine();
}

function onMapClick(e) {
    if (!isDrawing.value) return;
    drawingPoints.value.push([e.latlng.lat, e.latlng.lng]);
    updateDrawingLine();
}

function updateDrawingLine() {
    // Remove old drawing elements
    if (drawingPolyline) map.removeLayer(drawingPolyline);
    drawingMarkers.forEach(m => map.removeLayer(m));
    drawingMarkers = [];

    if (drawingPoints.value.length >= 1) {
        drawingPolyline = L.polyline(drawingPoints.value, {
            color: routeForm.value.color, weight: 3, opacity: 0.9,
            dashArray: '8 4',
        }).addTo(map);
    }

    // Add draggable markers on each point
    drawingPoints.value.forEach((p, i) => {
        const marker = L.marker(p, {
            draggable: true,
            icon: L.divIcon({
                className: '',
                html: `<div style="width:14px;height:14px;border-radius:50%;background:${i === 0 ? '#22c55e' : i === drawingPoints.value.length - 1 ? '#ef4444' : '#f59e0b'};border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.4);cursor:grab"></div>`,
                iconSize: [14, 14], iconAnchor: [7, 7],
            }),
        }).addTo(map);
        marker.on('drag', (e) => {
            drawingPoints.value[i] = [e.latlng.lat, e.latlng.lng];
            if (drawingPolyline) drawingPolyline.setLatLngs(drawingPoints.value);
        });
        // Right click to remove point
        marker.on('contextmenu', () => {
            if (drawingPoints.value.length > 2) {
                drawingPoints.value.splice(i, 1);
                updateDrawingLine();
            }
        });
        drawingMarkers.push(marker);
    });

    // Show distance info
    if (drawingPoints.value.length >= 2) {
        drawingDistance.value = calcRouteDistance(drawingPoints.value);
    } else {
        drawingDistance.value = '';
    }
}

const drawingDistance = ref('');

function undoLastPoint() {
    if (drawingPoints.value.length > 0) {
        drawingPoints.value.pop();
        updateDrawingLine();
    }
}

function cancelDrawing() {
    isDrawing.value = false;
    drawingMode.value = null;
    showRouteForm.value = false;
    editingRouteId.value = null;
    drawingPoints.value = [];
    drawingDistance.value = '';
    map.getContainer().style.cursor = '';
    if (drawingPolyline) { map.removeLayer(drawingPolyline); drawingPolyline = null; }
    drawingMarkers.forEach(m => map.removeLayer(m));
    drawingMarkers = [];
}

function finishDrawing() {
    if (drawingPoints.value.length < 2) return;
    map.getContainer().style.cursor = '';
    if (drawingMode.value === 'edit') {
        // Direct save for edit mode
        saveRoute();
    } else {
        showRouteForm.value = true;
    }
}

function saveRoute() {
    const coordinates = drawingPoints.value.map(p => [p[0], p[1]]);

    if (drawingMode.value === 'edit' && editingRouteId.value) {
        router.put(route('fiber-routes.update', { fiber_route: editingRouteId.value }), {
            name: routeForm.value.name,
            coordinates,
            color: routeForm.value.color,
            status: routeForm.value.status,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                cancelDrawing();
                rebuildFiberLayer();
            },
        });
    } else {
        router.post(route('fiber-routes.store'), {
            ...routeForm.value,
            coordinates,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                cancelDrawing();
                rebuildFiberLayer();
            },
        });
    }
}

function deleteRoute(routeId) {
    if (!confirm('Hapus rute fiber ini?')) return;
    router.delete(route('fiber-routes.destroy', { fiber_route: routeId }), {
        preserveScroll: true,
        onSuccess: () => rebuildFiberLayer(),
    });
}

function rebuildFiberLayer() {
    if (layers.fiber) map.removeLayer(layers.fiber);
    layers.fiber = L.layerGroup();
    // Re-fetch will happen via Inertia page reload
    router.reload({ only: ['fiberRoutes'], onSuccess: () => {
        buildFiberOnly();
    }});
}

function buildFiberOnly() {
    if (layers.fiber) map.removeLayer(layers.fiber);
    layers.fiber = L.layerGroup();
    props.fiberRoutes.forEach(r => {
        if (!r.coordinates || r.coordinates.length < 2) return;
        const latlngs = r.coordinates.map(c => [c[0], c[1]]);
        const weight = r.source_type === 'olt' ? 4 : r.source_type === 'odc' ? 3 : 2;
        const dist = calcRouteDistance(r.coordinates);
        const line = L.polyline(latlngs, {
            color: r.color || '#3388ff', weight, opacity: 0.8,
            dashArray: '10 6', className: 'animated-dash',
        });
        line.bindPopup(`
            <b>${r.name || 'Fiber Route'}</b><br>
            ${r.source_type} → ${r.destination_type}<br>
            Jarak: <b>${dist}</b><br>
            <div style="margin-top:6px">
                <button onclick="window.__editRoute(${r.id})" style="background:#3b82f6;color:white;padding:2px 8px;border-radius:4px;border:none;cursor:pointer;font-size:12px;margin-right:4px">Edit Rute</button>
                <button onclick="window.__deleteRoute(${r.id})" style="background:#ef4444;color:white;padding:2px 8px;border-radius:4px;border:none;cursor:pointer;font-size:12px">Hapus</button>
            </div>
        `);
        line.addTo(layers.fiber);
    });
    if (filters.value.fiber) layers.fiber.addTo(map);
}

// Source/destination options for form
function getDeviceOptions(type) {
    const elements = props.mapElements;
    if (type === 'olt') return elements.olts.map(d => ({ id: d.id, label: d.name }));
    if (type === 'odc') return elements.odcs.map(d => ({ id: d.id, label: d.name }));
    if (type === 'odp') return elements.odps.map(d => ({ id: d.id, label: d.name }));
    if (type === 'ont') return elements.onts.map(d => ({ id: d.id, label: d.name || d.serial_number }));
    return [];
}

onMounted(() => {
    // Base layers
    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri',
        maxZoom: 19,
    });
    const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    });
    const hybrid = L.layerGroup([
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 }),
        L.tileLayer('https://stamen-tiles.a.ssl.fastly.net/toner-labels/{z}/{x}/{y}.png', { maxZoom: 19, opacity: 0.7 }),
    ]);

    map = L.map(mapContainer.value, { layers: [satellite] }).setView([-8.1092, 111.6193], 16);

    L.control.layers({
        'Satelit': satellite,
        'Peta Jalan': streets,
        'Hybrid': hybrid,
    }, null, { position: 'topright' }).addTo(map);

    map.on('click', onMapClick);

    // Expose global functions for popup buttons
    window.__editRoute = startEditRoute;
    window.__deleteRoute = deleteRoute;

    buildLayers();
});

onUnmounted(() => {
    delete window.__editRoute;
    delete window.__deleteRoute;
    map?.remove();
});
</script>

<template>
    <Head title="Network Map" />
    <AuthenticatedLayout>
        <div class="relative" style="height: calc(100vh - 65px)">
            <!-- Layer filters -->
            <div class="absolute top-3 left-3 z-[1000] rounded-lg bg-white/95 p-3 shadow-lg backdrop-blur">
                <h4 class="mb-2 text-sm font-bold text-gray-700">Layers</h4>
                <label v-for="(label, key) in { mikrotik: 'Mikrotik', olt: 'OLT', odc: 'ODC', odp: 'ODP', ont: 'ONT', fiber: 'Fiber Routes' }"
                    :key="key" class="flex items-center gap-2 text-sm cursor-pointer mb-1">
                    <input type="checkbox" :checked="filters[key]" @change="toggleLayer(key)" class="rounded border-gray-300">
                    {{ label }}
                </label>
            </div>

            <!-- Drawing toolbar -->
            <div class="absolute top-3 left-1/2 -translate-x-1/2 z-[1000]">
                <div v-if="!isDrawing" class="flex gap-2">
                    <button @click="startDrawing" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-lg hover:bg-blue-700 transition">
                        + Gambar Rute Kabel
                    </button>
                </div>
                <div v-else class="rounded-lg bg-white/95 p-3 shadow-lg backdrop-blur space-y-2">
                    <div class="text-sm font-bold text-gray-700">
                        {{ drawingMode === 'edit' ? 'Edit Rute Kabel' : 'Gambar Rute Kabel' }}
                    </div>
                    <div class="text-xs text-gray-500">
                        Klik pada peta untuk menambah titik. Drag titik untuk memindahkan. Klik kanan titik untuk menghapus.
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span> Awal
                        <span class="inline-block w-3 h-3 rounded-full bg-yellow-500 ml-2"></span> Titik belok
                        <span class="inline-block w-3 h-3 rounded-full bg-red-500 ml-2"></span> Akhir
                    </div>
                    <div v-if="drawingDistance" class="text-sm font-medium text-gray-700">
                        Jarak: {{ drawingDistance }} ({{ drawingPoints.length }} titik)
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-600">Warna:</label>
                        <input type="color" v-model="routeForm.color" @input="updateDrawingLine" class="w-8 h-6 border rounded cursor-pointer">
                    </div>
                    <div class="flex gap-2">
                        <button @click="undoLastPoint" :disabled="drawingPoints.length === 0"
                            class="rounded bg-yellow-500 px-3 py-1 text-xs text-white hover:bg-yellow-600 disabled:opacity-50">
                            Undo
                        </button>
                        <button @click="finishDrawing" :disabled="drawingPoints.length < 2"
                            class="rounded bg-green-600 px-3 py-1 text-xs text-white hover:bg-green-700 disabled:opacity-50">
                            Selesai
                        </button>
                        <button @click="cancelDrawing" class="rounded bg-gray-500 px-3 py-1 text-xs text-white hover:bg-gray-600">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Save route form (new route only) -->
            <div v-if="showRouteForm" class="absolute top-3 right-3 z-[1000] w-72 rounded-lg bg-white/95 p-4 shadow-lg backdrop-blur">
                <h4 class="mb-3 text-sm font-bold text-gray-700">Simpan Rute Kabel</h4>
                <div class="space-y-2">
                    <input v-model="routeForm.name" placeholder="Nama rute (opsional)" class="w-full rounded border border-gray-300 px-2 py-1 text-sm">

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600">Sumber</label>
                            <select v-model="routeForm.source_type" class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                <option value="">-- Tipe --</option>
                                <option value="olt">OLT</option>
                                <option value="odc">ODC</option>
                                <option value="odp">ODP</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">&nbsp;</label>
                            <select v-model="routeForm.source_id" class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                <option value="">-- Pilih --</option>
                                <option v-for="opt in getDeviceOptions(routeForm.source_type)" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600">Tujuan</label>
                            <select v-model="routeForm.destination_type" class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                <option value="">-- Tipe --</option>
                                <option value="odc">ODC</option>
                                <option value="odp">ODP</option>
                                <option value="ont">ONT</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">&nbsp;</label>
                            <select v-model="routeForm.destination_id" class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                <option value="">-- Pilih --</option>
                                <option v-for="opt in getDeviceOptions(routeForm.destination_type)" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button @click="saveRoute" class="flex-1 rounded bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700">
                            Simpan
                        </button>
                        <button @click="cancelDrawing" class="flex-1 rounded bg-gray-400 px-3 py-1.5 text-sm text-white hover:bg-gray-500">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="absolute bottom-3 left-3 z-[1000] rounded-lg bg-white/95 p-3 shadow-lg backdrop-blur">
                <h4 class="mb-2 text-sm font-bold text-gray-700">Legend</h4>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-blue-500"></div> Mikrotik</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-purple-500"></div> OLT</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-indigo-500"></div> ODC</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-sky-500"></div> ODP</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-green-500"></div> ONT Online</div>
                    <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-full bg-red-500"></div> ONT Offline/LOS</div>
                    <div class="flex items-center gap-2 mt-1 pt-1 border-t">
                        <div class="h-0.5 w-4 bg-blue-500"></div> Feeder (OLT→ODC)
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-0.5 w-4 bg-green-500"></div> Distribusi (ODC→ODP)
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-0.5 w-4 bg-orange-400"></div> Drop (ODP→ONT)
                    </div>
                </div>
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
.leaflet-control-layers {
    margin-top: 60px !important;
}
</style>
