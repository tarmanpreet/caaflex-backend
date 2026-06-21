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
});

const form = useForm({
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
    <AppLayout title="Edit Client">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Client</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FormSection @submitted="submitForm">
                    <template #title>
                        Client Information
                    </template>

                    <template #description>
                        Update the client's details.
                    </template>

                    <template #form>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="first_name" value="First Name" />
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
                            <InputLabel for="last_name" value="Last Name" />
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
                            <InputLabel for="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="date_of_birth" value="Date of Birth" />
                            <TextInput
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.date_of_birth" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="fiscal_code" value="Fiscal Code" />
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
                            <InputLabel for="address" value="Address" />
                            <TextInput
                                id="address"
                                v-model="form.address"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.address" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="city" value="City" />
                            <TextInput
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.city" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="province" value="Province" />
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
                            <InputLabel for="postal_code" value="Postal Code" />
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
                            <InputLabel for="notes" value="Notes" />
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
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Save
                        </PrimaryButton>
                    </template>
                </FormSection>
            </div>
        </div>
    </AppLayout>
</template>
