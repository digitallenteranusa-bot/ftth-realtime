<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ customer: Object });
</script>

<template>
    <Head :title="customer.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ customer.name }}</h2>
                <Link :href="route('customers.edit', customer.id)" class="rounded-md bg-teal-600 px-4 py-2 text-sm text-white">Edit</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                <div><span class="text-xs text-gray-500">Phone</span><p class="font-semibold">{{ customer.phone }}</p></div>
                <div><span class="text-xs text-gray-500">Email</span><p class="font-semibold">{{ customer.email || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">NIK</span><p class="font-semibold">{{ customer.nik || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Status</span><p><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-green-100 text-green-800': customer.status === 'active' }">{{ customer.status }}</span></p></div>
                <div class="sm:col-span-2"><span class="text-xs text-gray-500">Address</span><p class="font-semibold">{{ customer.address }}</p></div>
            </div>
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold">ONTs ({{ customer.onts?.length || 0 }})</h3>
                <div class="space-y-2">
                    <div v-for="ont in customer.onts" :key="ont.id" class="flex items-center gap-3 rounded border p-3">
                        <div class="h-3 w-3 rounded-full" :class="{ 'bg-green-500': ont.status === 'online', 'bg-red-500': ont.status !== 'online' }"></div>
                        <div>
                            <Link :href="route('onts.show', ont.id)" class="text-sm font-medium text-blue-600 hover:underline">{{ ont.serial_number }}</Link>
                            <span class="ml-2 text-xs text-gray-500">{{ ont.status }} | ODP: {{ ont.odp?.name || '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white p-6 shadow" v-if="customer.trouble_tickets?.length">
                <h3 class="mb-4 text-lg font-semibold">Trouble Tickets</h3>
                <div class="space-y-2">
                    <div v-for="t in customer.trouble_tickets" :key="t.id" class="flex items-center justify-between rounded border p-3">
                        <div><span class="text-xs text-gray-500">{{ t.ticket_number }}</span><p class="text-sm font-medium">{{ t.title }}</p></div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-blue-100 text-blue-800': t.status === 'open', 'bg-green-100 text-green-800': t.status === 'resolved' }">{{ t.status }}</span>
                    </div>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
