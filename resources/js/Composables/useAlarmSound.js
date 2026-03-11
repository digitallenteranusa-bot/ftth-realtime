import { ref } from 'vue';

const isMuted = ref(localStorage.getItem('alarm_muted') === '1');
let audioContext = null;
let alarmInterval = null;
let isPlaying = false;

function getAudioContext() {
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioContext;
}

function beep(frequency = 880, duration = 200) {
    try {
        const ctx = getAudioContext();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();

        oscillator.connect(gain);
        gain.connect(ctx.destination);

        oscillator.type = 'square';
        oscillator.frequency.setValueAtTime(frequency, ctx.currentTime);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration / 1000);

        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + duration / 1000);
    } catch (e) {
        // Audio not supported
    }
}

function playAlarm() {
    if (isMuted.value || isPlaying) return;
    isPlaying = true;

    let count = 0;
    const maxBeeps = 6;

    function doBeep() {
        if (count >= maxBeeps || isMuted.value) {
            isPlaying = false;
            return;
        }
        beep(count % 2 === 0 ? 880 : 660, 200);
        count++;
        alarmInterval = setTimeout(doBeep, 300);
    }

    doBeep();
}

function stopAlarm() {
    if (alarmInterval) {
        clearTimeout(alarmInterval);
        alarmInterval = null;
    }
    isPlaying = false;
}

function toggleMute() {
    isMuted.value = !isMuted.value;
    localStorage.setItem('alarm_muted', isMuted.value ? '1' : '0');
    if (isMuted.value) stopAlarm();
}

export function useAlarmSound() {
    return { isMuted, playAlarm, stopAlarm, toggleMute };
}
