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
    primary: 'bg-primary-container text-on-primary-container',
    tertiary: 'bg-tertiary-container text-on-tertiary-container',
    neutral: 'bg-surface-container-high text-on-surface',
    success: 'bg-green-100 text-green-700',
};
</script>

<template>
    <div class="rounded-[1.75rem] bg-surface-container-lowest p-6 shadow-[0px_20px_40px_rgba(12,15,16,0.06)] ring-1 ring-outline-variant/10">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-on-surface-variant">{{ title }}</p>
                <p class="mt-4 font-headline text-3xl font-extrabold tracking-tight text-on-surface">{{ value }}</p>
            </div>

            <div :class="['rounded-2xl px-3 py-2 text-xs font-bold uppercase tracking-[0.18em]', toneMap[tone] ?? toneMap.primary]">
                {{ caption }}
            </div>
        </div>
    </div>
</template>
