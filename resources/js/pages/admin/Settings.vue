<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="mt-1 text-sm text-gray-500">Configure system settings and preferences</p>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading settings...</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6">
            <!-- General Settings -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">General Settings</h3>
                    <p class="mt-1 text-sm text-gray-500">Basic system configuration</p>
                </div>
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                        <input
                            type="text"
                            v-model="settings.company_name"
                            @blur="saveSetting('company_name', settings.company_name)"
                            placeholder="Enter company name"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p class="mt-1 text-xs text-gray-500">Your company or organization name</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year Start</label>
                        <input
                            type="date"
                            v-model="settings.fiscal_year_start"
                            @change="saveSetting('fiscal_year_start', settings.fiscal_year_start)"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p class="mt-1 text-xs text-gray-500">Start date of your fiscal year</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year End</label>
                        <input
                            type="date"
                            v-model="settings.fiscal_year_end"
                            @change="saveSetting('fiscal_year_end', settings.fiscal_year_end)"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p class="mt-1 text-xs text-gray-500">End date of your fiscal year</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                        <select
                            v-model="settings.currency"
                            @change="saveSetting('currency', settings.currency)"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="PKR">PKR - Pakistani Rupee</option>
                            <option value="INR">INR - Indian Rupee</option>
                            <option value="AED">AED - UAE Dirham</option>
                            <option value="SAR">SAR - Saudi Riyal</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Default currency for transactions</p>
                    </div>
                </div>
            </div>

            <!-- Accounting Settings -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Accounting Settings</h3>
                    <p class="mt-1 text-sm text-gray-500">Accounting preferences and defaults</p>
                </div>
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Account Type</label>
                        <select
                            v-model="settings.default_account_type"
                            @change="saveSetting('default_account_type', settings.default_account_type)"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="revenue">Revenue</option>
                            <option value="expense">Expense</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Default account type for new accounts</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Auto-generate Reference Numbers</label>
                            <p class="text-sm text-gray-500">Automatically generate reference numbers for transactions</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="settings.auto_generate_reference"
                                @change="saveSetting('auto_generate_reference', settings.auto_generate_reference)"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save All Button -->
            <div class="flex justify-end">
                <button
                    @click="saveAllSettings"
                    :disabled="saving"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ saving ? 'Saving...' : 'Save All Settings' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'Settings',
    setup() {
        const loading = ref(true);
        const saving = ref(false);
        const settings = reactive({
            company_name: '',
            fiscal_year_start: '',
            fiscal_year_end: '',
            currency: 'USD',
            default_account_type: 'asset',
            auto_generate_reference: false,
        });

        const loadSettings = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/settings');
                const settingsList = response.data;

                // Map settings to reactive object
                settingsList.forEach(setting => {
                    if (settings.hasOwnProperty(setting.key)) {
                        settings[setting.key] = setting.value;
                    }
                });

                // Set defaults if not found
                if (!settingsList.find(s => s.key === 'fiscal_year_start')) {
                    const now = new Date();
                    settings.fiscal_year_start = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
                }
                if (!settingsList.find(s => s.key === 'fiscal_year_end')) {
                    const now = new Date();
                    settings.fiscal_year_end = new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0];
                }
            } catch (error) {
                console.error('Error loading settings:', error);
                // Set defaults on error
                const now = new Date();
                settings.fiscal_year_start = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
                settings.fiscal_year_end = new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0];
            } finally {
                loading.value = false;
            }
        };

        const saveSetting = async (key, value) => {
            try {
                const type = typeof value === 'boolean' ? 'boolean' : 'string';
                await axios.put(`/api/settings/${key}`, {
                    value: value,
                    type: type,
                });
            } catch (error) {
                console.error(`Error saving setting ${key}:`, error);
                alert(`Failed to save ${key}. Please try again.`);
            }
        };

        const saveAllSettings = async () => {
            saving.value = true;
            try {
                const settingsArray = Object.keys(settings).map(key => ({
                    key: key,
                    value: settings[key],
                    type: typeof settings[key] === 'boolean' ? 'boolean' : 'string',
                }));

                await axios.post('/api/settings/update-multiple', {
                    settings: settingsArray,
                });

                alert('All settings saved successfully!');
            } catch (error) {
                console.error('Error saving settings:', error);
                alert('Failed to save settings. Please try again.');
            } finally {
                saving.value = false;
            }
        };

        onMounted(() => {
            loadSettings();
        });

        return {
            loading,
            saving,
            settings,
            loadSettings,
            saveSetting,
            saveAllSettings,
        };
    },
};
</script>
