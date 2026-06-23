<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { formatDate, formatDateTime } from '@/utils/date.js';
import { useForm, usePage, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Multiselect from '@vueform/multiselect';
import SortableTable from '@/Components/SortableTable.vue';
import DeadlinesSection from '@/Components/DeadlinesSection.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatusBadge from '@/Components/ui/UiStatusBadge.vue';
import {
    CalendarDaysIcon,
    ClockIcon,
    DocumentArrowDownIcon,
    DocumentArrowUpIcon,
    DocumentTextIcon,
    PencilSquareIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

const documentColumns = [
    { key: 'original_name', label: 'Nome File', sortable: true },
    { key: 'description', label: 'Descrizione', sortable: true },
    { key: 'created_at', label: 'Caricato il', sortable: true },
    { key: 'uploader', label: 'Caricato da', sortable: false },
];

const props = defineProps({
    practice: Object,
    notes_text: String,
    users: Array,
    procedures: Array,
    practiceTypes: Array,
    procedure_id: [Number, null],
    branches: Array,
});

const page = usePage();

// Permissions
const canUpdate = computed(() => page.props.auth.user?.permissions?.includes('practices.update'));
const canDelete = computed(() => page.props.auth.user?.permissions?.includes('practices.delete'));
const canAssign = computed(() => page.props.auth.user?.permissions?.includes('practices.assign'));
const canUploadDocument = computed(() => page.props.auth.user?.permissions?.includes('practice-documents.upload'));
const canDeleteDocument = computed(() => page.props.auth.user?.permissions?.includes('practice-documents.delete'));
const canCreateNote = computed(() => page.props.auth.user?.permissions?.includes('practice-notes.create'));
const canCreateDeadline = computed(() => page.props.auth.user?.permissions?.includes('practice-deadlines.create'));
const canUpdateDeadline = computed(() => page.props.auth.user?.permissions?.includes('practice-deadlines.update'));
const canDeleteDeadline = computed(() => page.props.auth.user?.permissions?.includes('practice-deadlines.delete'));

// Constants
const TYPES = ['730', 'ISEE', 'IMU_TASI', 'RED_INPS', 'SUCCESSIONE', 'BONUS_AGEVOLAZIONI', 'ALTRO'];
const STATUSES = ['nuova', 'in_lavorazione', 'in_attesa_documenti', 'completata', 'annullata', 'sospesa'];

// Edit Logic
const editMode = ref(false);

const editForm = useForm({
    type: props.practice?.type ?? '',
    practice_type_id: props.practice?.practice_type_id ?? null,
    procedure_id: props.procedure_id ?? null,
    status: props.practice?.status ?? '',
    reference_year: props.practice?.reference_year ?? '',
    notes: props.notes_text ?? '',
    user_ids: props.practice?.assigned_users ? props.practice.assigned_users.map(u => u.id) : [],
    deadline_at: props.practice?.deadline_at ?? '',
    branch_id: props.practice?.branch_id ?? null,
});

// Separate ref for multiselect — useForm doesn't track deep array mutations from external components
const selectedUserIds = ref(
    props.practice?.assigned_users ? props.practice.assigned_users.map(u => u.id) : []
);

// Computed filtered procedures based on practice_type_id
const filteredProcedures = computed(() => {
    if (!editForm.practice_type_id) return [];
    return (props.procedures ?? []).filter(p => p.procedure_type_id === editForm.practice_type_id);
});

// Watch practice_type_id to reset procedure_id when it changes
watch(() => editForm.practice_type_id, (newTypeId, oldTypeId) => {
    if (newTypeId !== oldTypeId) {
        editForm.procedure_id = null;
    }
});

// Watch editMode to initialize practice_type_id when entering edit mode
// This fixes the bug where Factory doesn't set practice_type_id
watch(editMode, (newEditMode) => {
    if (newEditMode && !editForm.practice_type_id && editForm.type) {
        editForm.practice_type_id = practiceTypeIdMap.value[editForm.type] ?? null;
    }
});

// Create a map of practice type short codes to IDs for filtering
// PracticeType.name follows pattern "730 - Dichiarazione dei Redditi", so we extract the short code
const practiceTypeIdMap = computed(() => {
    const map = {};
    props.practiceTypes?.forEach(pt => {
        const shortCode = pt.name.split(' - ')[0];
        map[shortCode] = pt.id;
    });
    return map;
});

// Derive practice_type_id from type when type changes
watch(() => editForm.type, (newType) => {
    if (newType !== props.practice?.type) {
        editForm.practice_type_id = practiceTypeIdMap.value[newType] ?? null;
        editForm.procedure_id = null;
    }
});

// Watch procedure_id to auto-fill notes with default_notes
watch(() => editForm.procedure_id, (newProcedureId) => {
    if (newProcedureId && editMode.value) {
        const procedure = (props.procedures ?? []).find(p => p.id === newProcedureId);
        if (procedure?.default_notes) {
            editForm.notes = procedure.default_notes;
        }
    }
});

const submitEdit = () => {
    editForm.user_ids = [...selectedUserIds.value];
    editForm.put(route('practices.update', props.practice.id), {
        preserveScroll: true,
        onSuccess: () => {
            editMode.value = false;
        }
    });
};

const cancelEdit = () => {
    editForm.type = props.practice?.type ?? '';
    editForm.practice_type_id = props.practice?.practice_type_id ?? null;
    editForm.procedure_id = props.procedure_id ?? null;
    editForm.status = props.practice?.status ?? '';
    editForm.reference_year = props.practice?.reference_year ?? '';
    editForm.notes = props.notes_text ?? '';
    editForm.deadline_at = props.practice?.deadline_at ?? '';
    editForm.branch_id = props.practice?.branch_id ?? null;
    selectedUserIds.value = props.practice?.assigned_users ? props.practice.assigned_users.map(u => u.id) : [];
    editForm.clearErrors();
    editMode.value = false;
};

// Sync form when Inertia re-renders with updated props (e.g. after successful save)
watch(() => props.practice?.assigned_users, (newUsers) => {
    if (!editMode.value) {
        selectedUserIds.value = newUsers ? newUsers.map(u => u.id) : [];
        editForm.user_ids = [...selectedUserIds.value];
    }
}, { deep: true });

watch(() => [props.practice?.type, props.practice?.status, props.practice?.reference_year, props.notes_text, props.practice?.practice_type_id, props.procedure_id, props.practice?.deadline_at, props.practice?.branch_id], () => {
    if (!editMode.value) {
        editForm.type = props.practice?.type ?? '';
        editForm.practice_type_id = props.practice?.practice_type_id ?? null;
        editForm.procedure_id = props.procedure_id ?? null;
        editForm.status = props.practice?.status ?? '';
        editForm.reference_year = props.practice?.reference_year ?? '';
        editForm.notes = props.notes_text ?? '';
        editForm.deadline_at = props.practice?.deadline_at ?? '';
        editForm.branch_id = props.practice?.branch_id ?? null;
    }
});

// Practice Deletion
const confirmingPracticeDelete = ref(false);

const deletePractice = () => {
    router.delete(route('practices.destroy', props.practice.id), {
        onSuccess: () => {
            router.visit(route('practices.index'));
        },
    });
};

// Notes Logic
const noteForm = useForm({ body: '' });

const submitNote = () => {
    if (!noteForm.body.trim()) return;

    noteForm.post(route('practices.notes.store', props.practice.id), {
        preserveScroll: true,
        onSuccess: () => noteForm.reset('body'),
    });
};

// Documents Upload Logic
const dropzoneInput = ref(null);
const isDragging = ref(false);
const stagedFiles = ref([]);

const docForm = useForm({
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
        if (dropzoneInput.value) {
            dropzoneInput.value.value = '';
        }
    }
};

const removeFile = (index) => {
    stagedFiles.value.splice(index, 1);
};

const submitUpload = () => {
    docForm.files = stagedFiles.value.map(f => f.file);
    docForm.descriptions = stagedFiles.value.map(f => f.description);
    docForm.post(route('practices.documents.store', props.practice.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            docForm.reset();
            stagedFiles.value = [];
        },
    });
};

// Document Deletion Logic
const confirmingDocDelete = ref(false);
const docToDelete = ref(null);

const confirmDocDelete = (doc) => {
    docToDelete.value = doc;
    confirmingDocDelete.value = true;
};

const deleteDocument = () => {
    router.delete(route('practices.documents.destroy', [props.practice.id, docToDelete.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            confirmingDocDelete.value = false;
            docToDelete.value = null;
        },
    });
};

const activeTab = ref('documents');

const openUpload = async () => {
    activeTab.value = 'documents';
    await nextTick();
    dropzoneInput.value?.click();
};

const tabs = computed(() => [
    { key: 'documents', label: 'Documenti', count: props.practice?.documents?.length ?? 0 },
    { key: 'timeline', label: 'Timeline', count: props.practice?.statusLogs?.length ?? 0 },
    { key: 'notes', label: 'Note', count: props.practice?.notes?.length ?? 0 },
    { key: 'details', label: 'Dettagli', count: null },
    { key: 'deadlines', label: 'Scadenze', count: props.practice?.deadlines?.length ?? 0 },
]);

const statusLabel = (status) => status ? status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : '';

const pendingDeadlines = computed(() => (props.practice?.deadlines ?? []).filter((deadline) => deadline.status === 'pending').length);

const completionPercentage = computed(() => {
    const deadlines = props.practice?.deadlines ?? [];
    if (!deadlines.length) return props.practice?.documents?.length ? 65 : 35;

    const completed = deadlines.filter((deadline) => deadline.status === 'completed').length;
    return Math.round((completed / deadlines.length) * 100);
});
</script>

<template>
    <AppLayout title="Dettaglio Pratica">
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Pratiche / Dettaglio</p>
                    <h1 class="mt-2 flex flex-wrap items-center gap-3 font-headline text-3xl font-extrabold tracking-tight text-on-surface">
                        <span>{{ practice?.type }}</span>
                        <span class="text-primary/50">#{{ practice?.id }}</span>
                    </h1>
                    <p class="mt-2 text-sm text-on-surface-variant">
                        <Link v-if="practice?.client" :href="route('clients.show', practice.client.id)" class="font-semibold text-primary transition hover:text-primary-dim">
                            {{ practice.client.first_name }} {{ practice.client.last_name }}
                        </Link>
                        <span v-if="practice?.reference_year"> · Anno fiscale {{ practice.reference_year }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        v-if="canUploadDocument"
                        type="button"
                        @click="openUpload"
                        class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-br from-primary to-primary-dim px-5 py-3 text-sm font-bold text-on-primary shadow-[0px_20px_40px_rgba(0,86,210,0.18)]"
                    >
                        <DocumentArrowUpIcon class="h-5 w-5" />
                        Carica documenti
                    </button>
                </div>
            </div>
        </template>

        <section v-if="practice" class="px-4 pb-12 lg:px-0">
            <div class="grid grid-cols-12 gap-8">
                <!-- Left Side: Practice Summary -->
                <div class="col-span-12 lg:col-span-4 space-y-6">
                    <!-- Summary Card -->
                    <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-sm shadow-blue-900/5 ring-1 ring-outline-variant/10">
                        <h3 class="text-sm font-bold text-on-surface uppercase tracking-wider mb-6">Riepilogo Pratica</h3>
                        <div class="space-y-6">
                            <div class="flex items-start">
                                <div class="w-10 h-10 rounded-full bg-primary-container flex items-center justify-center mr-4 shrink-0">
                                    <DocumentTextIcon class="h-5 w-5 text-on-primary-container" />
                                </div>
                                <div>
                                    <p class="text-xs text-on-surface-variant font-medium uppercase mb-0.5">Stato</p>
                                    <UiStatusBadge :label="statusLabel(practice.status)" :status="practice.status" />
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="w-10 h-10 rounded-full bg-tertiary-container flex items-center justify-center mr-4 shrink-0">
                                    <CalendarDaysIcon class="h-5 w-5 text-on-tertiary-container" />
                                </div>
                                <div>
                                    <p class="text-xs text-on-surface-variant font-medium uppercase mb-0.5">Scadenza</p>
                                    <p class="text-sm font-bold text-on-surface">{{ practice.deadline_at ? formatDate(practice.deadline_at) : 'Non impostata' }}</p>
                                    <span v-if="pendingDeadlines > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-error-container/20 text-on-error-container mt-1">
                                        <span class="mr-1">•</span>
                                        {{ pendingDeadlines }} scadenze aperte
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center mr-4 shrink-0">
                                    <ClockIcon class="h-5 w-5 text-on-secondary-container" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-on-surface-variant font-medium uppercase mb-0.5">Completamento</p>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-24 h-1.5 bg-surface-container-highest rounded-full overflow-hidden">
                                            <div class="h-full bg-primary rounded-full" :style="{ width: completionPercentage + '%' }"></div>
                                        </div>
                                        <span class="text-sm font-bold text-on-surface">{{ completionPercentage }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Form Section -->
                        <div v-if="editMode" class="mt-6 pt-6 border-t border-outline-variant/10 space-y-4">
                            <div>
                                <InputLabel for="edit-type" value="Tipo" />
                                <select id="edit-type" v-model="editForm.type" class="mt-1 block w-full rounded-xl border-0 bg-surface-container-high text-sm text-on-surface focus:ring-2 focus:ring-primary/25">
                                    <option v-for="t in TYPES" :key="t" :value="t">{{ t }}</option>
                                </select>
                                <InputError :message="editForm.errors.type" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="edit-procedure" value="Procedura" />
                                <select id="edit-procedure" v-model="editForm.procedure_id" class="mt-1 block w-full rounded-xl border-0 bg-surface-container-high text-sm text-on-surface focus:ring-2 focus:ring-primary/25">
                                    <option :value="null">-- Seleziona procedura --</option>
                                    <option v-for="proc in filteredProcedures" :key="proc.id" :value="proc.id">{{ proc.name }}</option>
                                </select>
                                <InputError :message="editForm.errors.procedure_id" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="edit-status" value="Stato" />
                                <select id="edit-status" v-model="editForm.status" class="mt-1 block w-full rounded-xl border-0 bg-surface-container-high text-sm text-on-surface focus:ring-2 focus:ring-primary/25">
                                    <option v-for="s in STATUSES" :key="s" :value="s">{{ s.replace(/_/g, ' ') }}</option>
                                </select>
                                <InputError :message="editForm.errors.status" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="edit-year" value="Anno Riferimento" />
                                <TextInput id="edit-year" v-model="editForm.reference_year" type="number" class="mt-1 block w-full rounded-xl border-0 bg-surface-container-high text-sm" />
                                <InputError :message="editForm.errors.reference_year" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="edit-deadline" value="Data Scadenza" />
                                <input id="edit-deadline" v-model="editForm.deadline_at" type="datetime-local" class="mt-1 block w-full rounded-xl border-0 bg-surface-container-high text-sm text-on-surface focus:ring-2 focus:ring-primary/25" />
                                <InputError :message="editForm.errors.deadline_at" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel value="Assegnato a" />
                                <Multiselect
                                    v-if="canAssign"
                                    v-model="selectedUserIds"
                                    :options="users.map((u) => ({ value: u.id, label: u.name }))"
                                    mode="tags"
                                    :searchable="true"
                                    :close-on-select="false"
                                    value-prop="value"
                                    label="label"
                                    track-by="label"
                                    placeholder="Cerca e seleziona utenti..."
                                    no-options-text="Nessun utente trovato"
                                    no-results-text="Nessun risultato"
                                    :append-to-body="true"
                                    class="mt-2"
                                />
                                <p v-else-if="practice.assigned_users?.length" class="mt-2 text-sm text-on-surface-variant">
                                    {{ practice.assigned_users.map(u => u.name).join(', ') }}
                                </p>
                                <p v-else class="mt-2 text-sm text-on-surface-variant">Nessuno assegnato</p>
                                <InputError :message="editForm.errors.user_ids" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel value="Filiale" />
                                <Multiselect
                                    v-model="editForm.branch_id"
                                    :options="[{ value: null, label: 'Nessuna' }, ...(branches ?? []).map(b => ({ value: b.id, label: b.name + ' - ' + b.city + ' (' + b.province + ')' }))]"
                                    mode="single"
                                    :searchable="true"
                                    value-prop="value"
                                    label="label"
                                    track-by="label"
                                    placeholder="Seleziona filiale..."
                                    no-options-text="Nessuna filiale trovata"
                                    no-results-text="Nessun risultato"
                                    :append-to-body="true"
                                    class="mt-2"
                                />
                                <InputError :message="editForm.errors.branch_id" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="edit-notes" value="Note Pratica" />
                                <textarea id="edit-notes" v-model="editForm.notes" rows="3" class="mt-1 block w-full rounded-xl border-0 bg-surface-container-high text-sm text-on-surface focus:ring-2 focus:ring-primary/25"></textarea>
                                <InputError :message="editForm.errors.notes" class="mt-1" />
                            </div>
                        </div>

                        <div class="mt-8 pt-8 border-t border-outline-variant/10">
                            <h4 class="text-xs font-bold text-on-surface-variant uppercase mb-4">Team Assegnato</h4>
                            <div class="flex -space-x-2">
                                <template v-if="practice.assigned_users?.length">
                                    <div
                                        v-for="user in practice.assigned_users.slice(0, 3)"
                                        :key="user.id"
                                        class="w-8 h-8 rounded-full bg-primary-container text-on-primary-container text-[10px] flex items-center justify-center font-bold ring-2 ring-surface-container-lowest"
                                        :title="user.name"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div v-if="practice.assigned_users.length > 3" class="w-8 h-8 rounded-full bg-primary text-on-primary text-[10px] flex items-center justify-center font-bold ring-2 ring-surface-container-lowest">
                                        +{{ practice.assigned_users.length - 3 }}
                                    </div>
                                </template>
                                <div v-else class="w-8 h-8 rounded-full bg-surface-container-high text-on-surface text-[10px] flex items-center justify-center font-bold ring-2 ring-surface-container-lowest">
                                    <UserGroupIcon class="h-4 w-4" />
                                </div>
                            </div>
                            <p v-if="practice.assigned_users?.length" class="mt-2 text-xs text-on-surface-variant">
                                {{ practice.assigned_users.map(u => u.name).join(', ') }}
                            </p>
                            <p v-else class="mt-2 text-xs text-on-surface-variant">Nessun utente assegnato</p>
                        </div>
                    </div>

                    <!-- Action Card -->
                    <div class="bg-primary/5 rounded-2xl p-6 border border-primary/10">
                        <h3 class="text-sm font-bold text-primary-dim mb-2">Azioni Rapide</h3>
                        <div class="space-y-3">
                            <button
                                v-if="canUpdate && !editMode"
                                @click="editMode = true"
                                class="w-full px-4 py-3 bg-surface-container-lowest text-on-surface font-semibold rounded-xl shadow-sm border border-outline-variant/10 hover:bg-surface-container transition-all flex items-center justify-center"
                            >
                                <PencilSquareIcon class="h-5 w-5 mr-2" />
                                Modifica Pratica
                            </button>

                            <template v-if="editMode">
                                <div class="flex gap-3">
                                    <SecondaryButton @click="cancelEdit" :disabled="editForm.processing" class="flex-1">
                                        Annulla
                                    </SecondaryButton>
                                    <PrimaryButton @click="submitEdit" :class="{ 'opacity-25': editForm.processing }" :disabled="editForm.processing" class="flex-1">
                                        Salva
                                    </PrimaryButton>
                                </div>
                            </template>

                            <DangerButton v-if="canDelete && !editMode" @click="confirmingPracticeDelete = true" class="w-full justify-center">
                                Elimina Pratica
                            </DangerButton>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Tabs Section -->
                <div class="col-span-12 lg:col-span-8">
                    <div class="bg-surface-container-lowest rounded-2xl shadow-sm shadow-blue-900/5 ring-1 ring-outline-variant/10 overflow-hidden flex flex-col h-full">
                        <!-- Tabs Header -->
                        <div class="flex items-center px-6 pt-6 border-b border-outline-variant/10 bg-surface-container-low/30 overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                @click="activeTab = tab.key"
                                :class="[
                                    'px-6 py-4 text-sm font-bold flex items-center whitespace-nowrap border-b-2 transition-colors',
                                    activeTab === tab.key
                                        ? 'text-primary border-primary'
                                        : 'text-on-surface-variant border-transparent hover:text-on-surface'
                                ]"
                            >
                                {{ tab.label }}
                                <span v-if="tab.count !== null" class="ml-2 w-5 h-5 rounded-full bg-surface-container-high text-[10px] flex items-center justify-center">
                                    {{ tab.count }}
                                </span>
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div class="p-0 flex-1">
                            <!-- Documents Tab -->
                            <div v-if="activeTab === 'documents'" class="p-6 space-y-6">
                                <!-- Upload Dropzone -->
                                <div v-if="canUploadDocument">
                                    <div
                                        v-if="stagedFiles.length === 0"
                                        @click="dropzoneInput?.click()"
                                        @dragover.prevent="isDragging = true"
                                        @dragleave.prevent="isDragging = false"
                                        @drop.prevent="onDrop"
                                        :class="[
                                            'rounded-2xl border-2 border-dashed p-8 text-center transition-all cursor-pointer',
                                            isDragging
                                                ? 'border-primary bg-primary/5'
                                                : 'border-outline-variant/30 hover:border-primary/40 hover:bg-primary/5'
                                        ]"
                                    >
                                        <DocumentArrowUpIcon class="mx-auto h-10 w-10 text-on-surface-variant" />
                                        <p class="mt-4 text-sm font-semibold text-on-surface">Trascina qui i file o clicca per selezionare</p>
                                        <p class="mt-2 text-xs uppercase tracking-widest text-on-surface-variant">PDF, immagini o documenti office</p>
                                        <input ref="dropzoneInput" type="file" class="hidden" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="onFileInputChange">
                                    </div>

                                    <div v-else class="rounded-2xl bg-surface-container-low p-5 ring-1 ring-outline-variant/10">
                                        <h4 class="text-sm font-semibold text-on-surface mb-4">File selezionati</h4>
                                        <div class="space-y-3">
                                            <div v-for="(item, index) in stagedFiles" :key="index" class="grid gap-3 rounded-xl bg-surface-container-lowest p-4 ring-1 ring-outline-variant/10 md:grid-cols-[1fr_220px_auto] md:items-center">
                                                <div>
                                                    <p class="text-sm font-semibold text-on-surface">{{ item.file.name }}</p>
                                                    <p class="mt-1 text-xs text-on-surface-variant">{{ formatFileSize(item.file.size) }}</p>
                                                </div>
                                                <input v-model="item.description" type="text" placeholder="Descrizione..." class="w-full rounded-xl border-0 bg-surface-container-high text-sm focus:ring-2 focus:ring-primary/25">
                                                <button @click="removeFile(index)" type="button" class="rounded-xl bg-error-container/20 px-4 py-2 text-sm font-semibold text-error hover:bg-error-container/30 transition-colors">
                                                    Rimuovi
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-5 flex justify-end gap-3 border-t border-outline-variant/10 pt-4">
                                            <SecondaryButton type="button" @click="stagedFiles.splice(0)" :disabled="docForm.processing">
                                                Annulla
                                            </SecondaryButton>
                                            <PrimaryButton type="button" @click="submitUpload" :class="{ 'opacity-25': docForm.processing }" :disabled="docForm.processing">
                                                Carica documenti
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </div>

                                <!-- Documents Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-surface-container-low/50">
                                            <tr>
                                                <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nome</th>
                                                <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Descrizione</th>
                                                <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Data</th>
                                                <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-right">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant/10">
                                            <tr v-if="!practice.documents?.length" class="group hover:bg-surface-container-low transition-colors">
                                                <td colspan="4" class="px-6 py-8 text-center text-on-surface-variant text-sm">
                                                    Nessun documento caricato.
                                                </td>
                                            </tr>
                                            <tr v-for="doc in practice.documents" :key="doc.id" class="group hover:bg-surface-container-low transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center">
                                                        <div class="w-10 h-10 rounded-lg bg-error-container/10 flex items-center justify-center mr-3">
                                                            <DocumentTextIcon class="h-5 w-5 text-error" />
                                                        </div>
                                                        <div>
                                                            <p class="text-sm font-bold text-on-surface">{{ doc.original_name }}</p>
                                                            <p class="text-[11px] text-on-surface-variant">{{ formatFileSize(doc.file_size) }} • {{ doc.description || 'Senza descrizione' }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-xs font-medium px-2 py-1 bg-surface-container-high rounded text-on-surface-variant">{{ doc.description || '-' }}</span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <p class="text-sm text-on-surface">{{ formatDate(doc.created_at) }}</p>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex items-center justify-end space-x-2">
                                                        <a :href="route('practices.documents.download', [practice.id, doc.id])" class="p-2 text-on-surface-variant hover:text-primary transition-colors hover:bg-primary/5 rounded-lg">
                                                            <DocumentArrowDownIcon class="h-5 w-5" />
                                                        </a>
                                                        <button v-if="canDeleteDocument" @click="confirmDocDelete(doc)" class="p-2 text-on-surface-variant hover:text-error transition-colors hover:bg-error/5 rounded-lg">
                                                            Elimina
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Timeline Tab -->
                            <div v-else-if="activeTab === 'timeline'" class="p-6">
                                <h3 class="text-xl font-headline font-extrabold text-on-surface mb-6 flex items-center">
                                    <ClockIcon class="mr-3 h-5 w-5 text-primary" />
                                    Timeline Audit
                                </h3>
                                <div class="relative pl-8 space-y-8 before:absolute before:left-3.5 before:top-2 before:bottom-2 before:w-[2px] before:bg-surface-container-highest">
                                    <div v-if="!practice.statusLogs?.length" class="text-sm text-on-surface-variant">
                                        Nessuno storico disponibile.
                                    </div>
                                    <div v-for="log in practice.statusLogs" :key="log.id" class="relative">
                                        <div class="absolute -left-[30px] top-1 w-[18px] h-[18px] rounded-full bg-primary border-4 border-background ring-4 ring-primary/10"></div>
                                        <div>
                                            <p class="text-sm font-bold text-on-surface">{{ statusLabel(log.new_status) }}</p>
                                            <p class="text-xs text-on-surface-variant mb-2">
                                                <template v-if="log.old_status">Da {{ statusLabel(log.old_status) }} a </template>{{ statusLabel(log.new_status) }} • {{ log.user?.name || 'Sistema' }}
                                            </p>
                                            <p class="text-xs text-on-surface-variant">{{ formatDateTime(log.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Tab -->
                            <div v-else-if="activeTab === 'notes'" class="p-6 space-y-6">
                                <h3 class="text-xl font-headline font-extrabold text-on-surface mb-6 flex items-center">
                                    <PencilSquareIcon class="mr-3 h-5 w-5 text-primary" />
                                    Note Pratica
                                </h3>

                                <div class="space-y-4">
                                    <div v-if="!practice.notes?.length" class="text-sm text-on-surface-variant">
                                        Nessuna nota presente.
                                    </div>
                                    <article v-for="note in practice.notes" :key="note.id" class="rounded-2xl bg-surface-container-low p-5 ring-1 ring-outline-variant/10">
                                        <div class="mb-3 flex items-start justify-between gap-4 border-b border-outline-variant/10 pb-3">
                                            <span class="text-sm font-semibold text-on-surface">{{ note.author?.name || 'Utente' }}</span>
                                            <span class="text-xs text-on-surface-variant">{{ formatDateTime(note.created_at) }}</span>
                                        </div>
                                        <p class="whitespace-pre-wrap text-sm text-on-surface-variant">{{ note.body }}</p>
                                    </article>
                                </div>

                                <div v-if="canCreateNote" class="rounded-2xl bg-surface-container-low p-5 ring-1 ring-outline-variant/10">
                                    <InputLabel for="new_note" value="Aggiungi una nota" />
                                    <textarea id="new_note" v-model="noteForm.body" rows="4" class="mt-3 block w-full rounded-xl border-0 bg-surface-container-lowest text-sm text-on-surface focus:ring-2 focus:ring-primary/25" placeholder="Scrivi qui la tua nota..."></textarea>
                                    <div class="mt-4 flex justify-end">
                                        <PrimaryButton @click="submitNote" :disabled="noteForm.processing || !noteForm.body.trim()">
                                            Aggiungi Nota
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Tab -->
                            <div v-else-if="activeTab === 'details'" class="p-6">
                                <h3 class="text-xl font-headline font-extrabold text-on-surface mb-6 flex items-center">
                                    <DocumentTextIcon class="mr-3 h-5 w-5 text-primary" />
                                    Dettagli Pratica
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel value="Cliente" />
                                        <div class="mt-1">
                                            <Link v-if="practice.client" :href="route('clients.show', practice.client.id)" class="text-sm font-semibold text-primary transition hover:text-primary-dim">
                                                {{ practice.client.first_name }} {{ practice.client.last_name }}
                                            </Link>
                                            <p v-else class="text-sm text-on-surface-variant">N/A</p>
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel value="Tipo" />
                                        <p class="mt-1 text-sm font-semibold text-on-surface">{{ practice.type }}</p>
                                    </div>

                                    <div>
                                        <InputLabel value="Procedura" />
                                        <p class="mt-1 text-sm font-semibold text-on-surface">{{ practice.procedure?.name || 'Nessuna procedura' }}</p>
                                    </div>

                                    <div>
                                        <InputLabel value="Stato" />
                                        <div class="mt-1">
                                            <UiStatusBadge :label="statusLabel(practice.status)" :status="practice.status" />
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel value="Anno Riferimento" />
                                        <p class="mt-1 text-sm font-semibold text-on-surface">{{ practice.reference_year || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <InputLabel value="Filiale" />
                                        <p class="mt-1 text-sm font-semibold text-on-surface">
                                            {{ practice.branch ? practice.branch.name + ' - ' + practice.branch.city + ' (' + practice.branch.province + ')' : 'Nessuna' }}
                                        </p>
                                    </div>

                                    <div>
                                        <InputLabel value="Data Scadenza" />
                                        <p class="mt-1 text-sm font-semibold text-on-surface">{{ practice.deadline_at ? formatDateTime(practice.deadline_at) : 'Non impostata' }}</p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <InputLabel value="Assegnato a" />
                                        <p class="mt-1 text-sm text-on-surface-variant">{{ practice.assigned_users?.map(u => u.name).join(', ') || 'Nessuno' }}</p>
                                    </div>

                                    <div v-if="notes_text" class="md:col-span-2">
                                        <InputLabel value="Note Pratica" />
                                        <p class="mt-2 whitespace-pre-wrap text-sm text-on-surface-variant">{{ notes_text }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Deadlines Tab -->
                            <div v-else-if="activeTab === 'deadlines'" class="p-6">
                                <DeadlinesSection
                                    :deadlines="practice.deadlines || []"
                                    :practice-id="practice.id"
                                    :can-create="canCreateDeadline"
                                    :can-edit="canUpdateDeadline"
                                    :can-delete="canDeleteDeadline"
                                    :users="practice.assigned_users || []"
                                    @refresh="$inertia.reload({ only: ['practice'] })"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Document Deletion Confirmation Modal -->
        <ConfirmationModal :show="confirmingDocDelete" @close="confirmingDocDelete = false">
            <template #title>
                Elimina Documento
            </template>

            <template #content>
                Sei sicuro di voler eliminare questo documento? L'azione non può essere annullata.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingDocDelete = false">
                    Annulla
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :disabled="docForm.processing"
                    @click="deleteDocument"
                >
                    Elimina Documento
                </DangerButton>
            </template>
        </ConfirmationModal>

        <!-- Practice Deletion Confirmation Modal -->
        <ConfirmationModal :show="confirmingPracticeDelete" @close="confirmingPracticeDelete = false">
            <template #title>
                Elimina Pratica
            </template>

            <template #content>
                Sei sicuro di voler eliminare questa pratica? Tutti i dati, documenti e log associati andranno persi. L'azione non può essere annullata.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingPracticeDelete = false">
                    Annulla
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="deletePractice"
                >
                    Elimina Pratica
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
