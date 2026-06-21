<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SortableTable from '@/Components/SortableTable.vue';
import IconButton from '@/Components/IconButton.vue';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const columns = [
    { key: 'name', label: 'Nome' },
    { key: 'procedure_type_id', label: 'Tipo Pratica' },
    { key: 'default_notes', label: 'Note Default' },
    { key: 'deadline_days', label: 'Giorni alla Scadenza' },
];

const props = defineProps({
    procedures: Array,
    procedureTypes: Array,
});

const page = usePage();
const canCreate = computed(() => page.props.auth.user?.permissions?.includes('procedures.create'));
const canEdit = computed(() => page.props.auth.user?.permissions?.includes('procedures.update'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('procedures.delete'));

const confirmingDelete = ref(false);
const procedureToDelete = ref(null);

// Create a lookup map for procedure types
const procedureTypeMap = computed(() => {
    const map = {};
    props.procedureTypes.forEach(type => {
        map[type.id] = type.name;
    });
    return map;
});

const getProcedureTypeName = (procedureTypeId) => {
    return procedureTypeMap.value[procedureTypeId] || '—';
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const confirmDelete = (procedure) => {
    procedureToDelete.value = procedure;
    confirmingDelete.value = true;
};

const deleteProcedure = () => {
    router.delete(route('procedures.destroy', procedureToDelete.value.id), {
        onFinish: () => {
            confirmingDelete.value = false;
            procedureToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Procedure">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Procedure
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Top bar -->
                <div class="mb-6 flex justify-end">
                    <Link
                        v-if="canCreate"
                        :href="route('procedures.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Nuova Procedura
                    </Link>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <SortableTable :columns="columns" :rows="procedures" empty-message="Nessuna procedura trovata.">
                            <template #cell-name="{ row }">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ row.name }}</span>
                            </template>
                            <template #cell-procedure_type_id="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ getProcedureTypeName(row.procedure_type_id) }}</span>
                            </template>
                            <template #cell-default_notes="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.default_notes || '—' }}</span>
                            </template>
                            <template #cell-deadline_days="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.deadline_days ? row.deadline_days + ' giorni' : '—' }}</span>
                            </template>
                            <template #actions="{ row }">
                                <span class="flex items-center space-x-2">
                                    <IconButton
                                        v-if="canEdit"
                                        :as="Link"
                                        :href="route('procedures.edit', row.id)"
                                        tooltip="Modifica"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                    >
                                        <PencilSquareIcon class="w-5 h-5" />
                                    </IconButton>
                                    <IconButton
                                        v-if="canDelete"
                                        tooltip="Elimina"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        @click="confirmDelete(row)"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                    </IconButton>
                                </span>
                            </template>
                        </SortableTable>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>
                Elimina Procedura
            </template>

            <template #content>
                <p>Sei sicuro di voler eliminare questa procedura?</p>
                <p v-if="procedureToDelete?.pratiche_count > 0" class="mt-2 text-red-600 dark:text-red-400 font-medium">
                    Attenzione: {{ procedureToDelete.pratiche_count }} pratiche collegate.
                </p>
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">
                    Annulla
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="deleteProcedure"
                >
                    Elimina
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>