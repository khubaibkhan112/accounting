<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Chart of Accounts</h1>
                <p class="mt-1 text-sm text-gray-500">Manage your chart of accounts with hierarchy</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- View Toggle -->
                <div class="flex items-center bg-gray-100 rounded-lg p-1">
                    <button
                        type="button"
                        @click="viewMode = 'table'"
                        :class="[
                            'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                            viewMode === 'table' 
                                ? 'bg-white text-blue-600 shadow-sm' 
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Table
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'tree'"
                        :class="[
                            'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                            viewMode === 'tree' 
                                ? 'bg-white text-blue-600 shadow-sm' 
                                : 'text-gray-600 hover:text-gray-900'
                        ]"
                    >
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Tree
                    </button>
                </div>
                <button 
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Account
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input
                        type="text"
                        v-model="filters.search"
                        @input="loadAccounts"
                        placeholder="Account code or name..."
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                    <select
                        v-model="filters.account_type"
                        @change="loadAccounts"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Types</option>
                        <option value="asset">Asset</option>
                        <option value="liability">Liability</option>
                        <option value="equity">Equity</option>
                        <option value="revenue">Revenue</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                        v-model="filters.is_active"
                        @change="loadAccounts"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Status</option>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button
                        @click="clearFilters"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Clear
                    </button>
                    <button
                        @click="loadAccounts"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Tree View -->
        <div v-if="viewMode === 'tree'" class="bg-white shadow rounded-lg p-6">
            <div v-if="treeLoading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-sm text-gray-500">Loading chart of accounts...</p>
            </div>
            <div v-else-if="accountTree.length === 0" class="text-center py-8 text-gray-500">
                <p>No accounts found. Create your first account to get started.</p>
            </div>
            <div v-else class="space-y-2">
                <AccountTreeNode
                    v-for="node in accountTree"
                    :key="node.id"
                    :node="node"
                    :level="0"
                    @edit="editAccount"
                    @view="viewAccount"
                />
            </div>
        </div>

        <!-- Accounts Table -->
        <div v-if="viewMode === 'table'" class="bg-white shadow rounded-lg">
            <!-- Table Toggle Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Accounts List</h3>
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
                    :key="`table-${accounts.total}-${accounts.current_page}`"
                    :data="accountsData"
                    :loading="tableLoading"
                    stripe
                    border
                    highlight-hover-row
                    height="600"
                    :scroll-y="{ enabled: true, gt: 0 }"
                    :sort-config="{ trigger: 'default', remote: true }"
                    :pager-config="{
                        enabled: true,
                        currentPage: accounts.current_page,
                        pageSize: 15,
                        total: accounts.total,
                        pageSizes: [10, 15, 20, 50, 100],
                        layouts: ['PrevJump', 'PrevPage', 'Number', 'NextPage', 'NextJump', 'Sizes', 'FullJump', 'Total']
                    }"
                    @page-change="handlePageChange"
                    @sort-change="handleSortChange"
                >
            <vxe-column field="account_code" title="Account Code" sortable width="150"></vxe-column>
            <vxe-column field="account_name" title="Account Name" sortable min-width="200"></vxe-column>
            <vxe-column field="account_type" title="Type" sortable width="120">
                <template #default="scope">
                    <span v-if="scope && scope.row" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getTypeClass(scope.row.account_type)">
                        {{ scope.row.account_type }}
                    </span>
                </template>
            </vxe-column>
            <vxe-column field="opening_balance" title="Opening Balance" sortable width="150" align="right">
                <template #default="scope">
                    <span v-if="scope && scope.row">{{ formatCurrency(scope.row.opening_balance) }}</span>
                </template>
            </vxe-column>
            <vxe-column field="current_balance" title="Current Balance" sortable width="150" align="right">
                <template #default="scope">
                    <span v-if="scope && scope.row">{{ formatCurrency(scope.row.current_balance || 0) }}</span>
                </template>
            </vxe-column>
            <vxe-column field="is_active" title="Status" sortable width="100">
                <template #default="scope">
                    <span v-if="scope && scope.row" :class="scope.row.is_active ? 'text-green-600' : 'text-red-600'">
                        {{ scope.row.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </template>
            </vxe-column>
            <vxe-column title="Actions" width="140" fixed="right" align="center">
                <template #default="scope">
                    <template v-if="scope && scope.row">
                        <div class="flex items-center justify-center gap-2">
                            <button 
                                @click="viewAccount(scope.row)" 
                                class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                                title="View"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                            <button 
                                @click="editAccount(scope.row)" 
                                class="p-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded transition-colors"
                                title="Edit"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button 
                                @click="deleteAccount(scope.row)" 
                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                                title="Delete"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </template>
            </vxe-column>
                </vxe-table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>
                
                <!-- Modal content -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingAccount ? 'Edit Account' : 'Add New Account' }}</h3>
                        
                        <form @submit.prevent="saveAccount">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Account Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Code *</label>
                                    <input
                                        type="text"
                                        v-model="form.account_code"
                                        required
                                        :class="[
                                            'block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                            formErrors.account_code ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    />
                                    <p v-if="formErrors.account_code" class="mt-1 text-sm text-red-600">{{ formErrors.account_code[0] }}</p>
                                </div>

                                <!-- Account Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name *</label>
                                    <input
                                        type="text"
                                        v-model="form.account_name"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Account Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Type *</label>
                                    <select
                                        v-model="form.account_type"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="">Select Type</option>
                                        <option value="asset">Asset</option>
                                        <option value="liability">Liability</option>
                                        <option value="equity">Equity</option>
                                        <option value="revenue">Revenue</option>
                                        <option value="expense">Expense</option>
                                    </select>
                                </div>

                                <!-- Parent Account -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent Account</label>
                                    <select
                                        v-model="form.parent_account_id"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="">None (Top Level)</option>
                                        <option v-for="account in parentAccounts" :key="account.id" :value="account.id">
                                            {{ account.account_code }} - {{ account.account_name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Opening Balance -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Opening Balance</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        v-model="form.opening_balance"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Description -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>

                                <!-- Active Status -->
                                <div>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_active"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Active</span>
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
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 flex items-center gap-2"
                                >
                                    <LoadingSpinner v-if="loading" size="sm" />
                                    {{ loading ? 'Saving...' : (editingAccount ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch, toRaw } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AccountTreeNode from '@/components/AccountTreeNode.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import { useNotifications } from '@/composables/useNotifications';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

export default {
    name: 'Accounts',
    setup() {
        const router = useRouter();
        const { showSuccess, showError } = useNotifications();
        const { confirmDelete } = useConfirmDialog();
        const accounts = ref({ data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 });
        
        // Use a ref for table data to ensure vxe-table reactivity
        const accountsData = ref([]);
        
        // Watch accounts and update accountsData when data changes
        watch(() => accounts.value?.data, (newData) => {
            if (Array.isArray(newData)) {
                accountsData.value = [...newData]; // Create a new array to ensure reactivity
            } else {
                accountsData.value = [];
            }
        }, { immediate: true, deep: true });
        
        const parentAccounts = ref([]);
        const showModal = ref(false);
        const editingAccount = ref(null);
        const loading = ref(false);
        const tableLoading = ref(false);
        const treeLoading = ref(false);
        const viewMode = ref('table'); // 'table' or 'tree'
        const accountTree = ref([]);
        const tableExpanded = ref(true); // Table toggle state
        const sortConfig = reactive({
            sortBy: 'account_code',
            sortOrder: 'asc',
        });

        const filters = reactive({
            search: '',
            account_type: '',
            is_active: '',
        });

        const form = reactive({
            account_code: '',
            account_name: '',
            account_type: '',
            parent_account_id: '',
            opening_balance: 0,
            description: '',
            is_active: true,
        });

        const formErrors = reactive({});

        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return '$0.00';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(amount);
        };

        const getTypeClass = (type) => {
            const classes = {
                asset: 'bg-green-100 text-green-800',
                liability: 'bg-red-100 text-red-800',
                equity: 'bg-blue-100 text-blue-800',
                revenue: 'bg-yellow-100 text-yellow-800',
                expense: 'bg-purple-100 text-purple-800',
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        };

        const loadAccounts = async () => {
            tableLoading.value = true;
            try {
                const params = new URLSearchParams();
                if (filters.search) params.append('search', filters.search);
                if (filters.account_type) params.append('account_type', filters.account_type);
                if (filters.is_active !== '') params.append('is_active', filters.is_active);
                if (sortConfig.sortBy) {
                    params.append('sort_by', sortConfig.sortBy);
                    params.append('sort_order', sortConfig.sortOrder);
                }
                params.append('per_page', 15);

                const response = await axios.get(`/api/accounts?${params}`);
                console.log('Accounts API Response:', response.data);
                // Keep the full pagination object, not just the data array
                accounts.value = response.data;
                // The watcher will update accountsData, but we can also set it directly
                if (Array.isArray(response.data.data)) {
                    accountsData.value = [...response.data.data];
                }
                console.log('Accounts value after assignment:', accounts.value);
                console.log('AccountsData ref:', accountsData.value);
                console.log('AccountsData length:', accountsData.value.length);
            } catch (error) {
                console.error('Error loading accounts:', error);
                if (error.response) {
                    console.error('Error response:', error.response.data);
                }
                accounts.value = { data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 };
            } finally {
                tableLoading.value = false;
            }
        };

        const clearFilters = () => {
            filters.search = '';
            filters.account_type = '';
            filters.is_active = '';
            loadAccounts();
        };

        const handlePageChange = ({ currentPage, pageSize }) => {
            accounts.value.current_page = currentPage;
            loadAccounts();
        };

        const handleSortChange = ({ property, order }) => {
            sortConfig.sortBy = property;
            sortConfig.sortOrder = order || 'asc';
            loadAccounts();
        };

        const loadAccountTree = async () => {
            treeLoading.value = true;
            try {
                const response = await axios.get('/api/accounts/tree');
                accountTree.value = response.data.tree || [];
            } catch (error) {
                console.error('Error loading account tree:', error);
                accountTree.value = [];
            } finally {
                treeLoading.value = false;
            }
        };

        watch(viewMode, (newMode) => {
            if (newMode === 'tree' && accountTree.value.length === 0) {
                loadAccountTree();
            }
        });

        const loadParentAccounts = async () => {
            try {
                const params = new URLSearchParams();
                params.append('parent_only', 'true');
                params.append('is_active', 'true');
                params.append('per_page', 100);

                const response = await axios.get(`/api/accounts?${params}`);
                parentAccounts.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading parent accounts:', error);
                parentAccounts.value = [];
            }
        };

        const openCreateModal = () => {
            editingAccount.value = null;
            resetForm();
            loadParentAccounts();
            showModal.value = true;
        };

        const editAccount = (account) => {
            editingAccount.value = account;
            Object.keys(form).forEach(key => {
                if (account[key] !== undefined && account[key] !== null) {
                    form[key] = account[key];
                } else {
                    form[key] = key === 'is_active' ? true : (key === 'opening_balance' ? 0 : '');
                }
            });
            loadParentAccounts();
            showModal.value = true;
        };

        const viewAccount = (account) => {
            router.push({ name: 'admin.accounts', params: { id: account.id } });
        };

        const resetForm = () => {
            form.account_code = '';
            form.account_name = '';
            form.account_type = '';
            form.parent_account_id = '';
            form.opening_balance = 0;
            form.description = '';
            form.is_active = true;
        };

        const closeModal = () => {
            showModal.value = false;
            editingAccount.value = null;
            resetForm();
        };

        const saveAccount = async () => {
            // Clear previous errors
            Object.keys(formErrors).forEach(key => delete formErrors[key]);
            
            loading.value = true;
            try {
                const formData = { ...form };
                
                if (editingAccount.value) {
                    await axios.put(`/api/accounts/${editingAccount.value.id}`, formData);
                    showSuccess('Account updated successfully');
                } else {
                    await axios.post('/api/accounts', formData);
                    showSuccess('Account created successfully');
                }
                
                closeModal();
                loadAccounts();
            } catch (error) {
                console.error('Error saving account:', error);
                
                // Handle validation errors
                if (error.response?.status === 422 && error.response?.data?.errors) {
                    Object.assign(formErrors, error.response.data.errors);
                    showError('Please fix the validation errors');
                } else {
                    const message = error.response?.data?.message || 'Failed to save account';
                    showError(message);
                }
            } finally {
                loading.value = false;
            }
        };

        const deleteAccount = async (account) => {
            const confirmed = await confirmDelete(`account "${account.account_code} - ${account.account_name}"`);
            if (!confirmed) {
                return;
            }

            try {
                await axios.delete(`/api/accounts/${account.id}`);
                showSuccess('Account deleted successfully');
                loadAccounts();
            } catch (error) {
                console.error('Error deleting account:', error);
                const message = error.response?.data?.message || 'Failed to delete account';
                showError(message);
            }
        };

        const changePage = (page) => {
            if (page >= 1 && page <= accounts.value.last_page) {
                accounts.value.current_page = page;
                loadAccounts();
            }
        };

        onMounted(() => {
            loadAccounts();
        });

        return {
            accounts,
            accountsData,
            parentAccounts,
            filters,
            clearFilters,
            form,
            showModal,
            editingAccount,
            loading,
            formatCurrency,
            getTypeClass,
            loadAccounts,
            openCreateModal,
            editAccount,
            viewAccount,
            closeModal,
            saveAccount,
            deleteAccount,
            changePage,
            tableLoading,
            handlePageChange,
            handleSortChange,
            sortConfig,
            viewMode,
            accountTree,
            treeLoading,
            loadAccountTree,
            formErrors,
            tableExpanded,
        };
    },
    components: {
        AccountTreeNode,
        LoadingSpinner,
    },
};
</script>
