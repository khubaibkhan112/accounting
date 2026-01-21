<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Journal Entries</h1>
                <p class="mt-1 text-sm text-gray-500">Manage journal entries with double-entry bookkeeping</p>
            </div>
            <button 
                type="button"
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Entry
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <input
                        type="text"
                        v-model="filters.search"
                        @input="loadJournalEntries"
                        placeholder="Search description or reference..."
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <select
                        v-model="filters.account_id"
                        @change="loadJournalEntries"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">All Accounts</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.account_code }} - {{ account.account_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <input
                        type="date"
                        v-model="filters.date_from"
                        @change="loadJournalEntries"
                        placeholder="From Date"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <input
                        type="date"
                        v-model="filters.date_to"
                        @change="loadJournalEntries"
                        placeholder="To Date"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <button
                        @click="loadJournalEntries"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Journal Entries Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Credit</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="entry in journalEntries.data" :key="entry.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(entry.entry_date) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ entry.reference_number || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <router-link
                                    v-if="entry.transaction_id"
                                    :to="`/admin/transactions?transaction_id=${entry.transaction_id}`"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    #{{ entry.transaction_id }}
                                </router-link>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ entry.description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ formatCurrency(entry.total_debit) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ formatCurrency(entry.total_credit) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span 
                                    v-if="entry.is_balanced"
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                                >
                                    Balanced
                                </span>
                                <span 
                                    v-else
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
                                >
                                    Unbalanced
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ entry.creator?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="viewEntry(entry)" 
                                        class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                                        title="View"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="editEntry(entry)" 
                                        class="p-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded transition-colors"
                                        title="Edit"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="deleteEntry(entry)" 
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
                        <tr v-if="journalEntries.data && journalEntries.data.length === 0">
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No journal entries found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="journalEntries.data && journalEntries.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ journalEntries.from }} to {{ journalEntries.to }} of {{ journalEntries.total }} results
                    </div>
                    <div class="flex space-x-2">
                        <button
                            @click="changePage(journalEntries.current_page - 1)"
                            :disabled="journalEntries.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="changePage(journalEntries.current_page + 1)"
                            :disabled="journalEntries.current_page === journalEntries.last_page"
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
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingEntry ? 'Edit Journal Entry' : 'Create New Journal Entry' }}</h3>
                        
                        <form @submit.prevent="saveEntry">
                            <!-- Header Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Entry Date *</label>
                                    <input
                                        type="date"
                                        v-model="form.entry_date"
                                        required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                                    <input
                                        type="text"
                                        v-model="form.reference_number"
                                        :disabled="autoGenerateReference && !editingEntry"
                                        :placeholder="autoGenerateReference && !editingEntry ? 'Auto-generated' : ''"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    />
                                    <p v-if="autoGenerateReference && !editingEntry" class="mt-1 text-xs text-gray-500">Reference number will be generated automatically.</p>
                                </div>
                                <div class="flex items-end">
                                    <div class="w-full">
                                        <div class="text-sm font-medium text-gray-700 mb-1">Balance Status</div>
                                        <div 
                                            class="px-3 py-2 rounded-md text-sm font-semibold"
                                            :class="isBalanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        >
                                            {{ isBalanced ? 'Balanced' : 'Unbalanced' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                                    <select
                                        v-model="form.customer_id"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="">None</option>
                                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                            {{ customer.customer_code }} - {{ customer.display_name || customer.company_name || customer.first_name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                                    <select
                                        v-model="form.employee_id"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="">None</option>
                                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                            {{ employee.employee_id }} - {{ employee.full_name || `${employee.first_name} ${employee.last_name}` }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction No (Optional)</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="text"
                                            v-model="transactionNoInput"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            placeholder="Enter transaction no (e.g. TRN000001)"
                                        />
                                        <button
                                            type="button"
                                            @click="loadTransactionByNumber"
                                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md border border-gray-300 hover:bg-gray-200"
                                        >
                                            Load
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Loads transaction details and fills one line item.
                                    </p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                                <textarea
                                    v-model="form.description"
                                    @input="clearError(formErrors, 'description')"
                                    required
                                    rows="2"
                                    :class="[
                                        'block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                        formErrors.description ? 'border-red-300' : 'border-gray-300'
                                    ]"
                                ></textarea>
                                <p v-if="formErrors.description" class="mt-1 text-sm text-red-600">{{ formErrors.description }}</p>
                            </div>

                            <!-- Line Items -->
                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Line Items * (Minimum 2)</label>
                                        <p class="text-xs text-gray-500 mt-1">Press Enter in account field to add new item</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addItem"
                                        class="inline-flex items-center px-3 py-1.5 text-sm text-blue-600 hover:text-blue-800 font-medium border border-blue-300 rounded-md hover:bg-blue-50 transition-colors"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Item
                                    </button>
                                </div>

                                <div class="overflow-x-auto">
                                    <p v-if="formErrors.items" class="mb-2 text-sm text-red-600">{{ formErrors.items }}</p>
                                    <p v-if="formErrors.balance" class="mb-2 text-sm text-red-600 font-semibold">{{ formErrors.balance }}</p>
                                    <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account *</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-16">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-3 py-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs text-gray-500 font-medium w-6">{{ index + 1 }}.</span>
                                                        <div class="flex-1">
                                                            <select
                                                                v-model="item.account_id"
                                                                :data-item-index="index"
                                                                @change="clearError(formErrors, `items.${index}.account_id`)"
                                                                required
                                                                :class="[
                                                                    'block w-full px-2 py-1 text-sm border rounded-md focus:ring-blue-500 focus:border-blue-500',
                                                                    formErrors[`items.${index}.account_id`] ? 'border-red-300' : 'border-gray-300'
                                                                ]"
                                                            >
                                                                <option value="">Select Account</option>
                                                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                                                    {{ account.account_code }} - {{ account.account_name }}
                                                                </option>
                                                            </select>
                                                            <p v-if="formErrors[`items.${index}.account_id`]" class="mt-1 text-xs text-red-600">{{ formErrors[`items.${index}.account_id`] }}</p>
                                                            <p v-if="formErrors[`items.${index}.amount`]" class="mt-1 text-xs text-red-600">{{ formErrors[`items.${index}.amount`] }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        type="number"
                                                        v-model.number="item.debit_amount"
                                                        step="0.01"
                                                        min="0"
                                                        @input="handleItemAmountChange(index, 'debit'); clearError(formErrors, `items.${index}.amount`)"
                                                        :class="[
                                                            'block w-full px-2 py-1 text-sm text-right border rounded-md focus:ring-blue-500 focus:border-blue-500',
                                                            formErrors[`items.${index}.amount`] ? 'border-red-300' : 'border-gray-300'
                                                        ]"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        type="number"
                                                        v-model.number="item.credit_amount"
                                                        step="0.01"
                                                        min="0"
                                                        @input="handleItemAmountChange(index, 'credit'); clearError(formErrors, `items.${index}.amount`)"
                                                        :class="[
                                                            'block w-full px-2 py-1 text-sm text-right border rounded-md focus:ring-blue-500 focus:border-blue-500',
                                                            formErrors[`items.${index}.amount`] ? 'border-red-300' : 'border-gray-300'
                                                        ]"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        type="text"
                                                        v-model="item.description"
                                                        class="block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                    />
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button
                                                            type="button"
                                                            @click="duplicateItem(index)"
                                                            class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50"
                                                            title="Duplicate this line"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                            </svg>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="removeItem(index)"
                                                            :disabled="form.items.length <= 2"
                                                            class="text-red-600 hover:text-red-800 disabled:text-gray-400 disabled:cursor-not-allowed p-1 rounded hover:bg-red-50"
                                                            :title="form.items.length <= 2 ? 'At least 2 items required' : 'Remove this line'"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                                            <tr>
                                                <td class="px-3 py-3 text-sm font-semibold text-gray-900">Total</td>
                                                <td class="px-3 py-3 text-sm font-bold text-right text-gray-900">
                                                    {{ formatCurrency(totalDebits) }}
                                                </td>
                                                <td class="px-3 py-3 text-sm font-bold text-right text-gray-900">
                                                    {{ formatCurrency(totalCredits) }}
                                                </td>
                                                <td class="px-3 py-3" colspan="2">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-sm font-medium"
                                                            :class="isBalanced ? 'text-green-600' : 'text-red-600'"
                                                        >
                                                            <span v-if="isBalanced" class="flex items-center">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Balanced
                                                            </span>
                                                            <span v-else class="flex items-center">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                Difference: {{ formatCurrency(Math.abs(totalDebits - totalCredits)) }}
                                                            </span>
                                                        </div>
                                                        <button
                                                            v-if="!isBalanced && Math.abs(totalDebits - totalCredits) > 0.01"
                                                            type="button"
                                                            @click="autoBalance"
                                                            class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium"
                                                            title="Auto-balance: Add the difference to the last item"
                                                        >
                                                            Auto-Balance
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Balance Status Alert -->
                            <div v-if="!isBalanced && totalDebits > 0 && totalCredits > 0" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-red-800">Journal entry is not balanced</p>
                                        <p class="text-xs text-red-600 mt-1">
                                            Total Debits: {{ formatCurrency(totalDebits) }} | 
                                            Total Credits: {{ formatCurrency(totalCredits) }} | 
                                            Difference: {{ formatCurrency(Math.abs(totalDebits - totalCredits)) }}
                                        </p>
                                        <p class="text-xs text-red-600 mt-1">Please ensure total debits equal total credits before saving.</p>
                                    </div>
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
                                    :disabled="loading || !isBalanced || form.items.length < 2"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ loading ? 'Saving...' : (editingEntry ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div v-if="showViewModal" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeViewModal"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full relative" @click.stop>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Journal Entry Details</h3>
                        
                        <div v-if="viewingEntry">
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Entry Date</div>
                                    <div class="text-sm text-gray-900">{{ formatDate(viewingEntry.entry_date) }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Reference Number</div>
                                    <div class="text-sm text-gray-900">{{ viewingEntry.reference_number || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Transaction ID</div>
                                    <div class="text-sm text-gray-900">{{ viewingEntry.transaction_id || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Customer</div>
                                    <div class="text-sm text-gray-900">
                                        {{ viewingEntry.customer ? `${viewingEntry.customer.customer_code} - ${viewingEntry.customer.display_name || viewingEntry.customer.company_name || viewingEntry.customer.first_name}` : '-' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Employee</div>
                                    <div class="text-sm text-gray-900">
                                        {{ viewingEntry.employee ? `${viewingEntry.employee.employee_id} - ${viewingEntry.employee.full_name || `${viewingEntry.employee.first_name} ${viewingEntry.employee.last_name}`}` : '-' }}
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-sm font-medium text-gray-500">Description</div>
                                    <div class="text-sm text-gray-900">{{ viewingEntry.description }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Created By</div>
                                    <div class="text-sm text-gray-900">{{ viewingEntry.creator?.name || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Status</div>
                                    <span 
                                        v-if="viewingEntry.is_balanced"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                                    >
                                        Balanced
                                    </span>
                                    <span 
                                        v-else
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
                                    >
                                        Unbalanced
                                    </span>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(item, index) in viewingEntry.items" :key="index">
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                {{ item.account?.account_code }} - {{ item.account?.account_name }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900">
                                                <span v-if="item.debit_amount > 0">{{ formatCurrency(item.debit_amount) }}</span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900">
                                                <span v-if="item.credit_amount > 0">{{ formatCurrency(item.credit_amount) }}</span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ item.description || '-' }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-semibold text-gray-700">Total</td>
                                            <td class="px-4 py-2 text-sm font-semibold text-right text-gray-900">
                                                {{ formatCurrency(viewingEntry.total_debit) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm font-semibold text-right text-gray-900">
                                                {{ formatCurrency(viewingEntry.total_credit) }}
                                            </td>
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button
                                type="button"
                                @click="closeViewModal"
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { validateForm, hasErrors, clearError, validators } from '@/utils/validation';
import { formatCurrency as formatCurrencyValue, getSetting } from '@/utils/settings';

export default {
    name: 'JournalEntries',
    setup() {
        const journalEntries = ref({ data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 });
        const accounts = ref([]);
        const customers = ref([]);
        const employees = ref([]);
        const tableExpanded = ref(true); // Table toggle state
        const showModal = ref(false);
        const showViewModal = ref(false);
        const editingEntry = ref(null);
        const viewingEntry = ref(null);
        const loading = ref(false);
        const formErrors = reactive({});

        const filters = reactive({
            search: '',
            account_id: '',
            date_from: '',
            date_to: '',
        });

        const autoGenerateReference = ref(getSetting('auto_generate_reference', false));

        const form = reactive({
            entry_date: new Date().toISOString().split('T')[0],
            description: '',
            reference_number: '',
            customer_id: '',
            employee_id: '',
            transaction_id: '',
            items: [
                { account_id: '', debit_amount: 0, credit_amount: 0, description: '' },
                { account_id: '', debit_amount: 0, credit_amount: 0, description: '' },
            ],
        });
        const transactionNoInput = ref('');

        const formatCurrency = (amount) => formatCurrencyValue(amount);

        const formatDate = (date) => {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        };

        const totalDebits = computed(() => {
            return form.items.reduce((sum, item) => sum + (parseFloat(item.debit_amount) || 0), 0);
        });

        const totalCredits = computed(() => {
            return form.items.reduce((sum, item) => sum + (parseFloat(item.credit_amount) || 0), 0);
        });

        const isBalanced = computed(() => {
            return Math.abs(totalDebits.value - totalCredits.value) < 0.01;
        });

        const handleItemAmountChange = (index, type) => {
            const item = form.items[index];
            // Ensure only one amount is entered at a time
            if (type === 'debit' && item.debit_amount > 0) {
                item.credit_amount = 0;
            } else if (type === 'credit' && item.credit_amount > 0) {
                item.debit_amount = 0;
            }
        };

        const loadTransactionByNumber = async () => {
            const transactionNo = transactionNoInput.value?.trim();
            if (!transactionNo) return;

            try {
                const response = await axios.get('/api/transactions', {
                    params: { transaction_no: transactionNo, per_page: 1 },
                });

                const transaction = response.data?.data?.[0];
                if (!transaction) {
                    alert('Transaction not found.');
                    return;
                }

                form.transaction_id = transaction.id;
                form.entry_date = transaction.date || form.entry_date;
                form.description = transaction.description || form.description;
                form.reference_number = transaction.reference_number || transaction.transaction_no || form.reference_number;
                form.customer_id = transaction.customer_id || form.customer_id;
                form.employee_id = transaction.employee_id || form.employee_id;

                form.items = [
                    {
                        account_id: transaction.account_id,
                        debit_amount: Number(transaction.debit_amount) || 0,
                        credit_amount: Number(transaction.credit_amount) || 0,
                        description: transaction.description || '',
                    },
                    { account_id: '', debit_amount: 0, credit_amount: 0, description: '' },
                ];
            } catch (error) {
                console.error('Failed to load transaction:', error);
                alert(error.response?.data?.message || 'Failed to load transaction.');
            }
        };

        const addItem = () => {
            form.items.push({ account_id: '', debit_amount: 0, credit_amount: 0, description: '' });
            // Clear any item-related errors when adding new item
            Object.keys(formErrors).forEach(key => {
                if (key.startsWith('items.')) {
                    delete formErrors[key];
                }
            });
            // Auto-focus on the new item's account field after Vue updates the DOM
            setTimeout(() => {
                const newIndex = form.items.length - 1;
                // Use a more reliable selector
                const selects = document.querySelectorAll('select[data-item-index]');
                if (selects[newIndex]) {
                    selects[newIndex].focus();
                }
            }, 150);
        };

        const removeItem = (index) => {
            if (form.items.length > 2) {
                // Clear errors for this item before removing
                Object.keys(formErrors).forEach(key => {
                    if (key.startsWith(`items.${index}.`)) {
                        delete formErrors[key];
                    }
                });
                form.items.splice(index, 1);
            }
        };

        const duplicateItem = (index) => {
            const itemToDuplicate = { ...form.items[index] };
            // Clear amounts when duplicating to avoid confusion
            itemToDuplicate.debit_amount = 0;
            itemToDuplicate.credit_amount = 0;
            form.items.splice(index + 1, 0, itemToDuplicate);
        };

        const autoBalance = () => {
            if (form.items.length === 0) return;
            
            const difference = totalDebits.value - totalCredits.value;
            const lastItem = form.items[form.items.length - 1];
            
            if (Math.abs(difference) < 0.01) return;
            
            // Add the difference to the last item
            if (difference > 0) {
                // Debits exceed credits, add to credits
                lastItem.credit_amount = (parseFloat(lastItem.credit_amount) || 0) + difference;
                lastItem.debit_amount = 0;
            } else {
                // Credits exceed debits, add to debits
                lastItem.debit_amount = (parseFloat(lastItem.debit_amount) || 0) + Math.abs(difference);
                lastItem.credit_amount = 0;
            }
            
            // Clear balance error
            if (formErrors.balance) {
                delete formErrors.balance;
            }
        };

        const loadJournalEntries = async () => {
            try {
                const params = new URLSearchParams();
                if (filters.search) params.append('search', filters.search);
                if (filters.account_id) params.append('account_id', filters.account_id);
                if (filters.date_from) params.append('date_from', filters.date_from);
                if (filters.date_to) params.append('date_to', filters.date_to);
                params.append('per_page', 15);

                const response = await axios.get(`/api/journal-entries?${params}`);
                journalEntries.value = response.data;
            } catch (error) {
                console.error('Error loading journal entries:', error);
                journalEntries.value = { data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 };
            }
        };

        const loadAccounts = async () => {
            try {
                const params = new URLSearchParams();
                params.append('is_active', 'true');
                params.append('per_page', 1000);

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
                params.append('per_page', 1000);

                const response = await axios.get(`/api/customers?${params}`);
                customers.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading customers:', error);
                customers.value = [];
            }
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

        const openCreateModal = () => {
            editingEntry.value = null;
            resetForm();
            loadAccounts();
            loadCustomers();
            loadEmployees();
            showModal.value = true;
        };

        const editEntry = async (entry) => {
            try {
                const response = await axios.get(`/api/journal-entries/${entry.id}`);
                const entryData = response.data;
                
                editingEntry.value = entryData;
                form.entry_date = entryData.entry_date;
                form.description = entryData.description;
                form.reference_number = entryData.reference_number || '';
                form.customer_id = entryData.customer_id || '';
                form.employee_id = entryData.employee_id || '';
                form.transaction_id = entryData.transaction_id || '';
                transactionNoInput.value = entryData.transaction?.transaction_no || '';
                form.items = entryData.items.map(item => ({
                    account_id: item.account_id,
                    debit_amount: parseFloat(item.debit_amount) || 0,
                    credit_amount: parseFloat(item.credit_amount) || 0,
                    description: item.description || '',
                }));

                loadAccounts();
                loadCustomers();
                loadEmployees();
                showModal.value = true;
            } catch (error) {
                console.error('Error loading journal entry:', error);
                alert('Failed to load journal entry');
            }
        };

        const viewEntry = async (entry) => {
            try {
                const response = await axios.get(`/api/journal-entries/${entry.id}`);
                viewingEntry.value = response.data;
                showViewModal.value = true;
            } catch (error) {
                console.error('Error loading journal entry:', error);
                alert('Failed to load journal entry');
            }
        };

        const resetForm = () => {
            form.entry_date = new Date().toISOString().split('T')[0];
            form.description = '';
            form.reference_number = '';
            form.customer_id = '';
            form.employee_id = '';
            form.transaction_id = '';
            transactionNoInput.value = '';
            form.items = [
                { account_id: '', debit_amount: 0, credit_amount: 0, description: '' },
                { account_id: '', debit_amount: 0, credit_amount: 0, description: '' },
            ];
        };

        const closeModal = () => {
            showModal.value = false;
            editingEntry.value = null;
            resetForm();
        };

        const closeViewModal = () => {
            showViewModal.value = false;
            viewingEntry.value = null;
        };

        const validateJournalEntryForm = () => {
            // Clear previous errors
            Object.keys(formErrors).forEach(key => delete formErrors[key]);

            const rules = {
                entry_date: [
                    { validator: 'required', message: 'Entry date is required' },
                    { validator: 'date', message: 'Please enter a valid date' },
                ],
                description: [
                    { validator: 'required', message: 'Description is required' },
                    { validator: 'minLength', params: [3], message: 'Description must be at least 3 characters' },
                    { validator: 'maxLength', params: [1000], message: 'Description must be no more than 1000 characters' },
                ],
            };

            const errors = validateForm(form, rules);

            // Validate items
            if (form.items.length < 2) {
                errors.items = 'At least 2 line items are required';
            }

            // Validate each item
            form.items.forEach((item, index) => {
                if (!item.account_id) {
                    errors[`items.${index}.account_id`] = 'Account is required';
                }
                const debit = parseFloat(item.debit_amount) || 0;
                const credit = parseFloat(item.credit_amount) || 0;
                if (debit === 0 && credit === 0) {
                    errors[`items.${index}.amount`] = 'Either debit or credit amount is required';
                }
                if (debit > 0 && credit > 0) {
                    errors[`items.${index}.amount`] = 'Cannot have both debit and credit amounts';
                }
                if (debit < 0 || credit < 0) {
                    errors[`items.${index}.amount`] = 'Amounts cannot be negative';
                }

                // Check for duplicate accounts (warning, not error)
                const duplicateAccounts = form.items.filter((it, idx) => 
                    it.account_id && it.account_id === item.account_id && idx !== index
                );
                if (duplicateAccounts.length > 0) {
                    // This is just a warning, not blocking
                    console.warn(`Account ${item.account_id} is used multiple times in this entry`);
                }
            });

            // Validate balance
            if (!isBalanced.value) {
                const difference = Math.abs(totalDebits.value - totalCredits.value);
                errors.balance = `Journal entry is not balanced. Total debits: ${formatCurrency(totalDebits.value)}, Total credits: ${formatCurrency(totalCredits.value)}. Difference: ${formatCurrency(difference)}`;
            }

            Object.assign(formErrors, errors);
            return !hasErrors(errors);
        };

        const saveEntry = async () => {
            // Validate form
            if (!validateJournalEntryForm()) {
                loading.value = false;
                return;
            }

            loading.value = true;
            try {

                const formData = {
                    entry_date: form.entry_date,
                    description: form.description,
                    reference_number: form.reference_number || null,
                    customer_id: form.customer_id || null,
                    employee_id: form.employee_id || null,
                    transaction_id: form.transaction_id || null,
                    items: form.items.map(item => ({
                        account_id: item.account_id,
                        debit_amount: parseFloat(item.debit_amount) || 0,
                        credit_amount: parseFloat(item.credit_amount) || 0,
                        description: item.description || null,
                    })),
                };
                
                if (editingEntry.value) {
                    await axios.put(`/api/journal-entries/${editingEntry.value.id}`, formData);
                    alert('Journal entry updated successfully');
                } else {
                    await axios.post('/api/journal-entries', formData);
                    alert('Journal entry created successfully');
                }
                
                closeModal();
                loadJournalEntries();
            } catch (error) {
                console.error('Error saving journal entry:', error);
                const message = error.response?.data?.message || 'Failed to save journal entry';
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

        const deleteEntry = async (entry) => {
            if (!confirm(`Are you sure you want to delete this journal entry?`)) {
                return;
            }

            try {
                await axios.delete(`/api/journal-entries/${entry.id}`);
                alert('Journal entry deleted successfully');
                loadJournalEntries();
            } catch (error) {
                console.error('Error deleting journal entry:', error);
                const message = error.response?.data?.message || 'Failed to delete journal entry';
                alert(message);
            }
        };

        const changePage = (page) => {
            if (page >= 1 && page <= journalEntries.value.last_page) {
                filters.page = page;
                loadJournalEntries();
            }
        };

        onMounted(() => {
            loadJournalEntries();
            loadAccounts();
        });

        return {
            journalEntries,
            accounts,
            customers,
            employees,
            filters,
            form,
            autoGenerateReference,
            showModal,
            showViewModal,
            editingEntry,
            viewingEntry,
            loading,
            totalDebits,
            totalCredits,
            isBalanced,
            autoBalance,
            formatCurrency,
            formatDate,
            tableExpanded,
            handleItemAmountChange,
            loadTransactionByNumber,
            transactionNoInput,
            addItem,
            removeItem,
            loadJournalEntries,
            openCreateModal,
            editEntry,
            viewEntry,
            closeModal,
            closeViewModal,
            saveEntry,
            deleteEntry,
            changePage,
            formErrors,
            clearError,
            duplicateItem,
        };
    },
};
</script>
