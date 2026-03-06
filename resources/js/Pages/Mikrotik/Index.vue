<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ mikrotiks: Object });

function destroy(id) {
    if (confirm('Hapus Mikrotik ini?')) {
        router.delete(route('mikrotiks.destroy', id));
    }
}
</script>

<template>
    <Head title="Mikrotik" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Mikrotik Routers</h2>
                <Link :href="route('mikrotiks.create')" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Add Mikrotik</Link>
            </div>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Host</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="mk in mikrotiks.data" :key="mk.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    <Link :href="route('mikrotiks.show', mk.id)" class="text-blue-600 hover:underline">{{ mk.name }}</Link>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ mk.host }}:{{ mk.api_port }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ mk.location || '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span :class="mk.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="rounded-full px-2 py-0.5 text-xs font-semibold">
                                        {{ mk.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('mikrotiks.edit', mk.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                                    <button @click="destroy(mk.id)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-center" v-if="mikrotiks.links">
                    <Link v-for="link in mikrotiks.links" :key="link.label" :href="link.url || '#'"
                        class="mx-1 rounded px-3 py-1 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        v-html="link.label" :preserve-scroll="true" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
