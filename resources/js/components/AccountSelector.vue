<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <select
            :value="modelValue"
            @update:modelValue="$emit('update:modelValue', $event)"
            :required="required"
            :disabled="disabled"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            :class="{ 'opacity-50 cursor-not-allowed': disabled }"
        >
            <option value="">{{ placeholder || 'Select Account' }}</option>
            <option v-for="account in accounts" :key="account.id" :value="account.id">
                {{ account.account_code }} - {{ account.account_name }}
            </option>
        </select>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'AccountSelector',
    props: {
        modelValue: {
            type: [String, Number],
            default: '',
        },
        label: {
            type: String,
            default: '',
        },
        required: {
            type: Boolean,
            default: false,
        },
        placeholder: {
            type: String,
            default: 'Select Account',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        activeOnly: {
            type: Boolean,
            default: true,
        },
    },
    emits: ['update:modelValue'],
    setup(props) {
        const accounts = ref([]);

        const loadAccounts = async () => {
            try {
                const params = new URLSearchParams();
                if (props.activeOnly) {
                    params.append('is_active', 'true');
                }
                params.append('per_page', 1000);

                const response = await axios.get(`/api/accounts?${params}`);
                accounts.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading accounts:', error);
                accounts.value = [];
            }
        };

        onMounted(() => {
            loadAccounts();
        });

        return {
            accounts,
        };
    },
};
</script>
