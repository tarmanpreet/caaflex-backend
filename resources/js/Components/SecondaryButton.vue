<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-outline-variant/60 bg-surface-container-lowest px-4 py-2.5 text-sm font-semibold text-on-surface shadow-sm transition duration-200 select-none hover:border-outline hover:bg-surface-container-low focus-visible:outline-none disabled:pointer-events-none disabled:opacity-40 motion-reduce:transition-none">
        <slot />
    </button>
</template>
