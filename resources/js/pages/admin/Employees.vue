<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Employees</h1>
                <p class="mt-1 text-sm text-gray-500">Manage employee records and user accounts</p>
            </div>
            <button 
                type="button"
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Employee
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <input
                        type="text"
                        v-model="filters.search"
                        @input="loadEmployees"
                        placeholder="Search employees..."
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <select
                        v-model="filters.department"
                        @change="loadEmployees"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Departments</option>
                        <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                    </select>
                </div>
                <div>
                    <select
                        v-model="filters.employment_type"
                        @change="loadEmployees"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Types</option>
                        <option value="full-time">Full-time</option>
                        <option value="part-time">Part-time</option>
                        <option value="contract">Contract</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>
                <div>
                    <select
                        v-model="filters.is_active"
                        @change="loadEmployees"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Status</option>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="bg-white shadow rounded-lg">
            <!-- Table Toggle Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Employees List</h3>
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
                    v-if="employees && Array.isArray(employees.data)"
                    :data="employeesData"
                    :loading="tableLoading"
                    stripe
                    border
                    highlight-hover-row
                    height="600"
                    :scroll-y="{ enabled: true, gt: 0 }"
                    :sort-config="{ trigger: 'default', remote: true }"
                    :pager-config="{
                        enabled: true,
                        currentPage: employees.current_page,
                        pageSize: 15,
                        total: employees.total,
                        pageSizes: [10, 15, 20, 50, 100],
                        layouts: ['PrevJump', 'PrevPage', 'Number', 'NextPage', 'NextJump', 'Sizes', 'FullJump', 'Total']
                    }"
                    :key="`table-${employees.total}-${employees.current_page}`"
                    @page-change="handlePageChange"
                    @sort-change="handleSortChange"
                >
                    <vxe-column field="employee_id" title="Employee ID" sortable width="150"></vxe-column>
                    <vxe-column field="full_name" title="Name" sortable min-width="200"></vxe-column>
                    <vxe-column field="position" title="Position" sortable width="150"></vxe-column>
                    <vxe-column field="department" title="Department" sortable width="150"></vxe-column>
                    <vxe-column field="email" title="Email" sortable width="200"></vxe-column>
                    <vxe-column field="employment_type" title="Employment Type" sortable width="150">
                        <template #default="slotProps">
                            <span v-if="slotProps && slotProps.row" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ slotProps.row?.employment_type }}
                            </span>
                        </template>
                    </vxe-column>
                    <vxe-column field="user_id" title="Login Access" sortable width="120">
                        <template #default="slotProps">
                            <span v-if="slotProps && slotProps.row">
                                <span v-if="slotProps.row.user_id" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Yes
                                </span>
                                <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    No
                                </span>
                            </span>
                        </template>
                    </vxe-column>
                    <vxe-column field="is_active" title="Status" sortable width="100">
                        <template #default="slotProps">
                            <span v-if="slotProps && slotProps.row" :class="slotProps.row.is_active ? 'text-green-600' : 'text-red-600'">
                                {{ slotProps.row.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </template>
                    </vxe-column>
                    <vxe-column type="expand" width="60">
                        <template #content="{ row }">
                            <div class="p-4 flex gap-4 bg-gray-50">
                                <button 
                                    @click="editEmployee(row)" 
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
                                    @click="deleteEmployee(row)" 
                                    class="inline-flex items-center px-3 py-1.5 border border-red-600 rounded-md text-red-600 hover:bg-red-50 text-sm font-medium"
                                >
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Deactivate
                                </button>
                            </div>
                        </template>
                    </vxe-column>
                    <vxe-column field="employee_id" title="Employee ID" sortable width="150"></vxe-column>
                </vxe-table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>
                
                <!-- Modal content -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingEmployee ? 'Edit Employee' : 'Add New Employee' }}</h3>
                        
                        <form @submit.prevent="saveEmployee">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Employee ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID *</label>
                                    <input
                                        type="text"
                                        v-model="form.employee_id"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                    <button
                                        type="button"
                                        @click="generateEmployeeId"
                                        class="mt-1 text-xs text-blue-600 hover:text-blue-800"
                                    >
                                        Generate ID
                                    </button>
                                </div>

                                <!-- First Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input
                                        type="text"
                                        v-model="form.first_name"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Last Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input
                                        type="text"
                                        v-model="form.last_name"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input
                                        type="email"
                                        v-model="form.email"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input
                                        type="tel"
                                        v-model="form.phone"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input
                                        type="date"
                                        v-model="form.date_of_birth"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Gender -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                    <select
                                        v-model="form.gender"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <!-- Position -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                                    <input
                                        type="text"
                                        v-model="form.position"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Department -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                    <input
                                        type="text"
                                        v-model="form.department"
                                        list="departments-list"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                    <datalist id="departments-list">
                                        <option v-for="dept in departments" :key="dept" :value="dept"></option>
                                    </datalist>
                                </div>

                                <!-- Employment Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type *</label>
                                    <select
                                        v-model="form.employment_type"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="full-time">Full-time</option>
                                        <option value="part-time">Part-time</option>
                                        <option value="contract">Contract</option>
                                        <option value="intern">Intern</option>
                                    </select>
                                </div>

                                <!-- Hire Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date</label>
                                    <input
                                        type="date"
                                        v-model="form.hire_date"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Salary -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Salary</label>
                                    <input
                                        type="number"
                                        v-model="form.salary"
                                        step="0.01"
                                        min="0"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Address -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea
                                        v-model="form.address"
                                        rows="2"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>

                                <!-- City, State, Postal Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                    <input
                                        type="text"
                                        v-model="form.city"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                    <input
                                        type="text"
                                        v-model="form.state"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                    <input
                                        type="text"
                                        v-model="form.postal_code"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                    <input
                                        type="text"
                                        v-model="form.country"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Emergency Contact -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Name</label>
                                    <input
                                        type="text"
                                        v-model="form.emergency_contact_name"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Phone</label>
                                    <input
                                        type="tel"
                                        v-model="form.emergency_contact_phone"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Notes -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_active"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Active</span>
                                    </label>
                                </div>

                                <!-- Create User Account Section -->
                                <div class="md:col-span-2 border-t pt-4 mt-4">
                                    <h4 class="text-md font-medium text-gray-900 mb-3">User Account</h4>
                                    <div class="mb-4">
                                        <label class="flex items-center">
                                            <input
                                                type="checkbox"
                                                v-model="form.create_user_account"
                                                :disabled="editingEmployee && editingEmployee.user_id"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">
                                                {{ editingEmployee && editingEmployee.user_id ? 'User account already exists' : 'Create user account for login' }}
                                            </span>
                                        </label>
                                    </div>
                                    <div v-if="form.create_user_account && !(editingEmployee && editingEmployee.user_id)" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                            <input
                                                type="email"
                                                v-model="form.user_email"
                                                :required="form.create_user_account"
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                            <input
                                                type="password"
                                                v-model="form.user_password"
                                                :required="form.create_user_account"
                                                minlength="8"
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                            <select
                                                v-model="form.user_role"
                                                :required="form.create_user_account"
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            >
                                                <option value="driver">Driver</option>
                                                <option value="accountant">Accountant</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ loading ? 'Saving...' : (editingEmployee ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <LedgerModal 
            :isOpen="showLedgerModal" 
            :entityId="selectedEmployee?.id" 
            entityType="employee"
            :title="selectedEmployee?.full_name"
            @close="showLedgerModal = false"
        />
    </div>
</template>

<script>
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from "vue-toastification";
import LedgerModal from '../../components/LedgerModal.vue';

export default {
    name: 'Employees',
    components: {
        LedgerModal
    },
    setup() {
        const toast = useToast();
        const showLedgerModal = ref(false);
        const selectedEmployee = ref(null);
        const employees = ref({ data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 });
        const employeesData = ref([]);
        const departments = ref([]);
        const tableExpanded = ref(true); // Table toggle state
        const showModal = ref(false);
        const editingEmployee = ref(null);
        const loading = ref(false);
        const tableLoading = ref(false);
        const sortConfig = reactive({
            sortBy: 'employee_id',
            sortOrder: 'asc',
        });
        
        const filters = reactive({
            search: '',
            department: '',
            employment_type: '',
            is_active: '',
        });

        const form = reactive({
            employee_id: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            date_of_birth: '',
            gender: '',
            address: '',
            city: '',
            state: '',
            postal_code: '',
            country: '',
            position: '',
            department: '',
            hire_date: '',
            termination_date: '',
            employment_type: 'full-time',
            salary: '',
            emergency_contact_name: '',
            emergency_contact_phone: '',
            notes: '',
            is_active: true,
            create_user_account: false,
            user_email: '',
            user_password: '',
            user_role: 'driver',
        });

        // Watch for changes in employees.data and update employeesData
        watch(() => employees.value.data, (newData) => {
            employeesData.value = Array.isArray(newData) ? newData : [];
        }, { immediate: true, deep: true });

        const loadEmployees = async () => {
            tableLoading.value = true;
            try {
                const params = new URLSearchParams();
                if (filters.search) params.append('search', filters.search);
                if (filters.department) params.append('department', filters.department);
                if (filters.employment_type) params.append('employment_type', filters.employment_type);
                if (filters.is_active !== '') params.append('is_active', filters.is_active);
                if (sortConfig.sortBy) {
                    params.append('sort_by', sortConfig.sortBy);
                    params.append('sort_order', sortConfig.sortOrder);
                }
                params.append('per_page', 15);

                const response = await axios.get(`/api/employees?${params}`);
                employees.value = response.data;
            } catch (error) {
                console.error('Error loading employees:', error);
                // Don't block the UI if API fails
                employees.value = { data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 };
                console.warn('Failed to load employees. API might not be available.');
            } finally {
                tableLoading.value = false;
            }
        };

        const handlePageChange = ({ currentPage, pageSize }) => {
            employees.value.current_page = currentPage;
            loadEmployees();
        };

        const handleSortChange = ({ property, order }) => {
            sortConfig.sortBy = property;
            sortConfig.sortOrder = order || 'asc';
            loadEmployees();
        };

        const loadDepartments = async () => {
            try {
                const response = await axios.get('/api/employees/departments');
                departments.value = response.data;
            } catch (error) {
                console.error('Error loading departments:', error);
                departments.value = [];
            }
        };

        const generateEmployeeId = async () => {
            try {
                const response = await axios.get('/api/employees/generate-id');
                form.employee_id = response.data.employee_id;
            } catch (error) {
                console.error('Error generating employee ID:', error);
            }
        };

        const openCreateModal = (e) => {
            e?.preventDefault();
            e?.stopPropagation();
            console.log('openCreateModal called'); // Debug
            editingEmployee.value = null;
            resetForm();
            showModal.value = true;
            console.log('showModal.value:', showModal.value); // Debug
        };

        const editEmployee = (employee) => {
            editingEmployee.value = employee;
            Object.keys(form).forEach(key => {
                if (employee[key] !== undefined && employee[key] !== null) {
                    form[key] = employee[key];
                } else {
                    form[key] = '';
                }
            });
            form.create_user_account = false;
            showModal.value = true;
        };

        const resetForm = () => {
            Object.keys(form).forEach(key => {
                if (key === 'employment_type') {
                    form[key] = 'full-time';
                } else if (key === 'is_active') {
                    form[key] = true;
                } else if (key === 'create_user_account') {
                    form[key] = false;
                } else if (key === 'user_role') {
                    form[key] = 'driver';
                } else {
                    form[key] = '';
                }
            });
        };

        const openLedger = (employee) => {
            selectedEmployee.value = employee;
            showLedgerModal.value = true;
        };

        const closeModal = () => {
            showModal.value = false;
            editingEmployee.value = null;
            resetForm();
        };

        const saveEmployee = async () => {
            loading.value = true;
            try {
                const formData = { ...form };
                
                // Only include user account fields if create_user_account is true
                if (!formData.create_user_account) {
                    delete formData.user_email;
                    delete formData.user_password;
                    delete formData.user_role;
                }
                
                if (editingEmployee.value) {
                    await axios.put(`/api/employees/${editingEmployee.value.id}`, formData);
                    alert('Employee updated successfully');
                } else {
                    await axios.post('/api/employees', formData);
                    alert('Employee created successfully');
                }
                
                closeModal();
                loadEmployees();
            } catch (error) {
                console.error('Error saving employee:', error);
                const message = error.response?.data?.message || 'Failed to save employee';
                alert(message);
            } finally {
                loading.value = false;
            }
        };

        const deleteEmployee = async (employee) => {
            if (!confirm(`Are you sure you want to deactivate ${employee.full_name}?`)) {
                return;
            }

            try {
                await axios.delete(`/api/employees/${employee.id}`);
                alert('Employee deactivated successfully');
                loadEmployees();
            } catch (error) {
                console.error('Error deleting employee:', error);
                alert('Failed to deactivate employee');
            }
        };

        const changePage = (page) => {
            if (page >= 1 && page <= employees.value.last_page) {
                filters.page = page;
                loadEmployees();
            }
        };

        onMounted(async () => {
            console.log('Employees component mounted');
            try {
                await Promise.all([loadEmployees(), loadDepartments()]);
            } catch (error) {
                console.error('Error initializing employees component:', error);
            }
        });

        return {
            employees,
            employeesData,
            departments,
            filters,
            form,
            showModal,
            editingEmployee,
            loading,
            loadEmployees,
            loadDepartments,
            generateEmployeeId,
            openCreateModal,
            editEmployee,
            closeModal,
            saveEmployee,
            deleteEmployee,
            changePage,
            tableLoading,
            handlePageChange,
            handleSortChange,
            sortConfig,
            tableExpanded,
            showLedgerModal,
            selectedEmployee,
            openLedger
        };
    },
};
</script>

