<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { formatDateTime } from '@/utils/date.js';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    deadlines: {
        type: Array,
        default: () => [],
    },
    practiceId: {
        type: Number,
        required: true,
    },
    canCreate: {
        type: Boolean,
        default: false,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['refresh']);

// ── Status & Priority Configuration ─────────────────────────────────────────────
const STATUS_CONFIG = {
    pending: {
        label: 'In attesa',
        icon: 'circle',
        class: 'bg-surface-container-low text-on-surface-variant',
    },
    in_progress: {
        label: 'In corso',
        icon: 'circle',
        class: 'bg-primary/10 text-primary',
    },
    completed: {
        label: 'Completata',
        icon: 'check',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
    },
    cancelled: {
        label: 'Annullata',
        icon: 'x',
        class: 'bg-surface-container-low text-on-surface-variant',
    },
};

const PRIORITY_CONFIG = {
    1: { label: 'Urgente', class: 'bg-error-container/30 text-on-error-container' },
    2: { label: 'Alta', class: 'bg-primary-container/80 text-on-primary-container' },
    3: { label: 'Media', class: 'bg-tertiary-container text-on-tertiary-container' },
    4: { label: 'Bassa', class: 'bg-surface-container-low text-on-surface-variant' },
};

// ── State ──────────────────────────────────────────────────────────────────────
const localDeadlines = ref([...props.deadlines]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingDeadline = ref(null);
const deletingDeadline = ref(null);

// Forms using Inertia
const createForm = useForm({
    title: '',
    notes: '',
    deadline_at: '',
    priority: 3,
    user_id: null,
});

const editForm = useForm({
    title: '',
    notes: '',
    deadline_at: '',
    priority: 3,
    user_id: null,
});

const deleteForm = useForm({});

// ── Computed ────────────────────────────────────────────────────────────────────
const sortedDeadlines = computed(() => {
    return [...localDeadlines.value].sort((a, b) => {
        const dateA = new Date(a.deadline_at);
        const dateB = new Date(b.deadline_at);
        return dateA - dateB;
    });
});

// ── Watchers ────────────────────────────────────────────────────────────────────
watch(() => props.deadlines, (newDeadlines) => {
    localDeadlines.value = [...newDeadlines];
}, { deep: true });

// ── Helpers ──────────────────────────────────────────────────────────────────────
const getStatusConfig = (status) => STATUS_CONFIG[status] || STATUS_CONFIG.pending;
const getPriorityConfig = (priority) => PRIORITY_CONFIG[priority] || PRIORITY_CONFIG[4];

const formatDeadlineDate = (dateStr) => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '—';
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
};

const isOverdue = (deadline) => {
    if (deadline.status === 'completed' || deadline.status === 'cancelled') return false;
    return new Date(deadline.deadline_at) < new Date();
};

const resetForms = () => {
    createForm.reset();
    editForm.reset();
};

// ── Form Actions ────────────────────────────────────────────────────────────────
const createDeadline = () => {
    createForm.post(route('practices.deadlines.store', props.practiceId), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            emit('refresh');
        },
    });
};

const updateDeadline = () => {
    if (!editingDeadline.value) return;

    editForm.put(route('practices.deadlines.update', [props.practiceId, editingDeadline.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingDeadline.value = null;
            editForm.reset();
            emit('refresh');
        },
    });
};

const deleteDeadline = () => {
    if (!deletingDeadline.value) return;

    deleteForm.delete(route('practices.deadlines.destroy', [props.practiceId, deletingDeadline.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingDeadline.value = null;
            emit('refresh');
        },
    });
};

// ── Quick Status Update ───────────────────────────────────────────────────────────
const statusUpdateForm = useForm({});
const updatingStatusId = ref(null);

const updateStatus = (deadline, newStatus) => {
    updatingStatusId.value = deadline.id;
    statusUpdateForm.put(route('practices.deadlines.update', [props.practiceId, deadline.id]), {
        data: { status: newStatus },
        preserveScroll: true,
        onSuccess: () => {
            updatingStatusId.value = null;
            emit('refresh');
        },
        onError: () => {
            updatingStatusId.value = null;
        },
    });
};

const STATUS_TRANSITIONS = {
    pending: ['in_progress'],
    in_progress: ['completed', 'pending'],
    completed: ['in_progress'],
    cancelled: ['pending'],
};

