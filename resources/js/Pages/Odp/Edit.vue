<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
const props = defineProps({ odp: Object, odcs: Array });
const form = useForm({ name: props.odp.name, odc_id: props.odp.odc_id, lat: props.odp.lat, lng: props.odp.lng, address: props.odp.address || '', capacity: props.odp.capacity, used_ports: props.odp.used_ports, splitter_ratio: props.odp.splitter_ratio, is_active: props.odp.is_active, notes: props.odp.notes || '' });
function submit() { form.put(route('odps.update', props.odp.id)); }
</script>

<template>
    <Head title="Edit ODP" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Edit ODP: {{ odp.name }}</h2></template>
        <div class="py-6"><div class="mx-auto max-w-3xl px-4">
            <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label class="block text-sm font-medium text-gray-700">Name *</label><input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">ODC *</label><select v-model="form.odc_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option v-for="odc in odcs" :key="odc.id" :value="odc.id">{{ odc.name }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Latitude *</label><input v-model="form.lat" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Longitude *</label><input v-model="form.lng" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Capacity</label><input v-model="form.capacity" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Splitter Ratio</label><input v-model="form.splitter_ratio" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                </div>
                <div><label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600" /><span class="text-sm text-gray-700">Active</span></label></div>
                <div><label class="block text-sm font-medium text-gray-700">Notes</label><textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea></div>
                <div class="flex justify-end gap-3">
                    <Link :href="route('odps.index')" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-sky-600 px-4 py-2 text-sm text-white disabled:opacity-50">Update</button>
                </div>
            </form>
        </div></div>
    </AuthenticatedLayout>
</template>
