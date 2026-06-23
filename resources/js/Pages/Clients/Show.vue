<script setup>
import { ref, computed } from 'vue';
import { formatDate } from '@/utils/date.js';
import { useForm, usePage, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SortableTable from '@/Components/SortableTable.vue';

const documentColumns = [
    { key: 'original_name', label: 'Filename', sortable: true },
    { key: 'description', label: 'Description', sortable: true },
    { key: 'created_at', label: 'Uploaded', sortable: true },
    { key: 'uploaded_by', label: 'Uploaded By', sortable: false },
];

const practiceColumns = [
    { key: 'type', label: 'Tipo', sortable: true },
    { key: 'status', label: 'Stato', sortable: true },
    { key: 'reference_year', label: 'Anno', sortable: true },
    { key: 'assigned_users', label: 'Assegnata a', sortable: false },
    { key: 'updated_at', label: 'Aggiornata il', sortable: true },
];

const props = defineProps({
    client: Object,
    documents: Array,
    practices: Object,
    practiceFilters: Object,
    conflictClientId: Number,
});

const page = usePage();

// Permissions
const canEditClient = computed(() => page.props.auth.user?.permissions?.includes('clients.update'));
const canUploadDocument = computed(() => page.props.auth.user?.permissions?.includes('documents.upload'));
const canDeleteDocument = computed(() => page.props.auth.user?.permissions?.includes('documents.delete'));

// Edit Logic
const editMode = ref(false);

const editForm = useForm({
    first_name: props.client.first_name,
    last_name: props.client.last_name,
    phone: props.client.phone,
    date_of_birth: props.client.date_of_birth,
    fiscal_code: props.client.fiscal_code ?? '',
    email: props.client.email ?? '',
    address: props.client.address ?? '',
    city: props.client.city ?? '',
    province: props.client.province ?? '',
    postal_code: props.client.postal_code ?? '',
    notes: props.client.notes ?? '',
});

// Invite user
const inviteForm = useForm({});
const submitInvite = () => {
    inviteForm.post(route('clients.invite-user', props.client.id), { preserveScroll: true });
};

const submitEdit = () => {
    editForm.put(route('clients.update', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            editMode.value = false;
        }
    });
};

const cancelEdit = () => {
    editForm.reset();
    editForm.clearErrors();
    editMode.value = false;
};

// Upload Logic
const dropzoneInput = ref(null);
const isDragging = ref(false);
const stagedFiles = ref([]);

const form = useForm({
    files: [],
    descriptions: [],
});

const formatFileSize = (bytes) => {
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const addFiles = (fileList) => {
    for (const file of fileList) {
        stagedFiles.value.push({ file, description: '' });
    }
};

const onDrop = (e) => {
    isDragging.value = false;
    if (e.dataTransfer.files) {
        addFiles(e.dataTransfer.files);
    }
};

const onFileInputChange = (e) => {
    if (e.target.files) {
        addFiles(e.target.files);
        dropzoneInput.value.value = '';
    }
};

const removeFile = (index) => {
    stagedFiles.value.splice(index, 1);
};

const submitUpload = () => {
    form.files = stagedFiles.value.map(f => f.file);
    form.descriptions = stagedFiles.value.map(f => f.description);
    form.post(route('clients.documents.store', props.client.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            stagedFiles.value = [];
        },
    });
};

// Delete Logic
const confirmingDocDelete = ref(false);
const docToDelete = ref(null);

const canDeleteClient = computed(() => page.props.auth.user?.permissions?.includes('clients.delete'));
const confirmingClientDelete = ref(false);
const deleteClient = () => {
    router.delete(route('clients.destroy', props.client.id), {
        onSuccess: () => { confirmingClientDelete.value = false; },
    });
};

const confirmDocDelete = (doc) => {
    docToDelete.value = doc;
    confirmingDocDelete.value = true;
};

const deleteDocument = () => {
    router.delete(route('clients.documents.destroy', [props.client.id, docToDelete.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            confirmingDocDelete.value = false;
            docToDelete.value = null;
        },
    });
};

