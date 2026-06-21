<script setup>
const props = defineProps({
    label: String,
    status: String,
    size: {
        type: String,
        default: 'md',
    },
});

const toneMap = {
    nuova: 'bg-primary-container/80 text-on-primary-container',
    in_lavorazione: 'bg-tertiary-container text-on-tertiary-container',
    in_attesa_documenti: 'bg-primary/10 text-primary',
    completata: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
    annullata: 'bg-error-container/30 text-on-error-container',
    sospesa: 'bg-surface-container-high text-on-surface-variant',
    da_confermare: 'bg-primary/10 text-primary',
    confermato: 'bg-primary-container/80 text-on-primary-container',
    completato: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
    cancellato: 'bg-surface-container-high text-on-surface-variant',
};

const sizeMap = {
    sm: 'px-2.5 py-1 text-[10px]',
    md: 'px-3 py-1.5 text-[11px]',
};
</script>

<template>
    <span :class="['inline-flex items-center rounded-full font-bold uppercase tracking-[0.18em]', sizeMap[size], toneMap[status] ?? 'bg-surface-container-high text-on-surface-variant']">
        {{ label }}
    </span>
</template>
