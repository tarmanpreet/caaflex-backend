<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SortableTable from '@/Components/SortableTable.vue';
import IconButton from '@/Components/IconButton.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';

const availabilityColumns = [
    { key: 'day_of_week', label: 'Giorno' },
    { key: 'time_from', label: 'Dalle' },
    { key: 'time_to', label: 'Alle' },
];

const props = defineProps({
    targetUser: Object,
    availabilities: Array,
    days: Object,
});

const form = useForm({
    day_of_week: '',
    time_from: '09:00',
    time_to: '17:00',
});

const submitForm = () => {
    form.post(route('users.availabilities.store', props.targetUser.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const confirmingDelete = ref(false);
const availabilityToDelete = ref(null);

const confirmDelete = (avail) => {
    availabilityToDelete.value = avail;
    confirmingDelete.value = true;
};

const deleteAvailability = () => {
    router.delete(route('users.availabilities.destroy', availabilityToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            confirmingDelete.value = false;
            availabilityToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout :title="'Disponibilità di ' + (targetUser?.name ?? '')">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <Link :href="route('users.show', targetUser.id)" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 text-sm">
                    &larr; Torna all'utente
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Disponibilità di {{ targetUser?.name }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Current Availability Slots -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Fasce orarie configurate</h3>
                    <div class="overflow-x-auto">
                        <SortableTable :columns="availabilityColumns" :rows="availabilities ?? []" empty-message="Nessuna disponibilità configurata.">
                            <template #cell-day_of_week="{ row }">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ days[row.day_of_week] }}</span>
                            </template>
                            <template #cell-time_from="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.time_from?.substring(0, 5) }}</span>
                            </template>
                            <template #cell-time_to="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.time_to?.substring(0, 5) }}</span>
                            </template>
                            <template #actions="{ row }">
                                <IconButton
                                    tooltip="Elimina"
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                    @click="confirmDelete(row)"
                                >
                                    <TrashIcon class="w-5 h-5" />
                                </IconButton>
                            </template>
                        </SortableTable>
                    </div>
                </div>

                <!-- Add Availability Form -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Aggiungi Disponibilità</h3>
                    <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">

                        <!-- Day -->
                        <div>
                            <InputLabel for="day_of_week" value="Giorno" />
                            <select
                                id="day_of_week"
                                v-model="form.day_of_week"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 shadow-sm"
                                required
                            >
                                <option value="" disabled>Seleziona giorno</option>
                                <option v-for="(label, key) in days" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <InputError :message="form.errors.day_of_week" class="mt-2" />
                        </div>

                        <!-- Time From -->
                        <div>
                            <InputLabel for="time_from" value="Dalle" />
                            <input
                                id="time_from"
                                v-model="form.time_from"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 shadow-sm"
                                required
                            />
                            <InputError :message="form.errors.time_from" class="mt-2" />
                        </div>

                        <!-- Time To -->
                        <div>
                            <InputLabel for="time_to" value="Alle" />
                            <input
                                id="time_to"
                                v-model="form.time_to"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 shadow-sm"
                                required
                            />
                            <InputError :message="form.errors.time_to" class="mt-2" />
                        </div>

                        <!-- Submit -->
                        <div>
                            <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Aggiungi
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>Elimina Disponibilità</template>
            <template #content>
                Sei sicuro di voler eliminare questa fascia oraria?
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">Annulla</SecondaryButton>
                <DangerButton class="ml-3" @click="deleteAvailability">Elimina</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
