<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import IconButton from '@/Components/IconButton.vue';
import { BuildingOffice2Icon, ChevronRightIcon, PencilSquareIcon, TrashIcon, UsersIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    branches: Array,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters?.search ?? '');
const sortKey = ref(props.filters?.sort ?? 'name');
const sortDir = ref(props.filters?.direction ?? 'asc');
const statusFilter = ref(props.filters?.is_active ?? '');
const canCreate = computed(() => page.props.auth.user?.permissions?.includes('branches.create'));
const canEdit = computed(() => page.props.auth.user?.permissions?.includes('branches.update'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('branches.delete'));

const performSearch = () => {
    router.get(route('branches.index'), {
        search: search.value,
        sort: sortKey.value,
        direction: sortDir.value,
        is_active: statusFilter.value,
    }, { preserveState: true, replace: true });
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    router.get(route('branches.index'), {
        search: search.value,
        sort: key,
        direction: dir,
        is_active: statusFilter.value,
    }, { preserveState: true, replace: true });
};

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
                <div class="mb-6 flex justify-between items-center flex-wrap gap-3">
                    <div class="flex items-center space-x-2 w-full max-w-md">
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="performSearch"
                            placeholder="Cerca filiale..."
                            class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                        />
                        <select
                            v-model="statusFilter"
                            class="rounded-xl border-0 bg-surface-container-high text-sm text-on-surface py-2 px-3 focus:ring-2 focus:ring-primary/25"
                            @change="performSearch"
                        >
                            <option value="">Tutte</option>
                            <option value="1">Attive</option>
                            <option value="0">Inattive</option>
                        </select>
                        <button
                            @click="performSearch"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Cerca
                        </button>
                    </div>

                    <Link
                        v-if="canCreate"
                        :href="route('branches.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Nuova Filiale
                    </Link>
                </div>

                <div class="mb-5 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-white p-5 dark:border-indigo-900/60 dark:from-indigo-950/40 dark:to-gray-900">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-indigo-600 p-2.5 text-white shadow-sm"><BuildingOffice2Icon class="h-6 w-6" /></div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Struttura gerarchica</h3>
                            <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">Ogni filiale accede ai propri clienti e pratiche e a quelli delle filiali sottostanti. Non può vedere dati delle filiali superiori o di altri rami.</p>
                        </div>
                    </div>
                </div>

                <TransitionGroup name="branch-list" tag="div" class="grid gap-3" aria-live="polite">
                    <article
                        v-for="branch in branches"
                        :key="branch.id"
                        class="group relative rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-700"
                        :style="{ marginInlineStart: `${Math.min(branch.depth, 5) * 24}px` }"
                    >
                        <div v-if="branch.depth > 0" class="absolute -start-4 top-1/2 hidden h-px w-4 bg-gray-300 sm:block dark:bg-gray-600" aria-hidden="true"></div>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <ChevronRightIcon v-if="branch.depth > 0" class="h-4 w-4 shrink-0 text-gray-400" aria-hidden="true" />
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate font-semibold text-gray-900 dark:text-white">{{ branch.name }}</h3>
                                        <span v-if="branch.is_main" class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-200">Principale</span>
                                        <span :class="branch.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'" class="rounded-full px-2.5 py-1 text-xs font-medium">{{ branch.is_active ? 'Attiva' : 'Inattiva' }}</span>
                                    </div>
                                    <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">{{ branch.hierarchy_path }}</p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ branch.city }} ({{ branch.province }}) · {{ branch.phone || 'Telefono non indicato' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-2.5 py-2 text-xs text-gray-600 dark:bg-gray-900/60 dark:text-gray-300"><UserGroupIcon class="h-4 w-4" />{{ branch.clients_count }} clienti</span>
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-2.5 py-2 text-xs text-gray-600 dark:bg-gray-900/60 dark:text-gray-300"><UsersIcon class="h-4 w-4" />{{ branch.employees_count }} utenti</span>
                                <IconButton v-if="canEdit" :as="Link" :href="route('branches.edit', branch.id)" tooltip="Modifica" class="min-h-[44px] min-w-[44px] text-indigo-600 dark:text-indigo-400"><PencilSquareIcon class="h-5 w-5" /></IconButton>
                                <IconButton v-if="canDelete && !branch.is_main && branch.children_count === 0" tooltip="Elimina" class="min-h-[44px] min-w-[44px] text-red-600 dark:text-red-400" @click="confirmDelete(branch)"><TrashIcon class="h-5 w-5" /></IconButton>
                            </div>
                        </div>
                    </article>
                </TransitionGroup>

                <div v-if="branches.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Nessuna filiale trovata.</div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>
                Elimina Filiale
            </template>

            <template #content>
                <div class="space-y-3 text-sm text-on-surface-variant">
                    <p>
                        Sei sicuro di voler eliminare <strong class="text-on-surface">{{ branchToDelete?.name }}</strong>?
                    </p>
                    <p>
                        Clienti, pratiche, appuntamenti e utenti assegnati verranno trasferiti automaticamente alla sede padre. L'azione non può essere annullata.
                    </p>
                    <div class="flex flex-wrap gap-2" aria-label="Dati che verranno trasferiti">
                        <span class="rounded-lg bg-surface-container-high px-2.5 py-1.5 text-xs">{{ branchToDelete?.clients_count ?? 0 }} clienti</span>
                        <span class="rounded-lg bg-surface-container-high px-2.5 py-1.5 text-xs">{{ branchToDelete?.practices_count ?? 0 }} pratiche</span>
                        <span class="rounded-lg bg-surface-container-high px-2.5 py-1.5 text-xs">{{ branchToDelete?.appointments_count ?? 0 }} appuntamenti</span>
                        <span class="rounded-lg bg-surface-container-high px-2.5 py-1.5 text-xs">{{ branchToDelete?.employees_count ?? 0 }} utenti</span>
                    </div>
                </div>
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

<style scoped>
.branch-list-enter-active,
.branch-list-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}

.branch-list-enter-from,
.branch-list-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

@media (prefers-reduced-motion: reduce) {
    .branch-list-enter-active,
    .branch-list-leave-active {
        transition: none;
    }
}
</style>
