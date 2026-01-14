<template>
    <div :class="['inline-block', sizeClass]">
        <div
            :class="[
                'animate-spin rounded-full border-b-2',
                colorClass,
                sizeClass
            ]"
            :style="{ borderTopColor: 'transparent' }"
        ></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md', // 'sm', 'md', 'lg', 'xl'
    },
    color: {
        type: String,
        default: 'blue', // 'blue', 'gray', 'white'
    },
});

const sizeClass = computed(() => {
    const sizes = {
        sm: 'h-4 w-4',
        md: 'h-8 w-8',
        lg: 'h-12 w-12',
        xl: 'h-16 w-16',
    };
    return sizes[props.size] || sizes.md;
});

const colorClass = computed(() => {
    const colors = {
        blue: 'border-blue-600',
        gray: 'border-gray-600',
        white: 'border-white',
    };
    return colors[props.color] || colors.blue;
});
</script>
