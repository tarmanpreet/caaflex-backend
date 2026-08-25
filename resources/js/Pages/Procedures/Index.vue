<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SortableTable from '@/Components/SortableTable.vue';
import IconButton from '@/Components/IconButton.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import { MagnifyingGlassIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const columns = [
    { key: 'name', label: 'Nome' },
    { key: 'procedure_type_id', label: 'Tipo Pratica' },
    { key: 'default_notes', label: 'Note Default', sortable: false },
    { key: 'deadline_days', label: 'Giorni alla Scadenza' },
];

const props = defineProps({
    procedures: Array,
    procedureTypes: Array,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters?.search ?? '');
const sortKey = ref(props.filters?.sort ?? 'name');
const sortDir = ref(props.filters?.direction ?? 'asc');
const canCreate = computed(() => page.props.auth.user?.permissions?.includes('procedures.create'));
const canEdit = computed(() => page.props.auth.user?.permissions?.includes('procedures.update'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('procedures.delete'));

const performSearch = () => {
    router.get(route('procedures.index'), { search: search.value, sort: sortKey.value, direction: sortDir.value }, { preserveState: true, replace: true });
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    router.get(route('procedures.index'), { search: search.value, sort: key, direction: dir }, { preserveState: true, replace: true });
};

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
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Configurazione / Procedure</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Procedure</h1>
                    <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Configura procedure, note predefinite e tempistiche delle scadenze.</p>
                </div>
                <Link v-if="canCreate" :href="route('procedures.create')" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-bold text-on-primary shadow-lg shadow-primary/20 transition hover:bg-primary-dim">
                    <PlusIcon class="h-5 w-5" />
                    Nuova procedura
                </Link>
            </div>
        </template>

        <UiSectionCard title="Archivio procedure" eyebrow="Vista operativa" :padded="false">
            <div class="border-b border-outline-variant/35 bg-surface-container-lowest p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative min-w-0 flex-1 sm:max-w-xl">
                        <label for="procedure-search" class="sr-only">Cerca procedure</label>
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                        <input
                            id="procedure-search"
                            v-model="search"
                            type="search"
                            @keyup.enter="performSearch"
                            placeholder="Cerca procedura…"
                            class="h-11 w-full rounded-xl border-0 bg-surface-container-high pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                        >
                    </div>
                    <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-surface-container-high px-4 text-sm font-semibold text-on-surface transition hover:bg-surface-container-highest" @click="performSearch">Cerca</button>
                </div>
            </div>

            <SortableTable
                            :columns="columns"
                            :rows="procedures"
                            :controlled="true"
                            :sort-key="sortKey"
                            :sort-dir="sortDir"
                            empty-message="Nessuna procedura trovata."
                            @sort="onSort"
                        >
                            <template #cell-name="{ row }">
                                <span class="font-medium text-on-surface">{{ row.name }}</span>
                            </template>
                            <template #cell-procedure_type_id="{ row }">
                                <span class="text-on-surface-variant">{{ getProcedureTypeName(row.procedure_type_id) }}</span>
                            </template>
                            <template #cell-default_notes="{ row }">
                                <span class="text-on-surface-variant">{{ row.default_notes || '—' }}</span>
                            </template>
                            <template #cell-deadline_days="{ row }">
                                <span class="text-on-surface-variant">{{ row.deadline_days ? row.deadline_days + ' giorni' : '—' }}</span>
                            </template>
                            <template #actions="{ row }">
                                <span class="flex items-center justify-end gap-2">
                                    <IconButton
                                        v-if="canEdit"
                                        :as="Link"
                                        :href="route('procedures.edit', row.id)"
                                        tooltip="Modifica"
                                        class="rounded-xl bg-primary-container p-2 text-on-primary-container transition hover:bg-primary/15"
                                    >
                                        <PencilSquareIcon class="w-5 h-5" />
                                    </IconButton>
                                    <IconButton
                                        v-if="canDelete"
                                        tooltip="Elimina"
                                        class="rounded-xl bg-error-container/30 p-2 text-error transition hover:bg-error-container/50"
                                        @click="confirmDelete(row)"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                    </IconButton>
                                </span>
                            </template>
            </SortableTable>
        </UiSectionCard>

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
