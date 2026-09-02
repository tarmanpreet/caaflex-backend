<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SortableTable from '@/Components/SortableTable.vue';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import { roleLabel } from '@/utils/roles.js';
import { EyeIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';

const columns = [
    { key: 'name', label: 'Nome' },
    { key: 'email', label: 'Email' },
    { key: 'roles', label: 'Ruolo', sortable: false },
    { key: 'open_practices_count', label: 'Pratiche aperte' },
    { key: 'is_active', label: 'Stato' }
];

const props = defineProps({
    users: Object,
    filters: Object,
    canCreateUser: Boolean,
});

const page = usePage();
const search = ref(props.filters?.search ?? '');
const sortKey = ref(props.filters?.sort ?? 'name');
const sortDir = ref(props.filters?.direction ?? 'asc');

const canViewUsers = computed(() => page.props.auth.user?.permissions?.includes('users.view-any'));

const performSearch = () => {
    router.get(route('users.index'), { search: search.value, sort: sortKey.value, direction: sortDir.value }, { preserveState: true, replace: true });
};

const onSort = ({ key, dir }) => {
    sortKey.value = key;
    sortDir.value = dir;
    router.get(route('users.index'), { search: search.value, sort: key, direction: dir }, { preserveState: true, replace: true });
};

const roleBadgeClass = (role) => {
    const map = {
        superadmin: 'bg-purple-100 text-purple-800',
        admin: 'bg-indigo-100 text-indigo-800',
        employee: 'bg-blue-100 text-blue-800',
        cliente: 'bg-gray-100 text-gray-800',
    };
    return map[role] ?? 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AppLayout title="Utenti">
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Amministrazione / Utenti</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Gestione utenti</h1>
                    <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">Consulta utenti, ruoli, stato e carico delle pratiche aperte.</p>
                </div>
                <Link v-if="canCreateUser" :href="route('users.create')" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-bold text-on-primary shadow-lg shadow-primary/20 transition hover:bg-primary-dim">
                    <PlusIcon class="h-5 w-5" />
                    Nuovo utente
                </Link>
            </div>
        </template>

        <UiSectionCard title="Archivio utenti" eyebrow="Vista operativa" :padded="false">
            <div class="border-b border-outline-variant/35 bg-surface-container-lowest p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative min-w-0 flex-1 sm:max-w-xl">
                        <label for="user-search" class="sr-only">Cerca utenti</label>
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                    <input
                        id="user-search"
                        v-model="search"
                        type="search"
                        @keyup.enter="performSearch"
                        placeholder="Cerca per nome o email…"
                        class="h-11 w-full rounded-xl border-0 bg-surface-container-high pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                    >
                    </div>
                    <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-surface-container-high px-4 text-sm font-semibold text-on-surface transition hover:bg-surface-container-highest" @click="performSearch">Cerca</button>
                </div>
            </div>

            <SortableTable
                            :columns="columns"
                            :rows="users.data"
                            :controlled="true"
                            :sort-key="sortKey"
                            :sort-dir="sortDir"
                            empty-message="Nessun utente trovato."
                            @sort="onSort"
                        >
                            <template #cell-name="{ row }">
                                <Link :href="route('users.show', row.id)" class="text-sm font-semibold text-primary transition hover:text-primary-dim">
                                    {{ row.name }}
                                </Link>
                            </template>
                            <template #cell-roles="{ row }">
                                <span
                                    v-for="role in row.roles"
                                    :key="role.id"
                                    :class="['px-2 py-1 rounded-full text-xs font-semibold mr-1', roleBadgeClass(role.name)]"
                                >
                                    {{ roleLabel(role.name) }}
                                </span>
                                <span v-if="!row.roles || row.roles.length === 0" class="text-xs text-gray-400">—</span>
                            </template>
                            <template #cell-open_practices_count="{ row }">
                                <span class="text-on-surface-variant">
                                    {{ row.open_practices_count ?? 0 }}
                                </span>
                            </template>
                            <template #cell-is_active="{ row }">
                                <span :class="['px-2 py-1 rounded-full text-xs font-semibold', row.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800']">
                                    {{ row.is_active ? 'Attivo' : 'Disattivato' }}
                                </span>
                            </template>
                            <template #actions="{ row }">
                                <IconButton :as="Link" :href="route('users.show', row.id)" tooltip="Dettaglio" class="rounded-xl bg-surface-container-low p-2 text-primary transition hover:bg-primary-container">
                                    <EyeIcon class="w-5 h-5" />
                                </IconButton>
                            </template>
            </SortableTable>

                <div v-if="users.links && users.links.length > 3" class="flex justify-end p-5">
                    <Pagination :links="users.links" />
                </div>
        </UiSectionCard>
    </AppLayout>
</template>
