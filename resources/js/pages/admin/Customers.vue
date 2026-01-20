<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
                <p class="mt-1 text-sm text-gray-500">Manage customer records and information</p>
            </div>
            <button 
                type="button"
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Customer
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <input
                        type="text"
                        v-model="filters.search"
                        @input="loadCustomers"
                        placeholder="Search customers..."
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <select
                        v-model="filters.customer_type"
                        @change="loadCustomers"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="business">Business</option>
                    </select>
                </div>
                <div>
                    <select
                        v-model="filters.is_active"
                        @change="loadCustomers"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Status</option>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="loadCustomers"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="bg-white shadow rounded-lg">
            <!-- Table Toggle Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Customers List</h3>
                <button
                    @click="tableExpanded = !tableExpanded"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors"
                    :title="tableExpanded ? 'Collapse Table' : 'Expand Table'"
                >
                    <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': tableExpanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <span>{{ tableExpanded ? 'Collapse' : 'Expand' }}</span>
                </button>
            </div>
            <div v-show="tableExpanded">
                <vxe-table
                    :data="customers.data"
                    :loading="tableLoading"
                    stripe
                    border
                    highlight-hover-row
                    height="600"
                    :scroll-y="{ enabled: true, gt: 0 }"
                    :sort-config="{ trigger: 'default', remote: true }"
                    :pager-config="{
                        enabled: true,
                        currentPage: customers.current_page,
                        pageSize: 15,
                        total: customers.total,
                        pageSizes: [10, 15, 20, 50, 100],
                        layouts: ['PrevJump', 'PrevPage', 'Number', 'NextPage', 'NextJump', 'Sizes', 'FullJump', 'Total']
                    }"
                    @page-change="handlePageChange"
                    @sort-change="handleSortChange"
                >
                    <vxe-column type="expand" width="60">
                        <template #content="{ row }">
                            <div class="p-4 flex gap-4 bg-gray-50">
                                <button 
                                    @click="editCustomer(row)" 
                                    class="inline-flex items-center px-3 py-1.5 border border-indigo-600 rounded-md text-indigo-600 hover:bg-indigo-50 text-sm font-medium"
                                >
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button 
                                    @click="openLedger(row)" 
                                    class="inline-flex items-center px-3 py-1.5 border border-green-600 rounded-md text-green-600 hover:bg-green-50 text-sm font-medium"
                                >
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Ledger
                                </button>
                                <button 
                                    @click="deleteCustomer(row)" 
                                    class="inline-flex items-center px-3 py-1.5 border border-red-600 rounded-md text-red-600 hover:bg-red-50 text-sm font-medium"
                                >
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                                <button 
                                    @click="viewCustomer(row)" 
                                    class="inline-flex items-center px-3 py-1.5 border border-blue-600 rounded-md text-blue-600 hover:bg-blue-50 text-sm font-medium"
                                >
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View
                                </button>
                            </div>
                        </template>
                    </vxe-column>
                    <vxe-column field="customer_code" title="Customer Code" sortable width="150"></vxe-column>
                    <vxe-column field="display_name" title="Name" sortable min-width="200">
                        <template #default="{ row }">
                            {{ row.display_name || row.full_name || row.company_name }}
                        </template>
                    </vxe-column>
                    <vxe-column field="customer_type" title="Type" sortable width="120">
                        <template #default="{ row }">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ row.customer_type }}
                            </span>
                        </template>
                    </vxe-column>
                    <vxe-column field="email" title="Email" sortable width="200"></vxe-column>
                    <vxe-column field="phone" title="Phone" sortable width="150"></vxe-column>
                    <vxe-column field="current_balance" title="Balance" sortable width="150" align="right">
                        <template #default="{ row }">
                            <span :class="getBalanceClass(row.current_balance)">
                                {{ formatCurrency(row.current_balance) }}
                            </span>
                        </template>
                    </vxe-column>
                    <vxe-column field="is_active" title="Status" sortable width="100">
                        <template #default="{ row }">
                            <span :class="row.is_active ? 'text-green-600' : 'text-red-600'">
                                {{ row.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </template>
                    </vxe-column>

                </vxe-table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingCustomer ? 'Edit Customer' : 'Add New Customer' }}</h3>
                        
                        <form @submit.prevent="saveCustomer">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Code *</label>
                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            v-model="form.customer_code"
                                            required
                                            class="flex-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                        <button
                                            type="button"
                                            @click="generateCustomerCode"
                                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Generate
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Type *</label>
                                    <select
                                        v-model="form.customer_type"
                                        required
                                        @change="onCustomerTypeChange"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="individual">Individual</option>
                                        <option value="business">Business</option>
                                    </select>
                                </div>

                                <!-- Individual fields -->
                                <template v-if="form.customer_type === 'individual'">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                        <input
                                            type="text"
                                            v-model="form.first_name"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                        <input
                                            type="text"
                                            v-model="form.last_name"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </template>

                                <!-- Business fields -->
                                <template v-if="form.customer_type === 'business'">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                                        <input
                                            type="text"
                                            v-model="form.company_name"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </template>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input
                                        type="email"
                                        v-model="form.email"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input
                                        type="text"
                                        v-model="form.phone"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile</label>
                                    <input
                                        type="text"
                                        v-model="form.mobile"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                                    <select
                                        v-model="form.payment_terms"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="">Select Payment Terms</option>
                                        <option value="cash">Cash</option>
                                        <option value="net_15">Net 15</option>
                                        <option value="net_30">Net 30</option>
                                        <option value="net_60">Net 60</option>
                                        <option value="net_90">Net 90</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Credit Limit</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model="form.credit_limit"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Opening Balance</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model="form.opening_balance"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                                    <select
                                        v-model="form.assigned_to"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="">None</option>
                                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                            {{ employee.employee_id }} - {{ employee.full_name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_active"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <span class="ml-2 block text-sm text-gray-900">Active</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                >
                                    {{ loading ? 'Saving...' : (editingCustomer ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <LedgerModal 
            :isOpen="showLedgerModal" 
            :entityId="selectedCustomer?.id" 
            entityType="customer"
            :title="selectedCustomer?.display_name || selectedCustomer?.company_name || selectedCustomer?.first_name"
            @close="showLedgerModal = false"
        />
    </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from "vue-toastification";
import LedgerModal from '../../components/LedgerModal.vue';
import { formatCurrency as formatCurrencyValue } from '@/utils/settings';

export default {
    name: 'Customers',
    components: {
        LedgerModal
    },
    setup() {
        const toast = useToast();
        const showLedgerModal = ref(false);
        const selectedCustomer = ref(null);
        const customers = ref({ data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 });
        const employees = ref([]);
        const tableExpanded = ref(true); // Table toggle state
        const showModal = ref(false);
        const editingCustomer = ref(null);
        const loading = ref(false);
        const tableLoading = ref(false);
        const sortConfig = reactive({
            sortBy: 'customer_code',
            sortOrder: 'asc',
        });

        const filters = reactive({
            search: '',
            customer_type: '',
            is_active: '',
        });

        const form = reactive({
            customer_code: '',
            company_name: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            mobile: '',
            customer_type: 'individual',
            payment_terms: '',
            credit_limit: '',
            opening_balance: 0,
            assigned_to: '',
            is_active: true,
        });

        const formatCurrency = (amount) => formatCurrencyValue(amount);

        const getBalanceClass = (balance) => {
            if (balance === null || balance === undefined) return 'text-gray-900';
            if (balance > 0) return 'text-red-600';
            if (balance < 0) return 'text-green-600';
            return 'text-gray-900';
        };

        const loadCustomers = async () => {
            tableLoading.value = true;
            try {
                const params = new URLSearchParams();
                if (filters.search) params.append('search', filters.search);
                if (filters.customer_type) params.append('customer_type', filters.customer_type);
                if (filters.is_active !== '') params.append('is_active', filters.is_active);
                if (sortConfig.sortBy) {
                    params.append('sort_by', sortConfig.sortBy);
                    params.append('sort_order', sortConfig.sortOrder);
                }
                params.append('per_page', 15);

                const response = await axios.get(`/api/customers?${params}`);
                customers.value = response.data;
            } catch (error) {
                console.error('Error loading customers:', error);
                customers.value = { data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 };
            } finally {
                tableLoading.value = false;
            }
        };

        const handlePageChange = ({ currentPage, pageSize }) => {
            customers.value.current_page = currentPage;
            loadCustomers();
        };

        const handleSortChange = ({ property, order }) => {
            sortConfig.sortBy = property;
            sortConfig.sortOrder = order || 'asc';
            loadCustomers();
        };

        const loadEmployees = async () => {
            try {
                const params = new URLSearchParams();
                params.append('is_active', 'true');
                params.append('per_page', 1000);

                const response = await axios.get(`/api/employees?${params}`);
                employees.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading employees:', error);
                employees.value = [];
            }
        };

        const generateCustomerCode = async () => {
            try {
                const response = await axios.get('/api/customers/generate-code');
                form.customer_code = response.data.customer_code;
            } catch (error) {
                console.error('Error generating customer code:', error);
                toast.error('Failed to generate customer code');
            }
        };

        const openCreateModal = () => {
            editingCustomer.value = null;
            resetForm();
            loadEmployees();
            showModal.value = true;
        };

        const editCustomer = (customer) => {
            editingCustomer.value = customer;
            form.customer_code = customer.customer_code;
            form.company_name = customer.company_name || '';
            form.first_name = customer.first_name || '';
            form.last_name = customer.last_name || '';
            form.email = customer.email || '';
            form.phone = customer.phone || '';
            form.mobile = customer.mobile || '';
            form.customer_type = customer.customer_type;
            form.payment_terms = customer.payment_terms || '';
            form.credit_limit = customer.credit_limit || '';
            form.opening_balance = customer.opening_balance || 0;
            form.assigned_to = customer.assigned_to || '';
            form.is_active = customer.is_active !== undefined ? customer.is_active : true;
            loadEmployees();
            showModal.value = true;
        };

        const viewCustomer = (customer) => {
            // Navigate to customer detail view
             console.log('View customer', customer);
        };

        const openLedger = (customer) => {
            // Redirect to Ledger page
            // Assuming we are using vue-router attached to instance or composable if available. 
            // In Options API setup(), we can use useRouter.
            // But wait, we didn't import useRouter in this file yet. Let's redirect via window or add router.
            // Ideally we should import useRouter.
            window.location.href = `/admin/ledger?type=customer&id=${customer.id}`;
        };

        const onCustomerTypeChange = () => {
            if (form.customer_type === 'business') {
                form.first_name = '';
                form.last_name = '';
            } else {
                form.company_name = '';
            }
        };

        const resetForm = () => {
            form.customer_code = '';
            form.company_name = '';
            form.first_name = '';
            form.last_name = '';
            form.email = '';
            form.phone = '';
            form.mobile = '';
            form.customer_type = 'individual';
            form.payment_terms = '';
            form.credit_limit = '';
            form.opening_balance = 0;
            form.assigned_to = '';
            form.is_active = true;
        };

        const closeModal = () => {
            showModal.value = false;
            editingCustomer.value = null;
            resetForm();
        };

        const saveCustomer = async () => {
            loading.value = true;
            try {
                const formData = { ...form };
                
                // Clean up empty strings
                if (!formData.credit_limit) formData.credit_limit = null;
                if (!formData.opening_balance) formData.opening_balance = 0;
                if (!formData.assigned_to) formData.assigned_to = null;
                
                if (editingCustomer.value) {
                    await axios.put(`/api/customers/${editingCustomer.value.id}`, formData);
                    toast.success('Customer updated successfully');
                } else {
                    await axios.post('/api/customers', formData);
                    toast.success('Customer created successfully');
                }
                
                closeModal();
                loadCustomers();
            } catch (error) {
                console.error('Error saving customer:', error);
                const message = error.response?.data?.message || 'Failed to save customer';
                const errors = error.response?.data?.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat().join('\n');
                    toast.error(errorMessages);
                } else {
                    toast.error(message);
                }
            } finally {
                loading.value = false;
            }
        };

        const deleteCustomer = async (customer) => {
            if (!confirm(`Are you sure you want to delete customer ${customer.customer_code}?`)) {
                return;
            }

            try {
                await axios.delete(`/api/customers/${customer.id}`);
                toast.success('Customer deleted successfully');
                loadCustomers();
            } catch (error) {
                console.error('Error deleting customer:', error);
                const message = error.response?.data?.message || 'Failed to delete customer';
                toast.error(message);
            }
        };

        const changePage = (page) => {
            if (page >= 1 && page <= customers.value.last_page) {
                filters.page = page;
                loadCustomers();
            }
        };

        onMounted(() => {
            loadCustomers();
        });

        return {
            customers,
            employees,
            filters,
            form,
            showModal,
            editingCustomer,
            loading,
            formatCurrency,
            getBalanceClass,
            loadCustomers,
            loadEmployees,
            generateCustomerCode,
            openCreateModal,
            editCustomer,
            viewCustomer,
            onCustomerTypeChange,
            closeModal,
            saveCustomer,
            deleteCustomer,
            changePage,
            tableLoading,
            handlePageChange,

            handleSortChange,
            sortConfig,
            tableExpanded,
            showLedgerModal,
            selectedCustomer,
            openLedger
        };
    },
};
</script>
