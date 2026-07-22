<script setup>
defineProps({
    title: String,
    eyebrow: String,
    padded: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <section class="app-card">
        <div v-if="title || eyebrow || $slots.actions" class="flex flex-col gap-4 border-b border-outline-variant/35 bg-surface-container-low px-5 py-5 sm:px-6 md:flex-row md:items-end md:justify-between">
            <div>
                <p v-if="eyebrow" class="text-[10px] font-bold uppercase tracking-[0.18em] text-primary">{{ eyebrow }}</p>
                <h3 v-if="title" class="mt-1.5 font-headline text-xl font-bold tracking-tight text-on-surface">{{ title }}</h3>
            </div>

            <div v-if="$slots.actions">
                <slot name="actions" />
            </div>
        </div>

        <div :class="padded ? 'p-6' : ''">
            <slot />
        </div>
    </section>
</template>
