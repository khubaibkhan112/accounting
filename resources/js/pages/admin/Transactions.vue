<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transactions</h1>
                <p class="mt-1 text-sm text-gray-500">View and manage all transactions</p>
            </div>
            <button 
                type="button"
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Transaction
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input
                        type="text"
                        v-model="filters.search"
                        @input="onFiltersChanged"
                        placeholder="Description, reference, account..."
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
                    <select
                        v-model="filters.account_id"
                        @change="onFiltersChanged"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Accounts</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.account_code }} - {{ account.account_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                    <select
                        v-model="filters.transaction_type"
                        @change="onFiltersChanged"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Types</option>
                        <option value="payment">Payment</option>
                        <option value="receipt">Receipt</option>
                        <option value="journal">Journal</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input
                        type="date"
                        v-model="filters.date_from"
                        @change="onFiltersChanged"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input
                        type="date"
                        v-model="filters.date_to"
                        @change="onFiltersChanged"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
            </div>
            <!-- Quick Date Range & Actions -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in datePresets"
                        :key="preset.label"
                        @click="applyDatePreset(preset)"
                        class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        {{ preset.label }}
                    </button>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="clearFilters"
                        class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Clear Filters
                    </button>
                    <button
                        @click="loadTransactions"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <!-- Table Toggle Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Transactions List</h3>
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
            <div v-show="tableExpanded" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="transaction in transactions.data" :key="transaction.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(transaction.date) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>{{ transaction.account?.account_code }}</div>
                                <div class="text-xs text-gray-500">{{ transaction.account?.account_name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ transaction.description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span v-if="transaction.customer">
                                    {{
                                        transaction.customer.company_name
                                            || [transaction.customer.first_name, transaction.customer.last_name].filter(Boolean).join(' ')
                                            || '-'
                                    }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span v-if="transaction.employee">
                                    {{ transaction.employee.first_name }} {{ transaction.employee.last_name }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div v-if="transaction.vehicle">
                                    <div class="font-medium">{{ transaction.vehicle.vehicle_number }}</div>
                                    <div class="text-xs text-gray-500">{{ transaction.vehicle.chassis_number }}</div>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                <span v-if="transaction.debit_amount > 0">{{ formatCurrency(transaction.debit_amount) }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                <span v-if="transaction.credit_amount > 0">{{ formatCurrency(transaction.credit_amount) }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ formatCurrency(transaction.running_balance) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ transaction.reference_number || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="editTransaction(transaction)" 
                                        class="p-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded transition-colors"
                                        title="Edit"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="deleteTransaction(transaction)" 
                                        class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                                        title="Delete"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="transactions.data && transactions.data.length === 0">
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No transactions found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="transactions.data && transactions.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ transactions.from }} to {{ transactions.to }} of {{ transactions.total }} results
                    </div>
                    <div class="flex space-x-2">
                        <button
                            @click="changePage(transactions.current_page - 1)"
                            :disabled="transactions.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="changePage(transactions.current_page + 1)"
                            :disabled="transactions.current_page === transactions.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                </div>
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
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingTransaction ? 'Edit Transaction' : 'Add New Transaction' }}</h3>
                        
                        <form @submit.prevent="saveTransaction">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                                    <input
                                        type="date"
                                        v-model="form.date"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <!-- Account -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account *</label>
                                    <select
                                        v-model="form.account_id"
                                        @change="onAccountChange(); clearError(formErrors, 'account_id')"
                                        required
                                        :class="[
                                            'block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                            formErrors.account_id ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    >
                                        <option value="">Select Account</option>
                                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                                            {{ account.account_code }} - {{ account.account_name }}
                                        </option>
                                    </select>
                                    <p v-if="formErrors.account_id" class="mt-1 text-sm text-red-600">{{ formErrors.account_id }}</p>
                                    <!-- Account Balance Info -->
                                    <div v-if="selectedAccount" class="mt-2 p-2 bg-blue-50 rounded-md">
                                        <div class="text-xs text-gray-600">
                                            <div class="flex justify-between">
                                                <span>Current Balance:</span>
                                                <span class="font-medium" :class="getBalanceClass(selectedAccount.current_balance)">
                                                    {{ formatCurrency(selectedAccount.current_balance) }}
                                                </span>
                                            </div>
                                            <div v-if="projectedBalance !== null" class="flex justify-between mt-1 pt-1 border-t border-blue-200">
                                                <span>After Transaction:</span>
                                                <span class="font-semibold" :class="getBalanceClass(projectedBalance)">
                                                    {{ formatCurrency(projectedBalance) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <input
                                                type="text"
                                                v-model="customerSearch"
                                                @input="onCustomerSearchInput"
                                                @focus="customerOptionsOpen = true"
                                                @blur="closeCustomerOptions"
                                                placeholder="Search customer..."
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            />
                                            <div
                                                v-show="customerOptionsOpen"
                                                class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"
                                            >
                                                <button
                                                    v-for="customer in filteredCustomers"
                                                    :key="customer.id"
                                                    type="button"
                                                    class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                                    @mousedown.prevent="selectCustomer(customer)"
                                                >
                                                    {{ getCustomerLabel(customer) }}
                                                </button>
                                                <div v-if="filteredCustomers.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                                    No customers found
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="openCreateCustomerModal"
                                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            title="Add New Customer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Vehicle (only show if customer is selected) -->
                                <div v-if="form.customer_id">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <input
                                                type="text"
                                                v-model="vehicleSearch"
                                                @input="onVehicleSearchInput"
                                                @focus="vehicleOptionsOpen = true"
                                                @blur="closeVehicleOptions"
                                                placeholder="Search vehicle..."
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            />
                                            <div
                                                v-show="vehicleOptionsOpen"
                                                class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"
                                            >
                                                <button
                                                    v-for="vehicle in filteredVehicles"
                                                    :key="vehicle.id"
                                                    type="button"
                                                    class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                                    @mousedown.prevent="selectVehicle(vehicle)"
                                                >
                                                    {{ getVehicleLabel(vehicle) }}
                                                </button>
                                                <div v-if="filteredVehicles.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                                    No vehicles found
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="openCreateVehicleModal"
                                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            title="Add New Vehicle"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Employee -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <input
                                                type="text"
                                                v-model="employeeSearch"
                                                @input="onEmployeeSearchInput"
                                                @focus="employeeOptionsOpen = true"
                                                @blur="closeEmployeeOptions"
                                                placeholder="Search employee..."
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            />
                                            <div
                                                v-show="employeeOptionsOpen"
                                                class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"
                                            >
                                                <button
                                                    v-for="employee in filteredEmployees"
                                                    :key="employee.id"
                                                    type="button"
                                                    class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                                    @mousedown.prevent="selectEmployee(employee)"
                                                >
                                                    {{ getEmployeeLabel(employee) }}
                                                </button>
                                                <div v-if="filteredEmployees.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                                    No employees found
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="openCreateEmployeeModal"
                                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            title="Add New Employee"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                                    <textarea
                                        v-model="form.description"
                                        @input="clearError(formErrors, 'description')"
                                        rows="3"
                                        required
                                        :class="[
                                            'block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                            formErrors.description ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    ></textarea>
                                    <p v-if="formErrors.description" class="mt-1 text-sm text-red-600">{{ formErrors.description }}</p>
                                </div>

                                <!-- Debit Amount -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Debit Amount</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="form.debit_amount"
                                        @input="handleAmountInput('debit'); calculateProjectedBalance(); clearError(formErrors, 'debit_amount')"
                                        :class="[
                                            'block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                            formErrors.debit_amount ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    />
                                    <p v-if="formErrors.debit_amount" class="mt-1 text-sm text-red-600">{{ formErrors.debit_amount }}</p>
                                </div>

                                <!-- Credit Amount -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Credit Amount</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="form.credit_amount"
                                        @input="handleAmountInput('credit'); calculateProjectedBalance(); clearError(formErrors, 'credit_amount')"
                                        :class="[
                                            'block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                            formErrors.credit_amount ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    />
                                    <p v-if="formErrors.credit_amount" class="mt-1 text-sm text-red-600">{{ formErrors.credit_amount }}</p>
                                </div>

                                <!-- Reference Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                                    <input
                                        type="text"
                                        v-model="form.reference_number"
                                        :disabled="autoGenerateReference && !editingTransaction"
                                        :placeholder="autoGenerateReference && !editingTransaction ? 'Auto-generated' : ''"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                    <p v-if="autoGenerateReference && !editingTransaction" class="mt-1 text-xs text-gray-500">Reference number will be generated automatically.</p>
                                </div>

                                <!-- Transaction Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                                    <input
                                        type="text"
                                        v-model="form.transaction_type"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
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
                                    {{ loading ? 'Saving...' : (editingTransaction ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Customer Creation Modal -->
        <div v-if="showCustomerModal" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 99999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCustomerModal"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Customer</h3>
                        
                        <form @submit.prevent="saveCustomerInline">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Code *</label>
                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            v-model="customerForm.customer_code"
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
                                        v-model="customerForm.customer_type"
                                        required
                                        @change="onCustomerTypeChange"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="individual">Individual</option>
                                        <option value="business">Business</option>
                                    </select>
                                </div>

                                <template v-if="customerForm.customer_type === 'individual'">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                            <input
                                                type="text"
                                                v-model="customerForm.first_name"
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                            <input
                                                type="text"
                                                v-model="customerForm.last_name"
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            />
                                        </div>
                                    </div>
                                </template>

                                <template v-if="customerForm.customer_type === 'business'">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                                        <input
                                            type="text"
                                            v-model="customerForm.company_name"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </template>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input
                                        type="email"
                                        v-model="customerForm.email"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input
                                        type="text"
                                        v-model="customerForm.phone"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="closeCustomerModal"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="customerLoading"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ customerLoading ? 'Saving...' : 'Create' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Employee Creation Modal -->
        <div v-if="showEmployeeModal" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 99999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEmployeeModal"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Employee</h3>
                        
                        <form @submit.prevent="saveEmployeeInline">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID *</label>
                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            v-model="employeeForm.employee_id"
                                            required
                                            class="flex-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                        <button
                                            type="button"
                                            @click="generateEmployeeId"
                                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Generate
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                        <input
                                            type="text"
                                            v-model="employeeForm.first_name"
                                            required
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                        <input
                                            type="text"
                                            v-model="employeeForm.last_name"
                                            required
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input
                                        type="email"
                                        v-model="employeeForm.email"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input
                                        type="text"
                                        v-model="employeeForm.phone"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                                    <input
                                        type="text"
                                        v-model="employeeForm.position"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                    <input
                                        type="text"
                                        v-model="employeeForm.department"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type *</label>
                                    <select
                                        v-model="employeeForm.employment_type"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="full-time">Full Time</option>
                                        <option value="part-time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="intern">Intern</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="closeEmployeeModal"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="employeeLoading"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ employeeLoading ? 'Saving...' : 'Create' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline Vehicle Creation Modal -->
        <div v-if="showVehicleModal" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 99999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeVehicleModal"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Vehicle</h3>
                        
                        <form @submit.prevent="saveVehicleInline">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Number (License Plate) *</label>
                                    <input
                                        type="text"
                                        v-model="vehicleForm.vehicle_number"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Chassis Number (VIN) *</label>
                                    <input
                                        type="text"
                                        v-model="vehicleForm.chassis_number"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Make</label>
                                        <input
                                            type="text"
                                            v-model="vehicleForm.make"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                        <input
                                            type="text"
                                            v-model="vehicleForm.model"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                                        <input
                                            type="number"
                                            v-model="vehicleForm.year"
                                            min="1900"
                                            :max="new Date().getFullYear() + 1"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                                        <input
                                            type="text"
                                            v-model="vehicleForm.color"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        v-model="vehicleForm.notes"
                                        rows="3"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="closeVehicleModal"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="vehicleLoading"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ vehicleLoading ? 'Saving...' : 'Create' }}
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
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import { validateForm, hasErrors, clearError, validators } from '@/utils/validation';
import { formatCurrency as formatCurrencyValue, getSetting } from '@/utils/settings';

export default {
    name: 'Transactions',
    setup() {
        const transactions = ref({ data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 });
        const accounts = ref([]);
        const customers = ref([]);
        const tableExpanded = ref(true); // Table toggle state
        const employees = ref([]);
        const vehicles = ref([]);
        const showModal = ref(false);
        const showCustomerModal = ref(false);
        const showEmployeeModal = ref(false);
        const showVehicleModal = ref(false);
        const editingTransaction = ref(null);
        const loading = ref(false);
        const customerLoading = ref(false);
        const employeeLoading = ref(false);
        const vehicleLoading = ref(false);
        const formErrors = reactive({});
        const selectedAccount = ref(null);
        const projectedBalance = ref(null);

        const filters = reactive({
            search: '',
            account_id: '',
            transaction_type: '',
            date_from: '',
            date_to: '',
            page: 1,
        });

        const datePresets = [
            { label: 'Today', getDates: () => {
                const today = new Date();
                return { from: today.toISOString().split('T')[0], to: today.toISOString().split('T')[0] };
            }},
            { label: 'This Week', getDates: () => {
                const today = new Date();
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay());
                return { from: startOfWeek.toISOString().split('T')[0], to: today.toISOString().split('T')[0] };
            }},
            { label: 'This Month', getDates: () => {
                const today = new Date();
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                return { from: startOfMonth.toISOString().split('T')[0], to: today.toISOString().split('T')[0] };
            }},
            { label: 'This Year', getDates: () => {
                const today = new Date();
                const startOfYear = new Date(today.getFullYear(), 0, 1);
                return { from: startOfYear.toISOString().split('T')[0], to: today.toISOString().split('T')[0] };
            }},
            { label: 'Last 30 Days', getDates: () => {
                const today = new Date();
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(today.getDate() - 30);
                return { from: thirtyDaysAgo.toISOString().split('T')[0], to: today.toISOString().split('T')[0] };
            }},
            { label: 'All Time', getDates: () => {
                return { from: '', to: '' };
            }},
        ];

        const autoGenerateReference = ref(getSetting('auto_generate_reference', false));

        const form = reactive({
            date: new Date().toISOString().split('T')[0],
            account_id: '',
            customer_id: '',
            employee_id: '',
            vehicle_id: '',
            description: '',
            debit_amount: '',
            credit_amount: '',
            reference_number: '',
            transaction_type: '',
        });

        const getCustomerLabel = (customer) => {
            if (!customer) return '';
            const name = customer.company_name || customer.full_name || `${customer.first_name || ''} ${customer.last_name || ''}`.trim();
            return `${customer.customer_code || ''}${name ? ' - ' + name : ''}`.trim();
        };

        const getEmployeeLabel = (employee) => {
            if (!employee) return '';
            const name = employee.full_name || `${employee.first_name || ''} ${employee.last_name || ''}`.trim();
            return `${employee.employee_id || ''}${name ? ' - ' + name : ''}`.trim();
        };

        const getVehicleLabel = (vehicle) => {
            if (!vehicle) return '';
            const details = vehicle.display_name || vehicle.chassis_number || '';
            return `${vehicle.vehicle_number || ''}${details ? ' - ' + details : ''}`.trim();
        };

        const customerSearch = ref('');
        const employeeSearch = ref('');
        const vehicleSearch = ref('');
        const customerOptionsOpen = ref(false);
        const employeeOptionsOpen = ref(false);
        const vehicleOptionsOpen = ref(false);

        const filteredCustomers = computed(() => {
            const term = customerSearch.value.trim().toLowerCase();
            if (!term) return customers.value;
            return customers.value.filter((customer) => getCustomerLabel(customer).toLowerCase().includes(term));
        });

        const filteredEmployees = computed(() => {
            const term = employeeSearch.value.trim().toLowerCase();
            if (!term) return employees.value;
            return employees.value.filter((employee) => getEmployeeLabel(employee).toLowerCase().includes(term));
        });

        const filteredVehicles = computed(() => {
            const term = vehicleSearch.value.trim().toLowerCase();
            if (!term) return vehicles.value;
            return vehicles.value.filter((vehicle) => getVehicleLabel(vehicle).toLowerCase().includes(term));
        });

        const closeCustomerOptions = () => {
            setTimeout(() => {
                customerOptionsOpen.value = false;
            }, 150);
        };

        const closeEmployeeOptions = () => {
            setTimeout(() => {
                employeeOptionsOpen.value = false;
            }, 150);
        };

        const closeVehicleOptions = () => {
            setTimeout(() => {
                vehicleOptionsOpen.value = false;
            }, 150);
        };

        const syncCustomerSearch = () => {
            if (!form.customer_id) return;
            const customer = customers.value.find(item => item.id === form.customer_id);
            if (customer) {
                customerSearch.value = getCustomerLabel(customer);
            }
        };

        const syncEmployeeSearch = () => {
            if (!form.employee_id) return;
            const employee = employees.value.find(item => item.id === form.employee_id);
            if (employee) {
                employeeSearch.value = getEmployeeLabel(employee);
            }
        };

        const syncVehicleSearch = () => {
            if (!form.vehicle_id) return;
            const vehicle = vehicles.value.find(item => item.id === form.vehicle_id);
            if (vehicle) {
                vehicleSearch.value = getVehicleLabel(vehicle);
            }
        };

        const selectCustomer = (customer) => {
            form.customer_id = customer.id;
            customerSearch.value = getCustomerLabel(customer);
            customerOptionsOpen.value = false;
            onCustomerChange();
        };

        const selectEmployee = (employee) => {
            form.employee_id = employee.id;
            employeeSearch.value = getEmployeeLabel(employee);
            employeeOptionsOpen.value = false;
        };

        const selectVehicle = (vehicle) => {
            form.vehicle_id = vehicle.id;
            vehicleSearch.value = getVehicleLabel(vehicle);
            vehicleOptionsOpen.value = false;
        };

        const onCustomerSearchInput = () => {
            customerOptionsOpen.value = true;
            if (form.customer_id) {
                const customer = customers.value.find(item => item.id === form.customer_id);
                if (customer && customerSearch.value !== getCustomerLabel(customer)) {
                    form.customer_id = '';
                    onCustomerChange();
                }
            }
        };

        const onEmployeeSearchInput = () => {
            employeeOptionsOpen.value = true;
            if (form.employee_id) {
                const employee = employees.value.find(item => item.id === form.employee_id);
                if (employee && employeeSearch.value !== getEmployeeLabel(employee)) {
                    form.employee_id = '';
                }
            }
        };

        const onVehicleSearchInput = () => {
            vehicleOptionsOpen.value = true;
            if (form.vehicle_id) {
                const vehicle = vehicles.value.find(item => item.id === form.vehicle_id);
                if (vehicle && vehicleSearch.value !== getVehicleLabel(vehicle)) {
                    form.vehicle_id = '';
                }
            }
        };

        const formatCurrency = (amount) => formatCurrencyValue(amount);

        const formatDate = (date) => {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        };

        const handleAmountInput = (type) => {
            // Ensure only one amount is entered at a time
            if (type === 'debit' && form.debit_amount && parseFloat(form.debit_amount) > 0) {
                form.credit_amount = '';
            } else if (type === 'credit' && form.credit_amount && parseFloat(form.credit_amount) > 0) {
                form.debit_amount = '';
            }
        };

        const onAccountChange = async () => {
            if (!form.account_id) {
                selectedAccount.value = null;
                projectedBalance.value = null;
                return;
            }

            try {
                const response = await axios.get(`/api/accounts/${form.account_id}`);
                selectedAccount.value = response.data;
                calculateProjectedBalance();
            } catch (error) {
                console.error('Error loading account:', error);
                selectedAccount.value = null;
                projectedBalance.value = null;
            }
        };

        const calculateProjectedBalance = () => {
            if (!selectedAccount.value) {
                projectedBalance.value = null;
                return;
            }

            const currentBalance = parseFloat(selectedAccount.value.current_balance) || 0;
            const debitAmount = parseFloat(form.debit_amount) || 0;
            const creditAmount = parseFloat(form.credit_amount) || 0;
            const accountType = selectedAccount.value.account_type;

            let newBalance = currentBalance;

            // Calculate based on account type
            if (accountType === 'asset' || accountType === 'expense') {
                // Assets and expenses: debits increase, credits decrease
                newBalance = currentBalance + debitAmount - creditAmount;
            } else {
                // Liabilities, equity, revenue: credits increase, debits decrease
                newBalance = currentBalance + creditAmount - debitAmount;
            }

            projectedBalance.value = newBalance;
        };

        const getBalanceClass = (balance) => {
            if (balance === null || balance === undefined) return 'text-gray-500';
            const numBalance = parseFloat(balance);
            if (numBalance > 0) return 'text-green-600';
            if (numBalance < 0) return 'text-red-600';
            return 'text-gray-500';
        };

        const onFiltersChanged = () => {
            filters.page = 1;
            loadTransactions();
        };

        const applyDatePreset = (preset) => {
            const dates = preset.getDates();
            filters.date_from = dates.from;
            filters.date_to = dates.to;
            onFiltersChanged();
        };

        const clearFilters = () => {
            filters.search = '';
            filters.account_id = '';
            filters.transaction_type = '';
            filters.date_from = '';
            filters.date_to = '';
            onFiltersChanged();
        };

        const loadTransactions = async () => {
            try {
                const params = new URLSearchParams();
                if (filters.search) params.append('search', filters.search);
                if (filters.account_id) params.append('account_id', filters.account_id);
                if (filters.transaction_type) params.append('transaction_type', filters.transaction_type);
                if (filters.date_from) params.append('date_from', filters.date_from);
                if (filters.date_to) params.append('date_to', filters.date_to);
                params.append('per_page', 15);
                params.append('page', filters.page || 1);

                const response = await axios.get(`/api/transactions?${params}`);
                transactions.value = response.data;
            } catch (error) {
                console.error('Error loading transactions:', error);
                transactions.value = { data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 };
            }
        };

        const loadAccounts = async () => {
            try {
                const params = new URLSearchParams();
                params.append('is_active', 'true');
                params.append('per_page', 100);

                const response = await axios.get(`/api/accounts?${params}`);
                accounts.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading accounts:', error);
                accounts.value = [];
            }
        };

        const loadCustomers = async () => {
            try {
                const params = new URLSearchParams();
                params.append('is_active', 'true');
                params.append('per_page', 100);

                const response = await axios.get(`/api/customers?${params}`);
                customers.value = response.data.data || [];
                syncCustomerSearch();
            } catch (error) {
                console.error('Error loading customers:', error);
                customers.value = [];
            }
        };

        const loadVehicles = async (customerId) => {
            if (!customerId) {
                vehicles.value = [];
                return;
            }
            try {
                const response = await axios.get(`/api/vehicles/customer/${customerId}`);
                vehicles.value = response.data.data || [];
                syncVehicleSearch();
            } catch (error) {
                console.error('Error loading vehicles:', error);
                vehicles.value = [];
            }
        };

        const onCustomerChange = () => {
            form.vehicle_id = ''; // Reset vehicle when customer changes
            vehicleSearch.value = '';
            vehicleOptionsOpen.value = false;
            if (form.customer_id) {
                loadVehicles(form.customer_id);
            } else {
                vehicles.value = [];
            }
        };

        const loadEmployees = async () => {
            try {
                const params = new URLSearchParams();
                params.append('is_active', 'true');
                params.append('per_page', 100);

                const response = await axios.get(`/api/employees?${params}`);
                employees.value = response.data.data || [];
                syncEmployeeSearch();
            } catch (error) {
                console.error('Error loading employees:', error);
                employees.value = [];
            }
        };

        const openCreateModal = () => {
            editingTransaction.value = null;
            resetForm();
            loadAccounts();
            loadCustomers();
            loadEmployees();
            showModal.value = true;
        };

        const editTransaction = async (transaction) => {
            editingTransaction.value = transaction;
            form.date = transaction.date;
            form.account_id = transaction.account_id;
            form.customer_id = transaction.customer_id || '';
            form.employee_id = transaction.employee_id || '';
            form.vehicle_id = transaction.vehicle_id || '';
            form.description = transaction.description;
            form.debit_amount = transaction.debit_amount || '';
            form.credit_amount = transaction.credit_amount || '';
            form.reference_number = transaction.reference_number || '';
            form.transaction_type = transaction.transaction_type || '';
            loadAccounts();
            loadCustomers();
            loadEmployees();
            if (form.customer_id) {
                loadVehicles(form.customer_id);
            }
            
            // Load account details for balance preview
            if (transaction.account_id) {
                try {
                    const response = await axios.get(`/api/accounts/${transaction.account_id}`);
                    selectedAccount.value = response.data;
                    calculateProjectedBalance();
                } catch (error) {
                    console.error('Error loading account:', error);
                }
            }
            
            showModal.value = true;
        };

        const resetForm = () => {
            form.date = new Date().toISOString().split('T')[0];
            form.account_id = '';
            form.customer_id = '';
            form.employee_id = '';
            form.description = '';
            form.debit_amount = '';
            form.credit_amount = '';
            form.reference_number = '';
            form.transaction_type = '';
            selectedAccount.value = null;
            projectedBalance.value = null;
            customerSearch.value = '';
            employeeSearch.value = '';
            vehicleSearch.value = '';
            customerOptionsOpen.value = false;
            employeeOptionsOpen.value = false;
            vehicleOptionsOpen.value = false;
        };

        const closeModal = () => {
            showModal.value = false;
            editingTransaction.value = null;
            resetForm();
        };

        const validateTransactionForm = () => {
            const rules = {
                date: [
                    { validator: 'required', message: 'Date is required' },
                    { validator: 'date', message: 'Please enter a valid date' },
                ],
                account_id: [
                    { validator: 'required', message: 'Account is required' },
                ],
                description: [
                    { validator: 'required', message: 'Description is required' },
                    { validator: 'minLength', params: [3], message: 'Description must be at least 3 characters' },
                    { validator: 'maxLength', params: [1000], message: 'Description must be no more than 1000 characters' },
                ],
            };

            const errors = validateForm(form, rules);

            // Custom validation: at least one amount must be provided
            if (!form.debit_amount && !form.credit_amount) {
                errors.debit_amount = 'Either debit or credit amount is required';
                errors.credit_amount = 'Either debit or credit amount is required';
            }

            // Custom validation: both amounts cannot be provided
            if (form.debit_amount && form.credit_amount && parseFloat(form.debit_amount) > 0 && parseFloat(form.credit_amount) > 0) {
                errors.debit_amount = 'Cannot have both debit and credit amounts';
                errors.credit_amount = 'Cannot have both debit and credit amounts';
            }

            // Validate amounts are positive if provided
            if (form.debit_amount && parseFloat(form.debit_amount) < 0) {
                errors.debit_amount = 'Debit amount cannot be negative';
            }
            if (form.credit_amount && parseFloat(form.credit_amount) < 0) {
                errors.credit_amount = 'Credit amount cannot be negative';
            }

            Object.assign(formErrors, errors);
            return !hasErrors(errors);
        };

        const saveTransaction = async () => {
            // Clear previous errors
            Object.keys(formErrors).forEach(key => delete formErrors[key]);

            // Validate form
            if (!validateTransactionForm()) {
                loading.value = false;
                return;
            }

            loading.value = true;
            try {
                const formData = { ...form };

                // Convert empty strings to null or 0
                if (!formData.debit_amount) formData.debit_amount = 0;
                if (!formData.credit_amount) formData.credit_amount = 0;
                if (!formData.customer_id) formData.customer_id = null;
                if (!formData.employee_id) formData.employee_id = null;
                if (!formData.reference_number) formData.reference_number = null;
                if (!formData.transaction_type) formData.transaction_type = null;
                
                if (editingTransaction.value) {
                    await axios.put(`/api/transactions/${editingTransaction.value.id}`, formData);
                    alert('Transaction updated successfully');
                } else {
                    await axios.post('/api/transactions', formData);
                    alert('Transaction created successfully');
                }
                
                closeModal();
                loadTransactions();
                // Reload accounts to refresh balances
                loadAccounts();
            } catch (error) {
                console.error('Error saving transaction:', error);
                const message = error.response?.data?.message || 'Failed to save transaction';
                const errors = error.response?.data?.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat().join('\n');
                    alert(errorMessages);
                } else {
                    alert(message);
                }
            } finally {
                loading.value = false;
            }
        };

        const deleteTransaction = async (transaction) => {
            if (!confirm(`Are you sure you want to delete this transaction?`)) {
                return;
            }

            try {
                await axios.delete(`/api/transactions/${transaction.id}`);
                alert('Transaction deleted successfully');
                loadTransactions();
            } catch (error) {
                console.error('Error deleting transaction:', error);
                const message = error.response?.data?.message || 'Failed to delete transaction';
                alert(message);
            }
        };

        const changePage = (page) => {
            if (page >= 1 && page <= transactions.value.last_page) {
                filters.page = page;
                loadTransactions();
            }
        };

        // Customer Form
        const customerForm = reactive({
            customer_code: '',
            customer_type: 'individual',
            company_name: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            is_active: true,
        });

        // Employee Form
        const employeeForm = reactive({
            employee_id: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            position: '',
            department: '',
            employment_type: 'full-time',
            is_active: true,
        });

        // Vehicle Form
        const vehicleForm = reactive({
            customer_id: '',
            vehicle_number: '',
            chassis_number: '',
            make: '',
            model: '',
            year: '',
            color: '',
            notes: '',
            is_active: true,
        });

        const openCreateCustomerModal = () => {
            resetCustomerForm();
            showCustomerModal.value = true;
        };

        const closeCustomerModal = () => {
            showCustomerModal.value = false;
            resetCustomerForm();
        };

        const resetCustomerForm = () => {
            customerForm.customer_code = '';
            customerForm.customer_type = 'individual';
            customerForm.company_name = '';
            customerForm.first_name = '';
            customerForm.last_name = '';
            customerForm.email = '';
            customerForm.phone = '';
            customerForm.is_active = true;
        };

        const onCustomerTypeChange = () => {
            if (customerForm.customer_type === 'business') {
                customerForm.first_name = '';
                customerForm.last_name = '';
            } else {
                customerForm.company_name = '';
            }
        };

        const generateCustomerCode = async () => {
            try {
                const response = await axios.get('/api/customers/generate-code');
                customerForm.customer_code = response.data.customer_code;
            } catch (error) {
                console.error('Error generating customer code:', error);
                alert('Failed to generate customer code');
            }
        };

        const saveCustomerInline = async () => {
            customerLoading.value = true;
            try {
                const formData = { ...customerForm };
                const response = await axios.post('/api/customers', formData);
                alert('Customer created successfully');
                form.customer_id = response.data.customer.id;
                closeCustomerModal();
                loadCustomers(); // Refresh customer list
            } catch (error) {
                console.error('Error saving customer:', error);
                const message = error.response?.data?.message || 'Failed to save customer';
                const errors = error.response?.data?.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat().join('\n');
                    alert(errorMessages);
                } else {
                    alert(message);
                }
            } finally {
                customerLoading.value = false;
            }
        };

        const openCreateEmployeeModal = () => {
            resetEmployeeForm();
            showEmployeeModal.value = true;
        };

        const closeEmployeeModal = () => {
            showEmployeeModal.value = false;
            resetEmployeeForm();
        };

        const resetEmployeeForm = () => {
            employeeForm.employee_id = '';
            employeeForm.first_name = '';
            employeeForm.last_name = '';
            employeeForm.email = '';
            employeeForm.phone = '';
            employeeForm.position = '';
            employeeForm.department = '';
            employeeForm.employment_type = 'full-time';
            employeeForm.is_active = true;
        };

        const generateEmployeeId = async () => {
            try {
                const response = await axios.get('/api/employees/generate-id');
                employeeForm.employee_id = response.data.employee_id;
            } catch (error) {
                console.error('Error generating employee ID:', error);
                alert('Failed to generate employee ID');
            }
        };

        const saveEmployeeInline = async () => {
            employeeLoading.value = true;
            try {
                const formData = { ...employeeForm };
                const response = await axios.post('/api/employees', formData);
                alert('Employee created successfully');
                form.employee_id = response.data.employee.id;
                closeEmployeeModal();
                loadEmployees(); // Refresh employee list
            } catch (error) {
                console.error('Error saving employee:', error);
                const message = error.response?.data?.message || 'Failed to save employee';
                const errors = error.response?.data?.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat().join('\n');
                    alert(errorMessages);
                } else {
                    alert(message);
                }
            } finally {
                employeeLoading.value = false;
            }
        };

        // Vehicle Modal Functions
        const openCreateVehicleModal = () => {
            resetVehicleForm();
            // Set the customer_id from the form if a customer is selected
            vehicleForm.customer_id = form.customer_id || '';
            showVehicleModal.value = true;
        };

        const closeVehicleModal = () => {
            showVehicleModal.value = false;
            resetVehicleForm();
        };

        const resetVehicleForm = () => {
            vehicleForm.customer_id = '';
            vehicleForm.vehicle_number = '';
            vehicleForm.chassis_number = '';
            vehicleForm.make = '';
            vehicleForm.model = '';
            vehicleForm.year = '';
            vehicleForm.color = '';
            vehicleForm.notes = '';
            vehicleForm.is_active = true;
        };

        const saveVehicleInline = async () => {
            // Validate required fields
            if (!vehicleForm.vehicle_number || !vehicleForm.chassis_number) {
                alert('Vehicle number and chassis number are required');
                return;
            }

            // Ensure customer_id is set from the form
            if (!vehicleForm.customer_id && form.customer_id) {
                vehicleForm.customer_id = form.customer_id;
            }

            if (!vehicleForm.customer_id) {
                alert('Please select a customer first');
                return;
            }

            vehicleLoading.value = true;
            try {
                const response = await axios.post('/api/vehicles', vehicleForm);
                alert('Vehicle created successfully');
                form.vehicle_id = response.data.data.id;
                closeVehicleModal();
                // Reload vehicles for the selected customer
                if (form.customer_id) {
                    loadVehicles(form.customer_id);
                }
            } catch (error) {
                console.error('Error saving vehicle:', error);
                const message = error.response?.data?.message || 'Failed to save vehicle';
                const errors = error.response?.data?.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat().join('\n');
                    alert(errorMessages);
                } else {
                    alert(message);
                }
            } finally {
                vehicleLoading.value = false;
            }
        };

        onMounted(() => {
            loadTransactions();
            loadAccounts();
            loadCustomers();
            loadEmployees();
        });

        return {
            transactions,
            accounts,
            customers,
            employees,
            filters,
            datePresets,
            applyDatePreset,
            clearFilters,
            onFiltersChanged,
            form,
            autoGenerateReference,
            showModal,
            editingTransaction,
            loading,
            formatCurrency,
            formatDate,
            handleAmountInput,
            loadTransactions,
            openCreateModal,
            editTransaction,
            closeModal,
            saveTransaction,
            deleteTransaction,
            changePage,
            showCustomerModal,
            showEmployeeModal,
            customerForm,
            employeeForm,
            customerLoading,
            employeeLoading,
            openCreateCustomerModal,
            closeCustomerModal,
            onCustomerTypeChange,
            generateCustomerCode,
            saveCustomerInline,
            openCreateEmployeeModal,
            closeEmployeeModal,
            generateEmployeeId,
            saveEmployeeInline,
            vehicles,
            showVehicleModal,
            vehicleForm,
            vehicleLoading,
            openCreateVehicleModal,
            closeVehicleModal,
            saveVehicleInline,
            onCustomerChange,
            loadVehicles,
            customerSearch,
            employeeSearch,
            vehicleSearch,
            customerOptionsOpen,
            employeeOptionsOpen,
            vehicleOptionsOpen,
            filteredCustomers,
            filteredEmployees,
            filteredVehicles,
            getCustomerLabel,
            getEmployeeLabel,
            getVehicleLabel,
            selectCustomer,
            selectEmployee,
            selectVehicle,
            onCustomerSearchInput,
            onEmployeeSearchInput,
            onVehicleSearchInput,
            closeCustomerOptions,
            closeEmployeeOptions,
            closeVehicleOptions,
            formErrors,
            clearError,
            tableExpanded,
        };
    },
};
</script>
