import { onMounted, onUnmounted } from 'vue';

export function useNetworkMonitoring({ onOntStatusChanged, onAlarmTriggered, onDeviceStatusChanged } = {}) {
    let channel = null;

    onMounted(() => {
        if (!window.Echo) return;

        channel = window.Echo.channel('network-monitoring');

        if (onOntStatusChanged) {
            channel.listen('OntStatusChanged', (e) => onOntStatusChanged(e));
        }
        if (onAlarmTriggered) {
            channel.listen('AlarmTriggered', (e) => onAlarmTriggered(e));
        }
        if (onDeviceStatusChanged) {
            channel.listen('DeviceStatusChanged', (e) => onDeviceStatusChanged(e));
        }
    });

    onUnmounted(() => {
        if (channel) {
            window.Echo?.leave('network-monitoring');
        }
    });
}

export function useTrafficMonitoring(mikrotikId, { onTrafficUpdated } = {}) {
    let channel = null;

    onMounted(() => {
        if (!window.Echo || !mikrotikId) return;

        channel = window.Echo.channel(`traffic.${mikrotikId}`);

        if (onTrafficUpdated) {
            channel.listen('TrafficUpdated', (e) => onTrafficUpdated(e));
        }
    });

    onUnmounted(() => {
        if (channel && mikrotikId) {
            window.Echo?.leave(`traffic.${mikrotikId}`);
        }
    });
}
