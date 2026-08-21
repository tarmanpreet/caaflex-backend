<script setup>
import { computed, nextTick, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import IconButton from '@/Components/IconButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SortableTable from '@/Components/SortableTable.vue';
import TextInput from '@/Components/TextInput.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatusBadge from '@/Components/ui/UiStatusBadge.vue';
import { formatDateTime } from '@/utils/date.js';
import {
    AdjustmentsHorizontalIcon,
    ArrowPathIcon,
    ChevronDownIcon,
    EyeIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

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
const showAdvancedFilters = ref(Boolean(
    props.filters?.priority
    || props.filters?.timing === 'upcoming'
    || ['pending', 'in_progress', 'cancelled'].includes(props.filters?.status),
));

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
    if (!search.value && !statusFilter.value && !priorityFilter.value && !timingFilter.value) return 'total';
    return '';
});

const quickFilters = computed(() => [
    { key: 'total', label: 'Tutte', value: props.summary?.total ?? 0 },
    { key: 'open', label: 'Aperte', value: props.summary?.open ?? 0 },
    { key: 'overdue', label: 'Scadute', value: props.summary?.overdue ?? 0 },
    { key: 'completed', label: 'Completate', value: props.summary?.completed ?? 0 },
]);

const activeFilterCount = computed(() => [
    search.value,
    statusFilter.value,
    priorityFilter.value,
    timingFilter.value,
].filter(Boolean).length);

const query = () => ({
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    priority: priorityFilter.value || undefined,
    timing: timingFilter.value || undefined,
    sort: sortKey.value,
    direction: sortDir.value,
});

