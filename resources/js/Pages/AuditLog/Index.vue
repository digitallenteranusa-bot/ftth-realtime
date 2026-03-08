<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    modelTypes: Array,
    users: Array,
});

const action = ref(props.filters?.action || '');
const modelType = ref(props.filters?.model_type || '');
const userId = ref(props.filters?.user_id || '');
const search = ref(props.filters?.search || '');
const expandedRow = ref(null);

let searchTimeout = null;

function applyFilters() {
    const params = {};
    if (action.value) params.action = action.value;
    if (modelType.value) params.model_type = modelType.value;
    if (userId.value) params.user_id = userId.value;
    if (search.value) params.search = search.value;
    router.get(route('audit-logs.index'), params, { preserveState: true, replace: true });
}

function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
}

watch([action, modelType, userId], () => applyFilters());

function toggleRow(id) {
    expandedRow.value = expandedRow.value === id ? null : id;
}

function actionBadgeClass(act) {
    switch (act) {
        case 'created': return 'bg-green-100 text-green-800';
        case 'updated': return 'bg-blue-100 text-blue-800';
        case 'deleted': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function shortModelType(type) {
    if (!type) return '-';
    return type.split('\\').pop();
}

function formatJson(obj) {
    if (!obj || Object.keys(obj).length === 0) return null;
    return JSON.stringify(obj, null, 2);
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('id-ID');
}
</script>

<template>
    <Head title="Audit Log" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Audit Log</h2>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:gap-4">
                    <input
                        v-model="search"
                        @input="onSearchInput"
                        type="text"
                        placeholder="Cari user, model, IP..."
                        class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm sm:text-sm"
                    />
                    <select v-model="action" class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm sm:text-sm">
                        <option value="">Semua Action</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                    </select>
                    <select v-model="modelType" class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm sm:text-sm">
                        <option value="">Semua Model</option>
                        <option v-for="mt in modelTypes" :key="mt" :value="mt">{{ shortModelType(mt) }}</option>
                    </select>
                    <select v-model="userId" class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm sm:text-sm">
                        <option value="">Semua User</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <!-- Desktop Table -->
                <div class="hidden sm:block overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Waktu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Model</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Detail</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template v-for="log in logs.data" :key="log.id">
                                <tr
                                    @click="toggleRow(log.id)"
                                    class="cursor-pointer hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ log.user?.name || '-' }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold uppercase" :class="actionBadgeClass(log.action)">
                                            {{ log.action }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ shortModelType(log.model_type) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ shortModelType(log.model_type) }} #{{ log.model_id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ log.ip_address || '-' }}</td>
                                </tr>
                                <!-- Expanded detail row -->
                                <tr v-if="expandedRow === log.id">
                                    <td colspan="6" class="px-6 py-4 bg-gray-50">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div v-if="formatJson(log.old_values)">
                                                <h4 class="text-xs font-semibold uppercase text-gray-500 mb-2">Old Values</h4>
                                                <pre class="rounded-md bg-red-50 border border-red-200 p-3 text-xs text-red-900 overflow-x-auto max-h-64 overflow-y-auto">{{ formatJson(log.old_values) }}</pre>
                                            </div>
                                            <div v-if="formatJson(log.new_values)">
                                                <h4 class="text-xs font-semibold uppercase text-gray-500 mb-2">New Values</h4>
                                                <pre class="rounded-md bg-green-50 border border-green-200 p-3 text-xs text-green-900 overflow-x-auto max-h-64 overflow-y-auto">{{ formatJson(log.new_values) }}</pre>
                                            </div>
                                            <div v-if="!formatJson(log.old_values) && !formatJson(log.new_values)" class="col-span-2">
                                                <p class="text-sm text-gray-400 italic">Tidak ada detail perubahan.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">Tidak ada audit log ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="sm:hidden space-y-3">
                    <div v-for="log in logs.data" :key="log.id" @click="toggleRow(log.id)" class="rounded-lg bg-white p-4 shadow cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-400">{{ formatDate(log.created_at) }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold uppercase" :class="actionBadgeClass(log.action)">{{ log.action }}</span>
                        </div>
                        <p class="text-sm font-medium text-gray-900">{{ log.user?.name || '-' }}</p>
                        <div class="mt-1 space-y-0.5 text-sm text-gray-500">
                            <p>{{ shortModelType(log.model_type) }} #{{ log.model_id }}</p>
                            <p class="font-mono text-xs">{{ log.ip_address || '-' }}</p>
                        </div>
                        <!-- Expanded detail -->
                        <div v-if="expandedRow === log.id" class="mt-3 border-t pt-3">
                            <div class="space-y-3">
                                <div v-if="formatJson(log.old_values)">
                                    <h4 class="text-xs font-semibold uppercase text-gray-500 mb-1">Old Values</h4>
                                    <pre class="rounded-md bg-red-50 border border-red-200 p-2 text-xs text-red-900 overflow-x-auto max-h-48 overflow-y-auto">{{ formatJson(log.old_values) }}</pre>
                                </div>
                                <div v-if="formatJson(log.new_values)">
                                    <h4 class="text-xs font-semibold uppercase text-gray-500 mb-1">New Values</h4>
                                    <pre class="rounded-md bg-green-50 border border-green-200 p-2 text-xs text-green-900 overflow-x-auto max-h-48 overflow-y-auto">{{ formatJson(log.new_values) }}</pre>
                                </div>
                                <p v-if="!formatJson(log.old_values) && !formatJson(log.new_values)" class="text-sm text-gray-400 italic">Tidak ada detail perubahan.</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="logs.data.length === 0" class="rounded-lg bg-white p-6 shadow text-center text-sm text-gray-400">
                        Tidak ada audit log ditemukan.
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex justify-center" v-if="logs.links">
                    <a
                        v-for="link in logs.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="mx-1 rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
