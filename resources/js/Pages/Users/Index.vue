<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SortableTable from '@/Components/SortableTable.vue';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { EyeIcon } from '@heroicons/vue/24/outline';

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
});

const page = usePage();
const search = ref(props.filters?.search ?? '');

const canViewUsers = computed(() => page.props.auth.user?.permissions?.includes('users.view-any'));

const performSearch = () => {
    router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true });
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestione Utenti
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Top bar -->
                <div class="mb-6 flex items-center space-x-2 w-full max-w-md">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="performSearch"
                        placeholder="Cerca per nome o email..."
                        class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                    />
                    <button
                        @click="performSearch"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Cerca
                    </button>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <SortableTable :columns="columns" :rows="users.data" empty-message="Nessun utente trovato.">
                            <template #cell-name="{ row }">
                                <Link :href="route('users.show', row.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium text-sm">
                                    {{ row.name }}
                                </Link>
                            </template>
                            <template #cell-roles="{ row }">
                                <span
                                    v-for="role in row.roles"
                                    :key="role.id"
                                    :class="['px-2 py-1 rounded-full text-xs font-semibold mr-1', roleBadgeClass(role.name)]"
                                >
                                    {{ role.name }}
                                </span>
                                <span v-if="!row.roles || row.roles.length === 0" class="text-xs text-gray-400">—</span>
                            </template>
                            <template #cell-open_practices_count="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ row.open_practices_count ?? 0 }}
                                </span>
                            </template>
                            <template #cell-is_active="{ row }">
                                <span :class="['px-2 py-1 rounded-full text-xs font-semibold', row.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800']">
                                    {{ row.is_active ? 'Attivo' : 'Disattivato' }}
                                </span>
                            </template>
                            <template #actions="{ row }">
                                <IconButton :as="Link" :href="route('users.show', row.id)" tooltip="Dettaglio" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    <EyeIcon class="w-5 h-5" />
                                </IconButton>
                            </template>
                        </SortableTable>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="mt-4">
                    <Pagination :links="users.links" />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
