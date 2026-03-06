<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ odc: Object });
</script>

<template>
    <Head :title="odc.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ odc.name }}</h2>
                <Link :href="route('odcs.edit', odc.id)" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white">Edit</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                <div><span class="text-xs text-gray-500">Address</span><p class="font-semibold">{{ odc.address || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Capacity</span><p class="font-semibold">{{ odc.used_ports }}/{{ odc.capacity }}</p></div>
                <div><span class="text-xs text-gray-500">Splitter</span><p class="font-semibold">{{ odc.splitter_ratio }}</p></div>
                <div><span class="text-xs text-gray-500">Coordinates</span><p class="font-semibold">{{ odc.lat }}, {{ odc.lng }}</p></div>
            </div>
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold">ODPs ({{ odc.odps?.length || 0 }})</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="odp in odc.odps" :key="odp.id" class="rounded-lg border p-4">
                        <Link :href="route('odps.show', odp.id)" class="text-sm font-semibold text-indigo-600 hover:underline">{{ odp.name }}</Link>
                        <p class="text-xs text-gray-500">{{ odp.used_ports }}/{{ odp.capacity }} ports | {{ odp.onts?.length || 0 }} ONTs</p>
                    </div>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
