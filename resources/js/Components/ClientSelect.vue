<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import Multiselect from '@vueform/multiselect';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: [Number, Array, null],
        default: null,
    },
    /**
     * Pre-selected options for edit pages — prevents missing selected items
     * that might not appear in the first page of backend results.
     *
     * single mode → pass array with one element: [{ value: 1, label: 'Rossi Mario' }]
     * tags mode   → pass all selected:           [{ value: 1, label: '...' }, ...]
     */
    initialOptions: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'single', // 'single' | 'tags' | 'multiple'
        validator: v => ['single', 'tags', 'multiple'].includes(v),
    },
    placeholder: {
        type: String,
        default: 'Cerca cliente...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    perPage: {
        type: Number,
        default: 15,
    },
});

const emit = defineEmits(['update:modelValue']);

const isMultiple = computed(() => props.mode === 'tags' || props.mode === 'multiple');

// ── State ─────────────────────────────────────────────────────────────────────
const options = ref([]);
const page    = ref(1);
const hasMore = ref(false);
const loading = ref(false);
const query   = ref('');

// Sentinel el injected via #afterlist slot for IntersectionObserver
const sentinel = ref(null);
const observer = ref(null);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Returns the set of currently selected IDs (works for both single and multi).
 */
function selectedIds() {
    if (isMultiple.value) {
        return new Set(Array.isArray(props.modelValue) ? props.modelValue : []);
    }
    return props.modelValue != null ? new Set([props.modelValue]) : new Set();
}

/**
 * Merges initialOptions (for selected items) with fetched results,
 * avoiding duplicates. Selected items always appear first.
 */
function mergeWithInitial(results) {
    if (!props.initialOptions.length) return results;

    const resultIds = new Set(results.map(r => r.value));
    // Keep only initialOptions whose id is currently selected and not already in results
    const ids = selectedIds();
    const missing = props.initialOptions.filter(
        o => ids.has(o.value) && !resultIds.has(o.value),
    );
    return [...missing, ...results];
}

// ── Data fetching ─────────────────────────────────────────────────────────────
async function fetchPage(reset = false) {
    if (loading.value) return;
    loading.value = true;

    try {
        const { data } = await axios.get(route('clients.search'), {
            params: { q: query.value, page: page.value, per_page: props.perPage },
        });

        if (reset) {
            options.value = mergeWithInitial(data.results);
        } else {
            // Append page, skip duplicates (initialOptions may already be in list)
            const existing = new Set(options.value.map(o => o.value));
            options.value.push(...data.results.filter(r => !existing.has(r.value)));
        }

        hasMore.value = data.hasMore;
    } finally {
        loading.value = false;
    }
}

// ── Search — debounced ────────────────────────────────────────────────────────
let debounceTimer = null;

watch(query, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        page.value = 1;
        fetchPage(true);
    }, 300);
});

// ── Infinite scroll via IntersectionObserver ──────────────────────────────────
function setupObserver() {
    if (!window.IntersectionObserver) return;
    observer.value = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMore.value && !loading.value) {
            page.value++;
            fetchPage(false);
        }
    });
}

function onOpen() {
    nextTick(() => {
        if (sentinel.value && observer.value) {
            observer.value.observe(sentinel.value);
        }
    });
}

function onClose() {
    if (sentinel.value && observer.value) {
        observer.value.unobserve(sentinel.value);
    }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    setupObserver();
    fetchPage(true);
});

onBeforeUnmount(() => {
    observer.value?.disconnect();
    clearTimeout(debounceTimer);
});
</script>

<template>
    <Multiselect
        :model-value="modelValue"
        @update:model-value="emit('update:modelValue', $event)"
        :options="options"
        :mode="mode"
        :searchable="true"
        :filter-results="false"
        :loading="loading"
        :close-on-select="!isMultiple"
        :append-to-body="true"
        value-prop="value"
        label="label"
        track-by="value"
        :placeholder="placeholder"
        no-options-text="Nessun cliente trovato"
        no-results-text="Nessun risultato"
        :disabled="disabled"
        class="mt-1"
        @search-change="query = $event"
        @open="onOpen"
        @close="onClose"
    >
        <template #afterlist>
            <div
                v-if="hasMore"
                :ref="el => { sentinel = el; if (el && observer) observer.observe(el); }"
                class="py-1 text-center text-xs text-gray-400 dark:text-gray-500"
            >
                <span v-if="loading">Caricamento...</span>
                <span v-else>↓ altri risultati</span>
            </div>
        </template>
    </Multiselect>
</template>
