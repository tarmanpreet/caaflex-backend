<script setup>
import { computed, ref, useSlots } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    emptyMessage: {
        type: String,
        default: 'Nessun dato trovato.',
    },
});

const slots = useSlots();
const sortKey = ref(null);
const sortDir = ref('asc');

const setSort = (col) => {
    if (col.sortable === false) return;

    if (sortKey.value === col.key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortKey.value = col.key;
    sortDir.value = 'asc';
};

const getNestedValue = (obj, key) => key.split('.').reduce((o, k) => (o != null ? o[k] : null), obj);

const sortedRows = computed(() => {
    if (!sortKey.value) return props.rows;

    return [...props.rows].sort((a, b) => {
        const av = getNestedValue(a, sortKey.value);
        const bv = getNestedValue(b, sortKey.value);

        if (av == null && bv == null) return 0;
        if (av == null) return 1;
        if (bv == null) return -1;

        if (typeof av === 'number' && typeof bv === 'number') {
            return sortDir.value === 'asc' ? av - bv : bv - av;
        }

        const da = Date.parse(av);
        const db = Date.parse(bv);
        if (!Number.isNaN(da) && !Number.isNaN(db)) {
            return sortDir.value === 'asc' ? da - db : db - da;
        }

        const sa = String(av).toLowerCase();
        const sb = String(bv).toLowerCase();
        if (sa < sb) return sortDir.value === 'asc' ? -1 : 1;
        if (sa > sb) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const totalCols = computed(() => props.columns.length + (slots.actions ? 1 : 0));
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm text-on-surface">
            <thead class="bg-surface-container-low text-on-surface-variant">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        scope="col"
                        :class="[
                            'px-6 py-4 text-[11px] font-semibold uppercase tracking-[0.18em] select-none',
                            col.align === 'right' ? 'text-right' : 'text-left',
                            col.sortable !== false ? 'cursor-pointer transition hover:text-primary' : '',
                        ]"
                        @click="setSort(col)"
                    >
                        <span class="inline-flex items-center gap-2">
                            {{ col.label }}
                            <span v-if="col.sortable !== false" class="inline-flex flex-col leading-none">
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    :class="[
                                        'h-3 w-3 -mb-1 transition-colors',
                                        sortKey === col.key && sortDir === 'asc' ? 'text-primary' : 'text-outline-variant',
                                    ]"
                                >
                                    <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
                                </svg>
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    :class="[
                                        'h-3 w-3 transition-colors',
                                        sortKey === col.key && sortDir === 'desc' ? 'text-primary' : 'text-outline-variant',
                                    ]"
                                >
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    </th>

                    <th
                        v-if="slots.actions"
                        scope="col"
                        class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant"
                    >
                        Azioni
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr v-if="sortedRows.length === 0">
                    <td :colspan="totalCols" class="px-6 py-12 text-center text-sm text-on-surface-variant">
                        {{ emptyMessage }}
                    </td>
                </tr>

                <tr
                    v-for="(row, index) in sortedRows"
                    :key="row.id ?? JSON.stringify(row)"
                    :class="[
                        'transition-colors hover:bg-surface-container-low/60',
                        index % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface/70',
                    ]"
                >
                    <td
                        v-for="col in columns"
                        :key="col.key"
                        :class="[
                            'px-6 py-5 align-middle text-sm',
                            col.align === 'right' ? 'text-right' : 'text-left',
                        ]"
                    >
                        <slot :name="'cell-' + col.key" :row="row" :value="getNestedValue(row, col.key)">
                            <span class="text-on-surface-variant">
                                {{ getNestedValue(row, col.key) ?? '—' }}
                            </span>
                        </slot>
                    </td>

                    <td v-if="slots.actions" class="px-6 py-5 text-right align-middle">
                        <slot name="actions" :row="row" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
