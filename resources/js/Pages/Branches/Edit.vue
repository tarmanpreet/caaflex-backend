<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    branch: Object,
});

const form = useForm({
    name: props.branch.name,
    address: props.branch.address,
    city: props.branch.city,
    province: props.branch.province,
    postal_code: props.branch.postal_code,
    phone: props.branch.phone || '',
    vat_number: props.branch.vat_number || '',
    is_active: props.branch.is_active,
});

const submitForm = () => {
    form.put(route('branches.update', props.branch.id));
};
</script>

<template>
    <AppLayout title="Modifica Filiale">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Modifica Filiale</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FormSection @submitted="submitForm">
                    <template #title>Modifica Filiale</template>
                    <template #description>Aggiorna i dati della filiale.</template>

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
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Indirizzo -->
                        <div class="col-span-6">
                            <InputLabel for="address" value="Indirizzo" />
                            <TextInput
                                id="address"
                                v-model="form.address"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.address" class="mt-2" />
                        </div>

                        <!-- Città -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="city" value="Città" />
                            <TextInput
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.city" class="mt-2" />
                        </div>

                        <!-- Provincia -->
                        <div class="col-span-6 sm:col-span-2">
                            <InputLabel for="province" value="Provincia" />
                            <TextInput
                                id="province"
                                v-model="form.province"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                maxlength="2"
                            />
                            <InputError :message="form.errors.province" class="mt-2" />
                        </div>

                        <!-- CAP -->
                        <div class="col-span-6 sm:col-span-2">
                            <InputLabel for="postal_code" value="CAP" />
                            <TextInput
                                id="postal_code"
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                maxlength="10"
                            />
                            <InputError :message="form.errors.postal_code" class="mt-2" />
                        </div>

                        <!-- Telefono -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="phone" value="Telefono" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>

                        <!-- PIVA / CF -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="vat_number" value="PIVA / Codice Fiscale" />
                            <TextInput
                                id="vat_number"
                                v-model="form.vat_number"
                                type="text"
                                class="mt-1 block w-full"
                                maxlength="20"
                            />
                            <InputError :message="form.errors.vat_number" class="mt-2" />
                        </div>

                        <!-- Attiva -->
                        <div class="col-span-6">
                            <label class="flex items-center">
                                <Checkbox v-model:checked="form.is_active" name="is_active" />
                                <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">Attiva</span>
                            </label>
                        </div>
                    </template>

                    <template #actions>
                        <SecondaryButton type="button" @click="router.get(route('branches.index'))">
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
