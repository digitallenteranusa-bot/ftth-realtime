<script setup>
import { ref, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let nextId = 0;

function addToast(message, type = 'success', duration = 5000) {
    const id = nextId++;
    toasts.value.push({ id, message, type });
    setTimeout(() => removeToast(id), duration);
}

function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

watch(() => page.props.flash?.success, (msg) => {
    if (msg) addToast(msg, 'success');
}, { immediate: true });

watch(() => page.props.flash?.error, (msg) => {
    if (msg) addToast(msg, 'error');
}, { immediate: true });

// Expose for global use
window.__addToast = addToast;

onMounted(() => {
    if (!window.Echo) return;

    window.Echo.channel('network-monitoring')
        .listen('AlarmTriggered', (e) => {
            const severity = e.severity || 'info';
            const type = severity === 'critical' || severity === 'major' ? 'error' : 'warning';
            addToast(`Alarm ${severity.toUpperCase()}: ${e.title}`, type, 8000);
        })
        .listen('OntStatusChanged', (e) => {
            if (e.new_status === 'offline' || e.new_status === 'los') {
                addToast(`ONT ${e.serial_number || e.ont_id}: ${e.old_status} -> ${e.new_status}`, 'error', 6000);
            } else if (e.new_status === 'online' && e.old_status !== 'online') {
                addToast(`ONT ${e.serial_number || e.ont_id}: back online`, 'success', 4000);
            }
        });
});
</script>

<template>
    <div class="fixed top-4 right-4 z-[9999] space-y-2" style="min-width: 320px">
        <transition-group name="toast">
            <div v-for="toast in toasts" :key="toast.id"
                class="flex items-center justify-between rounded-lg px-4 py-3 shadow-lg text-sm font-medium cursor-pointer"
                :class="{
                    'bg-green-600 text-white': toast.type === 'success',
                    'bg-red-600 text-white': toast.type === 'error',
                    'bg-yellow-500 text-white': toast.type === 'warning',
                    'bg-blue-600 text-white': toast.type === 'info',
                }"
                @click="removeToast(toast.id)">
                <span>{{ toast.message }}</span>
                <button class="ml-3 opacity-70 hover:opacity-100">&times;</button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active { transition: all 0.3s ease-out; }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from { opacity: 0; transform: translateX(100px); }
.toast-leave-to { opacity: 0; transform: translateX(100px); }
</style>
