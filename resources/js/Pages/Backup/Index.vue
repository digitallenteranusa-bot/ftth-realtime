<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    backups: Array,
});

const creating = ref(false);
const restoring = ref(null);
const confirmRestore = ref(null);
const confirmDelete = ref(null);

const uploadForm = useForm({
    backup_file: null,
});

function createBackup() {
    creating.value = true;
    router.post(route('backups.create'), {}, {
        onFinish: () => creating.value = false,
    });
}

function submitUpload() {
    uploadForm.post(route('backups.upload'), {
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function doRestore(filename) {
    restoring.value = filename;
    router.post(route('backups.restore', filename), {}, {
        onFinish: () => {
            restoring.value = null;
            confirmRestore.value = null;
        },
    });
}

function doDelete(filename) {
    router.delete(route('backups.destroy', filename), {
        onFinish: () => confirmDelete.value = null,
    });
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('id-ID');
}

const fileInput = ref(null);

function onFileChange(e) {
    uploadForm.backup_file = e.target.files[0];
}
</script>

<template>
    <Head title="Backup & Restore" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Backup & Restore Database</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Action Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Create Backup -->
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex-shrink-0 rounded-full bg-blue-100 dark:bg-blue-900 p-3">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Download Backup</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Buat backup baru dan simpan ke daftar</p>
                            </div>
                        </div>
                        <button
                            @click="createBackup"
                            :disabled="creating"
                            class="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <span v-if="creating" class="flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Membuat Backup...
                            </span>
                            <span v-else>Buat Backup Baru</span>
                        </button>
                    </div>

                    <!-- Upload Backup -->
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex-shrink-0 rounded-full bg-green-100 dark:bg-green-900 p-3">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upload Backup</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Upload file backup untuk restore nanti</p>
                            </div>
                        </div>
                        <form @submit.prevent="submitUpload" class="space-y-3">
                            <div>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".sql,.sqlite,.db"
                                    @change="onFileChange"
                                    class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:rounded-md file:border-0 file:bg-green-50 dark:file:bg-green-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-green-700 dark:file:text-green-300 hover:file:bg-green-100 dark:hover:file:bg-green-800 file:cursor-pointer file:transition"
                                />
                                <p v-if="uploadForm.errors.backup_file" class="mt-1 text-sm text-red-600">{{ uploadForm.errors.backup_file }}</p>
                            </div>
                            <button
                                type="submit"
                                :disabled="!uploadForm.backup_file || uploadForm.processing"
                                class="w-full rounded-md bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                <span v-if="uploadForm.processing" class="flex items-center justify-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Mengupload...
                                </span>
                                <span v-else>Upload File</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Backup List -->
                <div class="rounded-lg bg-white dark:bg-gray-800 shadow">
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Daftar File Backup</h3>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nama File</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ukuran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tanggal</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="backup in backups" :key="backup.name" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                            </svg>
                                            {{ backup.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatSize(backup.size) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(backup.date) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                :href="route('backups.download', backup.name)"
                                                class="inline-flex items-center gap-1 rounded-md bg-blue-50 dark:bg-blue-900/50 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900 transition"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                Download
                                            </a>
                                            <button
                                                v-if="confirmRestore !== backup.name"
                                                @click="confirmRestore = backup.name"
                                                class="inline-flex items-center gap-1 rounded-md bg-yellow-50 dark:bg-yellow-900/50 px-3 py-1.5 text-xs font-semibold text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-900 transition"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Restore
                                            </button>
                                            <template v-else>
                                                <button
                                                    @click="doRestore(backup.name)"
                                                    :disabled="restoring === backup.name"
                                                    class="inline-flex items-center gap-1 rounded-md bg-yellow-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-yellow-700 disabled:opacity-50 transition"
                                                >
                                                    <svg v-if="restoring === backup.name" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    Ya, Restore!
                                                </button>
                                                <button
                                                    @click="confirmRestore = null"
                                                    class="rounded-md bg-gray-100 dark:bg-gray-600 px-2 py-1.5 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 transition"
                                                >Batal</button>
                                            </template>
                                            <button
                                                v-if="confirmDelete !== backup.name"
                                                @click="confirmDelete = backup.name"
                                                class="inline-flex items-center gap-1 rounded-md bg-red-50 dark:bg-red-900/50 px-3 py-1.5 text-xs font-semibold text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900 transition"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus
                                            </button>
                                            <template v-else>
                                                <button
                                                    @click="doDelete(backup.name)"
                                                    class="inline-flex items-center gap-1 rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition"
                                                >Ya, Hapus!</button>
                                                <button
                                                    @click="confirmDelete = null"
                                                    class="rounded-md bg-gray-100 dark:bg-gray-600 px-2 py-1.5 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 transition"
                                                >Batal</button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="backups.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                        </svg>
                                        Belum ada file backup. Klik "Buat Backup Baru" untuk memulai.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="backup in backups" :key="backup.name" class="p-4 space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 break-all">{{ backup.name }}</p>
                                    <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ formatSize(backup.size) }}</span>
                                        <span>{{ formatDate(backup.date) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    :href="route('backups.download', backup.name)"
                                    class="inline-flex items-center gap-1 rounded-md bg-blue-50 dark:bg-blue-900/50 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download
                                </a>
                                <button
                                    v-if="confirmRestore !== backup.name"
                                    @click="confirmRestore = backup.name"
                                    class="inline-flex items-center gap-1 rounded-md bg-yellow-50 dark:bg-yellow-900/50 px-3 py-1.5 text-xs font-semibold text-yellow-700 dark:text-yellow-300"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Restore
                                </button>
                                <template v-else>
                                    <button
                                        @click="doRestore(backup.name)"
                                        :disabled="restoring === backup.name"
                                        class="inline-flex items-center gap-1 rounded-md bg-yellow-600 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50"
                                    >Ya, Restore!</button>
                                    <button @click="confirmRestore = null" class="rounded-md bg-gray-100 dark:bg-gray-600 px-2 py-1.5 text-xs text-gray-600 dark:text-gray-300">Batal</button>
                                </template>
                                <button
                                    v-if="confirmDelete !== backup.name"
                                    @click="confirmDelete = backup.name"
                                    class="inline-flex items-center gap-1 rounded-md bg-red-50 dark:bg-red-900/50 px-3 py-1.5 text-xs font-semibold text-red-700 dark:text-red-300"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus
                                </button>
                                <template v-else>
                                    <button @click="doDelete(backup.name)" class="inline-flex items-center gap-1 rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Ya, Hapus!</button>
                                    <button @click="confirmDelete = null" class="rounded-md bg-gray-100 dark:bg-gray-600 px-2 py-1.5 text-xs text-gray-600 dark:text-gray-300">Batal</button>
                                </template>
                            </div>
                        </div>
                        <div v-if="backups.length === 0" class="p-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            Belum ada file backup.
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 p-4">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-semibold mb-1">Petunjuk Backup & Restore</p>
                            <ul class="list-disc ml-4 space-y-1 text-blue-600 dark:text-blue-400">
                                <li><strong>Buat Backup</strong> - membuat salinan database saat ini sebagai file yang bisa didownload</li>
                                <li><strong>Upload Backup</strong> - upload file backup dari komputer Anda ke server</li>
                                <li><strong>Restore</strong> - mengembalikan database dari file backup (data saat ini akan ditimpa)</li>
                                <li>Format yang didukung: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">.sql</code>, <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">.sqlite</code>, <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">.db</code></li>
                                <li>Disarankan untuk membuat backup secara berkala dan menyimpan copy di tempat terpisah</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
