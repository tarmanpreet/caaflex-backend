<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { formatDate } from '@/utils/date';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SortableTable from '@/Components/SortableTable.vue';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { EyeIcon } from '@heroicons/vue/24/outline';

const columns = [
    { key: 'first_name', label: 'Full Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'date_of_birth', label: 'Date of Birth' },
    { key: 'fiscal_code', label: 'Fiscal Code' },
    { key: 'city', label: 'City' }
];

const props = defineProps({
    clients: Object,
    filters: Object,
});

const page = usePage();
const search = ref(props.filters?.search ?? '');

const canCreate = computed(() => page.props.auth.user?.permissions?.includes('clients.create'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('clients.delete'));

// Search
const performSearch = () => {
    router.get(route('clients.index'), { search: search.value }, { preserveState: true, replace: true });
};

// Delete confirmation
const confirmingDelete = ref(false);
const clientToDelete = ref(null);

const confirmDelete = (client) => {
    clientToDelete.value = client;
    confirmingDelete.value = true;
};

const deleteClient = () => {
    router.delete(route('clients.destroy', clientToDelete.value.id), {
        onFinish: () => {
            confirmingDelete.value = false;
            clientToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Clients">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Clients
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
                            placeholder="Search clients..."
                            class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                        />
                        <button
                            @click="performSearch"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Search
                        </button>
                    </div>

                    <Link
                        v-if="canCreate"
                        :href="route('clients.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        New Client
                    </Link>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <SortableTable :columns="columns" :rows="clients.data" empty-message="No clients found.">
                            <template #cell-first_name="{ row }">
                                <Link :href="route('clients.show', row.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium">
                                    {{ row.first_name }} {{ row.last_name }}
                                </Link>
                            </template>
                            <template #cell-date_of_birth="{ row }">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ formatDate(row.date_of_birth) }}
                                </span>
                            </template>
                            <template #actions="{ row }">
                                <IconButton :as="Link" :href="route('clients.show', row.id)" tooltip="Visualizza" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    <EyeIcon class="w-5 h-5" />
                                </IconButton>
                            </template>
                        </SortableTable>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="clients.links && clients.links.length > 3" class="mt-4">
                    <Pagination :links="clients.links" />
                </div>
            </div>
        </div>

    </AppLayout>
</template>