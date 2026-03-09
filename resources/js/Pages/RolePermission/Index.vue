<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';

const props = defineProps({
    groups: Object,
    roles: Array,
});

// Build reactive state from props
const perms = reactive({});
for (const [group, permissions] of Object.entries(props.groups)) {
    for (const [perm, data] of Object.entries(permissions)) {
        perms[perm] = {
            admin: data.admin,
            operator: data.operator,
            teknisi: data.teknisi,
        };
    }
}

const saving = ref(false);
const saved = ref(false);

function toggle(perm, role) {
    if (role === 'admin') return; // Admin always has all
    perms[perm][role] = !perms[perm][role];
}

function save() {
    const changes = [];
    for (const [perm, roles] of Object.entries(perms)) {
        for (const role of ['operator', 'teknisi']) {
            changes.push({
                role,
                permission: perm,
                allowed: roles[role],
            });
        }
    }

    saving.value = true;
    router.post(route('role-permissions.update'), { permissions: changes }, {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => { saved.value = false; }, 3000);
        },
        onFinish: () => { saving.value = false; },
    });
}

const roleColors = {
    admin: 'bg-red-100 text-red-800',
    operator: 'bg-blue-100 text-blue-800',
    teknisi: 'bg-green-100 text-green-800',
};

const groupIcons = {
    'Umum': 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'Mikrotik': 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
    'OLT': 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
    'Infrastruktur': 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    'Pelanggan': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    'Operasional': 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    'Admin': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
};
</script>

<template>
    <Head title="Pengaturan Role" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Pengaturan Role & Akses</h2>
                <button @click="save" :disabled="saving"
                    class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 flex items-center gap-2">
                    <svg v-if="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
            </div>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">
                <!-- Success message -->
                <div v-if="saved" class="rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-800">
                    Pengaturan role berhasil disimpan.
                </div>

                <!-- Role legend -->
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-gray-500">Role:</span>
                    <span v-for="role in roles" :key="role" class="rounded-full px-3 py-1 text-xs font-semibold" :class="roleColors[role]">
                        {{ role }}
                    </span>
                    <span class="text-xs text-gray-400 ml-auto">Admin selalu memiliki semua akses</span>
                </div>

                <!-- Permission Groups -->
                <div v-for="(permissions, group) in groups" :key="group" class="rounded-lg bg-white shadow overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="groupIcons[group] || groupIcons['Umum']" />
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700">{{ group }}</h3>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden sm:block">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50/50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 w-1/2">Fitur</th>
                                    <th v-for="role in roles" :key="role" class="px-4 py-2 text-center text-xs font-medium text-gray-500 w-1/6">
                                        <span class="rounded-full px-2 py-0.5 font-semibold" :class="roleColors[role]">{{ role }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(data, perm) in permissions" :key="perm" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-800">{{ data.label }}</p>
                                        <p class="text-xs text-gray-400">{{ data.desc }}</p>
                                    </td>
                                    <td v-for="role in roles" :key="role" class="px-4 py-3 text-center">
                                        <button @click="toggle(perm, role)"
                                            :disabled="role === 'admin'"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
                                            :class="perms[perm][role] ? 'bg-teal-500' : 'bg-gray-300'"
                                            :title="role === 'admin' ? 'Admin selalu aktif' : `Toggle ${data.label} untuk ${role}`">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                                :class="perms[perm][role] ? 'translate-x-6' : 'translate-x-1'" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards -->
                    <div class="sm:hidden divide-y divide-gray-100">
                        <div v-for="(data, perm) in permissions" :key="perm" class="p-4">
                            <p class="text-sm font-medium text-gray-800">{{ data.label }}</p>
                            <p class="text-xs text-gray-400 mb-2">{{ data.desc }}</p>
                            <div class="flex items-center gap-4">
                                <div v-for="role in roles" :key="role" class="flex items-center gap-1.5">
                                    <button @click="toggle(perm, role)"
                                        :disabled="role === 'admin'"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                        :class="perms[perm][role] ? 'bg-teal-500' : 'bg-gray-300'">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                                            :class="perms[perm][role] ? 'translate-x-4' : 'translate-x-0.5'" />
                                    </button>
                                    <span class="text-xs" :class="roleColors[role].replace('bg-', 'text-').replace('-100', '-600')">{{ role }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
