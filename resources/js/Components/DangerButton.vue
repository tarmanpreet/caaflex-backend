<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-transparent bg-error px-4 py-2.5 text-sm font-semibold text-on-error shadow-sm transition duration-200 select-none hover:brightness-110 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none">
        <slot />
    </button>
</template>
