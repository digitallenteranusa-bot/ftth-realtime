<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ olt: Object });
const form = useForm({
    name: props.olt.name, vendor: props.olt.vendor, host: props.olt.host,
    telnet_port: props.olt.telnet_port, ssh_port: props.olt.ssh_port,
    username: props.olt.username, password: '',
    snmp_community: props.olt.snmp_community, snmp_port: props.olt.snmp_port,
    is_active: props.olt.is_active, location: props.olt.location || '',
    lat: props.olt.lat || '', lng: props.olt.lng || '', notes: props.olt.notes || '',
});
function submit() { form.put(route('olts.update', props.olt.id)); }
</script>

<template>
    <Head title="Edit OLT" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Edit OLT: {{ olt.name }}</h2></template>
        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4">
                <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm font-medium text-gray-700">Name *</label><input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Vendor *</label><select v-model="form.vendor" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                <option value="">-- Pilih Vendor --</option>
                                <optgroup label="GPON">
                                    <option value="zte">ZTE</option>
                                    <option value="huawei">Huawei</option>
                                    <option value="fiberhome">FiberHome</option>
                                    <option value="nokia">Nokia (Alcatel-Lucent)</option>
                                    <option value="bdcom">BDCOM</option>
                                    <option value="raisecom">Raisecom</option>
                                    <option value="vsol">VSOL</option>
                                    <option value="tp-link">TP-Link</option>
                                    <option value="dasan">Dasan Zhone (DZS)</option>
                                    <option value="calix">Calix</option>
                                    <option value="ubiquiti">Ubiquiti (UFiber)</option>
                                    <option value="mikrotik">MikroTik</option>
                                </optgroup>
                                <optgroup label="EPON">
                                    <option value="bdcom-epon">BDCOM (EPON)</option>
                                    <option value="vsol-epon">VSOL (EPON)</option>
                                    <option value="cdata">C-Data (EPON)</option>
                                    <option value="syrotech">Syrotech (EPON)</option>
                                    <option value="netlink">Netlink (EPON)</option>
                                    <option value="hioso">HiOSO (EPON)</option>
                                    <option value="raisecom-epon">Raisecom (EPON)</option>
                                </optgroup>
                                <optgroup label="Lainnya">
                                    <option value="other">Lainnya</option>
                                </optgroup>
                            </select></div>
                        <div><label class="block text-sm font-medium text-gray-700">Host *</label><input v-model="form.host" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">SSH Port</label><input v-model="form.ssh_port" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Username</label><input v-model="form.username" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Password (blank to keep)</label><input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Location</label><input v-model="form.location" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Latitude</label><input v-model="form.lat" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                        <div><label class="block text-sm font-medium text-gray-700">Longitude</label><input v-model="form.lng" type="number" step="any" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" /></div>
                    </div>
                    <div><label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600" /><span class="text-sm text-gray-700">Active</span></label></div>
                    <div><label class="block text-sm font-medium text-gray-700">Notes</label><textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea></div>
                    <div class="flex justify-end gap-3">
                        <Link :href="route('olts.index')" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-purple-600 px-4 py-2 text-sm text-white hover:bg-purple-700 disabled:opacity-50">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
