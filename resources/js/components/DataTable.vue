<template>
    <div class="data-table-wrapper">
        <vxe-table
            ref="tableRef"
            :data="data"
            :loading="loading"
            :height="height"
            :max-height="maxHeight"
            :stripe="stripe"
            :border="border"
            :show-header="showHeader"
            :highlight-hover-row="highlightHoverRow"
            :highlight-current-row="highlightCurrentRow"
            :sort-config="sortConfig"
            :filter-config="filterConfig"
            :pager-config="pagerConfig"
            :column-config="columnConfig"
            :toolbar-config="toolbarConfig"
            @page-change="handlePageChange"
            @sort-change="handleSortChange"
            @filter-change="handleFilterChange"
            class="vxe-table-custom"
        >
            <vxe-column
                v-for="column in columns"
                :key="column.field || column.prop"
                :field="column.field || column.prop"
                :title="column.title"
                :width="column.width"
                :min-width="column.minWidth"
                :fixed="column.fixed"
                :align="column.align || 'left'"
                :sortable="column.sortable !== false"
                :filters="column.filters"
                :formatter="column.formatter"
                :show-overflow="column.showOverflow !== false"
            >
                <template v-if="column.slot" #default="{ row, $rowIndex, column: col }">
                    <slot :name="column.slot" :row="row" :index="$rowIndex" :column="col"></slot>
                </template>
            </vxe-column>
        </vxe-table>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    height: {
        type: [String, Number],
        default: null,
    },
    maxHeight: {
        type: [String, Number],
        default: null,
    },
    stripe: {
        type: Boolean,
        default: true,
    },
    border: {
        type: Boolean,
        default: true,
    },
    showHeader: {
        type: Boolean,
        default: true,
    },
    highlightHoverRow: {
        type: Boolean,
        default: true,
    },
    highlightCurrentRow: {
        type: Boolean,
        default: false,
    },
    sortable: {
        type: Boolean,
        default: true,
    },
    filterable: {
        type: Boolean,
        default: false,
    },
    pagination: {
        type: [Boolean, Object],
        default: false,
    },
    pageSize: {
        type: Number,
        default: 15,
    },
    currentPage: {
        type: Number,
        default: 1,
    },
    total: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['page-change', 'sort-change', 'filter-change', 'refresh']);

const tableRef = ref(null);

        const sortConfig = computed(() => {
            if (!props.sortable) return null;
            return {
                trigger: 'default',
                multiple: false,
            };
        });

        const filterConfig = computed(() => {
            if (!props.filterable) return null;
            return {
                remote: true,
            };
        });

        const pagerConfig = computed(() => {
            if (!props.pagination) return null;
            
            const config = {
                enabled: true,
                currentPage: props.currentPage,
                pageSize: props.pageSize,
                total: props.total,
                pageSizes: [10, 15, 20, 50, 100],
                layouts: ['PrevJump', 'PrevPage', 'Number', 'NextPage', 'NextJump', 'Sizes', 'FullJump', 'Total'],
            };

            if (typeof props.pagination === 'object') {
                return { ...config, ...props.pagination };
            }

            return config;
        });

        const columnConfig = computed(() => {
            return {
                resizable: true,
            };
        });

        const toolbarConfig = computed(() => {
            return {
                enabled: false,
            };
        });

        const handlePageChange = ({ currentPage, pageSize }) => {
            emit('page-change', { currentPage, pageSize });
        };

        const handleSortChange = ({ column, property, order, sortBy, sortList }) => {
            emit('sort-change', { column, property, order, sortBy, sortList });
        };

        const handleFilterChange = ({ column, property, values, datas, filterList }) => {
            emit('filter-change', { column, property, values, datas, filterList });
        };

        const refresh = () => {
            if (tableRef.value) {
                tableRef.value.refreshData();
            }
            emit('refresh');
        };

defineExpose({
    refresh,
    tableRef,
});
</script>

<style scoped>
.data-table-wrapper {
    @apply bg-white shadow rounded-lg overflow-hidden;
}

:deep(.vxe-table-custom) {
    @apply border-gray-200;
}

:deep(.vxe-table--header) {
    @apply bg-gray-50;
}

:deep(.vxe-table--header .vxe-header--column) {
    @apply text-xs font-medium text-gray-500 uppercase tracking-wider;
}

:deep(.vxe-table--body .vxe-body--column) {
    @apply text-sm text-gray-900;
}

:deep(.vxe-table--body .vxe-body--row.row--hover) {
    @apply bg-gray-50;
}

:deep(.vxe-pager) {
    @apply px-4 py-3 border-t border-gray-200;
}

:deep(.vxe-pager .vxe-pager--left) {
    @apply text-sm text-gray-700;
}

:deep(.vxe-pager .vxe-pager--right) {
    @apply flex space-x-2;
}

:deep(.vxe-pager .vxe-pager--btn) {
    @apply px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed;
}

:deep(.vxe-pager .vxe-pager--number-btn) {
    @apply px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50;
}

:deep(.vxe-pager .vxe-pager--number-btn.is--active) {
    @apply bg-blue-600 text-white border-blue-600;
}
</style>
