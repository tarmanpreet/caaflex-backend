<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import SortableTable from '@/Components/SortableTable.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatusBadge from '@/Components/ui/UiStatusBadge.vue';
import { formatDateTime } from '@/utils/date.js';
import { EyeIcon, FunnelIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    deadlines: Object,
    filters: Object,
    summary: Object,
});

const columns = [
    { key: 'title', label: 'Scadenza' },
    { key: 'practice', label: 'Pratica', sortable: false },
    { key: 'deadline_at', label: 'Data' },
    { key: 'status', label: 'Stato' },
    { key: 'priority', label: 'Priorità' },
    { key: 'assignee', label: 'Assegnatario', sortable: false },
];

const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');
const priorityFilter = ref(props.filters?.priority ?? '');
const timingFilter = ref(props.filters?.timing ?? '');
const sortKey = ref(props.filters?.sort ?? 'deadline_at');
const sortDir = ref(props.filters?.direction ?? 'asc');

const statusLabels = {
    pending: 'In attesa',
    in_progress: 'In corso',
    completed: 'Completata',
    cancelled: 'Annullata',
};

const priorityConfig = {
    1: { label: 'Urgente', class: 'bg-error-container/30 text-on-error-container' },
    2: { label: 'Alta', class: 'bg-primary-container/80 text-on-primary-container' },
    3: { label: 'Media', class: 'bg-tertiary-container text-on-tertiary-container' },
    4: { label: 'Bassa', class: 'bg-surface-container-high text-on-surface-variant' },
};

const activeSummary = computed(() => {
    if (statusFilter.value === 'completed') return 'completed';
    if (timingFilter.value === 'overdue') return 'overdue';
    if (timingFilter.value === 'open') return 'open';
    if (!statusFilter.value && !timingFilter.value) return 'total';
    return '';
});

const statCards = computed(() => [
    { key: 'total', label: 'Totali', value: props.summary?.total ?? 0, status: 'pending' },
    { key: 'open', label: 'Aperte', value: props.summary?.open ?? 0, status: 'in_progress' },
    { key: 'overdue', label: 'Scadute', value: props.summary?.overdue ?? 0, status: 'annullata' },
    { key: 'completed', label: 'Completate', value: props.summary?.completed ?? 0, status: 'completed' },
]);

const query = () => ({
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    priority: priorityFilter.value || undefined,
    timing: timingFilter.value || undefined,
    sort: sortKey.value,
    direction: sortDir.value,
});

const performSearch = () => {
    router.get(route('deadlines.index'), query(), { preserveState: true, replace: true });
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    performSearch();
};

const filterBySummary = (key) => {
    statusFilter.value = key === 'completed' ? 'completed' : '';
    timingFilter.value = ['open', 'overdue'].includes(key) ? key : '';
    performSearch();
};

const clientName = (deadline) => {
    const client = deadline.practice?.client;
    return client ? `${client.first_name} ${client.last_name}` : 'Cliente non disponibile';
};

const isOverdue = (deadline) => {
    return ['pending', 'in_progress'].includes(deadline.status)
        && new Date(deadline.deadline_at) < new Date();
};
</script>

