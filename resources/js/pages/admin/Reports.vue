<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Financial Reports</h1>
            <p class="mt-1 text-sm text-gray-500">Generate and view financial reports</p>
        </div>

        <!-- Report Type Tabs -->
        <div class="mb-4 bg-white shadow rounded-lg">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-4" aria-label="Tabs">
                    <button
                        @click="activeTab = 'trial-balance'"
                        :class="[
                            activeTab === 'trial-balance'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        ]"
                    >
                        Trial Balance
                    </button>
                    <button
                        @click="activeTab = 'balance-sheet'"
                        :class="[
                            activeTab === 'balance-sheet'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        ]"
                    >
                        Balance Sheet
                    </button>
                    <button
                        @click="activeTab = 'income-statement'"
                        :class="[
                            activeTab === 'income-statement'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        ]"
                    >
                        Income Statement (P&L)
                    </button>
                </nav>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div v-if="activeTab === 'trial-balance' || activeTab === 'balance-sheet'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input
                        type="date"
                        v-model="filters.date_to"
                        @change="loadReport"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div v-if="activeTab === 'trial-balance'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From (Optional)</label>
                    <input
                        type="date"
                        v-model="filters.date_from"
                        @change="loadReport"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div v-if="activeTab === 'income-statement'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input
                        type="date"
                        v-model="filters.date_from"
                        @change="loadReport"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div v-if="activeTab === 'income-statement'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input
                        type="date"
                        v-model="filters.date_to"
                        @change="loadReport"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>
                <div class="flex items-end">
                    <button
                        @click="loadReport"
                        :disabled="loading"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ loading ? 'Loading...' : 'Generate Report' }}
                    </button>
                </div>
                <div class="flex items-end">
                    <button
                        v-if="reportData"
                        @click="printReport"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Print Report
                    </button>
                </div>
            </div>
        </div>

        <div class="printable-report">
            <!-- Trial Balance Report -->
            <div v-if="activeTab === 'trial-balance' && reportData && reportData.report_type === 'trial_balance'" class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Trial Balance</h2>
                    <p v-if="getCompanyName()" class="text-sm text-gray-600 mt-1">{{ getCompanyName() }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        As of {{ formatDate(reportData.date_to) }}
                        <span v-if="reportData.date_from"> (From {{ formatDate(reportData.date_from) }})</span>
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit Balance</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Balance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="account in reportData.accounts" :key="account.account_code" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ account.account_code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ account.account_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                    <span v-if="account.debit_balance > 0" class="font-medium text-gray-900">
                                        {{ formatCurrency(account.debit_balance) }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                    <span v-if="account.credit_balance > 0" class="font-medium text-gray-900">
                                        {{ formatCurrency(account.credit_balance) }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                            </tr>
                            <tr v-if="!reportData.accounts || reportData.accounts.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No accounts found</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700" colspan="2">Total</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900 text-right">
                                    {{ formatCurrency(reportData.total_debit) }}
                                </td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900 text-right">
                                    {{ formatCurrency(reportData.total_credit) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">
                            Generated at {{ formatDateTime(reportData.generated_at) }}
                        </span>
                        <span 
                            class="px-3 py-1 rounded-full text-xs font-semibold"
                            :class="reportData.is_balanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ reportData.is_balanced ? 'Balanced' : 'Unbalanced' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Balance Sheet Report -->
            <div v-if="activeTab === 'balance-sheet' && reportData && reportData.report_type === 'balance_sheet'" class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Balance Sheet</h2>
                    <p v-if="getCompanyName()" class="text-sm text-gray-600 mt-1">{{ getCompanyName() }}</p>
                    <p class="text-sm text-gray-500 mt-1">As of {{ formatDate(reportData.date) }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                    <!-- Assets -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Assets</h3>
                        <div class="space-y-2">
                            <div v-for="account in reportData.assets.accounts" :key="account.account_code" class="flex justify-between items-center py-2 border-b border-gray-100">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ account.account_name }}</div>
                                    <div class="text-xs text-gray-500">{{ account.account_code }}</div>
                                </div>
                                <div class="text-sm font-medium text-gray-900">{{ formatCurrency(account.balance) }}</div>
                            </div>
                            <div class="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300">
                                <div class="text-lg font-bold text-gray-900">Total Assets</div>
                                <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.total_assets) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Liabilities and Equity -->
                    <div>
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Liabilities</h3>
                            <div class="space-y-2">
                                <div v-for="account in reportData.liabilities.accounts" :key="account.account_code" class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ account.account_name }}</div>
                                        <div class="text-xs text-gray-500">{{ account.account_code }}</div>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ formatCurrency(account.balance) }}</div>
                                </div>
                                <div class="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300">
                                    <div class="text-lg font-bold text-gray-900">Total Liabilities</div>
                                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.liabilities.total) }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Equity</h3>
                            <div class="space-y-2">
                                <div v-for="account in reportData.equity.accounts" :key="account.account_code" class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ account.account_name }}</div>
                                        <div class="text-xs text-gray-500">{{ account.account_code }}</div>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ formatCurrency(account.balance) }}</div>
                                </div>
                                <div v-if="reportData.equity.retained_earnings != 0" class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">Retained Earnings</div>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ formatCurrency(reportData.equity.retained_earnings) }}</div>
                                </div>
                                <div class="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300">
                                    <div class="text-lg font-bold text-gray-900">Total Equity</div>
                                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.equity.total) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300">
                            <div class="text-lg font-bold text-gray-900">Total Liabilities & Equity</div>
                            <div class="text-lg font-bold text-gray-900">{{ formatCurrency(reportData.total_liabilities_and_equity) }}</div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">
                            Generated at {{ formatDateTime(reportData.generated_at) }}
                        </span>
                        <span 
                            class="px-3 py-1 rounded-full text-xs font-semibold"
                            :class="reportData.is_balanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ reportData.is_balanced ? 'Balanced' : 'Unbalanced' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Income Statement Report -->
            <div v-if="activeTab === 'income-statement' && reportData && reportData.report_type === 'income_statement'" class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Income Statement (Profit & Loss)</h2>
                    <p v-if="getCompanyName()" class="text-sm text-gray-600 mt-1">{{ getCompanyName() }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        For the period {{ formatDate(reportData.date_from) }} to {{ formatDate(reportData.date_to) }}
                    </p>
                </div>
                <div class="p-6">
                    <!-- Revenue Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue</h3>
                        <div class="space-y-2">
                            <div v-for="account in reportData.revenue.accounts" :key="account.account_code" class="flex justify-between items-center py-2 border-b border-gray-100">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ account.account_name }}</div>
                                    <div class="text-xs text-gray-500">{{ account.account_code }}</div>
                                </div>
                                <div class="text-sm font-medium text-green-600">{{ formatCurrency(account.balance) }}</div>
                            </div>
                            <div class="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300">
                                <div class="text-lg font-bold text-gray-900">Total Revenue</div>
                                <div class="text-lg font-bold text-green-600">{{ formatCurrency(reportData.revenue.total) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Expenses Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Expenses</h3>
                        <div class="space-y-2">
                            <div v-for="account in reportData.expenses.accounts" :key="account.account_code" class="flex justify-between items-center py-2 border-b border-gray-100">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ account.account_name }}</div>
                                    <div class="text-xs text-gray-500">{{ account.account_code }}</div>
                                </div>
                                <div class="text-sm font-medium text-red-600">{{ formatCurrency(account.balance) }}</div>
                            </div>
                            <div class="flex justify-between items-center py-3 mt-4 border-t-2 border-gray-300">
                                <div class="text-lg font-bold text-gray-900">Total Expenses</div>
                                <div class="text-lg font-bold text-red-600">{{ formatCurrency(reportData.expenses.total) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Net Income -->
                    <div class="flex justify-between items-center py-4 border-t-2 border-gray-900">
                        <div class="text-xl font-bold text-gray-900">Net Income</div>
                        <div 
                            class="text-xl font-bold"
                            :class="reportData.net_income >= 0 ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ formatCurrency(reportData.net_income) }}
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">
                            Generated at {{ formatDateTime(reportData.generated_at) }}
                        </span>
                        <span 
                            class="px-3 py-1 rounded-full text-xs font-semibold"
                            :class="reportData.is_profit ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ reportData.is_profit ? 'Profit' : 'Loss' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!reportData" class="bg-white shadow rounded-lg p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No report generated</h3>
                <p class="mt-1 text-sm text-gray-500">Select filters and click "Generate Report" to view financial reports.</p>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';
import { formatCurrency as formatCurrencyValue, getCompanyName, getFiscalYearStart, getFiscalYearEnd } from '@/utils/settings';

export default {
    name: 'Reports',
    setup() {
        const activeTab = ref('trial-balance');
        const reportData = ref(null);
        const loading = ref(false);

        const filters = reactive({
            date_from: '',
            date_to: new Date().toISOString().split('T')[0], // Default to today
        });

        const formatCurrency = (amount) => formatCurrencyValue(amount);

        const formatDate = (date) => {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        };

        const formatDateTime = (dateTime) => {
            if (!dateTime) return '-';
            return new Date(dateTime).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const loadReport = async () => {
            loading.value = true;
            reportData.value = null;

            try {
                const params = new URLSearchParams();
                if (filters.date_from) params.append('date_from', filters.date_from);
                if (filters.date_to) params.append('date_to', filters.date_to);

                let endpoint = '';
                if (activeTab.value === 'trial-balance') {
                    endpoint = '/api/reports/trial-balance';
                } else if (activeTab.value === 'balance-sheet') {
                    endpoint = '/api/reports/balance-sheet';
                } else if (activeTab.value === 'income-statement') {
                    endpoint = '/api/reports/income-statement';
                }

                const response = await axios.get(`${endpoint}?${params}`);
                reportData.value = response.data;
            } catch (error) {
                console.error('Error loading report:', error);
                const message = error.response?.data?.message || 'Failed to load report';
                alert(message);
                reportData.value = null;
            } finally {
                loading.value = false;
            }
        };

        const printReport = () => {
            window.print();
        };

        // Initialize default dates when switching tabs
        const handleTabChange = () => {
            reportData.value = null;
            // Set default date_from for income statement (start of year)
            if (activeTab.value === 'income-statement' && !filters.date_from) {
                const now = new Date();
                filters.date_from = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
            }
            // Load report if date_to is set
            if (filters.date_to) {
                loadReport();
            }
        };

        // Watch for tab changes
        watch(activeTab, (newTab) => {
            // Reset report data when tab changes
            reportData.value = null;
            // Set default date_from for income statement (start of year)
            if (newTab === 'income-statement' && !filters.date_from) {
                const now = new Date();
                filters.date_from = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
            }
            // Load report if date_to is set
            if (filters.date_to) {
                loadReport();
            }
        });

        const applyFiscalDefaults = () => {
            const fiscalStart = getFiscalYearStart();
            const fiscalEnd = getFiscalYearEnd();
            if (fiscalStart) {
                filters.date_from = fiscalStart;
            }
            if (fiscalEnd) {
                filters.date_to = fiscalEnd;
            }
        };

        onMounted(() => {
            // Apply fiscal defaults if available
            applyFiscalDefaults();
            // Set default date_from for income statement when not defined
            if (activeTab.value === 'income-statement' && !filters.date_from) {
                const now = new Date();
                filters.date_from = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
            }
        });

        return {
            activeTab,
            reportData,
            loading,
            filters,
            formatCurrency,
            formatDate,
            formatDateTime,
            loadReport,
            printReport,
            getCompanyName,
        };
    },
};
</script>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }

    .printable-report,
    .printable-report * {
        visibility: visible;
    }

    .printable-report {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .mb-6,
    .mb-4,
    button {
        display: none;
    }

    .bg-white {
        box-shadow: none;
    }

    .overflow-x-auto {
        overflow: visible;
    }
}
</style>
