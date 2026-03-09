<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({ olt: Object });
const showAddPort = ref(false);
const expandedPorts = ref({});
const portForm = useForm({ olt_id: '', slot: 0, port: 1, description: '', is_active: true });

const syncing = ref(false);
const syncMessage = ref('');
const syncSuccess = ref(false);
const syncMethod = ref('');

const connStatus = ref(null);
const testingConn = ref(false);

const discovering = ref(false);
const discoverMessage = ref('');
const discoverSuccess = ref(false);
const discoveredOnus = ref([]);

function togglePort(portId) {
    expandedPorts.value[portId] = !expandedPorts.value[portId];
}

function addPort(oltId) {
    portForm.olt_id = oltId;
    portForm.post(route('pon-ports.store'), {
        onSuccess: () => {
            portForm.reset();
            portForm.port++;
            showAddPort.value = false;
        },
    });
}

function deletePort(portId) {
    if (confirm('Hapus PON Port ini?')) {
        router.delete(route('pon-ports.destroy', { id: portId }));
    }
}

async function testConnection(oltId) {
    testingConn.value = true;
    try {
        const response = await fetch(route('olts.test-connection', oltId), {
            headers: { 'Accept': 'application/json' },
        });
        connStatus.value = await response.json();
    } catch (e) {
        connStatus.value = { success: false, message: 'Gagal menghubungi server.' };
    } finally {
        testingConn.value = false;
    }
}

function syncSignal(oltId) {
    syncing.value = true;
    syncMessage.value = '';
    syncMethod.value = '';

    router.post(route('olts.sync-signal', oltId), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props.flash;
            syncSuccess.value = flash?.success ?? false;
            syncMessage.value = flash?.message ?? 'Sync selesai.';
            syncMethod.value = flash?.method ?? '';
        },
        onError: () => {
            syncSuccess.value = false;
            syncMessage.value = 'Gagal menghubungi server.';
        },
        onFinish: () => {
            syncing.value = false;
            setTimeout(() => { syncMessage.value = ''; }, 8000);
        },
    });
}

function discoverOnus(oltId) {
    discovering.value = true;
    discoverMessage.value = '';
    discoveredOnus.value = [];

    router.post(route('olts.discover-onus', oltId), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props.flash;
            discoverSuccess.value = flash?.success ?? false;
            discoverMessage.value = flash?.message ?? 'Discover selesai.';
            discoveredOnus.value = flash?.discovered ?? [];
        },
        onError: () => {
            discoverSuccess.value = false;
            discoverMessage.value = 'Gagal menghubungi server.';
        },
        onFinish: () => {
            discovering.value = false;
        },
    });
}

onMounted(() => {
    testConnection(props.olt.id);
});
</script>

