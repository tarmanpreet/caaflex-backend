<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const form = useForm({
    name: '',
    duration_minutes: 30,
    color: '#3B82F6',
});

const updateColorFromPicker = (event) => {
    form.color = event.target.value;
};

const updateColorFromText = (event) => {
    const value = event.target.value;
    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
        form.color = value;
    }
};

const submitForm = () => {
    form.post(route('practice-types.store'));
};
</script>

<template>
    <AppLayout title="Nuovo Tipo Pratica">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Nuovo Tipo Pratica</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FormSection @submitted="submitForm">
                    <template #title>Nuovo Tipo Pratica</template>
                    <template #description>Crea un nuovo tipo di pratica con nome, durata e colore.</template>

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
                                maxlength="50"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Durata (minuti) -->
                        <div class="col-span-6">
                            <InputLabel for="duration_minutes" value="Durata (minuti)" />
                            <TextInput
                                id="duration_minutes"
                                v-model="form.duration_minutes"
                                type="number"
                                class="mt-1 block w-full"
                                min="5"
                                max="480"
                                required
                            />
                            <InputError :message="form.errors.duration_minutes" class="mt-2" />
                        </div>

                        <!-- Colore -->
                        <div class="col-span-6">
                            <InputLabel for="color" value="Colore" />
                            <div class="mt-1 flex items-center space-x-3">
                                <input
                                    type="color"
                                    :value="form.color"
                                    @input="updateColorFromPicker"
                                    class="h-10 w-14 rounded border border-gray-300 cursor-pointer"
                                />
                                <TextInput
                                    id="color"
                                    :modelValue="form.color"
                                    @update:modelValue="(val) => form.color = val"
                                    @change="updateColorFromText"
                                    type="text"
                                    class="block w-32"
                                    maxlength="7"
                                    placeholder="#3B82F6"
                                />
                                <span
                                    :style="{ backgroundColor: form.color }"
                                    class="inline-block w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700"
                                ></span>
                            </div>
                            <InputError :message="form.errors.color" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <SecondaryButton type="button" @click="router.get(route('practice-types.index'))">
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
