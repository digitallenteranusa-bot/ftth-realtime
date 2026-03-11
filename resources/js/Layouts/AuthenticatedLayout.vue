<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import { useDarkMode } from '@/Composables/useDarkMode';
import { useAlarmSound } from '@/Composables/useAlarmSound';

const showingNavigationDropdown = ref(false);
const { isDark, toggle: toggleDark } = useDarkMode();
const { isMuted, toggleMute } = useAlarmSound();

const page = usePage();
function can(permission) {
    const role = page.props.auth?.user?.role;
    if (role === 'admin') return true;
    const perms = page.props.permissions || {};
    return !!perms[permission];
}
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
            <nav class="border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 transition-colors duration-200">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex overflow-x-auto">
                                <NavLink v-if="can('dashboard.view')" :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>
                                <NavLink v-if="can('map.view')" :href="route('map')" :active="route().current('map')">Map</NavLink>
                                <NavLink v-if="can('mikrotik.view')" :href="route('mikrotiks.index')" :active="route().current('mikrotiks.*')">Mikrotik</NavLink>
                                <NavLink v-if="can('olt.view')" :href="route('olts.index')" :active="route().current('olts.*')">OLT</NavLink>
                                <NavLink v-if="can('odc.view')" :href="route('odcs.index')" :active="route().current('odcs.*')">ODC</NavLink>
                                <NavLink v-if="can('odp.view')" :href="route('odps.index')" :active="route().current('odps.*')">ODP</NavLink>
                                <NavLink v-if="can('ont.view')" :href="route('onts.index')" :active="route().current('onts.*')">ONT</NavLink>
                                <NavLink v-if="can('customer.view')" :href="route('customers.index')" :active="route().current('customers.*')">Customers</NavLink>
                                <NavLink v-if="can('alarm.view')" :href="route('alarms.index')" :active="route().current('alarms.*')">Alarms</NavLink>
                                <NavLink v-if="can('ticket.view')" :href="route('tickets.index')" :active="route().current('tickets.*')">Tickets</NavLink>
                                <NavLink v-if="can('nearby.view')" :href="route('nearby.index')" :active="route().current('nearby.*')">Nearby</NavLink>
                                <NavLink v-if="can('bandwidth.view')" :href="route('bandwidth-plans.index')" :active="route().current('bandwidth-plans.*')">Bandwidth</NavLink>
                                <NavLink v-if="can('user.manage')" :href="route('users.index')" :active="route().current('users.*')">Users</NavLink>
                                <NavLink v-if="can('user.manage')" :href="route('role-permissions.index')" :active="route().current('role-permissions.*')">Roles</NavLink>
                                <NavLink v-if="can('audit_log.view')" :href="route('audit-logs.index')" :active="route().current('audit-logs.*')">Audit Log</NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-2">
                            <!-- Alarm Mute Toggle -->
                            <button @click="toggleMute" type="button" class="rounded-md p-2 transition" :class="isMuted ? 'text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700' : 'text-red-500 hover:bg-red-50 dark:hover:bg-gray-700'" :title="isMuted ? 'Alarm: Muted' : 'Alarm: Active'">
                                <svg v-if="!isMuted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                </svg>
                            </button>
                            <!-- Dark Mode Toggle -->
                            <button @click="toggleDark" type="button" class="rounded-md p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <!-- Sun icon (show when dark) -->
                                <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <!-- Moon icon (show when light) -->
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-gray-400 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden gap-2">
                            <button @click="toggleMute" type="button" class="rounded-md p-2 transition" :class="isMuted ? 'text-gray-400' : 'text-red-500'" :title="isMuted ? 'Alarm: Muted' : 'Alarm: Active'">
                                <svg v-if="!isMuted" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                </svg>
                            </button>
                            <button @click="toggleDark" type="button" class="rounded-md p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 dark:text-gray-500 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink v-if="can('dashboard.view')" :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('map.view')" :href="route('map')" :active="route().current('map')">Map</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('mikrotik.view')" :href="route('mikrotiks.index')" :active="route().current('mikrotiks.*')">Mikrotik</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('olt.view')" :href="route('olts.index')" :active="route().current('olts.*')">OLT</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('odc.view')" :href="route('odcs.index')" :active="route().current('odcs.*')">ODC</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('odp.view')" :href="route('odps.index')" :active="route().current('odps.*')">ODP</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('ont.view')" :href="route('onts.index')" :active="route().current('onts.*')">ONT</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('customer.view')" :href="route('customers.index')" :active="route().current('customers.*')">Customers</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('alarm.view')" :href="route('alarms.index')" :active="route().current('alarms.*')">Alarms</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('ticket.view')" :href="route('tickets.index')" :active="route().current('tickets.*')">Tickets</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('nearby.view')" :href="route('nearby.index')" :active="route().current('nearby.*')">Nearby</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('bandwidth.view')" :href="route('bandwidth-plans.index')" :active="route().current('bandwidth-plans.*')">Bandwidth</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('user.manage')" :href="route('users.index')" :active="route().current('users.*')">Users</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('user.manage')" :href="route('role-permissions.index')" :active="route().current('role-permissions.*')">Roles</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="can('audit_log.view')" :href="route('audit-logs.index')" :active="route().current('audit-logs.*')">Audit Log</ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-gray-200 dark:border-gray-600 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-800 shadow dark:shadow-gray-700/50 transition-colors duration-200" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Toast Notifications -->
            <Toast />

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-4 mt-8 transition-colors duration-200">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    &copy; 2026 FTTH Realtime Monitoring. All rights reserved. | Developed by Agus Setyono
                </div>
            </footer>
        </div>
    </div>
</template>
