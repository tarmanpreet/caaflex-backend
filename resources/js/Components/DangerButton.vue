<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="app-button inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-transparent bg-error px-4 py-2.5 text-sm font-semibold text-on-error shadow-sm transition duration-200 select-none hover:brightness-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none">
        <slot />
    </button>
</template>
