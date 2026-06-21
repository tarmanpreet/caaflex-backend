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
    <section class="overflow-hidden rounded-[1.75rem] bg-surface-container-lowest shadow-[0px_20px_40px_rgba(12,15,16,0.06)] ring-1 ring-outline-variant/10">
        <div v-if="title || eyebrow || $slots.actions" class="flex flex-col gap-4 border-b border-outline-variant/10 bg-surface-container-low/80 px-6 py-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p v-if="eyebrow" class="text-[10px] font-semibold uppercase tracking-[0.22em] text-on-surface-variant">{{ eyebrow }}</p>
                <h3 v-if="title" class="mt-2 font-headline text-xl font-extrabold tracking-tight text-on-surface">{{ title }}</h3>
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
