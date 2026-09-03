<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:modelValue']);

const addTemplate = () => {
    emit('update:modelValue', [
        ...props.modelValue,
        {
            title: '',
            notes: '',
            offset_days: 1,
            offset_hours: 0,
            priority: 3,
        },
    ]);
};

const removeTemplate = (index) => {
    emit('update:modelValue', props.modelValue.filter((_, templateIndex) => templateIndex !== index));
};

const updateTemplate = (index, field, value) => {
    const templates = props.modelValue.map((template, templateIndex) => templateIndex === index
        ? { ...template, [field]: value }
        : template);

    emit('update:modelValue', templates);
};

const errorFor = (index, field) => props.errors[`deadline_templates.${index}.${field}`];

const offsetSummary = (template) => {
    const days = Number(template.offset_days) || 0;
    const hours = Number(template.offset_hours) || 0;
    const dayLabel = days === 1 ? '1 giorno' : `${days} giorni`;
    const hourLabel = hours === 1 ? '1 ora' : `${hours} ore`;

    return `${dayLabel} e ${hourLabel}`;
};
</script>

<template>
    <section class="col-span-6 rounded-2xl border border-outline-variant/40 bg-surface-container-low p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="font-headline text-base font-bold text-on-surface">Step automatici della procedura</h3>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-on-surface-variant">
                    Quando crei una scadenza principale, questi step vengono anticipati rispetto alla sua data e assegnati allo stesso utente.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-xl bg-primary-container px-4 text-sm font-bold text-on-primary-container transition hover:bg-primary hover:text-on-primary focus-visible:outline-none"
                @click="addTemplate"
            >
                <PlusIcon class="h-4 w-4" />
                Aggiungi step
            </button>
        </div>

        <div v-if="modelValue.length" class="mt-5 grid gap-4">
            <article v-for="(template, index) in modelValue" :key="template.id ?? `new-${index}`" class="rounded-2xl border border-outline-variant/50 bg-surface-container-lowest p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary">Step {{ index + 1 }}</p>
                    <button
                        type="button"
                        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-error transition hover:bg-error-container focus-visible:outline-none"
                        :aria-label="`Rimuovi step ${index + 1}`"
                        @click="removeTemplate(index)"
                    >
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <InputLabel :for="`step_title_${index}`" value="Titolo *" />
                        <TextInput
                            :id="`step_title_${index}`"
                            :model-value="template.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Es. Verifica documenti"
                            @update:model-value="updateTemplate(index, 'title', $event)"
                        />
                        <InputError :message="errorFor(index, 'title')" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel :for="`step_days_${index}`" value="Giorni prima" />
                        <input
                            :id="`step_days_${index}`"
                            :value="template.offset_days"
                            type="number"
                            min="0"
                            max="3650"
                            class="app-input mt-1 block min-h-[44px] w-full rounded-xl"
                            @input="updateTemplate(index, 'offset_days', Number($event.target.value))"
                        />
                        <InputError :message="errorFor(index, 'offset_days')" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel :for="`step_hours_${index}`" value="Ore prima" />
                        <input
                            :id="`step_hours_${index}`"
                            :value="template.offset_hours"
                            type="number"
                            min="0"
                            max="23"
                            class="app-input mt-1 block min-h-[44px] w-full rounded-xl"
                            @input="updateTemplate(index, 'offset_hours', Number($event.target.value))"
                        />
                        <InputError :message="errorFor(index, 'offset_hours')" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel :for="`step_priority_${index}`" value="Priorità" />
                        <select
                            :id="`step_priority_${index}`"
                            :value="template.priority"
                            class="app-input mt-1 block min-h-[44px] w-full rounded-xl"
                            @change="updateTemplate(index, 'priority', Number($event.target.value))"
                        >
                            <option :value="1">Urgente</option>
                            <option :value="2">Alta</option>
                            <option :value="3">Media</option>
                            <option :value="4">Bassa</option>
                        </select>
                        <InputError :message="errorFor(index, 'priority')" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel :for="`step_notes_${index}`" value="Note" />
                        <textarea
                            :id="`step_notes_${index}`"
                            :value="template.notes"
                            rows="2"
                            class="app-input mt-1 block w-full rounded-xl"
                            placeholder="Indicazioni operative facoltative"
                            @input="updateTemplate(index, 'notes', $event.target.value)"
                        />
                        <InputError :message="errorFor(index, 'notes')" class="mt-1" />
                    </div>
                </div>

                <p class="mt-3 text-xs font-semibold text-on-surface-variant">
                    Verrà impostato {{ offsetSummary(template) }} prima della scadenza principale.
                </p>
            </article>
        </div>

        <p v-else class="mt-5 rounded-xl border border-dashed border-outline-variant px-4 py-5 text-sm text-on-surface-variant">
            Nessuno step configurato. La procedura non genererà scadenze automatiche.
        </p>
    </section>
</template>
