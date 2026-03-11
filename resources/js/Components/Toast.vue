<script setup>
import { ref, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useAlarmSound } from '@/Composables/useAlarmSound';

const page = usePage();
const toasts = ref([]);
const losAlarms = ref([]);
let nextId = 0;

const { playAlarm } = useAlarmSound();

function addToast(message, type = 'success', duration = 5000) {
    const id = nextId++;
    toasts.value.push({ id, message, type });
    setTimeout(() => removeToast(id), duration);
}

function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

function addLosAlarm(data) {
    const exists = losAlarms.value.find(a =>
        a.ont_id === (data.ont_id || data.device_id)
    );
    if (exists) return;

    losAlarms.value.push({
        id: nextId++,
        ont_id: data.ont_id || data.device_id,
        serial_number: data.serial_number || data.title || 'Unknown',
        time: new Date().toLocaleString('id-ID'),
        severity: data.severity || 'critical',
    });

    playAlarm();
}

function dismissLosAlarm(id) {
    losAlarms.value = losAlarms.value.filter(a => a.id !== id);
}

function dismissAllLosAlarms() {
    losAlarms.value = [];
}

watch(() => page.props.flash?.success, (msg) => {
    if (msg) addToast(msg, 'success');
}, { immediate: true });

watch(() => page.props.flash?.error, (msg) => {
    if (msg) addToast(msg, 'error');
}, { immediate: true });

window.__addToast = addToast;

onMounted(() => {
    if (!window.Echo) return;

    window.Echo.channel('network-monitoring')
        .listen('AlarmTriggered', (e) => {
            const severity = e.severity || 'info';
            const type = severity === 'critical' || severity === 'major' ? 'error' : 'warning';
            addToast(`Alarm ${severity.toUpperCase()}: ${e.title}`, type, 8000);

            if (severity === 'critical' && e.title?.toLowerCase().includes('los')) {
                addLosAlarm(e);
            }
        })
        .listen('OntStatusChanged', (e) => {
            if (e.new_status === 'los') {
                addToast(`ONT ${e.serial_number || e.ont_id}: ${e.old_status} -> LOS`, 'error', 8000);
                addLosAlarm(e);
            } else if (e.new_status === 'offline') {
                addToast(`ONT ${e.serial_number || e.ont_id}: ${e.old_status} -> ${e.new_status}`, 'error', 6000);
            } else if (e.new_status === 'online' && e.old_status !== 'online') {
                addToast(`ONT ${e.serial_number || e.ont_id}: back online`, 'success', 4000);
                // Auto-dismiss LOS alarm if ONT comes back online
                losAlarms.value = losAlarms.value.filter(a => a.ont_id !== e.ont_id);
            }
        });
});
</script>

<template>
    <!-- LOS Alarm Banner -->
    <div v-if="losAlarms.length" class="fixed top-0 left-0 right-0 z-[10000]">
        <div class="bg-red-700 text-white animate-pulse-slow">
            <div class="mx-auto max-w-7xl px-4 py-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="h-6 w-6 flex-shrink-0 animate-ping-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <div>
                            <span class="font-bold text-sm sm:text-base">ALARM LOS - Kabel Putus!</span>
                            <span class="ml-2 text-xs sm:text-sm opacity-90">({{ losAlarms.length }} ONT)</span>
                        </div>
                    </div>
                    <button @click="dismissAllLosAlarms" class="rounded bg-red-900 px-3 py-1 text-xs font-semibold hover:bg-red-800">
                        Dismiss All
                    </button>
                </div>

                <div class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                    <div v-for="alarm in losAlarms" :key="alarm.id"
                        class="flex items-center justify-between rounded bg-red-800/50 px-3 py-1.5 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-red-400 animate-pulse"></span>
                            <span class="font-medium">{{ alarm.serial_number }}</span>
                            <span class="text-xs opacity-75">{{ alarm.time }}</span>
                        </div>
                        <button @click="dismissLosAlarm(alarm.id)" class="text-red-300 hover:text-white text-xs">&times;</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-[9999] space-y-2" :class="{ 'top-[calc(1rem+var(--los-offset))]': losAlarms.length }" style="min-width: 320px">
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

@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }
}
.animate-pulse-slow { animation: pulse-slow 2s ease-in-out infinite; }

@keyframes ping-slow {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.15); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-ping-slow { animation: ping-slow 1.5s ease-in-out infinite; }
</style>
