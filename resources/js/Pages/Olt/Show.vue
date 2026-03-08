<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ olt: Object });
const showAddPort = ref(false);
const portForm = useForm({ olt_id: '', slot: 0, port: 1, description: '', is_active: true });

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

function deletePort(id) {
    if (confirm('Hapus PON Port ini?')) {
        router.delete(route('pon-ports.destroy', id));
    }
}
</script>

<template>
    <Head :title="olt.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ olt.name }}</h2>
                <Link :href="route('olts.edit', olt.id)" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white">Edit</Link>
            </div>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                    <div><span class="text-xs text-gray-500">Vendor</span><p class="font-semibold uppercase">{{ olt.vendor }}</p></div>
                    <div><span class="text-xs text-gray-500">Host</span><p class="font-semibold">{{ olt.host }}</p></div>
                    <div><span class="text-xs text-gray-500">Location</span><p class="font-semibold">{{ olt.location || '-' }}</p></div>
                    <div><span class="text-xs text-gray-500">Status</span><p :class="olt.is_active ? 'text-green-600' : 'text-red-600'" class="font-semibold">{{ olt.is_active ? 'Active' : 'Inactive' }}</p></div>
                </div>

                <!-- PON Ports Management -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">PON Ports ({{ olt.pon_ports?.length || 0 }})</h3>
                        <button @click="showAddPort = !showAddPort" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs text-white hover:bg-indigo-700">
                            {{ showAddPort ? 'Batal' : '+ Tambah PON Port' }}
                        </button>
                    </div>

                    <!-- Add PON Port form -->
                    <div v-if="showAddPort" class="mb-4 p-4 bg-gray-50 rounded-lg border">
                        <div class="grid grid-cols-4 gap-3 items-end">
                            <div><label class="block text-xs font-medium text-gray-600">Slot</label><input v-model="portForm.slot" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600">Port</label><input v-model="portForm.port" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-600">Keterangan</label><input v-model="portForm.description" type="text" placeholder="PON 1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                            <button @click="addPort(olt.id)" :disabled="portForm.processing" class="rounded-md bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50">Simpan</button>
                        </div>
                    </div>

                    <!-- PON Port list -->
                    <div v-if="olt.pon_ports?.length" class="space-y-3">
                        <div v-for="ponPort in olt.pon_ports" :key="ponPort.id" class="rounded-lg border p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold text-gray-800">
                                    {{ ponPort.description || `PON ${ponPort.slot}/${ponPort.port}` }}
                                    <span class="ml-2 text-xs text-gray-500">Slot {{ ponPort.slot }} / Port {{ ponPort.port }}</span>
                                    <span class="ml-2 rounded-full px-2 py-0.5 text-xs" :class="ponPort.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">{{ ponPort.is_active ? 'Active' : 'Inactive' }}</span>
                                    <span class="ml-1 text-xs text-gray-500">({{ ponPort.onts?.length || 0 }} ONTs)</span>
                                </h4>
                                <button @click="deletePort(ponPort.id)" class="text-xs text-red-500 hover:underline">Hapus</button>
                            </div>
                            <div v-if="ponPort.onts?.length" class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div v-for="ont in ponPort.onts" :key="ont.id" class="flex items-center gap-3 rounded border p-3"
                                    :class="{ 'border-green-200 bg-green-50': ont.status === 'online', 'border-red-200 bg-red-50': ont.status === 'offline' || ont.status === 'los', 'border-gray-200': ont.status === 'unknown' }">
                                    <div class="h-3 w-3 rounded-full" :class="{ 'bg-green-500': ont.status === 'online', 'bg-red-500': ont.status === 'offline' || ont.status === 'los', 'bg-gray-400': ont.status === 'unknown' }"></div>
                                    <div>
                                        <Link :href="route('onts.show', ont.id)" class="text-sm font-medium text-blue-600 hover:underline">{{ ont.name || ont.serial_number }}</Link>
                                        <p class="text-xs text-gray-500">ID: {{ ont.ont_id_number }} | Rx: {{ ont.rx_power ?? '-' }} dBm</p>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs text-gray-400">Belum ada ONT di port ini.</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500">Belum ada PON Port. Klik "+ Tambah PON Port" atau jalankan: <code class="bg-gray-100 px-1 rounded">php artisan pon:generate</code></p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
