<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ olts: Object });

function destroy(id) {
    if (confirm('Hapus OLT ini?')) router.delete(route('olts.destroy', id));
}
</script>

<template>
    <Head title="OLT" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">OLT Devices</h2>
                <Link :href="route('olts.create')" class="rounded-md bg-purple-600 px-4 py-2 text-sm text-white hover:bg-purple-700 text-center">Add OLT</Link>
            </div>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Mobile cards -->
                <div class="space-y-3 sm:hidden">
                    <div v-for="olt in olts.data" :key="olt.id" class="rounded-lg bg-white p-4 shadow">
                        <div class="flex items-center justify-between">
                            <Link :href="route('olts.show', olt.id)" class="text-sm font-semibold text-purple-600">{{ olt.name }}</Link>
                            <span :class="olt.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="rounded-full px-2 py-0.5 text-xs font-semibold">{{ olt.is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div class="mt-2 space-y-1 text-sm text-gray-500">
                            <p><span class="uppercase text-xs font-medium text-gray-400">Vendor:</span> {{ olt.vendor }}</p>
                            <p>Host: {{ olt.host }}</p>
                            <div class="flex gap-4">
                                <p>PON Ports: {{ olt.pon_ports_count }}</p>
                                <p>ONTs: {{ olt.onts_count }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-3 text-sm">
                            <Link :href="route('olts.edit', olt.id)" class="text-indigo-600">Edit</Link>
                            <button @click="destroy(olt.id)" class="text-red-600">Delete</button>
                        </div>
                    </div>
                </div>
                <!-- Desktop table -->
                <div class="hidden sm:block overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Host</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">PON Ports</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ONTs</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="olt in olts.data" :key="olt.id">
                                <td class="px-6 py-4 text-sm font-medium"><Link :href="route('olts.show', olt.id)" class="text-purple-600 hover:underline">{{ olt.name }}</Link></td>
                                <td class="px-6 py-4 text-sm uppercase text-gray-500">{{ olt.vendor }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ olt.host }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ olt.pon_ports_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ olt.onts_count }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="olt.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="rounded-full px-2 py-0.5 text-xs font-semibold">{{ olt.is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <Link :href="route('olts.edit', olt.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                                    <button @click="destroy(olt.id)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
