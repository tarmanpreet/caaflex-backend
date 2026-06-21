<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center rounded-lg border border-outline-variant/30 bg-surface-container-lowest px-4 py-2 text-xs font-semibold uppercase tracking-widest text-on-surface-variant shadow-sm transition ease-in-out duration-150 select-none hover:bg-surface-container-low hover:text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/25 focus:ring-offset-2 focus:ring-offset-background disabled:opacity-25">
        <slot />
    </button>
</template>
