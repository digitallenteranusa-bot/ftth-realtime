<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ mikrotik: Object, pppoeSessions: Object, interfaces: Array });
</script>

<template>
    <Head :title="mikrotik.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ mikrotik.name }}</h2>
                <Link :href="route('mikrotiks.edit', mikrotik.id)" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Edit</Link>
            </div>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                    <div><span class="text-xs text-gray-500">Host</span><p class="font-semibold">{{ mikrotik.host }}:{{ mikrotik.api_port }}</p></div>
                    <div><span class="text-xs text-gray-500">Location</span><p class="font-semibold">{{ mikrotik.location || '-' }}</p></div>
                    <div><span class="text-xs text-gray-500">Status</span><p><span :class="mikrotik.is_active ? 'text-green-600' : 'text-red-600'" class="font-semibold">{{ mikrotik.is_active ? 'Active' : 'Inactive' }}</span></p></div>
                    <div><span class="text-xs text-gray-500">Coordinates</span><p class="font-semibold">{{ mikrotik.lat }}, {{ mikrotik.lng }}</p></div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold">Active PPPoE Sessions ({{ pppoeSessions.total || 0 }})</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Username</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">IP Address</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">MAC</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uptime</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Service</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="s in pppoeSessions.data" :key="s.id">
                                <td class="px-4 py-2 text-sm">{{ s.username }}</td>
                                <td class="px-4 py-2 text-sm">{{ s.ip_address || '-' }}</td>
                                <td class="px-4 py-2 text-sm">{{ s.caller_id || '-' }}</td>
                                <td class="px-4 py-2 text-sm">{{ s.uptime || '-' }}</td>
                                <td class="px-4 py-2 text-sm">{{ s.service || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold">Interfaces</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Interface</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">RX Bytes</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">TX Bytes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="iface in interfaces" :key="iface.id">
                                <td class="px-4 py-2 text-sm font-medium">{{ iface.interface_name }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span :class="iface.status === 'up' ? 'text-green-600' : 'text-red-600'">{{ iface.status }}</span>
                                </td>
                                <td class="px-4 py-2 text-sm">{{ (iface.rx_bytes / 1048576).toFixed(2) }} MB</td>
                                <td class="px-4 py-2 text-sm">{{ (iface.tx_bytes / 1048576).toFixed(2) }} MB</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
