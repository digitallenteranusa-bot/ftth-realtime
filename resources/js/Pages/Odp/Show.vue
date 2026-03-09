<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({ odp: Object });
const mapContainer = ref(null);

function hasCoords() {
    return props.odp.lat && props.odp.lng;
}

function openGoogleMaps() {
    if (!hasCoords()) return;
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${props.odp.lat},${props.odp.lng}`, '_blank');
}

onMounted(() => {
    if (!hasCoords() || !mapContainer.value) return;

    const lat = parseFloat(props.odp.lat);
    const lng = parseFloat(props.odp.lng);

    const map = L.map(mapContainer.value).setView([lat, lng], 16);

    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, attribution: 'Esri'
    });
    const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    });

    satellite.addTo(map);
    L.control.layers({ 'Satelit': satellite, 'Jalan': streets }).addTo(map);

    const pinIcon = L.divIcon({
        html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0ea5e9" stroke="#fff" stroke-width="1" width="32" height="32"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/></svg>`,
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
    });

    L.marker([lat, lng], { icon: pinIcon }).addTo(map)
        .bindPopup(`<b>${props.odp.name}</b><br>Kapasitas: ${props.odp.used_ports}/${props.odp.capacity}`)
        .openPopup();
});
</script>

<template>
    <Head :title="odp.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ odp.name }}</h2>
                <Link :href="route('odps.edit', odp.id)" class="rounded-md bg-sky-600 px-4 py-2 text-sm text-white">Edit</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                <div><span class="text-xs text-gray-500">ODC</span><p class="font-semibold">{{ odp.odc?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Capacity</span><p class="font-semibold">{{ odp.used_ports }}/{{ odp.capacity }}</p></div>
                <div><span class="text-xs text-gray-500">Splitter</span><p class="font-semibold">{{ odp.splitter_ratio }}</p></div>
                <div><span class="text-xs text-gray-500">Coordinates</span><p class="font-semibold text-sm">{{ odp.lat }}, {{ odp.lng }}</p></div>
            </div>

            <!-- Map Location -->
            <div v-if="odp.lat && odp.lng" class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Lokasi ODP</h3>
                    <button @click="openGoogleMaps"
                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Navigasi Google Maps
                    </button>
                </div>
                <div ref="mapContainer" class="w-full h-64 sm:h-80 rounded-lg border border-gray-200 z-0"></div>
            </div>
            <div v-else class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-lg font-semibold mb-2">Lokasi ODP</h3>
                <p class="text-sm text-gray-400">Koordinat belum diisi. <Link :href="route('odps.edit', odp.id)" class="text-blue-600 hover:underline">Edit ODP</Link> untuk menambahkan lokasi.</p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold">ONTs ({{ odp.onts?.length || 0 }})</h3>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="ont in odp.onts" :key="ont.id" class="flex items-center gap-3 rounded border p-3"
                        :class="{ 'border-green-200 bg-green-50': ont.status === 'online', 'border-red-200 bg-red-50': ont.status !== 'online' }">
                        <div class="h-3 w-3 rounded-full" :class="{ 'bg-green-500': ont.status === 'online', 'bg-red-500': ont.status !== 'online' }"></div>
                        <div>
                            <Link :href="route('onts.show', ont.id)" class="text-sm font-medium text-blue-600 hover:underline">{{ ont.name || ont.serial_number }}</Link>
                            <p class="text-xs text-gray-500">{{ ont.customer?.name || '-' }} | {{ ont.status }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
