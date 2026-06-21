<script setup>
import { ref, computed } from 'vue';
import { formatDateTime } from '@/utils/date';
import { useForm, usePage, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    appointment: Object,
    users: Array,
    statuses: Array,
    branches: Array,
});

const page = usePage();

const canUpdate = computed(() => page.props.auth.user?.permissions?.includes('appointments.update'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('appointments.delete'));

const form = useForm({
    status: props.appointment?.status ?? '',
    assigned_user_id: props.appointment?.assigned_user_id ?? null,
    notes: props.appointment?.notes ?? '',
    branch_id: props.appointment?.branch_id ?? null,
});

const statusBadgeClass = (status) => {
    const map = {
        da_confermare: 'bg-yellow-100 text-yellow-800',
        confermato: 'bg-blue-100 text-blue-800',
        completato: 'bg-green-100 text-green-800',
        cancellato: 'bg-gray-100 text-gray-800',
    };

    return map[status] ?? 'bg-gray-100 text-gray-800';
};

const formatStatus = (status) => {
    if (!status) return '';

    return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const submit = () => {
    form.patch(route('appointments.update', props.appointment.id), { preserveScroll: true });
};

const confirmingDelete = ref(false);

const deleteAppointment = () => {
    router.delete(route('appointments.destroy', props.appointment.id));
};
</script>

<template>
    <AppLayout title="Dettaglio Appuntamento">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Appuntamento #{{ appointment.id }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Dettagli Appuntamento</h3>
                        <span :class="['px-3 py-1 rounded-full text-xs font-semibold uppercase', statusBadgeClass(appointment.status)]">
                            {{ formatStatus(appointment.status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel value="Cliente" />
                            <div class="mt-1">
                                <Link
                                    v-if="appointment.client"
                                    :href="route('clients.show', appointment.client.id)"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                >
                                    {{ appointment.client.first_name }} {{ appointment.client.last_name }}
                                </Link>
                                <p v-else class="text-sm text-gray-500 dark:text-gray-400">—</p>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Tipo Pratica" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ appointment.practice_type?.name ?? '—' }}</p>
                        </div>

                        <div>
                            <InputLabel value="Data/Ora" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDateTime(appointment.scheduled_at) }}</p>
                        </div>

                        <div>
                            <InputLabel value="Durata" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ appointment.duration_minutes }} min</p>
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel value="Note" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ appointment.notes || '—' }}</p>
                        </div>

                        <div>
                            <InputLabel value="Filiale" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ appointment.branch ? appointment.branch.name + ' - ' + appointment.branch.city + ' (' + appointment.branch.province + ')' : '—' }}
                            </p>
                        </div>

                        <div>
                            <InputLabel value="Creato da" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ appointment.creator?.name ?? '—' }}</p>
                        </div>

                        <div>
                            <InputLabel value="Pratica collegata" />
                            <div class="mt-1">
                                <Link
                                    v-if="appointment.practice"
                                    :href="route('practices.show', appointment.practice.id)"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                >
                                    Pratica #{{ appointment.practice.id }}
                                </Link>
                                <p v-else-if="appointment.status !== 'confermato'" class="text-sm text-gray-500 dark:text-gray-400">
                                    Pratica creata alla conferma
                                </p>
                                <p v-else class="text-sm text-gray-500 dark:text-gray-400">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="canUpdate" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Aggiorna Appuntamento</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="status" value="Stato" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option v-for="status in statuses" :key="status" :value="status">{{ formatStatus(status) }}</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="assigned_user_id" value="Assegnato a" />
                            <select
                                id="assigned_user_id"
                                v-model="form.assigned_user_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option :value="null">Nessuno</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <InputError :message="form.errors.assigned_user_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Filiale" />
                            <Multiselect
                                v-model="form.branch_id"
                                :options="[{ value: null, label: 'Nessuna' }, ...(branches ?? []).map(b => ({ value: b.id, label: b.name + ' - ' + b.city + ' (' + b.province + ')' }))]"
                                mode="single"
                                :searchable="true"
                                value-prop="value"
                                label="label"
                                track-by="label"
                                placeholder="Seleziona filiale..."
                                no-options-text="Nessuna filiale trovata"
                                no-results-text="Nessun risultato"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.branch_id" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="notes" value="Note" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            ></textarea>
                            <InputError :message="form.errors.notes" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <SecondaryButton type="button" @click="router.get(route('appointments.index'))">
                            Torna alla lista
                        </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">
                            Salva modifiche
                        </PrimaryButton>
                    </div>
                </div>

                <div v-if="canDelete" class="flex justify-end">
                    <DangerButton @click="confirmingDelete = true">
                        Elimina Appuntamento
                    </DangerButton>
                </div>
            </div>
        </div>

        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>
                Elimina Appuntamento
            </template>

            <template #content>
                Sei sicuro di voler eliminare questo appuntamento? L'azione non può essere annullata.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">
                    Annulla
                </SecondaryButton>
                <DangerButton class="ms-3" @click="deleteAppointment">
                    Elimina
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
