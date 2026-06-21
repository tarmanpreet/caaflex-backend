<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
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

const slotColumns = [
    { key: 'day_of_week', label: 'Giorno' },
    { key: 'time_from', label: 'Dalle' },
    { key: 'time_to', label: 'Alle' },
];

const props = defineProps({
    slots: Array,
    days: Object,
});

const form = useForm({
    day_of_week: '',
    time_from: '09:00',
    time_to: '17:00',
});

const submitForm = () => {
    form.post(route('auto-confirm-slots.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const confirmingDelete = ref(false);
const slotToDelete = ref(null);

const confirmDelete = (slot) => {
    slotToDelete.value = slot;
    confirmingDelete.value = true;
};

const deleteSlot = () => {
    router.delete(route('auto-confirm-slots.destroy', slotToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            confirmingDelete.value = false;
            slotToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Slot Auto-Conferma">
        <template #header>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Configurazione</p>
                <h2 class="mt-2 font-headline text-2xl font-extrabold tracking-tight text-on-surface">Slot Auto-Conferma</h2>
                <p class="mt-1 text-sm text-on-surface-variant">Gli appuntamenti creati in queste fasce orarie verranno confermati automaticamente.</p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Current Slots -->
                <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl ring-1 ring-outline-variant/10 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Slot configurati</h3>
                    <div class="overflow-x-auto">
                        <SortableTable :columns="slotColumns" :rows="slots ?? []" empty-message="Nessuno slot auto-conferma configurato.">
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

                <!-- Add Slot Form -->
                <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-xl ring-1 ring-outline-variant/10 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Aggiungi Slot Auto-Conferma</h3>
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
            <template #title>Elimina Slot Auto-Conferma</template>
            <template #content>
                Sei sicuro di voler eliminare questo slot auto-conferma?
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">Annulla</SecondaryButton>
                <DangerButton class="ml-3" @click="deleteSlot">Elimina</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
