<template>
    <component
        :is="as"
        v-bind="$attrs"
        class="relative inline-flex items-center justify-center group select-none"
    >
        <slot />
        <span
            class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-inverse-surface px-2 py-1 text-xs text-surface opacity-0 transition-opacity group-hover:opacity-100"
        >
            {{ tooltip }}
        </span>
    </component>
</template>

<script setup>
defineProps({
    tooltip: {
        type: String,
        required: true,
    },
    as: {
        type: [String, Object],
        default: 'button',
    },
});
</script>
