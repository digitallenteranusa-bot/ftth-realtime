<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
const props = defineProps({ users: Object, filters: Object });
const search = ref(props.filters?.search || '');
function applyFilters() { router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true }); }
function destroy(id) { if (confirm('Hapus user ini?')) router.delete(route('users.destroy', id)); }
watch(search, () => applyFilters());

function roleBadgeClass(role) {
    return {
        'bg-red-100 text-red-800': role === 'admin',
        'bg-blue-100 text-blue-800': role === 'operator',
        'bg-green-100 text-green-800': role === 'teknisi',
    };
}
</script>

<template>
    <Head title="Users" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Users</h2>
                <Link :href="route('users.create')" class="rounded-md bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">Add User</Link>
            </div>
        </template>
        <div class="py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4"><input v-model="search" type="text" placeholder="Search name, email..." class="rounded-md border-gray-300 shadow-sm sm:text-sm w-64" /></div>
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Role</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="u in users.data" :key="u.id">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ u.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ u.email }}</td>
                            <td class="px-6 py-4 text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="roleBadgeClass(u.role)">{{ u.role }}</span></td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('users.edit', u.id)" class="text-indigo-600 hover:underline mr-3">Edit</Link>
                                <button @click="destroy(u.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-center" v-if="users.links">
                <Link v-for="link in users.links" :key="link.label" :href="link.url || '#'" class="mx-1 rounded px-3 py-1 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" v-html="link.label" />
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>
