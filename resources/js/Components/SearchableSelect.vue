<script setup>
import { computed, getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref, useAttrs, watch } from 'vue';
import Multiselect from '@vueform/multiselect';

defineOptions({ inheritAttrs: false });
const props = defineProps({
    modelValue: { default: undefined },
    modelModifiers: { type: Object, default: () => ({}) },
    disabled: Boolean,
    required: Boolean,
    multiple: Boolean,
});
const emit = defineEmits(['update:modelValue', 'change']);
const attrs = useAttrs();
const source = ref(null);
const picker = ref(null);
const choices = ref([]);
const empty = ref(null);
const labelText = ref('Seleziona opzione');
const uid = getCurrentInstance().uid;
const inputId = computed(() => attrs.id || `searchable-select-${uid}`);
const layoutClass = computed(() => String(attrs.class || '').split(/\s+/).filter(token => /^(?:[a-z]+:)*(?:m[trblxyse]?-|w-|min-w-|max-w-|flex-|self-|col-span-)/.test(token)).join(' '));
const nativeAttrs = computed(() => Object.fromEntries(Object.entries(attrs).filter(([key]) => !['class', 'style', 'id', 'aria-label', 'aria-labelledby'].includes(key))));
const selection = ref(props.modelValue !== undefined ? props.modelValue : attrs.value);
const selected = computed(() => {
    if (selection.value === '') return null;
    const option = choices.value.find(option => selection.value != null && String(option.value) === String(selection.value));
    return option ? option.value : selection.value;
});
let observer;

function optionValue(option) {
    return Object.prototype.hasOwnProperty.call(option, '_value') ? option._value : option.value;
}

function syncSelection() {
    if (!source.value) return;
    const values = props.multiple ? (selection.value || []) : [selection.value];
    for (const option of source.value.options) {
        option.selected = values.some(value => Object.is(value, optionValue(option)) || (value != null && String(value) === option.value));
    }
}

function readOptions() {
    if (!source.value) return;
    const label = source.value.closest('label');
    if (label) {
        const copy = label.cloneNode(true);
        copy.querySelector('.searchable-select')?.remove();
        labelText.value = copy.textContent.trim() || labelText.value;
    } else if (attrs.id) {
        const linked = Array.from(document.querySelectorAll('label')).find(label => label.htmlFor === attrs.id);
        if (linked) labelText.value = linked.textContent.trim();
    }
    const options = Array.from(source.value.options);
    const blank = options.find(option => optionValue(option) === '' || optionValue(option) === null);
    const nextEmpty = blank ? { value: optionValue(blank), label: blank.textContent.trim(), disabled: blank.disabled } : null;
    if (JSON.stringify(nextEmpty) !== JSON.stringify(empty.value)) empty.value = nextEmpty;
    const nextChoices = options.filter(option => option !== blank).map(option => ({
        value: optionValue(option),
        label: option.textContent.trim(),
        disabled: option.disabled || option.parentElement?.disabled === true,
    }));
    if (nextChoices.length !== choices.value.length || nextChoices.some((option, index) => !Object.is(option.value, choices.value[index].value) || option.label !== choices.value[index].label || option.disabled !== choices.value[index].disabled)) choices.value = nextChoices;
    if (selection.value === undefined) {
        const values = Array.from(source.value.selectedOptions).map(optionValue);
        selection.value = props.multiple ? values : values[0];
    }
    syncSelection();
}

function notifyChange(event) {
    const values = Array.from(source.value.selectedOptions).map(optionValue);
    let value = props.multiple ? values : values[0];
    if (props.modelModifiers.number && typeof value === 'string' && value !== '') {
        const numeric = Number(value);
        if (!Number.isNaN(numeric)) value = numeric;
    }
    selection.value = value;
    emit('update:modelValue', value);
    emit('change', event);
}

function choose(value) {
    const values = props.multiple ? (value || []) : [value == null && empty.value ? empty.value.value : value];
    for (const option of source.value.options) option.selected = values.some(item => Object.is(item, optionValue(option)));
    source.value.dispatchEvent(new Event('change', { bubbles: true }));
}

function focusPicker() {
    picker.value?.focus();
    picker.value?.open();
}

watch(() => props.modelValue !== undefined ? props.modelValue : attrs.value, value => { selection.value = value; nextTick(syncSelection); }, { deep: true });
onMounted(() => {
    readOptions();
    observer = new MutationObserver(readOptions);
    observer.observe(source.value, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['value', 'disabled', 'label'] });
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <span class="searchable-select block min-w-0" :class="layoutClass" :style="attrs.style" @change.capture="event => { if (event.target !== source) event.stopPropagation(); }">
        <span :id="`${inputId}-label`" class="sr-only">{{ attrs['aria-label'] || labelText }}</span>
        <select
            ref="source" v-bind="nativeAttrs" :id="`${inputId}-native`"
            :disabled="disabled" :required="required" :multiple="multiple"
            tabindex="-1" aria-hidden="true" class="searchable-select-source"
            @change="notifyChange" @focus="focusPicker" @invalid.prevent="focusPicker"
        ><slot /></select>
        <Multiselect
            ref="picker" :id="inputId" :model-value="selected" :options="choices"
            :mode="multiple ? 'tags' : 'single'" :searchable="true" :strict="false"
            :can-clear="!!empty && !empty.disabled && !required" :can-deselect="false"
            :placeholder="empty?.label || 'Seleziona…'" :disabled="disabled"
            :append-to-body="true" :close-on-select="!multiple"
            :aria="{ 'aria-label': attrs['aria-label'] || labelText, 'aria-labelledby': attrs['aria-labelledby'] || `${inputId}-label` }"
            no-options-text="Nessuna opzione disponibile" no-results-text="Nessun risultato"
            @update:model-value="choose"
        />
    </span>
</template>

<style>
.searchable-select-source {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    min-height: 0 !important;
    margin: -1px !important;
    padding: 0 !important;
    overflow: hidden !important;
    clip-path: inset(50%) !important;
    white-space: nowrap !important;
    border: 0 !important;
}
.searchable-select { position: relative; }
.multiselect-dropdown { z-index: 100; }
</style>
