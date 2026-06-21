<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    procedureTypes: Array,
});

const form = useForm({
    name: '',
    procedure_type_id: '',
    default_notes: '',
    deadline_days: '',
});

const submitForm = () => {
    form.post(route('procedures.store'));
};
</script>

<template>
    <AppLayout title="Nuova Procedura">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Nuova Procedura</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FormSection @submitted="submitForm">
                    <template #title>Nuova Procedura</template>
                    <template #description>Crea una nuova procedura associata a un tipo pratica.</template>

                    <template #form>
                        <!-- Nome -->
                        <div class="col-span-6">
                            <InputLabel for="name" value="Nome" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                maxlength="100"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Tipo Pratica -->
                        <div class="col-span-6">
                            <InputLabel for="procedure_type_id" value="Tipo Pratica" />
                            <select
                                id="procedure_type_id"
                                v-model="form.procedure_type_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-lg shadow-sm transition-colors"
                                required
                            >
                                <option value="">Seleziona un tipo pratica</option>
                                <option v-for="type in procedureTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.procedure_type_id" class="mt-2" />
                        </div>

                        <!-- Note Default -->
                        <div class="col-span-6">
                            <InputLabel for="default_notes" value="Note Default" />
                            <textarea
                                id="default_notes"
                                v-model="form.default_notes"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-lg shadow-sm transition-colors"
                                rows="3"
                                placeholder="Note predefinite per questa procedura..."
                            ></textarea>
                            <InputError :message="form.errors.default_notes" class="mt-2" />
                        </div>

                        <!-- Giorni alla Scadenza -->
                        <div class="col-span-6">
                            <InputLabel for="deadline_days" value="Giorni alla Scadenza (opzionale)" />
                            <TextInput
                                id="deadline_days"
                                v-model="form.deadline_days"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                placeholder="Es: 30 (giorni prima della scadenza)"
                            />
                            <p class="mt-1 text-xs text-gray-500">Numero di giorni prima della scadenza della pratica in cui questa procedura deve essere completata</p>
                            <InputError :message="form.errors.deadline_days" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <SecondaryButton type="button" @click="router.get(route('procedures.index'))">
                            Annulla
                        </SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Salva
                        </PrimaryButton>
                    </template>
                </FormSection>
            </div>
        </div>
    </AppLayout>
</template>