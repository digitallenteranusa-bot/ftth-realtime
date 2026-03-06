<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
defineProps({ ticket: Object });
function updateStatus(id, status) { router.put(route('tickets.update', id), { status }); }
</script>

<template>
    <Head :title="ticket.ticket_number" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">{{ ticket.ticket_number }}</h2></template>
        <div class="py-6"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="text-lg font-bold text-gray-900">{{ ticket.title }}</h3>
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div><span class="text-xs text-gray-500">Customer</span><p class="font-semibold">{{ ticket.customer?.name || '-' }}</p></div>
                    <div><span class="text-xs text-gray-500">Priority</span><p><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-red-100 text-red-800': ticket.priority === 'urgent', 'bg-orange-100 text-orange-800': ticket.priority === 'high' }">{{ ticket.priority }}</span></p></div>
                    <div><span class="text-xs text-gray-500">Status</span><p><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-blue-100 text-blue-800': ticket.status === 'open', 'bg-yellow-100 text-yellow-800': ticket.status === 'in_progress', 'bg-green-100 text-green-800': ticket.status === 'resolved' }">{{ ticket.status }}</span></p></div>
                    <div><span class="text-xs text-gray-500">Assigned to</span><p class="font-semibold">{{ ticket.assigned_user?.name || '-' }}</p></div>
                    <div><span class="text-xs text-gray-500">Created</span><p class="text-sm">{{ new Date(ticket.created_at).toLocaleString('id-ID') }}</p></div>
                    <div v-if="ticket.resolved_at"><span class="text-xs text-gray-500">Resolved</span><p class="text-sm">{{ new Date(ticket.resolved_at).toLocaleString('id-ID') }}</p></div>
                </div>
                <div class="mt-4" v-if="ticket.description">
                    <span class="text-xs text-gray-500">Description</span>
                    <p class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">{{ ticket.description }}</p>
                </div>
                <div class="mt-6 flex gap-2" v-if="ticket.status !== 'closed'">
                    <button v-if="ticket.status === 'open'" @click="updateStatus(ticket.id, 'in_progress')" class="rounded-md bg-yellow-500 px-3 py-1.5 text-sm text-white">Start Progress</button>
                    <button v-if="ticket.status === 'in_progress'" @click="updateStatus(ticket.id, 'resolved')" class="rounded-md bg-green-600 px-3 py-1.5 text-sm text-white">Resolve</button>
                    <button v-if="ticket.status === 'resolved'" @click="updateStatus(ticket.id, 'closed')" class="rounded-md bg-gray-600 px-3 py-1.5 text-sm text-white">Close</button>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