const performSearch = () => {
    router.get(route('deadlines.index'), query(), { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    priorityFilter.value = '';
    timingFilter.value = '';
    showAdvancedFilters.value = false;
    performSearch();
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    performSearch();
};

const filterBySummary = (key) => {
    if (key === 'total') {
        resetFilters();
        return;
    }

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

const editingDeadline = ref(null);
const editTitleInput = ref(null);
const editForm = useForm({
    title: '',
    notes: '',
    deadline_at: '',
    status: 'pending',
    priority: 3,
    user_id: null,
});

const assignedUsers = computed(() => editingDeadline.value?.practice?.assigned_users ?? []);

const toDateTimeLocal = (value) => {
    if (!value) return '';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60_000));
    return localDate.toISOString().slice(0, 16);
};

const openEditModal = async (deadline) => {
    editingDeadline.value = deadline;
    editForm.title = deadline.title ?? '';
    editForm.notes = deadline.notes ?? '';
    editForm.deadline_at = toDateTimeLocal(deadline.deadline_at);
    editForm.status = deadline.status ?? 'pending';
    editForm.priority = deadline.priority ?? 3;
    editForm.user_id = deadline.user_id ?? null;
    editForm.clearErrors();

    await nextTick();
    editTitleInput.value?.focus();
};

const closeEditModal = () => {
    if (editForm.processing) return;

    editingDeadline.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const updateDeadline = () => {
    if (!editingDeadline.value) return;

    editForm.put(route('practices.deadlines.update', [editingDeadline.value.practice_id, editingDeadline.value.id]), {
        preserveScroll: true,
        onSuccess: closeEditModal,
    });
};
</script>

<template>
    <AppLayout title="Scadenze">
        <template #header>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Workspace / Scadenze</p>
                <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Gestione scadenze</h1>
                <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Controlla e aggiorna tutte le attività collegate alle pratiche da un’unica vista.</p>
            </div>
        </template>

        <UiSectionCard title="Archivio scadenze" eyebrow="Vista operativa" :padded="false">
            <div class="border-b border-outline-variant/35 bg-surface-container-lowest p-4 sm:p-5">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                    <div class="flex flex-wrap gap-2" aria-label="Filtri rapidi">
                        <button
                            v-for="filter in quickFilters"
                            :key="filter.key"
                            type="button"
                            :aria-pressed="activeSummary === filter.key"
                            :class="[
                                'inline-flex min-h-[44px] items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 motion-reduce:transition-none',
                                activeSummary === filter.key
                                    ? 'bg-primary text-on-primary shadow-sm'
                                    : 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface',
                            ]"
                            @click="filterBySummary(filter.key)"
                        >
                            <span>{{ filter.label }}</span>
                            <span :class="['rounded-md px-2 py-0.5 text-xs tabular-nums', activeSummary === filter.key ? 'bg-on-primary/15' : 'bg-surface-container-lowest']">
                                {{ filter.value }}
                            </span>
                        </button>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row xl:justify-end">
                        <div class="relative min-w-0 flex-1 xl:max-w-sm">
                            <label for="deadline-search" class="sr-only">Cerca scadenze</label>
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                            <input
                                id="deadline-search"
                                v-model="search"
                                type="search"
                                placeholder="Titolo, cliente o pratica..."
                                class="h-11 w-full rounded-xl border-0 bg-surface-container-high pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                                @keyup.enter="performSearch"
                            >
                        </div>

                        <button
                            type="button"
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-surface-container-high px-4 text-sm font-semibold text-on-surface transition duration-200 hover:bg-surface-container-highest focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 motion-reduce:transition-none"
                            aria-controls="advanced-deadline-filters"
                            :aria-expanded="showAdvancedFilters"
                            @click="showAdvancedFilters = !showAdvancedFilters"
                        >
                            <AdjustmentsHorizontalIcon class="h-5 w-5" />
                            Filtri
                            <span v-if="activeFilterCount" class="rounded-full bg-primary px-2 py-0.5 text-xs font-bold text-on-primary">{{ activeFilterCount }}</span>
                            <ChevronDownIcon :class="['h-4 w-4 transition-transform duration-200 motion-reduce:transition-none', showAdvancedFilters ? 'rotate-180' : '']" />
                        </button>

                        <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-primary px-4 text-sm font-semibold text-on-primary transition duration-200 hover:bg-primary-dim focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 motion-reduce:transition-none" @click="performSearch">
                            Cerca
                        </button>
                    </div>
                </div>

                <div
                    v-show="showAdvancedFilters"
                    id="advanced-deadline-filters"
                    class="mt-3 grid gap-3 rounded-2xl border border-outline-variant/30 bg-surface-container-low p-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end"
                >
                    <div>
                        <label for="deadline-status-filter" class="mb-1.5 block text-xs font-semibold text-on-surface-variant">Stato</label>
                        <select id="deadline-status-filter" v-model="statusFilter" class="h-11 w-full rounded-xl border-0 bg-surface-container-lowest px-3 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" @change="timingFilter = ''; performSearch()">
                            <option value="">Tutti gli stati</option>
                            <option v-for="(label, status) in statusLabels" :key="status" :value="status">{{ label }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="deadline-priority-filter" class="mb-1.5 block text-xs font-semibold text-on-surface-variant">Priorità</label>
                        <select id="deadline-priority-filter" v-model="priorityFilter" class="h-11 w-full rounded-xl border-0 bg-surface-container-lowest px-3 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" @change="performSearch">
                            <option value="">Tutte le priorità</option>
                            <option v-for="(config, priority) in priorityConfig" :key="priority" :value="priority">{{ config.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="deadline-timing-filter" class="mb-1.5 block text-xs font-semibold text-on-surface-variant">Periodo</label>
                        <select id="deadline-timing-filter" v-model="timingFilter" class="h-11 w-full rounded-xl border-0 bg-surface-container-lowest px-3 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" @change="statusFilter = ''; performSearch()">
                            <option value="">Tutte le date</option>
                            <option value="open">Aperte</option>
                            <option value="overdue">Scadute</option>
                            <option value="upcoming">In arrivo</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold text-on-surface-variant transition duration-200 hover:bg-surface-container-high hover:text-on-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:pointer-events-none disabled:opacity-40 motion-reduce:transition-none"
                        :disabled="activeFilterCount === 0"
                        @click="resetFilters"
                    >
                        <XMarkIcon class="h-5 w-5" />
                        Pulisci
                    </button>
                </div>
            </div>

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
                        <button v-if="row.can_update" type="button" class="inline-flex min-h-[44px] items-center text-left font-semibold text-on-surface transition hover:text-primary focus-visible:outline-none focus-visible:underline" @click="openEditModal(row)">
                            {{ row.title }}
                        </button>
                        <Link v-else :href="route('practices.show', row.practice_id) + '#deadlines'" class="font-semibold text-on-surface transition hover:text-primary">
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
                    <span class="whitespace-nowrap font-medium tabular-nums text-on-surface">{{ formatDateTime(row.deadline_at) }}</span>
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
                    <div class="flex justify-end gap-2">
                        <IconButton v-if="row.can_update" type="button" tooltip="Modifica scadenza" class="rounded-xl bg-primary-container p-2 text-on-primary-container transition hover:bg-primary/15" @click="openEditModal(row)">
                            <PencilSquareIcon class="h-5 w-5" />
                        </IconButton>
                        <IconButton :as="Link" :href="route('practices.show', row.practice_id) + '#deadlines'" tooltip="Apri pratica" class="rounded-xl bg-surface-container-low p-2 text-primary transition hover:bg-primary-container">
                            <EyeIcon class="h-5 w-5" />
                        </IconButton>
                    </div>
                </template>
            </SortableTable>

            <div v-if="deadlines.links?.length > 3" class="flex justify-end p-6 pt-0">
                <Pagination :links="deadlines.links" />
            </div>
        </UiSectionCard>

        <ConfirmationModal :show="Boolean(editingDeadline)" :closeable="!editForm.processing" max-width="2xl" @close="closeEditModal">
            <template #title>
                <span class="block pr-12">
                    Modifica scadenza
                    <span class="mt-1 block text-sm font-semibold text-primary">Pratica #{{ editingDeadline?.practice_id }}</span>
                </span>
            </template>

            <template #content>
                <button type="button" class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-xl text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30" aria-label="Chiudi modifica scadenza" :disabled="editForm.processing" @click="closeEditModal">
                    <XMarkIcon class="h-5 w-5" />
                </button>

                <form id="deadline-edit-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="updateDeadline">
                    <div class="sm:col-span-2">
                        <InputLabel for="global_deadline_title" value="Titolo *" />
                        <TextInput id="global_deadline_title" ref="editTitleInput" v-model="editForm.title" type="text" class="mt-1 block min-h-[44px] w-full" />
                        <InputError :message="editForm.errors.title" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="global_deadline_at" value="Data e ora *" />
                        <input id="global_deadline_at" v-model="editForm.deadline_at" type="datetime-local" class="app-input mt-1 block min-h-[44px] w-full rounded-xl" />
                        <InputError :message="editForm.errors.deadline_at" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="global_deadline_status" value="Stato" />
                        <select id="global_deadline_status" v-model="editForm.status" class="app-input mt-1 block min-h-[44px] w-full rounded-xl">
                            <option v-for="(label, status) in statusLabels" :key="status" :value="status">{{ label }}</option>
                        </select>
                        <InputError :message="editForm.errors.status" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="global_deadline_priority" value="Priorità" />
                        <select id="global_deadline_priority" v-model="editForm.priority" class="app-input mt-1 block min-h-[44px] w-full rounded-xl">
                            <option v-for="(config, priority) in priorityConfig" :key="priority" :value="Number(priority)">{{ config.label }}</option>
                        </select>
                        <InputError :message="editForm.errors.priority" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="global_deadline_user" value="Assegnatario" />
                        <select id="global_deadline_user" v-model="editForm.user_id" class="app-input mt-1 block min-h-[44px] w-full rounded-xl">
                            <option :value="null">Non assegnata</option>
                            <option v-for="user in assignedUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                        <p class="mt-1 text-xs text-on-surface-variant">Sono disponibili gli utenti assegnati alla pratica.</p>
                        <InputError :message="editForm.errors.user_id" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="global_deadline_notes" value="Note" />
                        <textarea id="global_deadline_notes" v-model="editForm.notes" rows="4" class="app-input mt-1 block w-full rounded-xl" placeholder="Aggiungi informazioni utili per questa scadenza"></textarea>
                        <InputError :message="editForm.errors.notes" class="mt-1" />
                    </div>
                </form>
            </template>

            <template #footer>
                <SecondaryButton :disabled="editForm.processing" @click="closeEditModal">
                    Annulla
                </SecondaryButton>
                <PrimaryButton type="submit" form="deadline-edit-form" :disabled="editForm.processing || !editForm.title || !editForm.deadline_at">
                    <ArrowPathIcon v-if="editForm.processing" class="h-4 w-4 animate-spin" />
                    {{ editForm.processing ? 'Salvataggio…' : 'Salva modifiche' }}
                </PrimaryButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
