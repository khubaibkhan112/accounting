<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Ledger</h1>
            <p class="mt-1 text-sm text-gray-500">View ledger entries for accounts matching your ledger format</p>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account *</label>
                    <select
                        v-model="filters.account_id"
                        @change="loadLedger"
                        required
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">Select Account</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.account_code }} - {{ account.account_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input
                        type="date"
                        v-model="filters.date_from"
                        @change="loadLedger"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input
                        type="date"
                        v-model="filters.date_to"
                        @change="loadLedger"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input
                        type="text"
                        v-model="filters.search"
                        @input="loadLedger"
                        placeholder="Search description or reference..."
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div class="flex items-end">
                    <button
                        @click="loadLedger"
                        :disabled="!filters.account_id || loading"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading">Loading...</span>
                        <span v-else>Load Ledger</span>
                    </button>
                </div>
            </div>
            <!-- Quick Date Range Buttons -->
            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="preset in datePresets"
                    :key="preset.label"
                    @click="applyDatePreset(preset)"
                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    {{ preset.label }}
                </button>
            </div>
        </div>

        <!-- Account Information & Summary -->
        <div v-if="ledgerData && ledgerData.account" class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-sm font-medium text-gray-500">Account</div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ ledgerData.account.account_code }} - {{ ledgerData.account.account_name }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ ledgerData.account.account_type }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Opening Balance</div>
                    <div class="text-lg font-semibold" :class="getBalanceClass(ledgerData.opening_balance)">
                        {{ formatCurrency(ledgerData.opening_balance) }}
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Closing Balance</div>
                    <div class="text-lg font-semibold" :class="getBalanceClass(ledgerData.closing_balance)">
                        {{ formatCurrency(ledgerData.closing_balance) }}
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Transactions</div>
                    <div class="text-lg font-semibold text-gray-900">{{ ledgerData.transactions_count }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        Total Debit: {{ formatCurrency(ledgerData.total_debit) }}<br>
                        Total Credit: {{ formatCurrency(ledgerData.total_credit) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading ledger data...</p>
        </div>

        <!-- Ledger Table -->
        <div v-else-if="ledgerData && ledgerData.ledger && ledgerData.ledger.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Opening Balance Row -->
                        <tr v-for="entry in ledgerData.ledger" :key="entry.id || 'opening'" 
                            :class="[getRowClass(entry.type), 'hover:bg-gray-50']">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(entry.date) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="font-medium">{{ entry.description }}</div>
                                <div v-if="entry.reference_number" class="text-xs text-gray-500">
                                    Ref: {{ entry.reference_number }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span v-if="entry.debit_amount > 0" class="font-medium text-gray-900">
                                    {{ formatCurrency(entry.debit_amount) }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span v-if="entry.credit_amount > 0" class="font-medium text-gray-900">
                                    {{ formatCurrency(entry.credit_amount) }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold"
                                :class="getBalanceClass(entry.balance)">
                                {{ formatCurrency(entry.balance) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td class="px-6 py-3 text-sm font-semibold text-gray-700" colspan="2">Total</td>
                            <td class="px-6 py-3 text-sm font-semibold text-gray-900 text-right">
                                {{ formatCurrency(ledgerData.total_debit) }}
                            </td>
                            <td class="px-6 py-3 text-sm font-semibold text-gray-900 text-right">
                                {{ formatCurrency(ledgerData.total_credit) }}
                            </td>
                            <td class="px-6 py-3 text-sm font-semibold text-gray-900 text-right"
                                :class="getBalanceClass(ledgerData.closing_balance)">
                                {{ formatCurrency(ledgerData.closing_balance) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="ledgerData && ledgerData.ledger && ledgerData.ledger.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No ledger entries</h3>
            <p class="mt-1 text-sm text-gray-500">No transactions found for this account in the selected date range.</p>
        </div>

        <!-- Initial State -->
        <div v-else class="bg-white shadow rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Select an account</h3>
            <p class="mt-1 text-sm text-gray-500">Please select an account to view its ledger entries.</p>
        </div>

        <!-- Action Buttons -->
        <div v-if="ledgerData && ledgerData.ledger && ledgerData.ledger.length > 0" class="mt-4 flex justify-end gap-3">
            <button
                @click="exportToCSV"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
            <button
                @click="printLedger"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Ledger
            </button>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'Ledger',
    setup() {
        const ledgerData = ref(null);
        const accounts = ref([]);
        const loading = ref(false);

        const filters = reactive({
            account_id: '',
            date_from: '',
            date_to: '',
            search: '',
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
            { label: 'All Time', getDates: () => {
                return { from: '', to: '' };
            }},
        ];

        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return '$0.00';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount);
        };

        const formatDate = (date) => {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        };

        const getBalanceClass = (balance) => {
            if (balance === null || balance === undefined) return 'text-gray-900';
            if (balance > 0) return 'text-green-600';
            if (balance < 0) return 'text-red-600';
            return 'text-gray-900';
        };

        const getRowClass = (type) => {
            if (type === 'opening') {
                return 'bg-blue-50';
            }
            return '';
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

        const loadLedger = async () => {
            if (!filters.account_id) {
                ledgerData.value = null;
                return;
            }

            loading.value = true;
            try {
                const params = new URLSearchParams();
                params.append('account_id', filters.account_id);
                if (filters.date_from) params.append('date_from', filters.date_from);
                if (filters.date_to) params.append('date_to', filters.date_to);
                if (filters.search) params.append('search', filters.search);

                const response = await axios.get(`/api/ledger?${params}`);
                ledgerData.value = response.data;
            } catch (error) {
                console.error('Error loading ledger:', error);
                const message = error.response?.data?.message || 'Failed to load ledger';
                alert(message);
                ledgerData.value = null;
            } finally {
                loading.value = false;
            }
        };

        const applyDatePreset = (preset) => {
            const dates = preset.getDates();
            filters.date_from = dates.from;
            filters.date_to = dates.to;
            if (filters.account_id) {
                loadLedger();
            }
        };

        const printLedger = () => {
            window.print();
        };

        const exportToCSV = () => {
            if (!ledgerData.value || !ledgerData.value.ledger || ledgerData.value.ledger.length === 0) {
                return;
            }

            const account = ledgerData.value.account;
            const lines = [];
            
            // Header
            lines.push(`Ledger Report - ${account.account_code} - ${account.account_name}`);
            lines.push(`Date Range: ${filters.date_from || 'All'} to ${filters.date_to || 'All'}`);
            lines.push('');
            
            // Column headers
            lines.push('Date,Description,Reference,Debit,Credit,Balance');
            
            // Data rows
            ledgerData.value.ledger.forEach(entry => {
                const date = formatDate(entry.date);
                const description = `"${entry.description.replace(/"/g, '""')}"`;
                const reference = entry.reference_number || '';
                const debit = entry.debit_amount > 0 ? entry.debit_amount.toFixed(2) : '';
                const credit = entry.credit_amount > 0 ? entry.credit_amount.toFixed(2) : '';
                const balance = entry.balance.toFixed(2);
                lines.push(`${date},${description},"${reference}",${debit},${credit},${balance}`);
            });
            
            // Footer
            lines.push('');
            lines.push(`Total,,"",${ledgerData.value.total_debit.toFixed(2)},${ledgerData.value.total_credit.toFixed(2)},${ledgerData.value.closing_balance.toFixed(2)}`);
            
            // Create and download
            const csvContent = lines.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `ledger_${account.account_code}_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        onMounted(() => {
            loadAccounts();
        });

        return {
            ledgerData,
            accounts,
            filters,
            loading,
            datePresets,
            formatCurrency,
            formatDate,
            getBalanceClass,
            getRowClass,
            loadLedger,
            applyDatePreset,
            printLedger,
            exportToCSV,
        };
    },
};
</script>

<style scoped>
@media print {
    .mb-6,
    .mb-4,
    .mt-4,
    button {
        display: none;
    }
    
    .bg-white {
        box-shadow: none;
    }
}
</style>
