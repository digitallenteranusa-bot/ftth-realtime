<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
defineProps({ odps: Array, customers: Array, olts: Array, ponPorts: Array });
const customerMode = ref('new'); // 'new' or 'existing'
const form = useForm({
    odp_id: '', customer_id: '', olt_id: '', pon_port_id: '',
    name: '', serial_number: '', ont_id_number: '', status: 'unknown',
    lat: '', lng: '', notes: '',
    // customer fields
    customer_mode: 'new',
    customer_name: '', customer_phone: '', customer_address: '', customer_bandwidth: '',
});
function submit() {
    form.customer_mode = customerMode.value;
    form.post(route('onts.store'));
}
</script>

<template>
    <Head title="Add ONT" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Add ONT</h2></template>
        <div class="py-6"><div class="mx-auto max-w-3xl px-4">
            <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">
                <!-- Customer Section -->
                <div>
                    <h3 class="mb-3 text-sm font-bold text-gray-800 border-b pb-2">Data Customer</h3>
                    <div class="mb-3 flex gap-4">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" v-model="customerMode" value="new" class="text-teal-600" /> Customer Baru
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" v-model="customerMode" value="existing" class="text-teal-600" /> Customer Existing
                        </label>
                    </div>
                    <div v-if="customerMode === 'existing'" class="grid grid-cols-1 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700">Pilih Customer</label><select v-model="form.customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- Pilih Customer --</option><option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                    </div>
                    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm font-medium text-gray-700">Nama Customer *</label><input v-model="form.customer_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.customer_name" class="mt-1 text-sm text-red-600">{{ form.errors.customer_name }}</p></div>
                        <div><label class="block text-sm font-medium text-gray-700">Phone *</label><input v-model="form.customer_phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.customer_phone" class="mt-1 text-sm text-red-600">{{ form.errors.customer_phone }}</p></div>
                        <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">Address *</label><input v-model="form.customer_address" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.customer_address" class="mt-1 text-sm text-red-600">{{ form.errors.customer_address }}</p></div>
                        <div><label class="block text-sm font-medium text-gray-700">Bandwidth</label>
                            <select v-model="form.customer_bandwidth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                <option value="">-- Pilih Bandwidth --</option>
                                <option value="5 Mbps">5 Mbps</option>
                                <option value="10 Mbps">10 Mbps</option>
                                <option value="20 Mbps">20 Mbps</option>
                                <option value="30 Mbps">30 Mbps</option>
                                <option value="50 Mbps">50 Mbps</option>
                                <option value="100 Mbps">100 Mbps</option>
                                <option value="200 Mbps">200 Mbps</option>
                                <option value="500 Mbps">500 Mbps</option>
                                <option value="1 Gbps">1 Gbps</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ONT Section -->
                <div>
                    <h3 class="mb-3 text-sm font-bold text-gray-800 border-b pb-2">Data ONT</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm font-medium text-gray-700">Name / Merk ONT</label><input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Serial Number</label><input v-model="form.serial_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">ODP</label><select v-model="form.odp_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- Pilih ODP --</option><option v-for="o in odps" :key="o.id" :value="o.id">{{ o.name }}</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700">OLT</label><select v-model="form.olt_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- Pilih OLT --</option><option v-for="o in olts" :key="o.id" :value="o.id">{{ o.name }}</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700">PON Port</label><select v-model="form.pon_port_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- None --</option><option v-for="p in ponPorts" :key="p.id" :value="p.id">{{ p.olt?.name }} - {{ p.slot }}/{{ p.port }}</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700">ONT ID Number</label><input v-model="form.ont_id_number" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    </div>
                </div>

                <!-- Location Section -->
                <div>
                    <h3 class="mb-3 text-sm font-bold text-gray-800 border-b pb-2">Lokasi</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm font-medium text-gray-700">Latitude</label><input v-model="form.lat" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Longitude</label><input v-model="form.lng" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <LocationPicker :lat="form.lat" :lng="form.lng" @update:lat="v => form.lat = v" @update:lng="v => form.lng = v" label="Pilih Lokasi di Peta" />
                    </div>
                </div>

                <div><label class="block text-sm font-medium text-gray-700">Notes</label><textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea></div>
                <div class="flex justify-end gap-3">
                    <Link :href="route('onts.index')" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-green-600 px-4 py-2 text-sm text-white disabled:opacity-50">Save</button>
                </div>
            </form>
        </div></div>
    </AuthenticatedLayout>
</template>
