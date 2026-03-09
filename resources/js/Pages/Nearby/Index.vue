<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const myLat = ref(null);
const myLng = ref(null);
const coordsInput = ref('');
const radius = ref(5);
const type = ref('all');
const loading = ref(false);
const locating = ref(false);
const error = ref('');
const gpsAvailable = ref(true);
const odps = ref([]);
const customers = ref([]);
const hasSearched = ref(false);

onMounted(() => {
    // GPS/Geolocation hanya tersedia di HTTPS atau localhost
    const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    if (!isSecure || !navigator.geolocation) {
        gpsAvailable.value = false;
    }
});

function getLocation() {
    if (!navigator.geolocation) {
        gpsAvailable.value = false;
        error.value = 'GPS tidak tersedia. Gunakan input koordinat manual.';
        return;
    }
    locating.value = true;
    error.value = '';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            myLat.value = pos.coords.latitude;
            myLng.value = pos.coords.longitude;
            coordsInput.value = `${pos.coords.latitude}, ${pos.coords.longitude}`;
            locating.value = false;
            searchNearby();
        },
        (err) => {
            locating.value = false;
            gpsAvailable.value = false;
            if (err.code === 1) error.value = 'GPS ditolak browser. Situs harus HTTPS untuk GPS otomatis. Gunakan input koordinat manual.';
            else if (err.code === 2) error.value = 'GPS tidak tersedia. Pastikan GPS aktif, atau input koordinat manual.';
            else error.value = 'Timeout GPS. Coba lagi atau input koordinat manual.';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

function parseCoords() {
    const input = coordsInput.value.trim();
    if (!input) {
        error.value = 'Masukkan koordinat. Contoh: -8.1215, 111.5629';
        return false;
    }

    // Support formats: "-8.1215, 111.5629" or "-8.1215 111.5629" or Google Maps URL
    let lat, lng;

    // Try Google Maps URL format
    const urlMatch = input.match(/@?([-\d.]+),([-\d.]+)/);
    if (urlMatch) {
        lat = parseFloat(urlMatch[1]);
        lng = parseFloat(urlMatch[2]);
    } else {
        // Try comma or space separated
        const parts = input.split(/[,\s]+/).filter(Boolean);
        if (parts.length >= 2) {
            lat = parseFloat(parts[0]);
            lng = parseFloat(parts[1]);
        }
    }

    if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        error.value = 'Koordinat tidak valid. Format: -8.1215, 111.5629';
        return false;
    }

    myLat.value = lat;
    myLng.value = lng;
    error.value = '';
    return true;
}

async function searchNearby() {
    if (!myLat.value || !myLng.value) {
        if (!parseCoords()) return;
    }
    loading.value = true;
    error.value = '';
    try {
        const params = new URLSearchParams({
            lat: myLat.value,
            lng: myLng.value,
            radius: radius.value,
            type: type.value,
        });
        const res = await fetch(`${route('nearby.search')}?${params}`);
        const data = await res.json();
        odps.value = data.odps || [];
        customers.value = data.customers || [];
        hasSearched.value = true;
    } catch (e) {
        error.value = 'Gagal mencari data. Coba lagi.';
    } finally {
        loading.value = false;
    }
}

function submitCoords() {
    if (parseCoords()) {
        searchNearby();
    }
}

function formatDistance(km) {
    if (km < 1) return `${Math.round(km * 1000)} m`;
    return `${km.toFixed(1)} km`;
}

function openMaps(lat, lng) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
}

function openMyLocationMaps() {
    window.open('https://www.google.com/maps/@?api=1&map_action=map', '_blank');
}
</script>

