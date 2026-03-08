<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
const props = defineProps({ customers: Object, filters: Object });
const search = ref(props.filters?.search || '');
function applyFilters() { router.get(route('customers.index'), { search: search.value }, { preserveState: true, replace: true }); }
function destroy(id) { if (confirm('Hapus customer ini?')) router.delete(route('customers.destroy', id)); }
watch(search, () => applyFilters());
</script>

<template>
    <Head title="Customers" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Customers</h2>
                <div class="flex items-center space-x-2">
                    <a :href="route('export.customers.csv')" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">CSV</a>
                    <a :href="route('export.customers.pdf')" target="_blank" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">PDF</a>
                    <Link :href="route('customers.create')" class="rounded-md bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">Add Customer</Link>
                </div>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4"><input v-model="search" type="text" placeholder="Search name, phone, address..." class="rounded-md border-gray-300 shadow-sm sm:text-sm w-full sm:w-64" /></div>
            <!-- Mobile cards -->
            <div class="space-y-3 sm:hidden">
                <div v-for="c in customers.data" :key="c.id" class="rounded-lg bg-white p-4 shadow">
                    <div class="flex items-center justify-between">
                        <Link :href="route('customers.show', c.id)" class="text-sm font-semibold text-teal-600">{{ c.name }}</Link>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-green-100 text-green-800': c.status === 'active', 'bg-red-100 text-red-800': c.status === 'inactive', 'bg-yellow-100 text-yellow-800': c.status === 'suspended' }">{{ c.status }}</span>
                    </div>
                    <div class="mt-2 space-y-1 text-sm text-gray-500">
                        <p>{{ c.phone }}</p>
                        <p class="truncate">{{ c.address }}</p>
                        <p v-if="c.bandwidth">{{ c.bandwidth }}</p>
                    </div>
                    <div class="mt-3 flex gap-3 text-sm">
                        <Link :href="route('customers.edit', c.id)" class="text-indigo-600">Edit</Link>
                        <button @click="destroy(c.id)" class="text-red-600">Delete</button>
                    </div>
                </div>
            </div>
            <!-- Desktop table -->
            <div class="hidden sm:block overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Bandwidth</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="c in customers.data" :key="c.id">
                            <td class="px-6 py-4 text-sm font-medium"><Link :href="route('customers.show', c.id)" class="text-teal-600 hover:underline">{{ c.name }}</Link></td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ c.phone }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">{{ c.address }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ c.bandwidth || '-' }}</td>
                            <td class="px-6 py-4 text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-green-100 text-green-800': c.status === 'active', 'bg-red-100 text-red-800': c.status === 'inactive', 'bg-yellow-100 text-yellow-800': c.status === 'suspended' }">{{ c.status }}</span></td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('customers.edit', c.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                                <button @click="destroy(c.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-center" v-if="customers.links">
                <Link v-for="link in customers.links" :key="link.label" :href="link.url || '#'" class="mx-1 rounded px-3 py-1 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" v-html="link.label" />
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
