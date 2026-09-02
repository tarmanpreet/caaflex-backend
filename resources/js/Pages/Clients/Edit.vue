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
    client: Object,
    branches: Array,
});

const form = useForm({
    branch_id: props.client.branch_id ?? props.branches?.[0]?.id ?? null,
    first_name: props.client.first_name ?? '',
    last_name: props.client.last_name ?? '',
    phone: props.client.phone ?? '',
    date_of_birth: props.client.date_of_birth ?? '',
    fiscal_code: props.client.fiscal_code ?? '',
    email: props.client.email ?? '',
    address: props.client.address ?? '',
    city: props.client.city ?? '',
    province: props.client.province ?? '',
    postal_code: props.client.postal_code ?? '',
    notes: props.client.notes ?? '',
});

const submitForm = () => {
    form.put(route('clients.update', props.client.id));
};
</script>

<template>
    <AppLayout title="Modifica cliente">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Modifica cliente</h2>
        </template>
        <div class="py-12">
            <div class="w-full">
                <FormSection @submitted="submitForm">
                    <template #title>
                        Informazioni cliente
                    </template>

                    <template #description>
                        Aggiorna i dati del cliente.
                    </template>

                    <template #form>
                        <div v-if="branches?.length" class="col-span-6">
                            <InputLabel for="branch_id" value="Filiale" />
                            <select id="branch_id" v-model="form.branch_id" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                            </select>
                            <InputError :message="form.errors.branch_id" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="first_name" value="Nome" />
                            <TextInput
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.first_name" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="last_name" value="Cognome" />
                            <TextInput
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.last_name" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="phone" value="Telefono" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="date_of_birth" value="Data di nascita" />
                            <TextInput
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.date_of_birth" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="fiscal_code" value="Codice fiscale" />
                            <TextInput
                                id="fiscal_code"
                                v-model="form.fiscal_code"
                                type="text"
                                class="mt-1 block w-full"
                                maxlength="16"
                            />
                            <InputError :message="form.errors.fiscal_code" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <div class="col-span-6">
                            <InputLabel for="address" value="Indirizzo" />
                            <TextInput
                                id="address"
                                v-model="form.address"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.address" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="city" value="Città" />
                            <TextInput
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.city" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="province" value="Provincia" />
                            <TextInput
                                id="province"
                                v-model="form.province"
                                type="text"
                                class="mt-1 block w-full"
                                maxlength="2"
                            />
                            <InputError :message="form.errors.province" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="postal_code" value="CAP" />
                            <TextInput
                                id="postal_code"
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1 block w-full"
                                maxlength="5"
                            />
                            <InputError :message="form.errors.postal_code" class="mt-2" />
                        </div>

                        <div class="col-span-6">
                            <InputLabel for="notes" value="Note" />
                            <TextInput
                                id="notes"
                                v-model="form.notes"
                                as="textarea"
                                rows="3"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.notes" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <SecondaryButton type="button" @click="router.get(route('clients.show', client.id))">
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
