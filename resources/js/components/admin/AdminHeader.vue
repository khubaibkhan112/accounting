<template>
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
            <!-- Mobile menu button -->
            <button
                @click="toggleSidebar"
                class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Search bar -->
            <div class="flex-1 max-w-lg mx-4 hidden md:block">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        placeholder="Search..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
            </div>

            <!-- Right side -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <button class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-lg relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                </button>

                <!-- User menu -->
                <div class="relative" ref="userMenuRef">
                    <button
                        @click="toggleUserMenu"
                        class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100"
                    >
                        <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-700">{{ userInitials }}</span>
                        </div>
                        <span class="hidden md:block text-sm font-medium text-gray-700">{{ userName }}</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown menu -->
                    <div
                        v-if="userMenuOpen"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200"
                    >
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                        <hr class="my-1 border-gray-200">
                        <a href="#" @click.prevent="logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

export default {
    name: 'AdminHeader',
    setup() {
        const router = useRouter();
        const userMenuOpen = ref(false);
        const userMenuRef = ref(null);
        const userName = ref('User');
        const userRole = ref('');
        const userInitials = computed(() => {
            return userName.value
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase()
                .slice(0, 2);
        });

        const loadUser = async () => {
            try {
                const response = await axios.get('/api/auth/me');
                if (response.data && response.data.user) {
                    userName.value = response.data.user.name || 'User';
                    userRole.value = response.data.user.role || '';
                }
            } catch (error) {
                console.error('Error loading user:', error);
            }
        };

        const toggleUserMenu = () => {
            userMenuOpen.value = !userMenuOpen.value;
        };

        const toggleSidebar = () => {
            // Emit event to toggle sidebar (can be handled by parent or via store)
            window.dispatchEvent(new CustomEvent('toggle-sidebar'));
        };

        const handleClickOutside = (event) => {
            if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
                userMenuOpen.value = false;
            }
        };

        const logout = async () => {
            try {
                await axios.post('/api/auth/logout');
                router.push('/login');
            } catch (error) {
                console.error('Error logging out:', error);
                // Still redirect to login even if API call fails
                router.push('/login');
            }
        };

        onMounted(() => {
            document.addEventListener('click', handleClickOutside);
            loadUser();
        });

        onUnmounted(() => {
            document.removeEventListener('click', handleClickOutside);
        });

        return {
            userMenuOpen,
            userMenuRef,
            userName,
            userRole,
            userInitials,
            toggleUserMenu,
            toggleSidebar,
            logout,
        };
    },
};
</script>

