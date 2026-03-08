<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
defineProps({ odps: Array, customers: Array, olts: Array, ponPorts: Array });
const form = useForm({ odp_id: '', customer_id: '', olt_id: '', pon_port_id: '', name: '', serial_number: '', ont_id_number: '', status: 'unknown', lat: '', lng: '', notes: '' });
function submit() { form.post(route('onts.store')); }
</script>

<template>
    <Head title="Add ONT" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Add ONT</h2></template>
        <div class="py-6"><div class="mx-auto max-w-3xl px-4">
            <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label class="block text-sm font-medium text-gray-700">Name / Merk ONT</label><input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Serial Number</label><input v-model="form.serial_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Customer</label><select v-model="form.customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- None --</option><option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">ODP</label><select v-model="form.odp_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- Pilih ODP --</option><option v-for="o in odps" :key="o.id" :value="o.id">{{ o.name }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">OLT</label><select v-model="form.olt_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- Pilih OLT --</option><option v-for="o in olts" :key="o.id" :value="o.id">{{ o.name }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">PON Port</label><select v-model="form.pon_port_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- None --</option><option v-for="p in ponPorts.filter(p => !form.olt_id || p.olt_id == form.olt_id)" :key="p.id" :value="p.id">{{ p.olt?.name }} - {{ p.slot }}/{{ p.port }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">ONT ID Number</label><input v-model="form.ont_id_number" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Latitude</label><input v-model="form.lat" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Longitude</label><input v-model="form.lng" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <LocationPicker :lat="form.lat" :lng="form.lng" @update:lat="v => form.lat = v" @update:lng="v => form.lng = v" label="Pilih Lokasi di Peta" />
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
