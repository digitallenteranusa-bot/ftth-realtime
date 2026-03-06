<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
const form = useForm({ name: '', address: '', phone: '', email: '', nik: '', lat: '', lng: '', status: 'active', notes: '' });
function submit() { form.post(route('customers.store')); }
</script>

<template>
    <Head title="Add Customer" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Add Customer</h2></template>
        <div class="py-6"><div class="mx-auto max-w-3xl px-4">
            <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label class="block text-sm font-medium text-gray-700">Name *</label><input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p></div>
                    <div><label class="block text-sm font-medium text-gray-700">Phone *</label><input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">Address *</label><input v-model="form.address" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Email</label><input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">NIK</label><input v-model="form.nik" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Latitude</label><input v-model="form.lat" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700">Longitude</label><input v-model="form.lng" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
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
