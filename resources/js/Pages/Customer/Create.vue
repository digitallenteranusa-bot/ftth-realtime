<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
defineProps({ odps: Array, olts: Array, ponPorts: Array });
const form = useForm({
    name: '', address: '', phone: '', bandwidth: '', lat: '', lng: '', status: 'active', notes: '',
    // ONT fields
    ont_name: '', ont_serial_number: '', odp_id: '', olt_id: '', pon_port_id: '', ont_id_number: '',
});
function submit() { form.post(route('customers.store')); }
</script>

<template>
    <Head title="Add Customer" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Add Customer</h2></template>
        <div class="py-6"><div class="mx-auto max-w-3xl px-4">
            <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">
                <!-- Customer Section -->
                <div>
                    <h3 class="mb-3 text-sm font-bold text-gray-800 border-b pb-2">Data Customer</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm font-medium text-gray-700">Nama *</label><input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p></div>
                        <div><label class="block text-sm font-medium text-gray-700">Phone *</label><input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p></div>
                        <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">Alamat *</label><input v-model="form.address" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p></div>
                        <div><label class="block text-sm font-medium text-gray-700">Bandwidth</label>
                            <select v-model="form.bandwidth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
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
                        <div><label class="block text-sm font-medium text-gray-700">Merk / Name ONT</label><input v-model="form.ont_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Serial Number</label><input v-model="form.ont_serial_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.ont_serial_number" class="mt-1 text-sm text-red-600">{{ form.errors.ont_serial_number }}</p></div>
                        <div><label class="block text-sm font-medium text-gray-700">ODP</label><select v-model="form.odp_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- Pilih ODP --</option><option v-for="o in odps" :key="o.id" :value="o.id">{{ o.name }}</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700">OLT</label><select v-model="form.olt_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- Pilih OLT --</option><option v-for="o in olts" :key="o.id" :value="o.id">{{ o.name }}</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700">PON Port</label><select v-model="form.pon_port_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- None --</option><option v-for="p in ponPorts.filter(p => !form.olt_id || p.olt_id == form.olt_id)" :key="p.id" :value="p.id">{{ p.olt?.name }} - {{ p.slot }}/{{ p.port }}</option></select></div>
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
                    <Link :href="route('customers.index')" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-teal-600 px-4 py-2 text-sm text-white disabled:opacity-50">Save</button>
                </div>
            </form>
        </div></div>
    </AuthenticatedLayout>
</template>
