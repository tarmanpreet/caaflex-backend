<template>
    <component
        :is="as"
        v-bind="$attrs"
        :aria-label="tooltip"
        class="app-icon-button group relative select-none"
    >
        <slot />
        <span
            class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-inverse-surface px-2.5 py-1.5 text-xs font-medium text-background opacity-0 shadow-lg transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
            role="tooltip"
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