<template>
    <AppLayout title="Scadenze">
        <template #header>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Workspace / Scadenze</p>
                <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Gestione scadenze</h1>
                <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Tutte le attività collegate alle pratiche, ordinate per data e priorità.</p>
            </div>
        </template>

        <div class="space-y-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <button
                    v-for="card in statCards"
                    :key="card.key"
                    type="button"
                    :class="[
                        'rounded-[1.5rem] bg-surface-container-lowest p-5 text-left shadow-[0px_20px_40px_rgba(12,15,16,0.06)] ring-1 ring-outline-variant/10 transition hover:-translate-y-0.5 hover:ring-primary/30',
                        activeSummary === card.key ? 'ring-2 ring-primary/50' : '',
                    ]"
                    @click="filterBySummary(card.key)"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">{{ card.label }}</p>
                        <span class="inline-flex items-center gap-1 rounded-full bg-surface-container-high px-2 py-1 text-[9px] font-bold uppercase tracking-[0.18em] text-on-surface-variant">
                            <FunnelIcon class="h-3 w-3" />
                            {{ activeSummary === card.key ? 'Attivo' : 'Filtra' }}
                        </span>
                    </div>
                    <div class="mt-4 flex items-end justify-between gap-4">
                        <p class="font-headline text-3xl font-extrabold tracking-tight text-on-surface">{{ card.value }}</p>
                        <UiStatusBadge :label="card.label" :status="card.status" size="sm" />
                    </div>
                </button>
            </section>

            <UiSectionCard title="Archivio scadenze" eyebrow="Ricerca e tabella" :padded="false">
                <template #actions>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative min-w-[240px] flex-1">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Titolo, cliente o pratica..."
                                class="h-11 w-full rounded-2xl border-0 bg-surface-container-high pl-11 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                                @keyup.enter="performSearch"
                            >
                        </div>
                        <select v-model="statusFilter" class="h-11 rounded-2xl border-0 bg-surface-container-high px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" @change="timingFilter = ''; performSearch()">
                            <option value="">Tutti gli stati</option>
                            <option v-for="(label, status) in statusLabels" :key="status" :value="status">{{ label }}</option>
                        </select>
                        <select v-model="priorityFilter" class="h-11 rounded-2xl border-0 bg-surface-container-high px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" @change="performSearch">
                            <option value="">Tutte le priorità</option>
                            <option v-for="(config, priority) in priorityConfig" :key="priority" :value="priority">{{ config.label }}</option>
                        </select>
                        <select v-model="timingFilter" class="h-11 rounded-2xl border-0 bg-surface-container-high px-4 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" @change="statusFilter = ''; performSearch()">
                            <option value="">Tutte le date</option>
                            <option value="open">Aperte</option>
                            <option value="overdue">Scadute</option>
                            <option value="upcoming">In arrivo</option>
                        </select>
                        <button type="button" class="rounded-2xl bg-surface-container-high px-4 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container-highest" @click="performSearch">Cerca</button>
                    </div>
                </template>

                <SortableTable
                    :columns="columns"
                    :rows="deadlines.data"
                    :controlled="true"
                    :sort-key="sortKey"
                    :sort-dir="sortDir"
                    empty-message="Nessuna scadenza trovata."
                    @sort="onSort"
                >
                    <template #cell-title="{ row }">
                        <div class="min-w-[180px]">
                            <Link :href="route('practices.show', row.practice_id) + '#deadlines'" class="font-semibold text-on-surface transition hover:text-primary">
                                {{ row.title }}
                            </Link>
                            <span v-if="isOverdue(row)" class="mt-1 block text-xs font-semibold text-error">Scaduta</span>
                        </div>
                    </template>
                    <template #cell-practice="{ row }">
                        <div class="min-w-[180px]">
                            <Link :href="route('practices.show', row.practice_id)" class="font-semibold text-primary transition hover:text-primary-dim">#{{ row.practice_id }} · {{ row.practice?.type }}</Link>
                            <p class="mt-1 text-xs text-on-surface-variant">{{ clientName(row) }}</p>
                        </div>
                    </template>
                    <template #cell-deadline_at="{ row }">
                        <span class="whitespace-nowrap font-medium text-on-surface">{{ formatDateTime(row.deadline_at) }}</span>
                    </template>
                    <template #cell-status="{ row }">
                        <UiStatusBadge :label="statusLabels[row.status] ?? row.status" :status="row.status" size="table" />
                    </template>
                    <template #cell-priority="{ row }">
                        <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', priorityConfig[row.priority]?.class]">{{ priorityConfig[row.priority]?.label ?? '—' }}</span>
                    </template>
                    <template #cell-assignee="{ row }">
                        <span class="text-sm text-on-surface-variant">{{ row.assignee?.name ?? 'Non assegnata' }}</span>
                    </template>
                    <template #actions="{ row }">
                        <IconButton :as="Link" :href="route('practices.show', row.practice_id) + '#deadlines'" tooltip="Apri pratica" class="rounded-xl bg-surface-container-low p-2 text-primary transition hover:bg-primary-container">
                            <EyeIcon class="h-5 w-5" />
                        </IconButton>
                    </template>
                </SortableTable>

                <div v-if="deadlines.links?.length > 3" class="flex justify-end p-6 pt-0">
                    <Pagination :links="deadlines.links" />
                </div>
            </UiSectionCard>
        </div>
    </AppLayout>
</template>
