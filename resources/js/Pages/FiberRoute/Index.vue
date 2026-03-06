<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
defineProps({ fiberRoutes: Object });
function destroy(id) { if (confirm('Hapus fiber route ini?')) router.delete(route('fiber-routes.destroy', id)); }
</script>

<template>
    <Head title="Fiber Routes" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Fiber Routes</h2></template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Destination</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="r in fiberRoutes.data" :key="r.id">
                            <td class="px-6 py-4 text-sm font-medium">{{ r.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ r.source_type }} #{{ r.source_id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ r.destination_type }} #{{ r.destination_id }}</td>
                            <td class="px-6 py-4 text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-green-100 text-green-800': r.status === 'active', 'bg-red-100 text-red-800': r.status === 'inactive', 'bg-yellow-100 text-yellow-800': r.status === 'maintenance' }">{{ r.status }}</span></td>
                            <td class="px-6 py-4 text-right text-sm"><button @click="destroy(r.id)" class="text-red-600 hover:underline">Delete</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
