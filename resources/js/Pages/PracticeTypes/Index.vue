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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Tipi Pratica
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Top bar -->
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex items-center space-x-2 w-full max-w-md">
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="performSearch"
                            placeholder="Cerca tipo pratica..."
                            class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                        />
                        <button
                            @click="performSearch"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Cerca
                        </button>
                    </div>

                    <Link
                        v-if="canCreate"
                        :href="route('practice-types.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Nuovo Tipo
                    </Link>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
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
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ row.name }}</span>
                            </template>
                            <template #cell-duration_minutes="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.duration_minutes }}</span>
                            </template>
                            <template #cell-color="{ row }">
                                <span class="inline-flex items-center text-gray-500 dark:text-gray-400">
                                    <span :style="{ backgroundColor: row.color }" class="inline-block w-4 h-4 rounded-full mr-2 border border-gray-200 dark:border-gray-700"></span>
                                    {{ row.color }}
                                </span>
                            </template>
                            <template #actions="{ row }">
                                <span class="flex items-center space-x-2">
                                    <IconButton
                                        v-if="canEdit"
                                        :as="Link"
                                        :href="route('practice-types.edit', row.id)"
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
