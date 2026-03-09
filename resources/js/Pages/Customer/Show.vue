<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({ customer: Object });

const mapContainer = ref(null);

function hasCoords() {
    return props.customer.lat && props.customer.lng;
}

function openGoogleMaps() {
    if (!hasCoords()) return;
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${props.customer.lat},${props.customer.lng}`, '_blank');
}

onMounted(() => {
    if (!hasCoords() || !mapContainer.value) return;

    const lat = parseFloat(props.customer.lat);
    const lng = parseFloat(props.customer.lng);

    const map = L.map(mapContainer.value).setView([lat, lng], 16);

    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, attribution: 'Esri'
    });
    const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    });

    satellite.addTo(map);
    L.control.layers({ 'Satelit': satellite, 'Jalan': streets }).addTo(map);

    L.marker([lat, lng]).addTo(map)
        .bindPopup(`<b>${props.customer.name}</b><br>${props.customer.address || ''}`)
        .openPopup();
});
</script>

<template>
    <Head :title="customer.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ customer.name }}</h2>
                <Link :href="route('customers.edit', customer.id)" class="rounded-md bg-teal-600 px-4 py-2 text-sm text-white">Edit</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                <div><span class="text-xs text-gray-500">Phone</span><p class="font-semibold">{{ customer.phone }}</p></div>
                <div><span class="text-xs text-gray-500">Bandwidth</span><p class="font-semibold">{{ customer.bandwidth || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Status</span><p><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-green-100 text-green-800': customer.status === 'active', 'bg-red-100 text-red-800': customer.status === 'inactive', 'bg-yellow-100 text-yellow-800': customer.status === 'suspended' }">{{ customer.status }}</span></p></div>
                <div class="sm:col-span-2"><span class="text-xs text-gray-500">Address</span><p class="font-semibold">{{ customer.address }}</p></div>
                <div v-if="customer.lat && customer.lng" class="col-span-2"><span class="text-xs text-gray-500">Koordinat</span><p class="font-semibold text-sm">{{ customer.lat }}, {{ customer.lng }}</p></div>
            </div>

            <!-- Map Location -->
            <div v-if="customer.lat && customer.lng" class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Lokasi Pelanggan</h3>
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
                <h3 class="text-lg font-semibold mb-2">Lokasi Pelanggan</h3>
                <p class="text-sm text-gray-400">Koordinat belum diisi. <Link :href="route('customers.edit', customer.id)" class="text-blue-600 hover:underline">Edit pelanggan</Link> untuk menambahkan lokasi.</p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold">ONTs ({{ customer.onts?.length || 0 }})</h3>
                <div class="space-y-2">
                    <div v-for="ont in customer.onts" :key="ont.id" class="flex items-center gap-3 rounded border p-3">
                        <div class="h-3 w-3 rounded-full" :class="{ 'bg-green-500': ont.status === 'online', 'bg-red-500': ont.status !== 'online' }"></div>
                        <div>
                            <Link :href="route('onts.show', ont.id)" class="text-sm font-medium text-blue-600 hover:underline">{{ ont.serial_number }}</Link>
                            <span class="ml-2 text-xs text-gray-500">{{ ont.status }} | ODP: {{ ont.odp?.name || '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white p-6 shadow" v-if="customer.trouble_tickets?.length">
                <h3 class="mb-4 text-lg font-semibold">Trouble Tickets</h3>
                <div class="space-y-2">
                    <div v-for="t in customer.trouble_tickets" :key="t.id" class="flex items-center justify-between rounded border p-3">
                        <div><span class="text-xs text-gray-500">{{ t.ticket_number }}</span><p class="text-sm font-medium">{{ t.title }}</p></div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-blue-100 text-blue-800': t.status === 'open', 'bg-green-100 text-green-800': t.status === 'resolved' }">{{ t.status }}</span>
                    </div>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
