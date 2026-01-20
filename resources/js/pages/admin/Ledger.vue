<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Ledger</h1>
            <p class="mt-1 text-sm text-gray-500">View ledger entries for Accounts, Customers, and Employees</p>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Entity Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select
                        v-model="filters.type"
                        @change="onTypeChange"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="account">Account</option>
                        <option value="customer">Customer</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>

                <!-- Entity Selection -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ entityLabel }} *</label>
                    <select
                        v-model="filters.entity_id"
                        @change="loadLedger"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="">Select {{ entityLabel }}</option>
                        <option v-for="entity in entities" :key="entity.id" :value="entity.id">
                            {{ formatEntityLabel(entity) }}
                        </option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input
                        type="date"
                        v-model="filters.date_from"
                        @change="loadLedger"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input
                        type="date"
                        v-model="filters.date_to"
                        @change="loadLedger"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                </div>

                <!-- Load Button -->
                <div class="flex items-end">
                    <button
                        @click="loadLedger"
                        :disabled="!filters.entity_id || loading"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading">Loading...</span>
                        <span v-else>Load Ledger</span>
                    </button>
                </div>
            </div>

            <!-- Quick Date Presets -->
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

        <!-- Ledger Summary & Data -->
        <div v-if="ledgerData" class="mb-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-sm font-medium text-gray-500">Opening Balance</div>
                    <div class="text-lg font-semibold text-gray-900">{{ formatCurrency(ledgerData.opening_balance) }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Total Debit</div>
                    <div class="text-lg font-semibold text-gray-900">{{ formatCurrency(ledgerData.total_debit) }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Total Credit</div>
                    <div class="text-lg font-semibold text-gray-900">{{ formatCurrency(ledgerData.total_credit) }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Closing Balance</div>
                    <div class="text-lg font-semibold" :class="getBalanceClass(ledgerData.closing_balance)">
                        {{ formatCurrency(ledgerData.closing_balance) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div v-if="ledgerData && ledgerData.ledger && ledgerData.ledger.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="entry in ledgerData.ledger" :key="entry.id || 'opening'" :class="{'bg-gray-50': entry.type === 'opening'}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(entry.date) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ entry.description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ entry.reference_number || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                {{ entry.debit_amount > 0 ? formatCurrency(entry.debit_amount) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                {{ entry.credit_amount > 0 ? formatCurrency(entry.credit_amount) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" :class="getBalanceClass(entry.balance)">
                                {{ formatCurrency(entry.balance) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loading / Empty States -->
        <div v-else-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading ledger data...</p>
        </div>
        <div v-else-if="filters.entity_id" class="bg-white shadow rounded-lg p-8 text-center">
            <p class="text-gray-500">No transactions found for the selected period.</p>
        </div>
        <div v-else class="bg-white shadow rounded-lg p-8 text-center">
            <p class="text-gray-500">Please select an entity to view its ledger.</p>
        </div>

        <!-- Export Buttons -->
        <div v-if="ledgerData && ledgerData.ledger && ledgerData.ledger.length > 0" class="mt-4 flex justify-end gap-3">
             <button
                @click="exportExcel"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
            >
                Export Excel
            </button>
            <button
                @click="exportPdf"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
            >
                Export PDF
            </button>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { formatCurrency as formatCurrencyValue } from '@/utils/settings';

export default {
    name: 'Ledger',
    setup() {
        const route = useRoute();
        const router = useRouter();
        const toast = useToast();

        const loading = ref(false);
        const entities = ref([]);
        const ledgerData = ref(null);

        const filters = reactive({
            type: 'account', // account, customer, employee
            entity_id: '',
            date_from: '',
            date_to: '',
        });

        const entityLabel = computed(() => {
            switch (filters.type) {
                case 'customer': return 'Customer';
                case 'employee': return 'Employee';
                default: return 'Account';
            }
        });

        const datePresets = [
            { label: 'This Month', getDates: () => {
                const now = new Date();
                return { 
                    from: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0],
                    to: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0]
                };
            }},
            { label: 'Last Month', getDates: () => {
                const now = new Date();
                return { 
                    from: new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString().split('T')[0],
                    to: new Date(now.getFullYear(), now.getMonth(), 0).toISOString().split('T')[0]
                };
            }},
            { label: 'This Year', getDates: () => {
                const now = new Date();
                return { 
                    from: new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0],
                    to: new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0]
                };
            }},
        ];

        const applyDatePreset = (preset) => {
            const dates = preset.getDates();
            filters.date_from = dates.from;
            filters.date_to = dates.to;
            loadLedger();
        };

        const onTypeChange = async () => {
            filters.entity_id = '';
            ledgerData.value = null;
            await loadEntities();
            updateUrl();
        };

        const loadEntities = async () => {
            loading.value = true;
            try {
                let url = '';
                if (filters.type === 'account') url = '/api/accounts?per_page=1000&is_active=true';
                else if (filters.type === 'customer') url = '/api/customers?per_page=1000&is_active=true';
                else if (filters.type === 'employee') url = '/api/employees?per_page=1000&is_active=true';

                const response = await axios.get(url);
                entities.value = response.data.data || response.data; // Handle pagination or list
            } catch (error) {
                console.error("Failed to load entities", error);
                toast.error("Failed to load list.");
            } finally {
                loading.value = false;
            }
        };

        const loadLedger = async () => {
             if (!filters.entity_id) return;
             
             updateUrl();
             loading.value = true;
             try {
                let url = '';
                // Construct URL based on type
                if (filters.type === 'account') {
                    // Existing account ledger endpoint
                    url = `/api/ledger?account_id=${filters.entity_id}`;
                } else if (filters.type === 'customer') {
                    url = `/api/customers/${filters.entity_id}/transactions`;
                } else if (filters.type === 'employee') {
                    url = `/api/employees/${filters.entity_id}/transactions`;
                }

                const params = {};
                if (filters.date_from) params.date_from = filters.date_from;
                if (filters.date_to) params.date_to = filters.date_to;

                const response = await axios.get(url, { params });
                
                // Normalize response
                const payload = response.data.ledger ? response.data : (response.data.data && response.data.data.ledger ? response.data.data : response.data);

                if (payload && (Array.isArray(payload.ledger) || filters.type === 'account')) {
                     // Note: Account endpoint might have different structure than Customer/Employee traits
                     // If type is account, payload might be directly the wrapper. 
                     // Let's ensure consistency.
                     if (filters.type === 'account') {
                        ledgerData.value = payload; // Assuming existing structure for accounts matches
                     } else {
                        ledgerData.value = payload;
                     }
                } else {
                    ledgerData.value = null;
                }

             } catch (error) {
                 console.error("Failed to load ledger", error);
                 toast.error("Failed to load ledger data.");
                 ledgerData.value = null;
             } finally {
                 loading.value = false;
             }
        };

        const formatEntityLabel = (entity) => {
            if (filters.type === 'account') return `${entity.account_code || ''} - ${entity.account_name}`;
            if (filters.type === 'customer') return `${entity.customer_code || ''} - ${entity.display_name || entity.company_name || entity.first_name}`;
            if (filters.type === 'employee') return `${entity.employee_id || ''} - ${entity.full_name}`;
            return entity.name || entity.id;
        };

        const updateUrl = () => {
             router.replace({
                query: {
                    type: filters.type,
                    id: filters.entity_id,
                    from: filters.date_from,
                    to: filters.date_to
                }
            });
        };

        const exportExcel = () => {
            let url = '';
            if (filters.type === 'account') {
                url = `/api/ledger/export/excel?account_id=${filters.entity_id}`;
            } else if (filters.type === 'customer') {
                url = `/api/customers/${filters.entity_id}/ledger/export/excel`;
            } else if (filters.type === 'employee') {
                url = `/api/employees/${filters.entity_id}/ledger/export/excel`;
            }

            const params = new URLSearchParams();
            if (filters.date_from) params.append('date_from', filters.date_from);
            if (filters.date_to) params.append('date_to', filters.date_to);

            if (params.toString()) {
                url += url.includes('?') ? `&${params}` : `?${params}`;
            }

            window.open(url, '_blank');
        };

        const exportPdf = () => {
            let url = '';
            if (filters.type === 'account') {
                url = `/api/ledger/export/pdf?account_id=${filters.entity_id}`;
            } else if (filters.type === 'customer') {
                url = `/api/customers/${filters.entity_id}/ledger/export/pdf`;
            } else if (filters.type === 'employee') {
                url = `/api/employees/${filters.entity_id}/ledger/export/pdf`;
            }

            const params = new URLSearchParams();
            if (filters.date_from) params.append('date_from', filters.date_from);
            if (filters.date_to) params.append('date_to', filters.date_to);

            if (params.toString()) {
                url += url.includes('?') ? `&${params}` : `?${params}`;
            }

            window.open(url, '_blank');
        };

        const formatCurrency = (val) => formatCurrencyValue(val);
        const formatDate = (date) => date ? new Date(date).toLocaleDateString() : '-';
        const getBalanceClass = (val) => val > 0 ? 'text-red-600' : (val < 0 ? 'text-green-600' : 'text-gray-900');

        onMounted(async () => {
            // Apply presets defaults
            const now = new Date();
            const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
            
            // Read query params
            filters.type = route.query.type || 'account';
            filters.date_from = route.query.from || startOfMonth;
            filters.date_to = route.query.to || endOfMonth;

            await loadEntities();

            if (route.query.id) {
                filters.entity_id = Number(route.query.id);
                loadLedger();
            }
        });

        watch(() => route.query, (newQuery) => {
             if (newQuery.type && newQuery.type !== filters.type) {
                 filters.type = newQuery.type;
                 loadEntities();
             }
             if (newQuery.id && Number(newQuery.id) !== Number(filters.entity_id)) {
                 filters.entity_id = Number(newQuery.id);
                 loadLedger();
             }
        });

        return {
            loading,
            filters,
            entities,
            ledgerData,
            entityLabel,
            datePresets,
            applyDatePreset,
            onTypeChange,
            loadLedger,
            formatEntityLabel,
            formatCurrency,
            formatDate,
            getBalanceClass,
            exportExcel,
            exportPdf
        };
    }
}
</script>