<template>
    <Head :title="olt.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ olt.name }}</h2>
                <div class="flex items-center gap-2">
                    <button
                        @click="discoverOnus(olt.id)"
                        :disabled="discovering"
                        class="rounded-md bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700 disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="discovering" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        {{ discovering ? 'Discovering...' : 'Discover ONU' }}
                    </button>
                    <button
                        @click="syncSignal(olt.id)"
                        :disabled="syncing"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="syncing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ syncing ? 'Syncing...' : 'Sync Signal' }}
                    </button>
                    <Link :href="route('olts.edit', olt.id)" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white">Edit</Link>
                </div>
            </div>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Sync message -->
                <div v-if="syncMessage" class="rounded-lg p-4 text-sm" :class="syncSuccess ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'">
                    {{ syncMessage }}
                    <span v-if="syncMethod" class="ml-2 rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-medium">{{ syncMethod }}</span>
                </div>

                <!-- Discover results -->
                <div v-if="discoverMessage" class="rounded-lg p-4 text-sm border" :class="discoverSuccess ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-red-50 text-red-800 border-red-200'">
                    <p class="font-medium">{{ discoverMessage }}</p>
                    <div v-if="discoveredOnus.length" class="mt-3 space-y-1">
                        <p class="text-xs font-semibold text-gray-600">ONU ditemukan:</p>
                        <div v-for="onu in discoveredOnus" :key="onu.if_index" class="flex items-center gap-3 text-xs bg-white rounded px-3 py-1.5 border">
                            <span class="font-mono font-semibold">{{ onu.if_descr }}</span>
                            <span class="rounded-full px-2 py-0.5" :class="onu.status === 'online' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">{{ onu.status }}</span>
                            <span class="text-gray-400">Port: {{ onu.slot }}/{{ onu.port }} | ONU ID: {{ onu.onu_id }}</span>
                        </div>
                    </div>
                </div>

                <!-- Connection Status -->
                <div v-if="connStatus" class="rounded-lg p-4 text-sm border" :class="connStatus.success ? 'bg-emerald-50 border-emerald-200' : 'bg-yellow-50 border-yellow-200'">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="font-medium" :class="connStatus.success ? 'text-emerald-800' : 'text-yellow-800'">{{ connStatus.message }}</span>
                            <div v-if="connStatus.connections" class="flex gap-2">
                                <span v-if="connStatus.connections.web !== undefined" class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="connStatus.connections.web ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    Web {{ connStatus.connections.web ? 'OK' : 'Fail' }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="connStatus.connections.telnet ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    Telnet {{ connStatus.connections.telnet ? 'OK' : 'Fail' }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="connStatus.connections.snmp ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    SNMP {{ connStatus.connections.snmp ? 'OK' : (olt.snmp_community ? 'Fail' : 'N/A') }}
                                </span>
                            </div>
                        </div>
                        <button @click="testConnection(olt.id)" :disabled="testingConn" class="text-xs text-blue-600 hover:underline">
                            {{ testingConn ? 'Testing...' : 'Re-test' }}
                        </button>
                    </div>
                    <p v-if="connStatus.connections?.snmp_info" class="mt-1 text-xs text-gray-500">{{ connStatus.connections.snmp_info }}</p>
                </div>
                <div v-else-if="testingConn" class="rounded-lg p-4 text-sm bg-gray-50 border border-gray-200 text-gray-600">
                    Testing koneksi ke OLT...
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                    <div><span class="text-xs text-gray-500">Vendor</span><p class="font-semibold uppercase">{{ olt.vendor }}</p></div>
                    <div><span class="text-xs text-gray-500">Host</span><p class="font-semibold">{{ olt.host }}</p></div>
                    <div><span class="text-xs text-gray-500">Location</span><p class="font-semibold">{{ olt.location || '-' }}</p></div>
                    <div><span class="text-xs text-gray-500">Status</span><p :class="olt.is_active ? 'text-green-600' : 'text-red-600'" class="font-semibold">{{ olt.is_active ? 'Active' : 'Inactive' }}</p></div>
                    <div><span class="text-xs text-gray-500">Total ONT</span><p class="font-semibold">{{ olt.pon_ports?.reduce((sum, p) => sum + (p.onts?.length || 0), 0) || 0 }}</p></div>
                    <div><span class="text-xs text-gray-500">PON Ports</span><p class="font-semibold">{{ olt.pon_ports?.length || 0 }}</p></div>
                    <div><span class="text-xs text-gray-500">SNMP Community</span><p class="font-semibold">{{ olt.snmp_community || '-' }}</p></div>
                    <div><span class="text-xs text-gray-500">Telnet Port</span><p class="font-semibold">{{ olt.telnet_port || 23 }}</p></div>
                </div>

                <!-- PON Ports -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">PON Ports</h3>
                        <button @click="showAddPort = !showAddPort" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs text-white hover:bg-indigo-700">
                            {{ showAddPort ? 'Batal' : '+ Tambah PON Port' }}
                        </button>
                    </div>

                    <div v-if="showAddPort" class="mb-4 p-4 bg-gray-50 rounded-lg border">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 items-end">
                            <div><label class="block text-xs font-medium text-gray-600">Slot</label><input v-model="portForm.slot" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600">Port</label><input v-model="portForm.port" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600">Keterangan</label><input v-model="portForm.description" type="text" placeholder="PON 1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                            <button @click="addPort(olt.id)" :disabled="portForm.processing" class="rounded-md bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50">Simpan</button>
                        </div>
                    </div>

                    <div v-if="olt.pon_ports?.length" class="space-y-2">
                        <div v-for="ponPort in olt.pon_ports" :key="ponPort.id" class="rounded-lg border">
                            <!-- PON Port header - clickable -->
                            <div class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50" @click="togglePort(ponPort.id)">
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-400 text-xs">{{ expandedPorts[ponPort.id] ? '▼' : '▶' }}</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ ponPort.description || `PON ${ponPort.slot}/${ponPort.port}` }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-xs" :class="ponPort.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">{{ ponPort.is_active ? 'Active' : 'Inactive' }}</span>
                                    <span class="rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-medium">{{ ponPort.onts?.length || 0 }} ONT</span>
                                </div>
                                <button @click.stop="deletePort(ponPort.id)" class="text-xs text-red-500 hover:underline">Hapus</button>
                            </div>

                            <!-- ONT list - shown on click -->
                            <div v-if="expandedPorts[ponPort.id]" class="border-t px-4 py-3 bg-gray-50">
                                <div v-if="ponPort.onts?.length" class="space-y-2">
                                    <div v-for="ont in ponPort.onts" :key="ont.id" class="rounded border bg-white p-3"
                                        :class="{ 'border-green-200': ont.status === 'online', 'border-red-200': ont.status === 'offline' || ont.status === 'los', 'border-gray-200': ont.status === 'unknown' }">
                                        <div class="flex items-center gap-3">
                                            <div class="h-3 w-3 rounded-full flex-shrink-0" :class="{ 'bg-green-500': ont.status === 'online', 'bg-red-500': ont.status === 'offline' || ont.status === 'los', 'bg-gray-400': ont.status === 'unknown' }"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 truncate">{{ ont.customer?.name || '-' }}</p>
                                            </div>
                                            <span class="rounded-full px-1.5 py-0.5 text-xs flex-shrink-0" :class="{ 'bg-green-100 text-green-700': ont.status === 'online', 'bg-red-100 text-red-700': ont.status === 'offline' || ont.status === 'los', 'bg-gray-100 text-gray-600': ont.status === 'unknown' }">{{ ont.status }}</span>
                                            <Link :href="route('onts.show', ont.id)" class="text-xs text-blue-600 hover:underline flex-shrink-0">Detail</Link>
                                        </div>
                                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 pl-6">
                                            <span>ONT: {{ ont.name || '-' }}</span>
                                            <span>SN: {{ ont.serial_number || '-' }}</span>
                                            <span>ID: {{ ont.ont_id_number ?? '-' }}</span>
                                            <span :class="{ 'text-red-600': ont.rx_power && ont.rx_power < -25, 'text-green-600': ont.rx_power && ont.rx_power >= -25 }">Rx: {{ ont.rx_power ?? '-' }} dBm</span>
                                            <span>Tx: {{ ont.tx_power ?? '-' }} dBm</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-gray-400">Belum ada ONT di port ini.</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500">Belum ada PON Port.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
