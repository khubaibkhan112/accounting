<template>
    <div class="fixed top-4 right-4 z-50 space-y-2 max-w-md w-full">
        <transition-group name="notification" tag="div">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="[
                    'rounded-lg shadow-lg p-4 flex items-start gap-3 animate-slide-in',
                    getNotificationClass(notification.type)
                ]"
            >
                <div class="flex-shrink-0">
                    <component :is="getIcon(notification.type)" class="w-5 h-5" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium" v-html="notification.message"></p>
                </div>
                <button
                    @click="removeNotification(notification.id)"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 focus:outline-none"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </transition-group>
    </div>
</template>

<script setup>
import { useNotifications } from '../composables/useNotifications';

const { notifications, removeNotification } = useNotifications();

const getNotificationClass = (type) => {
    const classes = {
        success: 'bg-green-50 border border-green-200 text-green-800',
        error: 'bg-red-50 border border-red-200 text-red-800',
        warning: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
        info: 'bg-blue-50 border border-blue-200 text-blue-800',
    };
    return classes[type] || classes.info;
};

const getIcon = (type) => {
    if (type === 'success') {
        return {
            template: `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            `
        };
    } else if (type === 'error') {
        return {
            template: `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            `
        };
    } else if (type === 'warning') {
        return {
            template: `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            `
        };
    } else {
        return {
            template: `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            `
        };
    }
};
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

@keyframes slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}
</style>
