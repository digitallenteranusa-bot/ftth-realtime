<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
defineProps({ customers: Array, operators: Array });
const form = useForm({ customer_id: '', title: '', description: '', priority: 'medium', assigned_to: '' });
function submit() { form.post(route('tickets.store')); }
</script>

<template>
    <Head title="Create Ticket" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Create Trouble Ticket</h2></template>
        <div class="py-6"><div class="mx-auto max-w-3xl px-4">
            <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">Title *</label><input v-model="form.title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /><p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p></div>
                    <div><label class="block text-sm font-medium text-gray-700">Customer</label><select v-model="form.customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- None --</option><option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Priority</label><select v-model="form.priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700">Assign to</label><select v-model="form.assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"><option value="">-- None --</option><option v-for="o in operators" :key="o.id" :value="o.id">{{ o.name }}</option></select></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea></div>
                <div class="flex justify-end gap-3">
                    <Link :href="route('tickets.index')" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-orange-600 px-4 py-2 text-sm text-white disabled:opacity-50">Create</button>
                </div>
            </form>
        </div></div>
    </AuthenticatedLayout>
</template>
