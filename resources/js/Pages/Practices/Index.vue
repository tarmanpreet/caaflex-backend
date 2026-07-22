<script setup>
import { computed, ref } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import SortableTable from '@/Components/SortableTable.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatusBadge from '@/Components/ui/UiStatusBadge.vue';
import { EyeIcon, FunnelIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    practices: Object,
    filters: Object,
    summary: Object,
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'client.first_name', label: 'Cliente', sortable: false },
    { key: 'type', label: 'Tipo' },
    { key: 'status', label: 'Stato' },
    { key: 'reference_year', label: 'Anno' },
    { key: 'assigned_users', label: 'Assegnati', sortable: false },
];

const page = usePage();
const search = ref(props.filters?.search ?? '');
const sortKey = ref(props.filters?.sort ?? 'id');
const sortDir = ref(props.filters?.direction ?? 'desc');
const statusFilter = ref(props.filters?.status ?? '');
const canCreate = computed(() => page.props.auth.user?.permissions?.includes('practices.create'));

const performSearch = () => {
    router.get(route('practices.index'), {
        search: search.value,
        sort: sortKey.value,
        direction: sortDir.value,
        status: statusFilter.value,
    }, { preserveState: true, replace: true });
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    router.get(route('practices.index'), {
        search: search.value,
        sort: key,
        direction: dir,
        status: statusFilter.value,
    }, { preserveState: true, replace: true });
};

const filterByStatus = (status) => {
    statusFilter.value = status;
    performSearch();
};

const activeStatus = computed(() => props.filters?.status ?? '');

const formatStatus = (status) => status ? status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : '';

const statCards = computed(() => [
    { label: 'Totale', value: props.summary?.total ?? 0, tone: 'nuova', status: '' },
    { label: 'In lavorazione', value: props.summary?.active ?? 0, tone: 'in_lavorazione', status: 'in_lavorazione' },
    { label: 'In attesa documenti', value: props.summary?.pending ?? 0, tone: 'in_attesa_documenti', status: 'in_attesa_documenti' },
    { label: 'Completate', value: props.summary?.complete ?? 0, tone: 'completata', status: 'completata' },
]);
</script>

