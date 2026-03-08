<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
const props = defineProps({ odcs: Object, filters: Object });
const search = ref(props.filters?.search || '');
function applyFilters() { router.get(route('odcs.index'), { search: search.value }, { preserveState: true, replace: true }); }
function destroy(id) { if (confirm('Hapus ODC ini?')) router.delete(route('odcs.destroy', id)); }
watch(search, () => applyFilters());
</script>

<template>
    <Head title="ODC" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ODC (Optical Distribution Cabinet)</h2>
                <Link :href="route('odcs.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 text-center">Add ODC</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4"><input v-model="search" type="text" placeholder="Search name, address..." class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>

            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">OLT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ODPs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Splitter</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="odc in odcs.data" :key="odc.id">
                            <td class="px-6 py-4 text-sm font-medium"><Link :href="route('odcs.show', odc.id)" class="text-indigo-600 hover:underline">{{ odc.name }}</Link></td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ odc.olt?.name || '-' }}</td>
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

            <!-- Mobile Cards -->
            <div class="sm:hidden space-y-3">
                <div v-for="odc in odcs.data" :key="odc.id" class="rounded-lg bg-white p-4 shadow">
                    <div class="flex items-center justify-between mb-2">
                        <Link :href="route('odcs.show', odc.id)" class="text-sm font-semibold text-indigo-600 hover:underline">{{ odc.name }}</Link>
                        <span class="text-xs text-gray-400">{{ odc.splitter_ratio }}</span>
                    </div>
                    <div class="space-y-1 text-sm text-gray-500">
                        <p><span class="font-medium text-gray-700">OLT:</span> {{ odc.olt?.name || '-' }}</p>
                        <p><span class="font-medium text-gray-700">Capacity:</span> {{ odc.used_ports }}/{{ odc.capacity }}</p>
                        <p><span class="font-medium text-gray-700">ODPs:</span> {{ odc.odps_count }}</p>
                    </div>
                    <div class="mt-3 flex gap-3 border-t pt-3">
                        <Link :href="route('odcs.edit', odc.id)" class="text-sm text-indigo-600 hover:underline">Edit</Link>
                        <button @click="destroy(odc.id)" class="text-sm text-red-600 hover:underline">Delete</button>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-center" v-if="odcs.links">
                <Link v-for="link in odcs.links" :key="link.label" :href="link.url || '#'" class="mx-1 rounded px-3 py-1 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" v-html="link.label" />
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
