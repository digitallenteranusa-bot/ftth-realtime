<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    upload_speed: '',
    download_speed: '',
    price: 0,
    is_active: true,
    notes: '',
});

function submit() {
    form.post(route('bandwidth-plans.store'));
}
</script>

<template>
    <Head title="Add Bandwidth Plan" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Bandwidth Plan</h2>
        </template>
        <div class="py-6">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Upload Speed</label>
                            <input v-model="form.upload_speed" type="text" placeholder="e.g. 10Mbps" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.upload_speed" class="mt-1 text-sm text-red-600">{{ form.errors.upload_speed }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Download Speed</label>
                            <input v-model="form.download_speed" type="text" placeholder="e.g. 50Mbps" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.download_speed" class="mt-1 text-sm text-red-600">{{ form.errors.download_speed }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price (IDR)</label>
                        <input v-model="form.price" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                    </div>
                    <div class="flex items-center">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <label class="ml-2 text-sm text-gray-700">Active</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <Link :href="route('bandwidth-plans.index')" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 disabled:opacity-50">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