<template>
    <AppLayout title="Pratiche">
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Workspace / Pratiche</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Gestione pratiche</h1>
                    <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Elenco riallineato al template editoriale con header, insight rapidi e tabella premium.</p>
                </div>

                <Link
                    v-if="canCreate"
                    :href="route('practices.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-br from-primary to-primary-dim px-5 py-3 text-sm font-bold text-on-primary shadow-[0px_20px_40px_rgba(0,86,210,0.18)]"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nuova pratica
                </Link>
            </div>
        </template>

        <div class="space-y-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    :class="[
                        'rounded-[1.5rem] bg-surface-container-lowest p-5 shadow-[0px_20px_40px_rgba(12,15,16,0.06)] ring-1 ring-outline-variant/10 transition',
                        activeStatus === card.status ? 'ring-2 ring-primary/50' : '',
                    ]"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">{{ card.label }}</p>
                        <button
                            type="button"
                            @click="filterByStatus(card.status)"
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2 py-1 text-[9px] font-bold uppercase tracking-[0.18em] transition',
                                activeStatus === card.status
                                    ? 'bg-primary text-on-primary'
                                    : 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest',
                            ]"
                            :title="activeStatus === card.status ? 'Filtro attivo' : 'Filtra per ' + card.label"
                        >
                            <FunnelIcon class="h-3 w-3" />
                            <span>{{ activeStatus === card.status ? 'Attivo' : 'Filtra' }}</span>
                        </button>
                    </div>
                    <div class="mt-4 flex items-end justify-between gap-4">
                        <p class="font-headline text-3xl font-extrabold tracking-tight text-on-surface">{{ card.value }}</p>
                        <UiStatusBadge :label="card.label" :status="card.tone" size="sm" />
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                <UiSectionCard title="Archivio pratiche" eyebrow="Ricerca e tabella" :padded="false">
                    <template #actions>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative min-w-[260px] flex-1">
                                <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cerca pratiche..."
                                    class="h-11 w-full rounded-2xl border-0 bg-surface-container-high pl-11 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                                    @keyup.enter="performSearch"
                                >
                            </div>
                            <select
                                v-model="statusFilter"
                                class="h-11 rounded-2xl border-0 bg-surface-container-high px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary/25"
                                @change="performSearch"
                            >
                                <option value="">Tutti gli stati</option>
                                <option value="nuova">Nuova</option>
                                <option value="in_lavorazione">In lavorazione</option>
                                <option value="in_attesa_documenti">In attesa documenti</option>
                                <option value="completata">Completata</option>
                                <option value="annullata">Annullata</option>
                                <option value="sospesa">Sospesa</option>
                            </select>
                            <button @click="performSearch" class="rounded-2xl bg-surface-container-high px-4 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container-highest">Cerca</button>
                        </div>
                    </template>

                    <SortableTable
                        :columns="columns"
                        :rows="practices.data"
                        :controlled="true"
                        :sort-key="sortKey"
                        :sort-dir="sortDir"
                        empty-message="Nessuna pratica trovata."
                        @sort="onSort"
                    >
                        <template #cell-id="{ row }">
                            <Link
                                :href="route('practices.show', row.id)"
                                prefetch
                                class="inline-flex min-h-[44px] items-center rounded-lg font-semibold text-primary underline-offset-4 transition hover:text-primary-dim hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                                :aria-label="`Apri pratica ${row.id}`"
                            >
                                #{{ row.id }}
                            </Link>
                        </template>
                        <template #[`cell-client.first_name`]="{ row }">
                            <Link :href="route('clients.show', row.client.id)" class="font-semibold text-on-surface transition hover:text-primary">
                                {{ row.client.first_name }} {{ row.client.last_name }}
                            </Link>
                        </template>
                        <template #cell-type="{ row }">
                            <span class="rounded-full bg-surface-container-high px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-on-surface-variant">{{ row.type }}</span>
                        </template>
                        <template #cell-status="{ row }">
                            <UiStatusBadge :label="formatStatus(row.status)" :status="row.status" />
                        </template>
                        <template #cell-reference_year="{ row }">
                            <span class="font-medium text-on-surface-variant">{{ row.reference_year || '—' }}</span>
                        </template>
                        <template #cell-assigned_users="{ row }">
                            <span class="text-sm text-on-surface-variant">{{ row.assigned_users?.map((user) => user.name).join(', ') || '—' }}</span>
                        </template>
                        <template #actions="{ row }">
                            <IconButton :as="Link" :href="route('practices.show', row.id)" tooltip="Visualizza" class="rounded-xl bg-surface-container-low p-2 text-primary transition hover:bg-primary-container">
                                <EyeIcon class="h-5 w-5" />
                            </IconButton>
                        </template>
                    </SortableTable>

                    <div v-if="practices.links && practices.links.length > 3" class="p-6 pt-0 flex justify-end">
                        <Pagination :links="practices.links" />
                    </div>
                </UiSectionCard>

                <div class="space-y-6">
                    <UiSectionCard title="Insight sistema" eyebrow="Priorità studio">
                        <div class="rounded-[1.5rem] bg-tertiary-container p-5 text-on-tertiary-container">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em]">Alert operativo</p>
                            <p class="mt-3 font-headline text-2xl font-extrabold">{{ summary.pending }}</p>
                            <p class="mt-2 text-sm opacity-80">Pratiche attualmente in attesa di documentazione cliente.</p>
                        </div>
                        <div class="mt-4 rounded-[1.5rem] bg-primary/5 p-5 text-sm text-on-surface-variant ring-1 ring-primary/10">
                            Nessun contratto API è stato toccato: il redesign resta confinato alla web UI.
                        </div>
                    </UiSectionCard>

                    <UiSectionCard title="Filtri rapidi" eyebrow="Lettura guidata">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-primary-container px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-on-primary-container">Tutte</span>
                            <span class="rounded-full bg-surface-container-high px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-on-surface-variant">In lavorazione</span>
                            <span class="rounded-full bg-surface-container-high px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-on-surface-variant">Documenti</span>
                            <span class="rounded-full bg-surface-container-high px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-on-surface-variant">Completate</span>
                        </div>
                    </UiSectionCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
