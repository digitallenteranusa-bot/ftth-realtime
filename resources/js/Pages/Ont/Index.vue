<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ onts: Object, filters: Object });
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

function applyFilters() {
    router.get(route('onts.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true });
}
function destroy(id) { if (confirm('Hapus ONT ini?')) router.delete(route('onts.destroy', id)); }

watch([search, status], () => applyFilters());
</script>

<template>
    <Head title="ONT" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ONT Devices</h2>
                <Link :href="route('onts.create')" class="rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Add ONT</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex gap-4">
                <input v-model="search" type="text" placeholder="Search by name, SN, customer..." class="rounded-md border-gray-300 shadow-sm sm:text-sm w-64" />
                <select v-model="status" class="rounded-md border-gray-300 shadow-sm sm:text-sm">
                    <option value="">All Status</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="los">LOS</option>
                    <option value="dyinggasp">Dying Gasp</option>
                    <option value="unknown">Unknown</option>
                </select>
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name / SN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">ODP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Rx Power</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="ont in onts.data" :key="ont.id">
                            <td class="px-6 py-4 text-sm">
                                <Link :href="route('onts.show', ont.id)" class="font-medium text-blue-600 hover:underline">{{ ont.name || ont.serial_number }}</Link>
                                <p class="text-xs text-gray-400">{{ ont.serial_number }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ ont.customer?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ ont.odp?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="{ 'bg-green-100 text-green-800': ont.status === 'online', 'bg-red-100 text-red-800': ont.status === 'offline' || ont.status === 'los', 'bg-yellow-100 text-yellow-800': ont.status === 'dyinggasp', 'bg-gray-100 text-gray-800': ont.status === 'unknown' }">
                                    {{ ont.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="{ 'text-red-600': ont.rx_power && ont.rx_power < -25, 'text-green-600': ont.rx_power && ont.rx_power >= -25 }">{{ ont.rx_power ? ont.rx_power + ' dBm' : '-' }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('onts.edit', ont.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                                <button @click="destroy(ont.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-center" v-if="onts.links">
                <Link v-for="link in onts.links" :key="link.label" :href="link.url || '#'"
                    class="mx-1 rounded px-3 py-1 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" v-html="link.label" />
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
