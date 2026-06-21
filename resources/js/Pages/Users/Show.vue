<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, router, Link } from '@inertiajs/vue3';
import { formatDate } from '@/utils/date';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SortableTable from '@/Components/SortableTable.vue';
import Multiselect from '@vueform/multiselect';

const practiceColumns = [
    { key: 'type', label: 'Tipo', sortable: true },
    { key: 'client', label: 'Cliente', sortable: false },
    { key: 'status', label: 'Stato', sortable: true },
    { key: 'updated_at', label: 'Aggiornata il', sortable: true },
];

const props = defineProps({
    user: Object,
    activePractices: Object,
    closedPractices: Object,
    availableRoles: Array,
    allPracticeTypes: Array,
    branches: Array,
    practiceFilters: Object,
});

const page = usePage();

// Permissions
const canUpdate = computed(() => page.props.auth.user?.permissions?.includes('users.update'));
const isSelf = computed(() => page.props.auth.user?.id === props.user?.id);
const userRole = computed(() => props.user?.roles?.[0]?.name ?? '');
const isViewerAdmin = computed(() => {
    const roles = page.props.auth.user?.roles ?? [];
    return roles.includes('admin') || roles.includes('superadmin');
});
const canManageAvailabilities = computed(() => isViewerAdmin.value && userRole.value === 'employee');

// Edit form
const editForm = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    role: props.user?.roles?.[0]?.name ?? '',
    practice_type_ids: props.user?.practice_types?.map(pt => pt.id) ?? [],
    branch_ids: props.user?.branches?.map(b => b.id) ?? [],
});

const isEmployee = computed(() => editForm.role === 'employee');

const submitEdit = () => {
    editForm.put(route('users.update', props.user.id), { preserveScroll: true });
};

// Toggle active
const toggleActive = () => {
    router.post(route('users.toggle-active', props.user.id), {}, { preserveScroll: true });
};

// Practices search
const activeSearch = ref(props.practiceFilters?.active_search ?? '');
const closedSearch = ref(props.practiceFilters?.closed_search ?? '');

const searchActive = () => {
    router.get(route('users.show', props.user.id), { active_search: activeSearch.value, closed_search: closedSearch.value }, { preserveState: true, preserveScroll: true, replace: true });
};
const searchClosed = () => {
    router.get(route('users.show', props.user.id), { active_search: activeSearch.value, closed_search: closedSearch.value }, { preserveState: true, preserveScroll: true, replace: true });
};

// Role badge
const roleBadgeClass = (role) => {
    const map = {
        superadmin: 'bg-purple-100 text-purple-800',
        admin: 'bg-indigo-100 text-indigo-800',
        employee: 'bg-blue-100 text-blue-800',
        cliente: 'bg-gray-100 text-gray-800',
    };
    return map[role] ?? 'bg-gray-100 text-gray-800';
};

