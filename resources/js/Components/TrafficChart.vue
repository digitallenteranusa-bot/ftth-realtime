<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale, PointElement,
    LineElement, Title, Tooltip, Legend, Filler
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    mikrotikId: { type: Number, required: true },
    mikrotikName: { type: String, default: '' },
});

const MAX_POINTS = 30;
const labels = ref([]);
const rxData = ref([]);
const txData = ref([]);
const connected = ref(null);
let interval = null;

const chartData = ref({
    labels: [],
    datasets: [
        {
            label: 'RX (Download)',
            data: [],
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 0,
        },
        {
            label: 'TX (Upload)',
            data: [],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 0,
        },
    ],
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 300 },
    scales: {
        x: { display: true, grid: { display: false } },
        y: {
            display: true,
            beginAtZero: true,
            ticks: {
                callback: (value) => formatBits(value) + '/s',
            },
        },
    },
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } },
        tooltip: {
            callbacks: {
                label: (ctx) => `${ctx.dataset.label}: ${formatBits(ctx.parsed.y)}/s`,
            },
        },
    },
};

function formatBits(bits) {
    if (bits === 0) return '0 bps';
    const k = 1000;
    const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps'];
    const i = Math.floor(Math.log(Math.abs(bits)) / Math.log(k));
    return parseFloat((bits / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function updateChart() {
    chartData.value = {
        labels: [...labels.value],
        datasets: [
            { ...chartData.value.datasets[0], data: [...rxData.value] },
            { ...chartData.value.datasets[1], data: [...txData.value] },
        ],
    };
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
        const now = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        labels.value.push(now);
        rxData.value.push(data.rx || 0);
        txData.value.push(data.tx || 0);

        if (labels.value.length > MAX_POINTS) {
            labels.value.shift();
            rxData.value.shift();
            txData.value.shift();
        }
        updateChart();
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
                const now = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                let totalRx = 0, totalTx = 0;
                (e.data || []).forEach(d => {
                    totalRx += parseInt(d.rx_bytes || 0);
                    totalTx += parseInt(d.tx_bytes || 0);
                });
                labels.value.push(now);
                rxData.value.push(totalRx);
                txData.value.push(totalTx);
                if (labels.value.length > MAX_POINTS) {
                    labels.value.shift();
                    rxData.value.shift();
                    txData.value.shift();
                }
                updateChart();
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
        <div class="mb-2 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-700">{{ mikrotikName || 'Traffic' }} - Live Traffic</h4>
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
        <div v-if="connected === true" style="height: 200px">
            <Line :data="chartData" :options="chartOptions" />
        </div>
        <div v-else-if="connected === false" class="flex h-[200px] items-center justify-center rounded-lg border-2 border-dashed border-gray-200 bg-gray-50">
            <div class="text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636a9 9 0 11-12.728 0M12 9v4m0 4h.01" />
                </svg>
                <p class="mt-2 text-sm text-gray-500">Mikrotik tidak terhubung</p>
                <p class="text-xs text-gray-400">Periksa koneksi dan kredensial API</p>
            </div>
        </div>
        <div v-else class="flex h-[200px] items-center justify-center">
            <svg class="h-6 w-6 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>
    </div>
</template>
