<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Import Transactions</h1>
            <p class="mt-1 text-sm text-gray-500">Import transactions from Excel file</p>
        </div>

        <!-- Import Form -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <form @submit.prevent="handleImport" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Excel File
                    </label>
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleFileSelect"
                        accept=".xlsx,.xls"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        required
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Select an Excel file (.xlsx or .xls). Maximum file size: 10MB
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Sheet Number
                        </label>
                        <input
                            type="number"
                            v-model="form.sheet"
                            min="1"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Sheet number to import from (default: 2)
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Default Date
                        </label>
                        <input
                            type="date"
                            v-model="form.default_date"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Date to use if not found in file (default: today)
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <button
                        type="button"
                        @click="previewFile"
                        :disabled="!selectedFile || previewLoading"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ previewLoading ? 'Loading...' : 'Preview' }}
                    </button>
                    <button
                        type="submit"
                        :disabled="!selectedFile || importing"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ importing ? 'Importing...' : 'Import Transactions' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Section -->
        <div v-if="previewData" class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">File Preview</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                v-for="(header, index) in previewData.headers"
                                :key="index"
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                {{ header || `Column ${index + 1}` }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(row, rowIndex) in previewData.rows" :key="rowIndex">
                            <td
                                v-for="(cell, cellIndex) in row"
                                :key="cellIndex"
                                class="px-4 py-3 text-sm text-gray-900"
                            >
                                {{ cell }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                Total rows in sheet: {{ previewData.total_rows }}
            </p>
        </div>

        <!-- Import Results -->
        <div v-if="importResults" class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Import Results</h3>
            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <div class="flex-1">
                        <div class="text-sm text-gray-500">Successfully Imported</div>
                        <div class="text-2xl font-bold text-green-600">{{ importResults.imported }}</div>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm text-gray-500">Skipped</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ importResults.skipped }}</div>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm text-gray-500">Errors</div>
                        <div class="text-2xl font-bold text-red-600">{{ importResults.total_errors }}</div>
                    </div>
                </div>

                <div v-if="importResults.errors && importResults.errors.length > 0" class="mt-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Errors:</h4>
                    <div class="max-h-64 overflow-y-auto bg-red-50 border border-red-200 rounded-md p-4">
                        <ul class="list-disc list-inside space-y-1">
                            <li
                                v-for="(error, index) in importResults.errors"
                                :key="index"
                                class="text-sm text-red-700"
                            >
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
            <h3 class="text-sm font-medium text-blue-900 mb-2">Import Instructions</h3>
            <ul class="list-disc list-inside space-y-1 text-sm text-blue-800">
                <li><strong>Excel Structure:</strong> Column B = Description, Column C = Customer Name, Column D = Source, Column E = Driver/Employee, Column M = Work Amount, Column P = Vehicle Number</li>
                <li>Data starts from row 11 (row 9 contains headers)</li>
                <li>If date is not found in the file, the default date you specify will be used</li>
                <li>If customer doesn't exist, it will be automatically created</li>
                <li>If employee/driver doesn't exist, it will be automatically created</li>
                <li>Amounts in Column M (WORK) are treated as revenue (credit), amounts in Column H/J are treated as debit/credit</li>
                <li>Vehicle numbers from Column P are used as reference numbers</li>
                <li>Empty rows will be automatically skipped</li>
            </ul>
        </div>
    </div>
</template>

<script>
import { ref, reactive } from 'vue';
import axios from 'axios';

export default {
    name: 'Import',
    setup() {
        const fileInput = ref(null);
        const selectedFile = ref(null);
        const importing = ref(false);
        const previewLoading = ref(false);
        const previewData = ref(null);
        const importResults = ref(null);

        const form = reactive({
            sheet: 2,
            default_date: new Date().toISOString().split('T')[0],
        });

        const handleFileSelect = (event) => {
            selectedFile.value = event.target.files[0];
            previewData.value = null;
            importResults.value = null;
        };

        const previewFile = async () => {
            if (!selectedFile.value) return;

            previewLoading.value = true;
            const formData = new FormData();
            formData.append('file', selectedFile.value);
            formData.append('sheet', form.sheet);
            formData.append('rows', 5);

            try {
                const response = await axios.post('/api/import/preview', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                previewData.value = response.data;
            } catch (error) {
                console.error('Preview error:', error);
                alert('Failed to preview file: ' + (error.response?.data?.message || error.message));
            } finally {
                previewLoading.value = false;
            }
        };

        const handleImport = async () => {
            if (!selectedFile.value) return;

            if (!confirm('Are you sure you want to import transactions? This action cannot be undone.')) {
                return;
            }

            importing.value = true;
            const formData = new FormData();
            formData.append('file', selectedFile.value);
            formData.append('sheet', form.sheet);
            formData.append('default_date', form.default_date);

            try {
                const response = await axios.post('/api/import/transactions', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                importResults.value = response.data.results;
                alert(`Import completed! ${response.data.results.imported} transactions imported, ${response.data.results.skipped} skipped.`);
            } catch (error) {
                console.error('Import error:', error);
                alert('Import failed: ' + (error.response?.data?.message || error.message));
            } finally {
                importing.value = false;
            }
        };

        return {
            fileInput,
            selectedFile,
            importing,
            previewLoading,
            previewData,
            importResults,
            form,
            handleFileSelect,
            previewFile,
            handleImport,
        };
    },
};
</script>
