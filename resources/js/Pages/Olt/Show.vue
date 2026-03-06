<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ olt: Object });
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

                <div v-for="ponPort in olt.pon_ports" :key="ponPort.id" class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-3 text-md font-semibold text-gray-800">{{ ponPort.description || `PON ${ponPort.slot}/${ponPort.port}` }} <span class="text-sm text-gray-500">({{ ponPort.onts?.length || 0 }} ONTs)</span></h3>
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
                    <p v-else class="text-sm text-gray-500">No ONTs on this port.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
