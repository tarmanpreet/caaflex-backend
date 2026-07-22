<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary shadow-lg shadow-primary/15 transition duration-200 select-none hover:bg-primary-dim focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none">
        <slot />
    </button>
</template>
