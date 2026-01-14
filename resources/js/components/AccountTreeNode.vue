<template>
    <div class="account-tree-node">
        <div 
            class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :style="{ paddingLeft: `${level * 24 + 12}px` }"
        >
            <div class="flex items-center flex-1 min-w-0">
                <!-- Expand/Collapse Icon -->
                <button
                    v-if="hasChildren"
                    type="button"
                    @click="toggleExpanded"
                    class="mr-2 flex-shrink-0 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded hover:bg-gray-200 transition-colors"
                >
                    <svg 
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-90': isExpanded }"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <div v-else class="mr-2 w-6"></div>

                <!-- Account Code -->
                <div class="flex-shrink-0 w-24">
                    <span class="text-sm font-mono font-medium text-gray-900">{{ node.account_code }}</span>
                </div>

                <!-- Account Name -->
                <div class="flex-1 min-w-0 mr-4">
                    <span class="text-sm font-medium text-gray-900">{{ node.account_name }}</span>
                </div>

                <!-- Account Type -->
                <div class="flex-shrink-0 w-24 mr-4">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getTypeClass(node.account_type)">
                        {{ node.account_type }}
                    </span>
                </div>

                <!-- Current Balance -->
                <div class="flex-shrink-0 w-32 text-right mr-4">
                    <span class="text-sm font-medium" :class="getBalanceClass(node.current_balance)">
                        {{ formatCurrency(node.current_balance) }}
                    </span>
                </div>

                <!-- Actions -->
                <div class="flex-shrink-0 flex items-center gap-2">
                    <button
                        type="button"
                        @click="$emit('view', node)"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        title="View Details"
                    >
                        View
                    </button>
                    <button
                        type="button"
                        @click="$emit('edit', node)"
                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                        title="Edit Account"
                    >
                        Edit
                    </button>
                </div>
            </div>
        </div>

        <!-- Children -->
        <div v-if="hasChildren && isExpanded" class="children-container">
            <AccountTreeNode
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :level="level + 1"
                @edit="$emit('edit', $event)"
                @view="$emit('view', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    level: {
        type: Number,
        default: 0,
    },
});

defineEmits(['edit', 'view']);

const isExpanded = ref(true);

const hasChildren = computed(() => {
    return props.node.children && props.node.children.length > 0;
});

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value;
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const getTypeClass = (type) => {
    const classes = {
        asset: 'bg-green-100 text-green-800',
        liability: 'bg-red-100 text-red-800',
        equity: 'bg-blue-100 text-blue-800',
        revenue: 'bg-yellow-100 text-yellow-800',
        expense: 'bg-purple-100 text-purple-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

const getBalanceClass = (balance) => {
    if (balance === null || balance === undefined) return 'text-gray-500';
    const numBalance = parseFloat(balance);
    if (numBalance > 0) return 'text-green-600';
    if (numBalance < 0) return 'text-red-600';
    return 'text-gray-500';
};
</script>

<style scoped>
.account-tree-node {
    position: relative;
}

.children-container {
    border-left: 2px solid #e5e7eb;
    margin-left: 12px;
}
</style>
