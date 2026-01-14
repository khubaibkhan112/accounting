<template>
    <div class="min-h-screen bg-gray-50">
        <AdminSidebar />
        <div class="lg:pl-64">
            <AdminHeader />
            <main class="py-6 px-4 sm:px-6 lg:px-8">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script>
import AdminSidebar from '@/components/admin/AdminSidebar.vue';
import AdminHeader from '@/components/admin/AdminHeader.vue';

export default {
    name: 'AdminLayout',
    components: {
        AdminSidebar,
        AdminHeader,
    },
};
</script>

