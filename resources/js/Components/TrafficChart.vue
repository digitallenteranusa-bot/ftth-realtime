<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    mikrotikId: { type: Number, required: true },
    mikrotikName: { type: String, default: '' },
});

const connected = ref(null);
const activeInterface = ref(null);
const rxSpeed = ref(0);
const txSpeed = ref(0);
let interval = null;

function formatBits(bits) {
    if (bits === 0) return '0 bps';
    const k = 1000;
    const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps'];
    const i = Math.floor(Math.log(Math.abs(bits)) / Math.log(k));
    return parseFloat((bits / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

async function fetchTraffic() {
    try {
        const res = await fetch(`/mikrotiks/${props.mikrotikId}/traffic`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) { connected.value = false; return; }
        const data = await res.json();

        if (!data.connected) {
            connected.value = false;
            return;
        }

        connected.value = true;
        activeInterface.value = data.interface || null;
        rxSpeed.value = data.rx || 0;
        txSpeed.value = data.tx || 0;
    } catch (e) {
        connected.value = false;
    }
}

onMounted(() => {
    fetchTraffic();
    interval = setInterval(fetchTraffic, 5000);

    if (window.Echo) {
        window.Echo.channel(`traffic.${props.mikrotikId}`)
            .listen('TrafficUpdated', (e) => {
                connected.value = true;
                activeInterface.value = e.interface || null;
                rxSpeed.value = e.rx || 0;
                txSpeed.value = e.tx || 0;
            });
    }
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
    if (window.Echo) window.Echo.leave(`traffic.${props.mikrotikId}`);
});
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ mikrotikName || 'Traffic' }}
                <span v-if="activeInterface" class="ml-1 text-xs font-normal text-gray-500">({{ activeInterface }})</span>
            </h4>
            <span v-if="connected === true" class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span> Connected
            </span>
            <span v-else-if="connected === false" class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Disconnected
            </span>
            <span v-else class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">
                Connecting...
            </span>
        </div>

        <div v-if="connected === true" class="grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                    <span class="text-xs font-medium text-green-600 dark:text-green-400">Download</span>
                </div>
                <div class="mt-1 text-xl font-bold text-green-700 dark:text-green-300">{{ formatBits(rxSpeed) }}/s</div>
            </div>
            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Upload</span>
                </div>
                <div class="mt-1 text-xl font-bold text-blue-700 dark:text-blue-300">{{ formatBits(txSpeed) }}/s</div>
            </div>
        </div>

        <div v-else-if="connected === false" class="flex items-center gap-3 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4">
            <svg class="h-8 w-8 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636a9 9 0 11-12.728 0M12 9v4m0 4h.01" />
            </svg>
            <div>
                <p class="text-sm font-medium text-gray-500">Mikrotik tidak terhubung</p>
                <p class="text-xs text-gray-400">Periksa koneksi dan kredensial API</p>
            </div>
        </div>

        <div v-else class="flex items-center justify-center py-4">
            <svg class="h-5 w-5 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>
    </div>
</template>
