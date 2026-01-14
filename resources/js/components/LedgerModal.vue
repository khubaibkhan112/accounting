<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Ledger: {{ title }}
                            </h3>
                            
                            <!-- Controls -->
                            <div class="mt-4 flex flex-wrap gap-4 items-end">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">From Date</label>
                                    <input type="date" v-model="filters.date_from" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">To Date</label>
                                    <input type="date" v-model="filters.date_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                <div class="flex-grow"></div>
                                <div class="flex gap-2">
                                    <button @click="fetchLedger" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                        Filter
                                    </button>
                                    <button @click="exportExcel" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                        Export Excel
                                    </button>
                                    <button @click="exportPdf" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                        Export PDF
                                    </button>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-4 bg-gray-50 p-4 rounded-lg">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Opening Balance</dt>
                                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(summary.opening_balance) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Total Debit</dt>
                                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(summary.total_debit) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Total Credit</dt>
                                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatCurrency(summary.total_credit) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Closing Balance</dt>
                                    <dd class="mt-1 text-lg font-semibold" :class="getBalanceClass(summary.closing_balance)">
                                        {{ formatCurrency(summary.closing_balance) }}
                                    </dd>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-if="loading">
                                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Loading ledger data...</td>
                                        </tr>
                                        <tr v-else-if="transactions.length === 0">
                                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No transactions found for this period.</td>
                                        </tr>
                                        <tr v-for="entry in transactions" :key="entry.id || 'ob'" :class="{'bg-gray-50': entry.type === 'opening'}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(entry.date) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ entry.description }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ entry.reference_number }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ entry.debit_amount ? formatCurrency(entry.debit_amount) : '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ entry.credit_amount ? formatCurrency(entry.credit_amount) : '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right" :class="getBalanceClass(entry.balance)">
                                                {{ formatCurrency(entry.balance) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="close">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from "vue-toastification";

export default {
    name: 'LedgerModal',
    props: {
        isOpen: Boolean,
        entityId: [Number, String], // Customer ID or Employee ID
        entityType: String, // 'customer' or 'employee'
        title: String,
    },
    emits: ['close'],
    setup(props, { emit }) {
        const toast = useToast();
        const loading = ref(false);
        const transactions = ref([]);
        const filters = reactive({
            date_from: '',
            date_to: '',
        });
        const summary = reactive({
            opening_balance: 0,
            closing_balance: 0,
            total_debit: 0,
            total_credit: 0,
        });

        // Initialize dates to current month
        onMounted(() => {
            const date = new Date();
            filters.date_from = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
            filters.date_to = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
        });

        const fetchLedger = async () => {
            if (!props.isOpen || !props.entityId) return;
            
            loading.value = true;
            try {
                const endpoint = props.entityType === 'customer' 
                    ? `/api/customers/${props.entityId}/ledger`
                    : `/api/employees/${props.entityId}/ledger`;
                
                const response = await axios.get(endpoint, { params: filters });
                transactions.value = response.data.ledger;
                summary.opening_balance = response.data.opening_balance;
                summary.closing_balance = response.data.closing_balance;
                summary.total_debit = response.data.total_debit;
                summary.total_credit = response.data.total_credit;
            } catch (error) {
                console.error("Failed to fetch ledger:", error);
                toast.error("Failed to load ledger data.");
            } finally {
                loading.value = false;
            }
        };

        watch(() => props.isOpen, (newVal) => {
            if (newVal) {
                fetchLedger();
            }
        });

        const exportExcel = () => {
             const endpoint = props.entityType === 'customer' 
                ? `/api/customers/${props.entityId}/ledger/export/excel`
                : `/api/employees/${props.entityId}/ledger/export/excel`;
            
            const url = `${endpoint}?date_from=${filters.date_from}&date_to=${filters.date_to}`;
            window.open(url, '_blank');
        };

        const exportPdf = () => {
             const endpoint = props.entityType === 'customer' 
                ? `/api/customers/${props.entityId}/ledger/export/pdf`
                : `/api/employees/${props.entityId}/ledger/export/pdf`;

            const url = `${endpoint}?date_from=${filters.date_from}&date_to=${filters.date_to}`;
            window.open(url, '_blank');
        };

        const close = () => {
            emit('close');
        };

        const formatCurrency = (val) => {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString();
        };

        const getBalanceClass = (balance) => {
            if (balance > 0) return 'text-red-600'; // Owing money (Debit balance for Asset/Expense usually, but for Customer (Asset) > 0 means they owe us)
            if (balance < 0) return 'text-green-600'; 
            return 'text-gray-900';
        };

        return {
            loading,
            transactions,
            filters,
            summary,
            fetchLedger,
            exportExcel,
            exportPdf,
            close,
            formatCurrency,
            formatDate,
            getBalanceClass
        };
    }
}
</script>
