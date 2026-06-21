<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center rounded-lg border border-transparent bg-error px-4 py-2 text-xs font-semibold uppercase tracking-widest text-on-primary transition ease-in-out duration-150 select-none hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-error/35 focus:ring-offset-2 focus:ring-offset-background disabled:opacity-50">
        <slot />
    </button>
</template>
