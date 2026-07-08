<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    assignableRoles: Array,
    allPracticeTypes: Array,
    branches: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: props.assignableRoles?.length === 1 ? props.assignableRoles[0] : '',
    is_active: true,
    practice_type_ids: [],
    branch_ids: [],
});

const isEmployee = computed(() => form.role === 'employee');

const submit = () => {
    form.post(route('users.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Nuovo Utente">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <Link :href="route('users.index')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 text-sm">
                    &larr; Utenti
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Nuovo Utente
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Crea Utente</h3>

                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Nome -->
                        <div>
                            <InputLabel for="name" value="Nome" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autocomplete="name"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                                autocomplete="email"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div>
                            <InputLabel for="password" value="Password" />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <!-- Conferma Password -->
                        <div>
                            <InputLabel for="password_confirmation" value="Conferma Password" />
                            <TextInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password_confirmation" class="mt-2" />
                        </div>

                        <!-- Ruolo -->
                        <div>
                            <InputLabel for="role" value="Ruolo" />
                            <select
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full rounded-xl border-0 bg-surface-container-high text-sm text-on-surface py-2 px-3 focus:ring-2 focus:ring-primary/25"
                                required
                            >
                                <option value="" disabled>Seleziona un ruolo...</option>
                                <option v-for="r in assignableRoles" :key="r" :value="r">{{ r }}</option>
                            </select>
                            <InputError :message="form.errors.role" class="mt-2" />
                        </div>

                        <!-- Tipologie Pratiche (solo employee) -->
                        <div v-if="isEmployee" class="md:col-span-2">
                            <InputLabel for="practice_type_ids" value="Tipologie di pratiche gestite" />
                            <Multiselect
                                v-model="form.practice_type_ids"
                                :options="(allPracticeTypes ?? []).map(pt => ({ value: pt.id, label: pt.name }))"
                                mode="tags"
                                :searchable="true"
                                :close-on-select="false"
                                value-prop="value"
                                label="label"
                                track-by="value"
                                placeholder="Seleziona tipologie..."
                                no-options-text="Nessuna tipologia trovata"
                                no-results-text="Nessun risultato"
                                :append-to-body="true"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.practice_type_ids" class="mt-2" />
                        </div>

                        <!-- Filiali (solo employee) -->
                        <div v-if="isEmployee" class="md:col-span-2">
                            <InputLabel for="branch_ids" value="Filiali assegnate" />
                            <Multiselect
                                v-model="form.branch_ids"
                                :options="(branches ?? []).map(b => ({ value: b.id, label: b.name + ' - ' + b.city + ' (' + b.province + ')' }))"
                                mode="tags"
                                :searchable="true"
                                :close-on-select="false"
                                value-prop="value"
                                label="label"
                                track-by="value"
                                placeholder="Seleziona filiali..."
                                no-options-text="Nessuna filiale trovata"
                                no-results-text="Nessun risultato"
                                :append-to-body="true"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.branch_ids" class="mt-2" />
                        </div>

                        <!-- Azioni -->
                        <div class="md:col-span-2 flex justify-end gap-3">
                            <Link :href="route('users.index')">
                                <SecondaryButton type="button">Annulla</SecondaryButton>
                            </Link>
                            <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Crea Utente
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
