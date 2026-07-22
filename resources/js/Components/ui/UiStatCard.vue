<script setup>
defineProps({
    title: String,
    value: [String, Number],
    caption: String,
    tone: {
        type: String,
        default: 'primary',
    },
});

const toneMap = {
    primary: 'bg-primary',
    tertiary: 'bg-tertiary',
    neutral: 'bg-outline',
    success: 'bg-secondary',
};
</script>

<template>
    <div class="app-card relative p-5 sm:p-6">
        <div class="flex items-center gap-2.5">
            <span :class="['h-2.5 w-2.5 rounded-full', toneMap[tone] ?? toneMap.primary]" aria-hidden="true" />
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">{{ title }}</p>
        </div>
        <p class="mt-4 font-headline text-3xl font-extrabold tracking-tight text-on-surface">{{ value }}</p>
        <p class="mt-2 text-sm leading-6 text-on-surface-variant">{{ caption }}</p>
    </div>
</template>
