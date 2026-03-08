<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ bandwidthPlans: Object });

function destroy(id) {
    if (confirm('Yakin ingin menghapus bandwidth plan ini?')) {
        router.delete(route('bandwidth-plans.destroy', id));
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
}
</script>

<template>
    <Head title="Bandwidth Plans" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Bandwidth Plans</h2>
                <Link :href="route('bandwidth-plans.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 text-center">Add Plan</Link>
            </div>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Mobile cards -->
                <div class="space-y-3 sm:hidden">
                    <div v-for="plan in bandwidthPlans.data" :key="plan.id" class="rounded-lg bg-white p-4 shadow">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900">{{ plan.name }}</span>
                            <span :class="plan.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="rounded-full px-2 py-0.5 text-xs font-semibold">
                                {{ plan.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="mt-2 space-y-1 text-sm text-gray-500">
                            <div class="flex gap-4">
                                <p>Up: {{ plan.upload_speed }}</p>
                                <p>Down: {{ plan.download_speed }}</p>
                            </div>
                            <p class="font-medium text-gray-700">{{ formatPrice(plan.price) }}</p>
                        </div>
                        <div class="mt-3 flex gap-3 text-sm">
                            <Link :href="route('bandwidth-plans.edit', plan.id)" class="text-indigo-600">Edit</Link>
                            <button @click="destroy(plan.id)" class="text-red-600">Delete</button>
                        </div>
                    </div>
                    <div v-if="!bandwidthPlans.data.length" class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow">No bandwidth plans found.</div>
                </div>
                <!-- Desktop table -->
                <div class="hidden sm:block overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Upload</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Download</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="plan in bandwidthPlans.data" :key="plan.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ plan.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ plan.upload_speed }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ plan.download_speed }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ formatPrice(plan.price) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span :class="plan.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="rounded-full px-2 py-0.5 text-xs font-semibold">
                                        {{ plan.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm space-x-2">
                                    <Link :href="route('bandwidth-plans.edit', plan.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <button @click="destroy(plan.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!bandwidthPlans.data.length">
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No bandwidth plans found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="bandwidthPlans.links && bandwidthPlans.last_page > 1" class="mt-4 flex justify-center space-x-1">
                    <template v-for="link in bandwidthPlans.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" class="rounded border px-3 py-1 text-sm" :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" v-html="link.label" />
                        <span v-else class="rounded border px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
