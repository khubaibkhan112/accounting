<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <input
            type="date"
            :value="modelValue"
            @update:value="$emit('update:modelValue', $event)"
            @input="$emit('update:modelValue', $event.target.value)"
            :required="required"
            :disabled="disabled"
            :min="min"
            :max="max"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            :class="{ 'opacity-50 cursor-not-allowed': disabled }"
        />
    </div>
</template>

<script>
export default {
    name: 'DatePicker',
    props: {
        modelValue: {
            type: String,
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
            type: String,
            default: '',
        },
        max: {
            type: String,
            default: '',
        },
    },
    emits: ['update:modelValue'],
};
</script>
