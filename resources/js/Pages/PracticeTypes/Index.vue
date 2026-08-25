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
    { key: 'duration_minutes', label: 'Durata (minuti)' },
    { key: 'color', label: 'Colore', sortable: false }
];

const props = defineProps({
    types: Array,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters?.search ?? '');
const sortKey = ref(props.filters?.sort ?? 'name');
const sortDir = ref(props.filters?.direction ?? 'asc');
const canCreate = computed(() => page.props.auth.user?.permissions?.includes('practice-types.create'));
const canEdit = computed(() => page.props.auth.user?.permissions?.includes('practice-types.update'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('practice-types.delete'));

const performSearch = () => {
    router.get(route('practice-types.index'), { search: search.value, sort: sortKey.value, direction: sortDir.value }, { preserveState: true, replace: true });
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    router.get(route('practice-types.index'), { search: search.value, sort: key, direction: dir }, { preserveState: true, replace: true });
};

const confirmingDelete = ref(false);
const typeToDelete = ref(null);

const confirmDelete = (type) => {
    typeToDelete.value = type;
    confirmingDelete.value = true;
};

const deleteType = () => {
    router.delete(route('practice-types.destroy', typeToDelete.value.id), {
        onFinish: () => {
            confirmingDelete.value = false;
            typeToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Tipi Pratica">
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Configurazione / Tipi pratica</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Tipi pratica</h1>
                    <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Gestisci le categorie utilizzate per organizzare pratiche e appuntamenti.</p>
                </div>
                <Link v-if="canCreate" :href="route('practice-types.create')" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-bold text-on-primary shadow-lg shadow-primary/20 transition hover:bg-primary-dim">
                    <PlusIcon class="h-5 w-5" />
                    Nuovo tipo
                </Link>
            </div>
        </template>

        <UiSectionCard title="Archivio tipi pratica" eyebrow="Vista operativa" :padded="false">
            <div class="border-b border-outline-variant/35 bg-surface-container-lowest p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative min-w-0 flex-1 sm:max-w-xl">
                        <label for="practice-type-search" class="sr-only">Cerca tipi pratica</label>
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                        <input
                            id="practice-type-search"
                            v-model="search"
                            type="search"
                            @keyup.enter="performSearch"
                            placeholder="Cerca tipo pratica…"
                            class="h-11 w-full rounded-xl border-0 bg-surface-container-high pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                        >
                    </div>
                    <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-surface-container-high px-4 text-sm font-semibold text-on-surface transition hover:bg-surface-container-highest" @click="performSearch">Cerca</button>
                </div>
            </div>

            <SortableTable
                            :columns="columns"
                            :rows="types"
                            :controlled="true"
                            :sort-key="sortKey"
                            :sort-dir="sortDir"
                            empty-message="Nessun tipo pratica trovato."
                            @sort="onSort"
                        >
                            <template #cell-name="{ row }">
                                <span class="font-medium text-on-surface">{{ row.name }}</span>
                            </template>
                            <template #cell-duration_minutes="{ row }">
                                <span class="text-on-surface-variant">{{ row.duration_minutes }}</span>
                            </template>
                            <template #cell-color="{ row }">
                                <span class="inline-flex items-center gap-2 text-on-surface-variant">
                                    <span :style="{ backgroundColor: row.color }" class="inline-block h-4 w-4 rounded-full border border-outline-variant"></span>
                                    {{ row.color }}
                                </span>
                            </template>
                            <template #actions="{ row }">
                                <span class="flex items-center justify-end gap-2">
                                    <IconButton
                                        v-if="canEdit"
                                        :as="Link"
                                        :href="route('practice-types.edit', row.id)"
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
                Elimina Tipo Pratica
            </template>

            <template #content>
                Sei sicuro di voler eliminare questo tipo pratica?
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">
                    Annulla
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="deleteType"
                >
                    Elimina
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
