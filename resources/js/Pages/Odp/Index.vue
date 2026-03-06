<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
defineProps({ odps: Object });
function destroy(id) { if (confirm('Hapus ODP ini?')) router.delete(route('odps.destroy', id)); }
</script>

<template>
    <Head title="ODP" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ODP (Optical Distribution Point)</h2>
                <Link :href="route('odps.create')" class="rounded-md bg-sky-600 px-4 py-2 text-sm text-white hover:bg-sky-700">Add ODP</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ODC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ONTs</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="odp in odps.data" :key="odp.id">
                            <td class="px-6 py-4 text-sm font-medium"><Link :href="route('odps.show', odp.id)" class="text-sky-600 hover:underline">{{ odp.name }}</Link></td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odp.odc?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odp.used_ports }}/{{ odp.capacity }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odp.onts_count }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('odps.edit', odp.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                                <button @click="destroy(odp.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
