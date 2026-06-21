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
    { key: 'city', label: 'Città' },
    { key: 'province', label: 'Prov.' },
    { key: 'phone', label: 'Telefono', sortable: false },
    { key: 'is_active', label: 'Stato', sortable: false },
];

const props = defineProps({
    branches: Array,
});

const page = usePage();
const canCreate = computed(() => page.props.auth.user?.permissions?.includes('branches.create'));
const canEdit = computed(() => page.props.auth.user?.permissions?.includes('branches.update'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('branches.delete'));

const confirmingDelete = ref(false);
const branchToDelete = ref(null);

const confirmDelete = (branch) => {
    branchToDelete.value = branch;
    confirmingDelete.value = true;
};

const deleteBranch = () => {
    router.delete(route('branches.destroy', branchToDelete.value.id), {
        onFinish: () => {
            confirmingDelete.value = false;
            branchToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Filiali">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Filiali
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Top bar -->
                <div class="mb-6 flex justify-end">
                    <Link
                        v-if="canCreate"
                        :href="route('branches.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Nuova Filiale
                    </Link>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <SortableTable :columns="columns" :rows="branches" empty-message="Nessuna filiale trovata.">
                            <template #cell-name="{ row }">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ row.name }}</span>
                            </template>
                            <template #cell-city="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.city }}</span>
                            </template>
                            <template #cell-province="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.province }}</span>
                            </template>
                            <template #cell-phone="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">{{ row.phone || '—' }}</span>
                            </template>
                            <template #cell-is_active="{ row }">
                                <span
                                    :class="row.is_active
                                        ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    "
                                >
                                    {{ row.is_active ? 'Attiva' : 'Inattiva' }}
                                </span>
                            </template>
                            <template #actions="{ row }">
                                <span class="flex items-center space-x-2">
                                    <IconButton
                                        v-if="canEdit"
                                        :as="Link"
                                        :href="route('branches.edit', row.id)"
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
                Elimina Filiale
            </template>

            <template #content>
                Sei sicuro di voler eliminare questa filiale? L'azione non può essere annullata.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">
                    Annulla
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="deleteBranch"
                >
                    Elimina
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
