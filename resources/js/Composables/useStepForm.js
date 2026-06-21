import { ref, computed } from 'vue';

const STORAGE_KEY = 'pratica-draft';
const DEBOUNCE_DELAY = 300;

const stepFieldMap = {
    1: ['client_profile_id'],
    2: ['type', 'procedure_id', 'reference_year', 'deadline_at'],
    3: ['status', 'notes', 'user_ids'],
};

let saveTimeout = null;

export function useStepForm(options = {}) {
    const currentStep = ref(options.currentStep ?? 1);
    const totalSteps = ref(options.totalSteps ?? 3);

    const isFirstStep = computed(() => currentStep.value === 1);
    const isLastStep = computed(() => currentStep.value === totalSteps.value);

    const nextStep = () => {
        if (currentStep.value < totalSteps.value) {
            currentStep.value++;
        }
    };

    const prevStep = () => {
        if (currentStep.value > 1) {
            currentStep.value--;
        }
    };

    const goToStep = (step) => {
        if (step >= 1 && step <= totalSteps.value) {
            currentStep.value = step;
        }
    };

    const saveDraft = (data) => {
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }

        saveTimeout = setTimeout(() => {
            try {
                const draftData = {
                    currentStep: currentStep.value,
                    data,
                    savedAt: Date.now(),
                };
                localStorage.setItem(STORAGE_KEY, JSON.stringify(draftData));
            } catch (error) {
                if (error.name === 'QuotaExceededError' || error.name === 'NS_ERROR_FILE_CORRUPTED') {
                    console.warn('localStorage quota exceeded, clearing old drafts');
                    try {
                        localStorage.removeItem(STORAGE_KEY);
                    } catch (e) {
                        return;
                    }
                }
            }
        }, DEBOUNCE_DELAY);
    };

    const loadDraft = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (!stored) return null;

            const draftData = JSON.parse(stored);

            if (draftData.currentStep) {
                currentStep.value = draftData.currentStep;
            }

            return draftData;
        } catch (error) {
            console.warn('Failed to load draft from localStorage:', error);
            return null;
        }
    };

    const clearDraft = () => {
        try {
            if (saveTimeout) {
                clearTimeout(saveTimeout);
                saveTimeout = null;
            }
            localStorage.removeItem(STORAGE_KEY);
        } catch (error) {
            console.warn('Failed to clear draft from localStorage:', error);
        }
    };

    const mapErrors = (formErrors) => {
        const stepErrors = {};

        for (const [step, fields] of Object.entries(stepFieldMap)) {
            const errors = {};
            let hasErrors = false;

            for (const field of fields) {
                if (formErrors[field]) {
                    errors[field] = formErrors[field];
                    hasErrors = true;
                }
            }

            if (hasErrors) {
                stepErrors[parseInt(step)] = errors;
            }
        }

        return stepErrors;
    };

    const hasStepErrors = (stepIndex) => {
        return (formErrors) => {
            const fields = stepFieldMap[stepIndex];
            if (!fields) return false;

            return fields.some(field => formErrors[field]);
        };
    };

    return {
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
        hasStepErrors,
        stepFieldMap,
    };
}
