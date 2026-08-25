<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { formatDate } from '@/utils/date.js';
import AppLayout from '@/Layouts/AppLayout.vue';
import SortableTable from '@/Components/SortableTable.vue';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import { EyeIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';

const columns = [
    { key: 'first_name', label: 'Full Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'date_of_birth', label: 'Date of Birth' },
    { key: 'fiscal_code', label: 'Fiscal Code' },
    { key: 'city', label: 'City' },
    { key: 'branch', label: 'Filiale', sortable: false },
];

const props = defineProps({
    clients: Object,
    filters: Object,
    branches: Array,
});

const page = usePage();
const search = ref(props.filters?.search ?? '');
const sortKey = ref(props.filters?.sort ?? 'last_name');
const sortDir = ref(props.filters?.direction ?? 'asc');
const branchFilter = ref(props.filters?.branch_id ?? '');

const canCreate = computed(() => page.props.auth.user?.permissions?.includes('clients.create'));

// Search
const performSearch = () => {
    router.get(route('clients.index'), { search: search.value, branch_id: branchFilter.value, sort: sortKey.value, direction: sortDir.value }, { preserveState: true, replace: true });
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    router.get(route('clients.index'), { search: search.value, branch_id: branchFilter.value, sort: key, direction: dir }, { preserveState: true, replace: true });
};

</script>

<template>
    <AppLayout title="Clienti">
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Workspace / Clienti</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Gestione clienti</h1>
                    <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Consulta e gestisci l’anagrafica dei clienti visibili nella tua rete.</p>
                </div>
                <Link v-if="canCreate" :href="route('clients.create')" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-bold text-on-primary shadow-lg shadow-primary/20 transition hover:bg-primary-dim">
                    <PlusIcon class="h-5 w-5" />
                    Nuovo cliente
                </Link>
            </div>
        </template>

        <UiSectionCard title="Archivio clienti" eyebrow="Vista operativa" :padded="false">
            <div class="border-b border-outline-variant/35 bg-surface-container-lowest p-4 sm:p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row lg:max-w-3xl">
                        <div class="relative min-w-0 flex-1">
                            <label for="client-search" class="sr-only">Cerca clienti</label>
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                        <input
                            id="client-search"
                            v-model="search"
                            type="search"
                            @keyup.enter="performSearch"
                            placeholder="Cerca per nome, codice fiscale o città…"
                            class="h-11 w-full rounded-xl border-0 bg-surface-container-high pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                        >
                        </div>
                        <select v-if="branches?.length > 1" v-model="branchFilter" class="h-11 rounded-xl border-0 bg-surface-container-high px-3 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" @change="performSearch">
                            <option value="">Tutte le filiali visibili</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                    </div>
                    <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-surface-container-high px-4 text-sm font-semibold text-on-surface transition hover:bg-surface-container-highest" @click="performSearch">Cerca</button>
                </div>
            </div>

            <SortableTable
                            :columns="columns"
                            :rows="clients.data"
                            :controlled="true"
                            :sort-key="sortKey"
                            :sort-dir="sortDir"
                            empty-message="Nessun cliente trovato."
                            @sort="onSort"
                        >
                            <template #cell-first_name="{ row }">
                                <Link :href="route('clients.show', row.id)" class="font-semibold text-primary transition hover:text-primary-dim">
                                    {{ row.first_name }} {{ row.last_name }}
                                </Link>
                            </template>
                            <template #cell-date_of_birth="{ row }">
                                <span class="text-on-surface-variant">
                                    {{ formatDate(row.date_of_birth) }}
                                </span>
                            </template>
                            <template #cell-branch="{ row }">
                                <span class="inline-flex rounded-full bg-primary-container px-2.5 py-1 text-xs font-medium text-on-primary-container">
                                    {{ row.branch?.name || '—' }}
                                </span>
                            </template>
                            <template #actions="{ row }">
                                <IconButton :as="Link" :href="route('clients.show', row.id)" tooltip="Visualizza" class="rounded-xl bg-surface-container-low p-2 text-primary transition hover:bg-primary-container">
                                    <EyeIcon class="w-5 h-5" />
                                </IconButton>
                            </template>
            </SortableTable>

                <div v-if="clients.links && clients.links.length > 3" class="flex justify-end p-5">
                    <Pagination :links="clients.links" />
                </div>
        </UiSectionCard>
    </AppLayout>
</template>
