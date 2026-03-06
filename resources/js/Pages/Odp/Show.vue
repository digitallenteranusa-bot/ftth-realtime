<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ odp: Object });
</script>

<template>
    <Head :title="odp.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ odp.name }}</h2>
                <Link :href="route('odps.edit', odp.id)" class="rounded-md bg-sky-600 px-4 py-2 text-sm text-white">Edit</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 rounded-lg bg-white p-6 shadow">
                <div><span class="text-xs text-gray-500">ODC</span><p class="font-semibold">{{ odp.odc?.name || '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Capacity</span><p class="font-semibold">{{ odp.used_ports }}/{{ odp.capacity }}</p></div>
                <div><span class="text-xs text-gray-500">Splitter</span><p class="font-semibold">{{ odp.splitter_ratio }}</p></div>
                <div><span class="text-xs text-gray-500">Coordinates</span><p class="font-semibold">{{ odp.lat }}, {{ odp.lng }}</p></div>
            </div>
            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold">ONTs ({{ odp.onts?.length || 0 }})</h3>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="ont in odp.onts" :key="ont.id" class="flex items-center gap-3 rounded border p-3"
                        :class="{ 'border-green-200 bg-green-50': ont.status === 'online', 'border-red-200 bg-red-50': ont.status !== 'online' }">
                        <div class="h-3 w-3 rounded-full" :class="{ 'bg-green-500': ont.status === 'online', 'bg-red-500': ont.status !== 'online' }"></div>
                        <div>
                            <Link :href="route('onts.show', ont.id)" class="text-sm font-medium text-blue-600 hover:underline">{{ ont.name || ont.serial_number }}</Link>
                            <p class="text-xs text-gray-500">{{ ont.customer?.name || '-' }} | {{ ont.status }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
