<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentAlarms: Array,
    recentTickets: Array,
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">FTTH Monitoring Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">Customers</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ stats.customers }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">Mikrotik</div>
                        <div class="mt-1 text-2xl font-bold text-blue-600">{{ stats.mikrotiks }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">OLT</div>
                        <div class="mt-1 text-2xl font-bold text-purple-600">{{ stats.olts }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">ODC / ODP</div>
                        <div class="mt-1 text-2xl font-bold text-indigo-600">{{ stats.odcs }} / {{ stats.odps }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">ONT Online</div>
                        <div class="mt-1 text-2xl font-bold text-green-600">{{ stats.onts_online }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">ONT Offline/LOS</div>
                        <div class="mt-1 text-2xl font-bold text-red-600">{{ stats.onts_offline + stats.onts_los }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">Active PPPoE</div>
                        <div class="mt-1 text-2xl font-bold text-teal-600">{{ stats.active_pppoe }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">Open Tickets</div>
                        <div class="mt-1 text-2xl font-bold text-orange-600">{{ stats.open_tickets }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <div class="text-sm font-medium text-gray-500">Unresolved Alarms</div>
                        <div class="mt-1 text-2xl font-bold text-red-600">{{ stats.unresolved_alarms }}</div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800">Recent Alarms</h3>
                        <div v-if="!recentAlarms.length" class="text-sm text-gray-500">No unresolved alarms.</div>
                        <div class="space-y-2">
                            <div v-for="alarm in recentAlarms" :key="alarm.id"
                                class="flex items-center justify-between rounded-md border px-3 py-2"
                                :class="{
                                    'border-red-300 bg-red-50': alarm.severity === 'critical',
                                    'border-orange-300 bg-orange-50': alarm.severity === 'major',
                                    'border-yellow-300 bg-yellow-50': alarm.severity === 'warning',
                                    'border-gray-200 bg-gray-50': alarm.severity === 'info',
                                }">
                                <div>
                                    <span class="text-xs font-bold uppercase" :class="{
                                        'text-red-700': alarm.severity === 'critical',
                                        'text-orange-700': alarm.severity === 'major',
                                        'text-yellow-700': alarm.severity === 'warning',
                                    }">{{ alarm.severity }}</span>
                                    <p class="text-sm font-medium text-gray-800">{{ alarm.title }}</p>
                                </div>
                                <span class="text-xs text-gray-500">{{ new Date(alarm.created_at).toLocaleString('id-ID') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800">Recent Tickets</h3>
                        <div v-if="!recentTickets.length" class="text-sm text-gray-500">No recent tickets.</div>
                        <div class="space-y-2">
                            <div v-for="ticket in recentTickets" :key="ticket.id"
                                class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2">
                                <div>
                                    <span class="text-xs font-semibold text-gray-500">{{ ticket.ticket_number }}</span>
                                    <p class="text-sm font-medium text-gray-800">{{ ticket.title }}</p>
                                    <p class="text-xs text-gray-500">{{ ticket.customer?.name || '-' }}</p>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="{
                                        'bg-blue-100 text-blue-800': ticket.status === 'open',
                                        'bg-yellow-100 text-yellow-800': ticket.status === 'in_progress',
                                        'bg-green-100 text-green-800': ticket.status === 'resolved',
                                        'bg-gray-100 text-gray-800': ticket.status === 'closed',
                                    }">{{ ticket.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
