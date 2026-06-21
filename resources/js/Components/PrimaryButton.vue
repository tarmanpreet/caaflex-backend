<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center rounded-lg border border-transparent bg-gradient-to-br from-primary to-primary-dim px-4 py-2 text-xs font-semibold uppercase tracking-widest text-on-primary shadow-[0px_12px_24px_rgba(0,86,210,0.18)] transition ease-in-out duration-150 select-none hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-primary/35 focus:ring-offset-2 focus:ring-offset-background disabled:opacity-50">
        <slot />
    </button>
</template>
