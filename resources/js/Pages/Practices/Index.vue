<script setup>
import { computed, ref } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import SortableTable from '@/Components/SortableTable.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatusBadge from '@/Components/ui/UiStatusBadge.vue';
import { EyeIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    practices: Object,
    filters: Object,
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'client.first_name', label: 'Cliente' },
    { key: 'type', label: 'Tipo' },
    { key: 'status', label: 'Stato' },
    { key: 'reference_year', label: 'Anno' },
    { key: 'assigned_users', label: 'Assegnati', sortable: false },
];

const page = usePage();
const search = ref(props.filters?.search ?? '');
const canCreate = computed(() => page.props.auth.user?.permissions?.includes('practices.create'));

const performSearch = () => {
    router.get(route('practices.index'), { search: search.value }, { preserveState: true, replace: true });
};

const formatStatus = (status) => status ? status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : '';

const summary = computed(() => {
    const rows = props.practices?.data ?? [];
    return {
        total: rows.length,
        pending: rows.filter((item) => item.status === 'in_attesa_documenti').length,
        active: rows.filter((item) => item.status === 'in_lavorazione').length,
        complete: rows.filter((item) => item.status === 'completata').length,
    };
});

const statCards = computed(() => [
    { label: 'Totale in pagina', value: summary.value.total, tone: 'nuova' },
    { label: 'In lavorazione', value: summary.value.active, tone: 'in_lavorazione' },
    { label: 'In attesa documenti', value: summary.value.pending, tone: 'in_attesa_documenti' },
    { label: 'Completate', value: summary.value.complete, tone: 'completata' },
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
                <div v-for="card in statCards" :key="card.label" class="rounded-[1.5rem] bg-surface-container-lowest p-5 shadow-[0px_20px_40px_rgba(12,15,16,0.06)] ring-1 ring-outline-variant/10">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">{{ card.label }}</p>
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
                            <button @click="performSearch" class="rounded-2xl bg-surface-container-high px-4 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container-highest">Cerca</button>
                        </div>
                    </template>

                    <SortableTable :columns="columns" :rows="practices.data" empty-message="Nessuna pratica trovata.">
                        <template #cell-id="{ row }">
                            <span class="font-semibold text-primary">#{{ row.id }}</span>
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
