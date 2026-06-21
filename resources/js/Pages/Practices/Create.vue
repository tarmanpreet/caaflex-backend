<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { computed, watch, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StepForm from '@/Components/StepForm.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Multiselect from '@vueform/multiselect';
import ClientSelect from '@/Components/ClientSelect.vue';
import { useStepForm } from '@/Composables/useStepForm';
import { useToast } from 'vue-toastification';

const props = defineProps({
    users: Array,
    procedures: Array,
    practiceTypes: Array,
    branches: Array,
});

const toast = useToast();

const TYPES = ['730', 'ISEE', 'IMU_TASI', 'RED_INPS', 'SUCCESSIONE', 'BONUS_AGEVOLAZIONI', 'ALTRO'];
const STATUSES = ['nuova', 'in_lavorazione', 'in_attesa_documenti', 'completata', 'annullata', 'sospesa'];

const formatStatus = (status) => {
    if (!status) return '';
    return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

const form = useForm({
    client_profile_id: null,
    type: '',
    procedure_id: null,
    status: 'nuova',
    reference_year: new Date().getFullYear(),
    notes: '',
    user_ids: [],
    deadline_at: '',
    branch_id: null,
});

const {
    currentStep,
    totalSteps,
    isFirstStep,
    isLastStep,
    nextStep,
    prevStep,
    goToStep,
    saveDraft,
    loadDraft,
    clearDraft,
    mapErrors,
} = useStepForm({ totalSteps: 3 });

const practiceTypeIdMap = computed(() => {
    const map = {};
    props.practiceTypes.forEach(pt => {
        const shortCode = pt.name.split(' - ')[0];
        map[shortCode] = pt.id;
    });
    return map;
});

const filteredProcedures = computed(() => {
    if (!form.type) return [];
    const practiceTypeId = practiceTypeIdMap.value[form.type];
    if (!practiceTypeId) return [];
    return props.procedures.filter(p => p.procedure_type_id === practiceTypeId);
});

watch(() => form.type, (newType) => {
    form.procedure_id = null;
    saveDraft(form.data());
});

watch(() => form.procedure_id, (newProcedureId) => {
    if (newProcedureId) {
        const procedure = props.procedures.find(p => p.id === newProcedureId);
        if (procedure) {
            if (procedure.default_notes && !form.notes) {
                form.notes = procedure.default_notes;
            }
            if (procedure.deadline_days && !form.deadline_at) {
                const deadlineDate = new Date();
                deadlineDate.setDate(deadlineDate.getDate() + procedure.deadline_days);
                const year = deadlineDate.getFullYear();
                const month = String(deadlineDate.getMonth() + 1).padStart(2, '0');
                const day = String(deadlineDate.getDate()).padStart(2, '0');
                const hours = String(deadlineDate.getHours()).padStart(2, '0');
                const minutes = String(deadlineDate.getMinutes()).padStart(2, '0');
                form.deadline_at = `${year}-${month}-${day}T${hours}:${minutes}`;
            }
        }
    }
    saveDraft(form.data());
});

watch(() => [form.client_profile_id, form.reference_year, form.deadline_at, form.status, form.notes], () => {
    saveDraft(form.data());
});

watch(() => form.user_ids, () => {
    saveDraft(form.data());
}, { deep: true });

onMounted(() => {
    const draft = loadDraft();
    if (draft?.data && (draft.data.client_profile_id || draft.data.type)) {
        form.client_profile_id = draft.data.client_profile_id ?? form.client_profile_id;
        form.type = draft.data.type ?? form.type;
        form.procedure_id = draft.data.procedure_id ?? form.procedure_id;
        form.status = draft.data.status ?? form.status;
        form.reference_year = draft.data.reference_year ?? form.reference_year;
        form.notes = draft.data.notes ?? form.notes;
        form.user_ids = draft.data.user_ids ?? form.user_ids;
        form.deadline_at = draft.data.deadline_at ?? form.deadline_at;
        toast.info('Bozza ripristinata. Puoi continuare da dove avevi interrotto.');
    }
});

const stepErrors = computed(() => mapErrors(form.errors));

const submitForm = () => {
    clearDraft();
    form.post(route('practices.store'));
};

const steps = [
    { label: 'Cliente', description: 'Seleziona il cliente' },
    { label: 'Tipo e Procedura', description: 'Definisci il tipo e la procedura' },
    { label: 'Dettagli e Assegnazione', description: 'Aggiungi note e assegna operatori' },
];
</script>

<template>
    <AppLayout title="Nuova Pratica">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Nuova Pratica</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg">
                    <div class="p-6 overflow-visible">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Nuova Pratica</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Crea una nuova pratica e assegnala agli operatori.</p>
                        
                        <StepForm 
                            v-model:currentStep="currentStep"
                            :steps="steps"
                            :errors="stepErrors"
                        >
                            <template #step-1>
                                <div class="col-span-6">
                                    <InputLabel for="client_profile_id" value="Cliente" />
                                    <ClientSelect
                                        v-model="form.client_profile_id"
                                    />
                                    <InputError :message="form.errors.client_profile_id" class="mt-2" />
                                </div>
                            </template>

                            <template #step-2>
                                <div class="col-span-6">
                                    <InputLabel for="type" value="Tipo" />
                                    <select 
                                        id="type"
                                        v-model="form.type" 
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                        required
                                    >
                                        <option value="" disabled>Seleziona tipo...</option>
                                        <option v-for="t in TYPES" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                    <InputError :message="form.errors.type" class="mt-2" />
                                </div>

                                <div class="col-span-6">
                                    <InputLabel for="procedure_id" value="Procedura" />
                                    <select 
                                        id="procedure_id"
                                        v-model="form.procedure_id" 
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                        :disabled="!form.type"
                                    >
                                        <option :value="null">Seleziona procedura...</option>
                                        <option v-for="procedure in filteredProcedures" :key="procedure.id" :value="procedure.id">
                                            {{ procedure.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.procedure_id" class="mt-2" />
                                </div>

                                <div class="col-span-6">
                                    <InputLabel for="reference_year" value="Anno Riferimento" />
                                    <TextInput 
                                        id="reference_year"
                                        type="number"
                                        v-model="form.reference_year" 
                                        class="mt-1 block w-full"
                                        min="2000"
                                        max="2100"
                                        required
                                    />
                                    <InputError :message="form.errors.reference_year" class="mt-2" />
                                </div>

                                <div class="col-span-6">
                                    <InputLabel for="deadline_at" value="Data Scadenza" />
                                    <input 
                                        id="deadline_at"
                                        type="datetime-local"
                                        v-model="form.deadline_at" 
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Calcolata automaticamente dalla procedura, modificabile
                                    </p>
                                    <InputError :message="form.errors.deadline_at" class="mt-2" />
                                </div>
                            </template>

                            <template #step-3>
                                <div class="col-span-6">
                                    <InputLabel for="status" value="Stato" />
                                    <select 
                                        id="status"
                                        v-model="form.status" 
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                        required
                                    >
                                        <option v-for="s in STATUSES" :key="s" :value="s">{{ formatStatus(s) }}</option>
                                    </select>
                                    <InputError :message="form.errors.status" class="mt-2" />
                                </div>

                                <div class="col-span-6">
                                    <InputLabel for="notes" value="Note" />
                                    <textarea 
                                        id="notes"
                                        v-model="form.notes" 
                                        rows="3" 
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    ></textarea>
                                    <InputError :message="form.errors.notes" class="mt-2" />
                                </div>

                                <div class="col-span-6">
                                    <InputLabel for="user_ids" value="Assegna a" />
                                    <Multiselect
                                        v-model="form.user_ids"
                                        :options="(users ?? []).map(u => ({ value: u.id, label: u.name }))"
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
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.user_ids" class="mt-2" />
                                </div>

                                <div class="col-span-6">
                                    <InputLabel for="branch_id" value="Filiale" />
                                    <Multiselect
                                        v-model="form.branch_id"
                                        :options="[{ value: null, label: 'Nessuna' }, ...(branches ?? []).map(b => ({ value: b.id, label: b.name + ' - ' + b.city + ' (' + b.province + ')' }))]"
                                        mode="single"
                                        :searchable="true"
                                        value-prop="value"
                                        label="label"
                                        track-by="label"
                                        placeholder="Seleziona filiale..."
                                        no-options-text="Nessuna filiale trovata"
                                        no-results-text="Nessun risultato"
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.branch_id" class="mt-2" />
                                </div>
                            </template>

                            <template #actions>
                                <div class="flex items-center justify-between w-full">
                                    <div class="flex items-center">
                                        <SecondaryButton 
                                            type="button" 
                                            @click="router.get(route('practices.index'))"
                                        >
                                            Annulla
                                        </SecondaryButton>
                                    </div>
                                    
                                    <div class="flex items-center gap-3">
                                        <SecondaryButton 
                                            v-if="!isFirstStep" 
                                            type="button"
                                            @click="prevStep"
                                        >
                                            Indietro
                                        </SecondaryButton>
                                        
                                        <PrimaryButton 
                                            v-if="!isLastStep" 
                                            type="button"
                                            @click="nextStep"
                                        >
                                            Avanti
                                        </PrimaryButton>
                                        
                                        <PrimaryButton 
                                            v-else 
                                            type="button"
                                            @click="submitForm" 
                                            :disabled="form.processing"
                                            :class="{ 'opacity-25': form.processing }"
                                        >
                                            Salva
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </template>
                        </StepForm>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
