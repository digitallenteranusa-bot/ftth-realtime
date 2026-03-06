<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
const props = defineProps({ tickets: Object, filters: Object });
const status = ref(props.filters?.status || '');
function applyFilters() { router.get(route('tickets.index'), { status: status.value }, { preserveState: true, replace: true }); }
function destroy(id) { if (confirm('Hapus tiket ini?')) router.delete(route('tickets.destroy', id)); }
watch(status, () => applyFilters());
</script>

<template>
    <Head title="Trouble Tickets" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Trouble Tickets</h2>
                <Link :href="route('tickets.create')" class="rounded-md bg-orange-600 px-4 py-2 text-sm text-white hover:bg-orange-700">Create Ticket</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <select v-model="status" class="rounded-md border-gray-300 shadow-sm sm:text-sm">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Ticket #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Assigned</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="t in tickets.data" :key="t.id">
                            <td class="px-6 py-4 text-sm font-medium"><Link :href="route('tickets.show', t.id)" class="text-orange-600 hover:underline">{{ t.ticket_number }}</Link></td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ t.title }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ t.customer?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-red-100 text-red-800': t.priority === 'urgent', 'bg-orange-100 text-orange-800': t.priority === 'high', 'bg-yellow-100 text-yellow-800': t.priority === 'medium', 'bg-gray-100 text-gray-800': t.priority === 'low' }">{{ t.priority }}</span></td>
                            <td class="px-6 py-4 text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-blue-100 text-blue-800': t.status === 'open', 'bg-yellow-100 text-yellow-800': t.status === 'in_progress', 'bg-green-100 text-green-800': t.status === 'resolved', 'bg-gray-100 text-gray-800': t.status === 'closed' }">{{ t.status }}</span></td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ t.assigned_user?.name || '-' }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('tickets.show', t.id)" class="text-indigo-600 hover:underline mr-3">View</Link>
                                <button @click="destroy(t.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