// ── Modal Handlers ──────────────────────────────────────────────────────────────
const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const openEditModal = (deadline) => {
    editingDeadline.value = deadline;
    // Convert datetime for datetime-local input (YYYY-MM-DDTHH:mm)
    let deadlineAt = '';
    if (deadline.deadline_at) {
        const date = new Date(deadline.deadline_at);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        deadlineAt = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    editForm.title = deadline.title || '';
    editForm.notes = deadline.notes || '';
    editForm.deadline_at = deadlineAt;
    editForm.priority = deadline.priority || 3;
    editForm.user_id = deadline.user_id || null;
    editForm.clearErrors();
    showEditModal.value = true;
};

const openDeleteModal = (deadline) => {
    deletingDeadline.value = deadline;
    showDeleteModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingDeadline.value = null;
    editForm.reset();
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingDeadline.value = null;
};
</script>

<template>
    <div class="overflow-hidden bg-surface-container-lowest p-6 shadow-xl sm:rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-on-surface">Scadenze</h3>
            <PrimaryButton v-if="canCreate" @click="openCreateModal">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nuova Scadenza
            </PrimaryButton>
        </div>

        <!-- Deadlines List -->
        <div v-if="sortedDeadlines.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="mt-2 text-sm text-outline">Nessuna scadenza presente.</p>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="deadline in sortedDeadlines"
                :key="deadline.id"
                class="border rounded-lg p-4 transition-colors"
                :class="[
                    isOverdue(deadline)
                        ? 'border-red-200 dark:border-red-900/50 bg-red-50/50 dark:bg-red-900/10'
                        : 'border-outline-variant bg-surface-container-lowest/50'
                ]"
            >
                <div class="flex items-start justify-between gap-4">
                    <!-- Left: Title, Date, Badges -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="font-semibold text-on-surface  truncate">
                                {{ deadline.title }}
                            </h4>
                            <!-- Status Badge with Quick Actions -->
                            <div class="relative inline-flex items-center gap-1">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium',
                                        getStatusConfig(deadline.status).class
                                    ]"
                                >
                                    <!-- Status Icon -->
                                    <svg v-if="getStatusConfig(deadline.status).icon === 'check'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else-if="getStatusConfig(deadline.status).icon === 'x'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="6" fill="currentColor" />
                                    </svg>
                                    {{ getStatusConfig(deadline.status).label }}
                                </span>
                                <!-- Quick Status Change Buttons -->
                                <div v-if="canEdit && STATUS_TRANSITIONS[deadline.status]?.length > 0" class="flex items-center gap-1">
                                    <button
                                        v-for="nextStatus in STATUS_TRANSITIONS[deadline.status]"
                                        :key="nextStatus"
                                        @click="updateStatus(deadline, nextStatus)"
                                        :disabled="updatingStatusId === deadline.id"
                                        :title="`Cambia in ${getStatusConfig(nextStatus).label}`"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full text-xs transition-colors hover:bg-surface-container-high disabled:opacity-50"
                                        :class="[
                                            nextStatus === 'in_progress' ? 'text-yellow-600 hover:text-yellow-700' : '',
                                            nextStatus === 'completed' ? 'text-green-600 hover:text-green-700' : '',
                                            nextStatus === 'pending' ? 'text-on-surface-variant hover:text-on-surface-variant' : ''
                                        ]"
                                    >
                                        <svg v-if="updatingStatusId === deadline.id" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else-if="nextStatus === 'in_progress'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <svg v-else-if="nextStatus === 'completed'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Priority Badge -->
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-xs font-medium',
                                    getPriorityConfig(deadline.priority).class
                                ]"
                            >
                                {{ getPriorityConfig(deadline.priority).label }}
                            </span>
                            <!-- Overdue Badge -->
                            <span
                                v-if="isOverdue(deadline)"
                                class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white"
                            >
                                Scaduta
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-on-surface-variant ">
                            <svg class="mr-1 inline h-4 w-4 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ formatDeadlineDate(deadline.deadline_at) }}
                        </p>
                        <!-- Assigned User -->
                        <div v-if="deadline.assignee" class="mt-2 flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-container/70">
                                <span class="text-xs font-medium text-on-primary-container">
                                    {{ deadline.assignee.name?.charAt(0)?.toUpperCase() || '?' }}
                                </span>
                            </div>
                            <span class="text-sm text-on-surface-variant">{{ deadline.assignee.name }}</span>
                        </div>
                        <!-- Notes -->
                        <p v-if="deadline.notes" class="mt-2 whitespace-pre-wrap text-sm text-outline">
                            {{ deadline.notes }}
                        </p>
                    </div>

                    <!-- Right: Actions -->
                    <div v-if="canEdit || canDelete" class="flex items-center gap-2 flex-shrink-0">
                        <button
                            v-if="canEdit"
                            @click="openEditModal(deadline)"
                            class="p-1.5 text-gray-400 hover:text-primary dark:hover:text-indigo-400 transition-colors"
                            title="Modifica"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button
                            v-if="canDelete"
                            @click="openDeleteModal(deadline)"
                            class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                            title="Elimina"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <ConfirmationModal :show="showCreateModal" @close="closeCreateModal" max-width="lg">
            <template #title>
                Nuova Scadenza
            </template>

            <template #content>
                <div class="space-y-4">
                    <!-- Title -->
                    <div>
                        <InputLabel for="deadline_title" value="Titolo *" />
                        <TextInput
                            id="deadline_title"
                            v-model="createForm.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Es. Scadenza dichiarazione"
                        />
                        <InputError :message="createForm.errors.title" class="mt-1" />
                    </div>

                    <!-- Deadline Date -->
                    <div>
                        <InputLabel for="deadline_at" value="Data e ora scadenza *" />
                        <input
                            id="deadline_at"
                            v-model="createForm.deadline_at"
                            type="datetime-local"
                            class="app-input mt-1 block w-full rounded-lg"
                        />
                        <InputError :message="createForm.errors.deadline_at" class="mt-1" />
                    </div>

                    <!-- Priority -->
                    <div>
                        <InputLabel for="deadline_priority" value="Priorità" />
                        <select
                            id="deadline_priority"
                            v-model="createForm.priority"
                            class="app-input mt-1 block w-full rounded-lg"
                        >
                            <option :value="1">Urgente</option>
                            <option :value="2">Alta</option>
                            <option :value="3">Media</option>
                            <option :value="4">Bassa</option>
                        </select>
                        <InputError :message="createForm.errors.priority" class="mt-1" />
                    </div>

                    <!-- Assigned User -->
                    <div>
                        <InputLabel for="deadline_user" value="Assegnato a" />
                        <select
                            id="deadline_user"
                            v-model="createForm.user_id"
                            class="app-input mt-1 block w-full rounded-lg"
                        >
                            <option :value="null">-- Non assegnato --</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.user_id" class="mt-1" />
                    </div>

                    <!-- Notes -->
                    <div>
                        <InputLabel for="deadline_notes" value="Note" />
                        <textarea
                            id="deadline_notes"
                            v-model="createForm.notes"
                            rows="3"
                            class="app-input mt-1 block w-full rounded-lg"
                            placeholder="Note aggiuntive..."
                        ></textarea>
                        <InputError :message="createForm.errors.notes" class="mt-1" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeCreateModal" :disabled="createForm.processing">
                    Annulla
                </SecondaryButton>
                <PrimaryButton
                    class="ms-3"
                    :disabled="createForm.processing || !createForm.title || !createForm.deadline_at"
                    @click="createDeadline"
                >
                    <svg v-if="createForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Crea Scadenza
                </PrimaryButton>
            </template>
        </ConfirmationModal>

        <!-- Edit Modal -->
        <ConfirmationModal :show="showEditModal" @close="closeEditModal" max-width="lg">
            <template #title>
                Modifica Scadenza
            </template>

            <template #content>
                <div class="space-y-4">
                    <!-- Title -->
                    <div>
                        <InputLabel for="edit_deadline_title" value="Titolo *" />
                        <TextInput
                            id="edit_deadline_title"
                            v-model="editForm.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Es. Scadenza dichiarazione"
                        />
                        <InputError :message="editForm.errors.title" class="mt-1" />
                    </div>

                    <!-- Deadline Date -->
                    <div>
                        <InputLabel for="edit_deadline_at" value="Data e ora scadenza *" />
                        <input
                            id="edit_deadline_at"
                            v-model="editForm.deadline_at"
                            type="datetime-local"
                            class="app-input mt-1 block w-full rounded-lg"
                        />
                        <InputError :message="editForm.errors.deadline_at" class="mt-1" />
                    </div>

                    <!-- Priority -->
                    <div>
                        <InputLabel for="edit_deadline_priority" value="Priorità" />
                        <select
                            id="edit_deadline_priority"
                            v-model="editForm.priority"
                            class="app-input mt-1 block w-full rounded-lg"
                        >
                            <option :value="1">Urgente</option>
                            <option :value="2">Alta</option>
                            <option :value="3">Media</option>
                            <option :value="4">Bassa</option>
                        </select>
                        <InputError :message="editForm.errors.priority" class="mt-1" />
                    </div>

                    <!-- Assigned User -->
                    <div>
                        <InputLabel for="edit_deadline_user" value="Assegnato a" />
                        <select
                            id="edit_deadline_user"
                            v-model="editForm.user_id"
                            class="mt-1 block w-full rounded-lg border-gray-300    focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="null">-- Non assegnato --</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.user_id" class="mt-1" />
                    </div>

                    <!-- Notes -->
                    <div>
                        <InputLabel for="edit_deadline_notes" value="Note" />
                        <textarea
                            id="edit_deadline_notes"
                            v-model="editForm.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-gray-300    focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Note aggiuntive..."
                        ></textarea>
                        <InputError :message="editForm.errors.notes" class="mt-1" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeEditModal" :disabled="editForm.processing">
                    Annulla
                </SecondaryButton>
                <PrimaryButton
                    class="ms-3"
                    :disabled="editForm.processing || !editForm.title || !editForm.deadline_at"
                    @click="updateDeadline"
                >
                    <svg v-if="editForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Salva Modifiche
                </PrimaryButton>
            </template>
        </ConfirmationModal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="showDeleteModal" @close="closeDeleteModal">
            <template #title>
                Elimina Scadenza
            </template>

            <template #content>
                <p class="text-sm text-on-surface-variant">
                    Sei sicuro di voler eliminare la scadenza <strong>"{{ deletingDeadline?.title }}"</strong>?
                    L'azione non può essere annullata.
                </p>
            </template>

            <template #footer>
                <SecondaryButton @click="closeDeleteModal" :disabled="deleteForm.processing">
                    Annulla
                </SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :disabled="deleteForm.processing"
                    @click="deleteDeadline"
                >
                    <svg v-if="deleteForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Elimina
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
</template>