// Status badge for practices
const statusBadgeClass = (status) => {
    const map = {
        nuova: 'bg-blue-100 text-blue-800',
        in_lavorazione: 'bg-yellow-100 text-yellow-800',
        in_attesa_documenti: 'bg-orange-100 text-orange-800',
        completata: 'bg-green-100 text-green-800',
        annullata: 'bg-red-100 text-red-800',
        sospesa: 'bg-gray-100 text-gray-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AppLayout :title="'Utente: ' + (user?.name ?? '')">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <Link :href="route('users.index')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 text-sm">
                    &larr; Utenti
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ user?.name }}
                </h2>
                <span
                    v-for="role in user?.roles"
                    :key="role.id"
                    :class="['px-2 py-1 rounded-full text-xs font-semibold', roleBadgeClass(role.name)]"
                >
                    {{ role.name }}
                </span>
                <span :class="['px-2 py-1 rounded-full text-xs font-semibold', user?.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800']">
                    {{ user?.is_active ? 'Attivo' : 'Disattivato' }}
                </span>
            </div>
        </template>

        <div class="py-12" v-if="user">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Section 1: Edit Form -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Dati Utente</h3>
                        <!-- Admin Actions -->
                        <div class="flex items-center gap-2">
                            <Link
                                v-if="canManageAvailabilities"
                                :href="route('users.availabilities.index', user.id)"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Gestisci Disponibilità
                            </Link>
                            <template v-if="canUpdate && !isSelf">
                                <DangerButton v-if="user?.is_active" @click="toggleActive">
                                    Disattiva Utente
                                </DangerButton>
                                <PrimaryButton v-else @click="toggleActive" class="bg-green-600 hover:bg-green-700 focus:ring-green-500 active:bg-green-800">
                                    Attiva Utente
                                </PrimaryButton>
                            </template>
                        </div>
                    </div>
                    <form @submit.prevent="submitEdit" class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Nome -->
                        <div>
                            <InputLabel for="name" value="Nome" />
                            <TextInput
                                id="name"
                                v-model="editForm.name"
                                type="text"
                                class="mt-1 block w-full"
                                :disabled="!canUpdate"
                                required
                            />
                            <InputError :message="editForm.errors.name" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="editForm.email"
                                type="email"
                                class="mt-1 block w-full"
                                :disabled="!canUpdate"
                                required
                            />
                            <InputError :message="editForm.errors.email" class="mt-2" />
                        </div>

                        <!-- Ruolo -->
                        <div>
                            <InputLabel for="role" value="Ruolo" />
                            <select
                                v-if="canUpdate"
                                id="role"
                                v-model="editForm.role"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 shadow-sm"
                                required
                            >
                                <option v-for="r in availableRoles" :key="r" :value="r">{{ r }}</option>
                            </select>
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ user.roles?.[0]?.name ?? '—' }}</p>
                            <InputError :message="editForm.errors.role" class="mt-2" />
                        </div>

                        <!-- Tipologie Pratiche (solo employee) -->
                        <div v-if="isEmployee" class="md:col-span-2">
                            <InputLabel for="practice_type_ids" value="Tipologie di pratiche gestite" />
                            <Multiselect
                                v-model="editForm.practice_type_ids"
                                :options="(allPracticeTypes ?? []).map(pt => ({ value: pt.id, label: pt.name }))"
                                mode="tags"
                                :searchable="true"
                                :close-on-select="false"
                                value-prop="value"
                                label="label"
                                track-by="value"
                                placeholder="Seleziona tipologie..."
                                no-options-text="Nessuna tipologia trovata"
                                no-results-text="Nessun risultato"
                                :disabled="!canUpdate"
                                :append-to-body="true"
                                class="mt-1"
                            />
                            <InputError :message="editForm.errors.practice_type_ids" class="mt-2" />
                        </div>

                        <!-- Filiali (solo employee) -->
                        <div v-if="isEmployee" class="md:col-span-2">
                            <InputLabel for="branch_ids" value="Filiali assegnate" />
                            <Multiselect
                                v-model="editForm.branch_ids"
                                :options="(branches ?? []).map(b => ({ value: b.id, label: b.name + ' - ' + b.city + ' (' + b.province + ')' }))"
                                mode="tags"
                                :searchable="true"
                                :close-on-select="false"
                                value-prop="value"
                                label="label"
                                track-by="value"
                                placeholder="Seleziona filiali..."
                                no-options-text="Nessuna filiale trovata"
                                no-results-text="Nessun risultato"
                                :disabled="!canUpdate"
                                :append-to-body="true"
                                class="mt-1"
                            />
                            <InputError :message="editForm.errors.branch_ids" class="mt-2" />
                        </div>

                        <!-- Save Button -->
                        <div v-if="canUpdate" class="md:col-span-2 flex justify-end">
                            <PrimaryButton type="submit" :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing">
                                Salva Modifiche
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Section 2: Pratiche in gestione -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Pratiche in gestione
                            <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">({{ activePractices?.total ?? 0 }})</span>
                        </h3>
                        <div class="flex items-center space-x-2">
                            <input
                                type="text"
                                v-model="activeSearch"
                                @keyup.enter="searchActive"
                                placeholder="Cerca per tipo o stato..."
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm block w-52"
                            />
                            <button
                                @click="searchActive"
                                class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Cerca
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <SortableTable
                            :columns="practiceColumns"
                            :rows="activePractices?.data || []"
                            emptyMessage="Nessuna pratica in gestione."
                        >
                            <template #cell-client="{ row }">
                                <template v-if="row.client">
                                    {{ row.client.first_name }} {{ row.client.last_name }}
                                </template>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                            <template #cell-status="{ row }">
                                <span :class="['px-2 py-1 rounded-full text-xs font-semibold uppercase', statusBadgeClass(row.status)]">
                                    {{ row.status?.replace(/_/g, ' ') }}
                                </span>
                            </template>
                            <template #cell-updated_at="{ row }">
                                {{ formatDate(row.updated_at) }}
                            </template>
                            <template #actions="{ row }">
                                <Link :href="route('practices.show', row.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    Apri
                                </Link>
                            </template>
                        </SortableTable>
                    </div>
                    <!-- Pagination -->
                    <div v-if="activePractices?.links && activePractices.links.length > 3" class="mt-4 flex flex-wrap -mb-1">
                        <template v-for="(link, key) in activePractices.links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white dark:bg-gray-800 focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white dark:bg-gray-800': link.active, 'bg-gray-100 dark:bg-gray-700': !link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>

                <!-- Section 3: Pratiche chiuse -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Pratiche chiuse
                            <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">({{ closedPractices?.total ?? 0 }})</span>
                        </h3>
                        <div class="flex items-center space-x-2">
                            <input
                                type="text"
                                v-model="closedSearch"
                                @keyup.enter="searchClosed"
                                placeholder="Cerca per tipo o stato..."
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm block w-52"
                            />
                            <button
                                @click="searchClosed"
                                class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Cerca
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <SortableTable
                            :columns="practiceColumns"
                            :rows="closedPractices?.data || []"
                            emptyMessage="Nessuna pratica chiusa."
                        >
                            <template #cell-client="{ row }">
                                <template v-if="row.client">
                                    {{ row.client.first_name }} {{ row.client.last_name }}
                                </template>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                            <template #cell-status="{ row }">
                                <span :class="['px-2 py-1 rounded-full text-xs font-semibold uppercase', statusBadgeClass(row.status)]">
                                    {{ row.status?.replace(/_/g, ' ') }}
                                </span>
                            </template>
                            <template #cell-updated_at="{ row }">
                                {{ formatDate(row.updated_at) }}
                            </template>
                            <template #actions="{ row }">
                                <Link :href="route('practices.show', row.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    Apri
                                </Link>
                            </template>
                        </SortableTable>
                    </div>
                    <!-- Pagination -->
                    <div v-if="closedPractices?.links && closedPractices.links.length > 3" class="mt-4 flex flex-wrap -mb-1">
                        <template v-for="(link, key) in closedPractices.links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white dark:bg-gray-800 focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white dark:bg-gray-800': link.active, 'bg-gray-100 dark:bg-gray-700': !link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

