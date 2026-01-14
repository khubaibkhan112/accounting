<template>
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transform transition-transform duration-300 ease-in-out lg:translate-x-0"
           :class="{ '-translate-x-full': !isOpen, 'translate-x-0': isOpen }">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
                <h1 class="text-xl font-bold text-white">Accounting</h1>
                <button @click="toggleSidebar" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <router-link
                    v-for="item in menuItems"
                    :key="item.name"
                    :to="{ name: item.route }"
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors"
                    :class="isActiveRoute(item.route) 
                        ? 'bg-gray-800 text-white' 
                        : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
                >
                    <svg v-if="item.icon === 'DashboardIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <svg v-else-if="item.icon === 'ChartIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <svg v-else-if="item.icon === 'DocumentIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <svg v-else-if="item.icon === 'BookIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <svg v-else-if="item.icon === 'TableIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg v-else-if="item.icon === 'ChartBarIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <svg v-else-if="item.icon === 'UsersIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg v-else-if="item.icon === 'CogIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg v-else-if="item.icon === 'UploadIcon'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    {{ item.label }}
                </router-link>
            </nav>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-800">
                <div class="text-xs text-gray-400">
                    <p>Accounting Software</p>
                    <p class="mt-1">Version 1.0.0</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div v-if="isOpen" 
         @click="toggleSidebar"
         class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
    ></div>
</template>

<script>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';

export default {
    name: 'AdminSidebar',
    setup() {
        const route = useRoute();
        const isOpen = ref(true);

        const menuItems = [
            {
                name: 'dashboard',
                label: 'Dashboard',
                route: 'admin.dashboard',
                icon: 'DashboardIcon',
            },
            {
                name: 'accounts',
                label: 'Accounts',
                route: 'admin.accounts',
                icon: 'ChartIcon',
            },
            {
                name: 'transactions',
                label: 'Transactions',
                route: 'admin.transactions',
                icon: 'DocumentIcon',
            },
            {
                name: 'journal-entries',
                label: 'Journal Entries',
                route: 'admin.journal-entries',
                icon: 'BookIcon',
            },
            {
                name: 'ledger',
                label: 'Ledger',
                route: 'admin.ledger',
                icon: 'TableIcon',
            },
            {
                name: 'reports',
                label: 'Reports',
                route: 'admin.reports',
                icon: 'ChartBarIcon',
            },
            {
                name: 'users',
                label: 'Users',
                route: 'admin.users',
                icon: 'UsersIcon',
            },
            {
                name: 'employees',
                label: 'Employees',
                route: 'admin.employees',
                icon: 'UsersIcon',
            },
            {
                name: 'customers',
                label: 'Customers',
                route: 'admin.customers',
                icon: 'UsersIcon',
            },
            {
                name: 'settings',
                label: 'Settings',
                route: 'admin.settings',
                icon: 'CogIcon',
            },
            {
                name: 'import',
                label: 'Import Data',
                route: 'admin.import',
                icon: 'UploadIcon',
            },
        ];

        const isActiveRoute = (routeName) => {
            return route.name === routeName;
        };

        const toggleSidebar = () => {
            isOpen.value = !isOpen.value;
        };

        return {
            isOpen,
            menuItems,
            isActiveRoute,
            toggleSidebar,
        };
    },
};
</script>


