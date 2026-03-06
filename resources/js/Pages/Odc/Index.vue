<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
defineProps({ odcs: Object });
function destroy(id) { if (confirm('Hapus ODC ini?')) router.delete(route('odcs.destroy', id)); }
</script>

<template>
    <Head title="ODC" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ODC (Optical Distribution Cabinet)</h2>
                <Link :href="route('odcs.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Add ODC</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ODPs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Splitter</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="odc in odcs.data" :key="odc.id">
                            <td class="px-6 py-4 text-sm font-medium"><Link :href="route('odcs.show', odc.id)" class="text-indigo-600 hover:underline">{{ odc.name }}</Link></td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odc.address || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odc.used_ports }}/{{ odc.capacity }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odc.odps_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odc.splitter_ratio }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('odcs.edit', odc.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                                <button @click="destroy(odc.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
