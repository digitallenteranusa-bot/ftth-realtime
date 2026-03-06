<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    name: '', vendor: 'zte', host: '', telnet_port: 23, ssh_port: 22,
    username: '', password: '', snmp_community: 'public', snmp_port: 161,
    is_active: true, location: '', lat: '', lng: '', notes: '',
});

function submit() { form.post(route('olts.store')); }
</script>

<template>
    <Head title="Add OLT" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Add OLT</h2></template>
        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4">
                <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name *</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vendor *</label>
                            <select v-model="form.vendor" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                <option value="zte">ZTE</option>
                                <option value="huawei">Huawei</option>
                                <option value="fiberhome">FiberHome</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Host *</label>
                            <input v-model="form.host" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">SSH Port</label>
                            <input v-model="form.ssh_port" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username *</label>
                            <input v-model="form.username" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password *</label>
                            <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input v-model="form.location" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input v-model="form.lat" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input v-model="form.lng" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" />
                        </div>
                    </div>
                    <div><label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600" /><span class="text-sm text-gray-700">Active</span></label></div>
                    <div><label class="block text-sm font-medium text-gray-700">Notes</label><textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea></div>
                    <div class="flex justify-end gap-3">
                        <Link :href="route('olts.index')" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-purple-600 px-4 py-2 text-sm text-white hover:bg-purple-700 disabled:opacity-50">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
