import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Login from '@/pages/Login.vue';
import Dashboard from '@/pages/admin/Dashboard.vue';
import Accounts from '@/pages/admin/Accounts.vue';
import Transactions from '@/pages/admin/Transactions.vue';
import JournalEntries from '@/pages/admin/JournalEntries.vue';
import Ledger from '@/pages/admin/Ledger.vue';
import Reports from '@/pages/admin/Reports.vue';
import Settings from '@/pages/admin/Settings.vue';
import Users from '@/pages/admin/Users.vue';
import Employees from '@/pages/admin/Employees.vue';
import Customers from '@/pages/admin/Customers.vue';
import Import from '@/pages/admin/Import.vue';
import axios from 'axios';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'admin.dashboard',
                component: Dashboard,
            },
            {
                path: 'accounts',
                name: 'admin.accounts',
                component: Accounts,
                meta: { requiresRole: ['admin', 'accountant'] },
            },
            {
                path: 'transactions',
                name: 'admin.transactions',
                component: Transactions,
            },
            {
                path: 'journal-entries',
                name: 'admin.journal-entries',
                component: JournalEntries,
            },
            {
                path: 'ledger',
                name: 'admin.ledger',
                component: Ledger,
            },
            {
                path: 'reports',
                name: 'admin.reports',
                component: Reports,
            },
            {
                path: 'users',
                name: 'admin.users',
                component: Users,
                meta: { requiresRole: 'admin' },
            },
            {
                path: 'employees',
                name: 'admin.employees',
                component: Employees,
                meta: { requiresAuth: true },
            },
            {
                path: 'customers',
                name: 'admin.customers',
                component: Customers,
                meta: { requiresAuth: true },
            },
            {
                path: 'settings',
                name: 'admin.settings',
                component: Settings,
                meta: { requiresRole: 'admin' },
            },
            {
                path: 'import',
                name: 'admin.import',
                component: Import,
                meta: { requiresRole: ['admin', 'accountant'] },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard for authentication
router.beforeEach(async (to, from, next) => {
    // Check if route requires authentication
    if (to.meta.requiresAuth) {
        try {
            // Check if user is authenticated
            const response = await axios.get('/api/auth/me');
            if (response.data.user) {
                // User is authenticated, check role if required
                if (to.meta.requiresRole) {
                    const userRole = response.data.user.role;
                    const requiredRoles = Array.isArray(to.meta.requiresRole) 
                        ? to.meta.requiresRole 
                        : [to.meta.requiresRole];
                    
                    if (!requiredRoles.includes(userRole)) {
                        // User doesn't have required role, redirect to dashboard
                        next({ name: 'admin.dashboard' });
                        return;
                    }
                }
                next();
            } else {
                // Not authenticated, redirect to login
                next({ name: 'login' });
            }
        } catch (error) {
            // Not authenticated, redirect to login
            next({ name: 'login' });
        }
    } else if (to.meta.guest) {
        // Login page - redirect to admin if already logged in
        try {
            const response = await axios.get('/api/auth/me');
            if (response.data.user) {
                next({ name: 'admin.dashboard' });
            } else {
                next();
            }
        } catch (error) {
            next();
        }
    } else {
        next();
    }
});

export default router;

