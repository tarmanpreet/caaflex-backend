<script setup>
import { computed } from 'vue';
import { CalendarDaysIcon } from '@heroicons/vue/24/outline';
import { formatDate } from '@/utils/date.js';

const props = defineProps({
    expiresOn: {
        type: String,
        default: null,
    },
});

const localDateKey = (date = new Date()) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const expiryDateKey = computed(() => props.expiresOn?.slice(0, 10) ?? null);

const expiryState = computed(() => {
    if (!expiryDateKey.value) {
        return {
            label: 'Nessuna scadenza',
            class: 'bg-surface-container-high text-on-surface-variant',
        };
    }

    if (expiryDateKey.value < localDateKey()) {
        return {
            label: `Scaduto il ${formatDate(props.expiresOn)}`,
            class: 'bg-error-container/30 text-on-error-container',
        };
    }

    if (expiryDateKey.value === localDateKey()) {
        return {
            label: 'Scade oggi',
            class: 'bg-tertiary-container text-on-tertiary-container',
        };
    }

    return {
        label: `Scade il ${formatDate(props.expiresOn)}`,
        class: 'bg-primary-container text-on-primary-container',
    };
});
</script>

<template>
    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap', expiryState.class]">
        <CalendarDaysIcon class="h-4 w-4" aria-hidden="true" />
        {{ expiryState.label }}
    </span>
</template>
