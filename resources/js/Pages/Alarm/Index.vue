<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
const props = defineProps({ alarms: Object, filters: Object });
const severity = ref(props.filters?.severity || '');
const unresolved = ref(props.filters?.unresolved || false);

function applyFilters() { router.get(route('alarms.index'), { severity: severity.value, unresolved: unresolved.value ? 1 : 0 }, { preserveState: true, replace: true }); }
function resolve(id) { router.post(route('alarms.resolve', id)); }
watch([severity, unresolved], () => applyFilters());
</script>

<template>
    <Head title="Alarms" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Alarms</h2></template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex gap-4">
                <select v-model="severity" class="rounded-md border-gray-300 shadow-sm sm:text-sm">
                    <option value="">All Severity</option>
                    <option value="critical">Critical</option>
                    <option value="major">Major</option>
                    <option value="minor">Minor</option>
                    <option value="warning">Warning</option>
                    <option value="info">Info</option>
                </select>
                <label class="flex items-center gap-2 text-sm"><input v-model="unresolved" type="checkbox" class="rounded border-gray-300" /> Unresolved only</label>
            </div>
            <div class="space-y-2">
                <div v-for="alarm in alarms.data" :key="alarm.id"
                    class="flex items-center justify-between rounded-lg bg-white p-4 shadow"
                    :class="{ 'border-l-4 border-red-500': alarm.severity === 'critical', 'border-l-4 border-orange-500': alarm.severity === 'major', 'border-l-4 border-yellow-500': alarm.severity === 'warning', 'border-l-4 border-blue-500': alarm.severity === 'info' }">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold uppercase"
                                :class="{ 'bg-red-100 text-red-800': alarm.severity === 'critical', 'bg-orange-100 text-orange-800': alarm.severity === 'major', 'bg-yellow-100 text-yellow-800': alarm.severity === 'warning', 'bg-blue-100 text-blue-800': alarm.severity === 'info' }">
                                {{ alarm.severity }}
                            </span>
                            <span v-if="alarm.is_resolved" class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Resolved</span>
                        </div>
                        <p class="mt-1 font-medium text-gray-800">{{ alarm.title }}</p>
                        <p class="text-sm text-gray-500">{{ alarm.description }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ new Date(alarm.created_at).toLocaleString('id-ID') }}</p>
                    </div>
                    <button v-if="!alarm.is_resolved" @click="resolve(alarm.id)" class="rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-700">Resolve</button>
                </div>
            </div>
            <div class="mt-4 flex justify-center" v-if="alarms.links">
                <a v-for="link in alarms.links" :key="link.label" :href="link.url || '#'" class="mx-1 rounded px-3 py-1 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" v-html="link.label" />
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