<template>
    <Head title="Nearby Search" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Nearby Search</h2>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- GPS/Location Controls -->
                <div class="rounded-lg bg-white p-4 shadow mb-4">
                    <!-- GPS Button (jika HTTPS) -->
                    <div v-if="gpsAvailable" class="mb-3">
                        <button @click="getLocation" :disabled="locating"
                            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50 w-full sm:w-auto justify-center">
                            <svg v-if="locating" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ locating ? 'Mendeteksi GPS...' : 'Deteksi GPS Otomatis' }}
                        </button>
                    </div>

                    <!-- Koordinat Input (selalu tampil) -->
                    <div class="flex flex-col gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Koordinat
                                <span class="text-gray-400 font-normal">(paste dari Google Maps)</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="coordsInput" type="text"
                                    placeholder="-8.1215, 111.5629"
                                    @keyup.enter="submitCoords"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm text-sm" />
                                <button @click="openMyLocationMaps" type="button"
                                    title="Buka Google Maps untuk lihat koordinat lokasi Anda"
                                    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-200 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span class="hidden sm:inline">Google Maps</span>
                                </button>
                            </div>
                            <p v-if="!gpsAvailable" class="mt-1 text-xs text-amber-600">GPS otomatis tidak tersedia (butuh HTTPS). Copy koordinat dari Google Maps lalu paste di atas.</p>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div class="flex-1 flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Radius</label>
                                    <select v-model="radius" @change="hasSearched && myLat && searchNearby()" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                        <option :value="1">1 km</option>
                                        <option :value="2">2 km</option>
                                        <option :value="5">5 km</option>
                                        <option :value="10">10 km</option>
                                        <option :value="20">20 km</option>
                                        <option :value="50">50 km</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipe</label>
                                    <select v-model="type" @change="hasSearched && myLat && searchNearby()" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                        <option value="all">Semua</option>
                                        <option value="odp">ODP</option>
                                        <option value="customer">Pelanggan</option>
                                    </select>
                                </div>
                            </div>
                            <button @click="submitCoords" :disabled="loading"
                                class="rounded-md bg-teal-600 px-6 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">
                                {{ loading ? 'Mencari...' : 'Cari Terdekat' }}
                            </button>
                        </div>
                    </div>

                    <!-- Koordinat aktif -->
                    <div v-if="myLat" class="mt-2 text-xs text-green-600 font-medium">
                        Lokasi: {{ myLat.toFixed(6) }}, {{ myLng.toFixed(6) }}
                    </div>

                    <!-- Error message -->
                    <div v-if="error" class="mt-3 rounded-md bg-red-50 border border-red-200 p-3">
                        <p class="text-sm text-red-700">{{ error }}</p>
                    </div>
                </div>

                <!-- Results -->
                <div v-if="loading" class="text-center py-12">
                    <svg class="animate-spin h-8 w-8 mx-auto text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Mencari lokasi terdekat...</p>
                </div>

                <div v-else-if="hasSearched">
                    <!-- ODP Results -->
                    <div v-if="type === 'all' || type === 'odp'" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">
                            ODP Terdekat
                            <span class="text-sm font-normal text-gray-500">({{ odps.length }} ditemukan)</span>
                        </h3>
                        <div v-if="odps.length" class="space-y-2">
                            <div v-for="odp in odps" :key="odp.id"
                                class="rounded-lg bg-white p-4 shadow">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <Link :href="route('odps.show', odp.id)" class="text-sm font-semibold text-teal-600 hover:underline">{{ odp.name }}</Link>
                                            <span class="rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-medium">{{ odp.onts_count }} ONT</span>
                                            <span class="rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs font-bold">{{ formatDistance(odp.distance) }}</span>
                                        </div>
                                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span v-if="odp.odc">ODC: {{ odp.odc.name }}</span>
                                            <span>Kapasitas: {{ odp.capacity || '-' }}</span>
                                            <span>Splitter: {{ odp.splitter_type || '-' }}</span>
                                        </div>
                                    </div>
                                    <button @click="openMaps(odp.lat, odp.lng)"
                                        class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                        <span class="hidden sm:inline">Navigasi</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 bg-white rounded-lg p-4 shadow">Tidak ada ODP dalam radius {{ radius }} km.</p>
                    </div>

                    <!-- Customer Results -->
                    <div v-if="type === 'all' || type === 'customer'">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">
                            Pelanggan Terdekat
                            <span class="text-sm font-normal text-gray-500">({{ customers.length }} ditemukan)</span>
                        </h3>
                        <div v-if="customers.length" class="space-y-2">
                            <div v-for="c in customers" :key="c.id"
                                class="rounded-lg bg-white p-4 shadow">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <Link :href="route('customers.show', c.id)" class="text-sm font-semibold text-teal-600 hover:underline">{{ c.name }}</Link>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                                :class="{ 'bg-green-100 text-green-800': c.status === 'active', 'bg-red-100 text-red-800': c.status === 'inactive', 'bg-yellow-100 text-yellow-800': c.status === 'suspended' }">
                                                {{ c.status }}
                                            </span>
                                            <span class="rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs font-bold">{{ formatDistance(c.distance) }}</span>
                                        </div>
                                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span>{{ c.phone || '-' }}</span>
                                            <span class="truncate max-w-[250px]">{{ c.address || '-' }}</span>
                                        </div>
                                    </div>
                                    <button @click="openMaps(c.lat, c.lng)"
                                        class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                        <span class="hidden sm:inline">Navigasi</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 bg-white rounded-lg p-4 shadow">Tidak ada pelanggan dalam radius {{ radius }} km.</p>
                    </div>
                </div>

                <!-- Empty state before search -->
                <div v-else class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="mt-3 text-gray-500">Masukkan koordinat dan tekan <strong>Cari Terdekat</strong>.</p>
                    <p class="mt-1 text-xs text-gray-400">Buka <strong>Google Maps</strong> di HP, tekan tahan lokasi Anda, lalu copy koordinat.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