// Practices search
const practiceSearch = ref(props.practiceFilters?.search ?? '');
const searchPractices = () => {
    router.get(route('clients.show', props.client.id), { practice_search: practiceSearch.value }, { preserveState: true, preserveScroll: true, replace: true });
};

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
    <AppLayout title="Client Details">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ client.first_name }} {{ client.last_name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Profile Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Profile Information</h3>
                        <div class="flex items-center gap-3">
                            <template v-if="!editMode">
                                <DangerButton v-if="canDeleteClient" @click="confirmingClientDelete = true">
                                    Delete Client
                                </DangerButton>
                                <button
                                    v-if="canEditClient"
                                    @click="editMode = true"
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:bg-gray-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    Edit
                                </button>
                            </template>
                            <template v-else>
                                <SecondaryButton @click="cancelEdit" :disabled="editForm.processing">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="submitEdit" :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing">
                                    Save
                                </PrimaryButton>
                            </template>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- First Name -->
                        <div>
                            <InputLabel for="first_name" value="First Name" />
                            
                            <TextInput v-if="editMode" id="first_name" v-model="editForm.first_name" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" required />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.first_name }}</p>
                            
                            <InputError :message="editForm.errors.first_name" class="mt-2" />
                        </div>

                        <!-- Last Name -->
                        <div>
                            <InputLabel for="last_name" value="Last Name" />
                            
                            <TextInput v-if="editMode" id="last_name" v-model="editForm.last_name" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" required />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.last_name }}</p>
                            
                            <InputError :message="editForm.errors.last_name" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email" />
                            
                            <TextInput v-if="editMode" id="email" v-model="editForm.email" type="email" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.email }}</p>
                            
                            <InputError :message="editForm.errors.email" class="mt-2" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            
                            <TextInput v-if="editMode" id="phone" v-model="editForm.phone" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" required />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.phone }}</p>
                            
                            <InputError :message="editForm.errors.phone" class="mt-2" />
                        </div>

                        <!-- Date of Birth -->
                        <div>
                            <InputLabel for="date_of_birth" value="Date of Birth" />
                            
                            <TextInput v-if="editMode" id="date_of_birth" v-model="editForm.date_of_birth" type="date" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" required />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(client.date_of_birth) }}</p>
                            
                            <InputError :message="editForm.errors.date_of_birth" class="mt-2" />
                        </div>

                        <!-- Fiscal Code -->
                        <div>
                            <InputLabel for="fiscal_code" value="Fiscal Code" />
                            
                            <TextInput v-if="editMode" id="fiscal_code" v-model="editForm.fiscal_code" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" maxlength="16" />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.fiscal_code }}</p>
                            
                            <InputError :message="editForm.errors.fiscal_code" class="mt-2" />
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <InputLabel for="address" value="Address" />
                            
                            <TextInput v-if="editMode" id="address" v-model="editForm.address" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.address }}</p>
                            
                            <InputError :message="editForm.errors.address" class="mt-2" />
                        </div>

                        <!-- City -->
                        <div>
                            <InputLabel for="city" value="City" />
                            
                            <TextInput v-if="editMode" id="city" v-model="editForm.city" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.city }}</p>
                            
                            <InputError :message="editForm.errors.city" class="mt-2" />
                        </div>

                        <!-- Province -->
                        <div>
                            <InputLabel for="province" value="Province" />
                            
                            <TextInput v-if="editMode" id="province" v-model="editForm.province" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" maxlength="2" />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.province }}</p>
                            
                            <InputError :message="editForm.errors.province" class="mt-2" />
                        </div>

                        <!-- Postal Code -->
                        <div>
                            <InputLabel for="postal_code" value="Postal Code" />
                            
                            <TextInput v-if="editMode" id="postal_code" v-model="editForm.postal_code" type="text" class="mt-1 block w-full text-sm py-1 px-2 !shadow-none" maxlength="5" />
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.postal_code }}</p>
                            
                            <InputError :message="editForm.errors.postal_code" class="mt-2" />
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2" v-if="editMode || client.notes">
                            <InputLabel for="notes" value="Notes" />
                            
                            <textarea
                                v-if="editMode"
                                id="notes"
                                v-model="editForm.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1 px-2 !shadow-none"
                            ></textarea>
                            <p v-else class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ client.notes }}</p>
                            
                            <InputError :message="editForm.errors.notes" class="mt-2" />
                        </div>

                    </div>
                </div>

                <!-- Account Utente Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Account Portale</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Utente collegato per l'accesso al portale clienti.
                            </p>
                        </div>
                        <button
                            v-if="!client.user && canEditClient && client.email"
                            type="button"
                            :disabled="inviteForm.processing"
                            @click="submitInvite"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                        >
                            Invita utente
                        </button>
                    </div>

                    <!-- Errore invito -->
                    <div v-if="inviteForm.errors.invite_email" class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-400">
                        {{ inviteForm.errors.invite_email }}
                        <Link
                            v-if="conflictClientId"
                            :href="route('clients.show', conflictClientId)"
                            class="ml-1 underline font-medium hover:text-red-900 dark:hover:text-red-200"
                        >Vai al profilo &rarr;</Link>
                    </div>

                    <!-- Utente già collegato -->
                    <template v-if="client.user">
                        <div class="flex items-center gap-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 p-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ client.user.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ client.user.email }}</p>
                            </div>
                            <Link
                                :href="route('users.show', client.user.id)"
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium"
                            >
                                Vai al profilo utente &rarr;
                            </Link>
                        </div>
                    </template>

                    <!-- Nessun utente collegato -->
                    <template v-else-if="!inviteForm.errors.invite_email">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <template v-if="client.email">
                                Nessun account collegato. L'invito verrà inviato a <strong>{{ client.email }}</strong>.
                            </template>
                            <template v-else>
                                Nessun account collegato. Aggiungi un'email al profilo per poter creare un account.
                            </template>
                        </p>
                    </template>
                </div>

                <!-- Documents Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Documents</h3>

                    <!-- Upload Form -->
                    <div v-if="canUploadDocument" class="mb-8">
                        <!-- Step 1: Dropzone -->
                        <div v-if="stagedFiles.length === 0"
                             class="p-8 bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 flex flex-col items-center justify-center text-center cursor-pointer transition-colors"
                             :class="{ 'border-indigo-400 bg-indigo-50': isDragging, 'hover:border-indigo-400 hover:bg-indigo-50': !isDragging }"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="onDrop"
                             @click="dropzoneInput.click()">
                            
                            <svg class="w-10 h-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="text-sm text-gray-600 font-medium">Trascina i file qui o clicca per sfogliare</p>
                            <input
                                type="file"
                                ref="dropzoneInput"
                                class="hidden"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                @change="onFileInputChange"
                            />
                        </div>

                        <!-- Step 2: Lista file e form -->
                        <div v-else class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">File selezionati</h4>
                            <div class="space-y-3 mb-4">
                                <div v-for="(item, index) in stagedFiles" :key="index" class="flex items-center gap-3 bg-white p-3 rounded-md border border-gray-100 shadow-sm">
                                    <div class="flex-1 min-w-0 flex flex-col justify-center">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ item.file.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(item.file.size) }}</p>
                                    </div>
                                    <div class="flex-1">
                                        <input
                                            type="text"
                                            v-model="item.description"
                                            placeholder="Descrizione..."
                                            class="w-full text-sm py-1 px-2 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 !shadow-none"
                                        />
                                    </div>
                                    <button @click="removeFile(index)" type="button" class="text-gray-400 hover:text-red-500 p-1 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <SecondaryButton type="button" @click="stagedFiles.splice(0)" :disabled="form.processing">
                                    Annulla
                                </SecondaryButton>
                                <PrimaryButton type="button" @click="submitUpload" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Carica documenti
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Table -->
                    <div class="overflow-x-auto">
                        <SortableTable
                            :columns="documentColumns"
                            :rows="documents"
                            emptyMessage="No documents yet."
                        >
                            <template #cell-created_at="{ row }">
                                {{ formatDate(row.created_at) }}
                            </template>
                            <template #cell-uploaded_by="{ row }">
                                {{ row.uploaded_by?.name || 'Unknown' }}
                            </template>
                            <template #actions="{ row }">
                                <a :href="route('clients.documents.download', [client.id, row.id])" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 inline-block mr-4">Download</a>
                                <button v-if="canDeleteDocument" @click="confirmDocDelete(row)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 inline-block">Delete</button>
                            </template>
                        </SortableTable>
                    </div>
                </div>

                <!-- Practices Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pratiche</h3>
                        <div class="flex items-center space-x-2">
                            <input
                                type="text"
                                v-model="practiceSearch"
                                @keyup.enter="searchPractices"
                                placeholder="Cerca per tipo o stato..."
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm block w-52"
                            />
                            <button
                                @click="searchPractices"
                                class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Cerca
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <SortableTable
                            :columns="practiceColumns"
                            :rows="practices?.data || []"
                            emptyMessage="Nessuna pratica trovata."
                        >
                            <template #cell-status="{ row }">
                                <span :class="['px-2 py-1 rounded-full text-xs font-semibold uppercase', statusBadgeClass(row.status)]">
                                    {{ row.status?.replace(/_/g, ' ') }}
                                </span>
                            </template>
                            <template #cell-reference_year="{ row }">
                                {{ row.reference_year || '—' }}
                            </template>
                            <template #cell-assigned_users="{ row }">
                                <template v-if="row.assigned_users && row.assigned_users.length">
                                    {{ row.assigned_users.map(u => u.name).join(', ') }}
                                </template>
                                <span v-else class="text-gray-400">—</span>
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
                    <div v-if="practices?.links && practices.links.length > 3" class="mt-4 flex flex-wrap -mb-1">
                        <template v-for="(link, key) in practices.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 dark:text-gray-600 border dark:border-gray-600 rounded"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border dark:border-gray-600 rounded hover:bg-white dark:hover:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:text-indigo-500"
                                :class="{ 'bg-white': link.active, 'bg-gray-100 dark:bg-gray-950': !link.active }"
                                :href="link.url"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>

            </div>
        </div>

        <!-- Document Deletion Confirmation Modal -->
        <ConfirmationModal :show="confirmingDocDelete" @close="confirmingDocDelete = false">
            <template #title>
                Delete Document
            </template>

            <template #content>
                Are you sure you want to delete this document? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingDocDelete = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deleteDocument"
                >
                    Delete Document
                </DangerButton>
            </template>
        </ConfirmationModal>

        <ConfirmationModal :show="confirmingClientDelete" @close="confirmingClientDelete = false">
            <template #title>Delete Client</template>
            <template #content>Are you sure you want to delete this client? This action cannot be undone.</template>
            <template #footer>
                <SecondaryButton @click="confirmingClientDelete = false">Cancel</SecondaryButton>
                <DangerButton class="ms-3" @click="deleteClient">Delete</DangerButton>
            </template>
        </ConfirmationModal>

    </AppLayout>
</template>
