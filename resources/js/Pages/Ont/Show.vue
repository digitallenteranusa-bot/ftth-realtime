<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ ont: Object });

const syncing = ref(false);
const syncMessage = ref('');
const syncSuccess = ref(false);
const signalData = ref(null);

const rxPower = computed(() => signalData.value?.rx_power ?? props.ont.rx_power);
const txPower = computed(() => signalData.value?.tx_power ?? props.ont.tx_power);

async function refreshSignal() {
    if (!props.ont.olt?.id) {
        syncMessage.value = 'ONT belum terhubung ke OLT.';
        syncSuccess.value = false;
        return;
    }

    syncing.value = true;
    syncMessage.value = '';

    try {
        const response = await fetch(route('olts.sync-ont-signal', { olt: props.ont.olt.id, ont: props.ont.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        syncSuccess.value = data.success;
        syncMessage.value = data.message;

        if (data.success && data.data) {
            signalData.value = data.data;
        }
    } catch (e) {
        syncSuccess.value = false;
        syncMessage.value = 'Gagal menghubungi server.';
    } finally {
        syncing.value = false;
        setTimeout(() => { syncMessage.value = ''; }, 8000);
    }
}
</script>

<template>
    <Head :title="ont.name || ont.serial_number" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ONT: {{ ont.name || ont.serial_number }}</h2>
                <div class="flex items-center gap-2">
                    <button
                        v-if="ont.olt?.id && ont.ont_id_number"
                        @click="refreshSignal"
                        :disabled="syncing"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="syncing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ syncing ? 'Reading...' : 'Refresh Signal' }}
                    </button>
                    <Link :href="route('onts.edit', ont.id)" class="rounded-md bg-green-600 px-4 py-2 text-sm text-white">Edit</Link>
                </div>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Sync message -->
            <div v-if="syncMessage" class="rounded-lg p-4 text-sm" :class="syncSuccess ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'">
                {{ syncMessage }}
                <div v-if="syncSuccess && signalData" class="mt-2 flex gap-6 font-semibold">
                    <span :class="signalData.rx_power < -25 ? 'text-red-600' : 'text-green-700'">Rx: {{ signalData.rx_power }} dBm</span>
                    <span>Tx: {{ signalData.tx_power }} dBm</span>
                    <span v-if="signalData.temperature" class="text-gray-600">Temp: {{ signalData.temperature }}°C</span>
                    <span v-if="signalData.voltage" class="text-gray-600">Volt: {{ signalData.voltage }}V</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                <div><span class="text-xs text-gray-500">Serial Number</span><p class="font-semibold">{{ ont.serial_number || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Status</span><p><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-green-100 text-green-800': ont.status === 'online', 'bg-red-100 text-red-800': ont.status !== 'online' }">{{ ont.status }}</span></p></div>
                <div>
                    <span class="text-xs text-gray-500">Rx Power</span>
                    <p class="font-semibold" :class="{ 'text-red-600': rxPower && rxPower < -25, 'text-green-600': rxPower && rxPower >= -25 }">
                        {{ rxPower ? rxPower + ' dBm' : '-' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Tx Power</span>
                    <p class="font-semibold">{{ txPower ? txPower + ' dBm' : '-' }}</p>
                </div>
                <div><span class="text-xs text-gray-500">Customer</span><p class="font-semibold"><Link v-if="ont.customer" :href="route('customers.show', ont.customer.id)" class="text-blue-600 hover:underline">{{ ont.customer.name }}</Link><span v-else>-</span></p></div>
                <div><span class="text-xs text-gray-500">ODP</span><p class="font-semibold">{{ ont.odp?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">ODC</span><p class="font-semibold">{{ ont.odp?.odc?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">OLT</span><p class="font-semibold">{{ ont.olt?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">PON Port</span><p class="font-semibold">{{ ont.pon_port ? `${ont.pon_port.slot}/${ont.pon_port.port}` : '-' }}</p></div>
                <div><span class="text-xs text-gray-500">ONT ID</span><p class="font-semibold">{{ ont.ont_id_number ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Last Online</span><p class="font-semibold">{{ ont.last_online_at ? new Date(ont.last_online_at).toLocaleString('id-ID') : '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Coordinates</span><p class="font-semibold">{{ ont.lat && ont.lng ? `${ont.lat}, ${ont.lng}` : '-' }}</p></div>
            </div>

            <!-- Signal Quality Indicator -->
            <div v-if="rxPower" class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Kualitas Signal</h3>
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="h-3 rounded-full bg-gray-200 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :class="{
                                    'bg-green-500': rxPower >= -20,
                                    'bg-yellow-500': rxPower >= -25 && rxPower < -20,
                                    'bg-orange-500': rxPower >= -28 && rxPower < -25,
                                    'bg-red-500': rxPower < -28,
                                }"
                                :style="{ width: Math.max(5, Math.min(100, ((rxPower + 35) / 27) * 100)) + '%' }"
                            ></div>
                        </div>
                    </div>
                    <span class="text-sm font-semibold min-w-[80px] text-right"
                        :class="{
                            'text-green-600': rxPower >= -20,
                            'text-yellow-600': rxPower >= -25 && rxPower < -20,
                            'text-orange-600': rxPower >= -28 && rxPower < -25,
                            'text-red-600': rxPower < -28,
                        }">
                        {{ rxPower >= -20 ? 'Bagus' : rxPower >= -25 ? 'Normal' : rxPower >= -28 ? 'Lemah' : 'Kritis' }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-2">Normal: -8 s/d -25 dBm | Lemah: -25 s/d -28 dBm | Kritis: &lt; -28 dBm</p>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
