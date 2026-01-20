<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">{{ currencySymbol }}</span>
            </div>
            <input
                type="number"
                :value="modelValue"
                @update:value="$emit('update:modelValue', $event)"
                @input="handleInput($event)"
                :required="required"
                :disabled="disabled"
                :min="min"
                :max="max"
                :step="step"
                :placeholder="placeholder"
                class="block w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            />
        </div>
        <p v-if="formattedValue && showFormatted" class="mt-1 text-sm text-gray-500">
            {{ formattedValue }}
        </p>
    </div>
</template>

<script>
import { computed } from 'vue';
import { formatCurrency as formatCurrencyValue, getCurrencySymbol } from '@/utils/settings';

export default {
    name: 'CurrencyInput',
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
        disabled: {
            type: Boolean,
            default: false,
        },
        min: {
            type: Number,
            default: 0,
        },
        max: {
            type: Number,
            default: null,
        },
        step: {
            type: Number,
            default: 0.01,
        },
        placeholder: {
            type: String,
            default: '0.00',
        },
        showFormatted: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const formattedValue = computed(() => {
            if (!props.modelValue || props.modelValue === '') return null;
            const value = parseFloat(props.modelValue);
            if (isNaN(value)) return null;
            return formatCurrencyValue(value);
        });

        const currencySymbol = computed(() => getCurrencySymbol());

        const handleInput = (event) => {
            let value = event.target.value;
            // Allow empty value
            if (value === '') {
                emit('update:modelValue', '');
                return;
            }
            // Convert to number
            const numValue = parseFloat(value);
            if (!isNaN(numValue)) {
                // Apply min/max constraints
                let finalValue = numValue;
                if (props.min !== null && finalValue < props.min) {
                    finalValue = props.min;
                }
                if (props.max !== null && finalValue > props.max) {
                    finalValue = props.max;
                }
                emit('update:modelValue', finalValue);
            }
        };

        return {
            formattedValue,
            currencySymbol,
            handleInput,
        };
    },
};
</script>
