<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ ont: Object });
</script>

<template>
    <Head :title="ont.name || ont.serial_number" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ONT: {{ ont.name || ont.serial_number }}</h2>
                <Link :href="route('onts.edit', ont.id)" class="rounded-md bg-green-600 px-4 py-2 text-sm text-white">Edit</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                <div><span class="text-xs text-gray-500">Serial Number</span><p class="font-semibold">{{ ont.serial_number || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Status</span><p><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-green-100 text-green-800': ont.status === 'online', 'bg-red-100 text-red-800': ont.status !== 'online' }">{{ ont.status }}</span></p></div>
                <div><span class="text-xs text-gray-500">Rx Power</span><p class="font-semibold" :class="{ 'text-red-600': ont.rx_power && ont.rx_power < -25 }">{{ ont.rx_power ? ont.rx_power + ' dBm' : '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Tx Power</span><p class="font-semibold">{{ ont.tx_power ? ont.tx_power + ' dBm' : '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Customer</span><p class="font-semibold"><Link v-if="ont.customer" :href="route('customers.show', ont.customer.id)" class="text-blue-600 hover:underline">{{ ont.customer.name }}</Link><span v-else>-</span></p></div>
                <div><span class="text-xs text-gray-500">ODP</span><p class="font-semibold">{{ ont.odp?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">ODC</span><p class="font-semibold">{{ ont.odp?.odc?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">OLT</span><p class="font-semibold">{{ ont.olt?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">PON Port</span><p class="font-semibold">{{ ont.pon_port ? `${ont.pon_port.slot}/${ont.pon_port.port}` : '-' }}</p></div>
                <div><span class="text-xs text-gray-500">ONT ID</span><p class="font-semibold">{{ ont.ont_id_number ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Last Online</span><p class="font-semibold">{{ ont.last_online_at ? new Date(ont.last_online_at).toLocaleString('id-ID') : '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Coordinates</span><p class="font-semibold">{{ ont.lat }}, {{ ont.lng }}</p></div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
