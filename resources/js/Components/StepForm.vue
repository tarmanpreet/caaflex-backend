<script setup>
import { computed } from 'vue';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    steps: {
        type: Array,
        default: () => [
            { label: 'Step 1' },
            { label: 'Step 2' },
            { label: 'Step 3' },
        ],
    },
    currentStep: {
        type: Number,
        default: 1,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:currentStep', 'stepChange']);

const totalSteps = computed(() => props.steps.length);

const getStepState = (stepIndex) => {
    const step = stepIndex + 1;
    const hasErrors = props.errors[step] && Object.keys(props.errors[step]).length > 0;
    
    if (hasErrors) {
        return 'error';
    }
    if (step < props.currentStep) {
        return 'completed';
    }
    if (step === props.currentStep) {
        return 'active';
    }
    return 'upcoming';
};

const isStepClickable = (stepIndex) => {
    const step = stepIndex + 1;
    return step <= props.currentStep;
};

const handleStepClick = (stepIndex) => {
    const step = stepIndex + 1;
    if (isStepClickable(stepIndex)) {
        emit('update:currentStep', step);
        emit('stepChange', step);
    }
};

const getStepClasses = (stepIndex) => {
    const state = getStepState(stepIndex);
    const baseClasses = 'flex items-center';
    
    if (state === 'active' || state === 'completed') {
        return `${baseClasses} text-primary`;
    }
    if (state === 'error') {
        return `${baseClasses} text-red-600 dark:text-red-400`;
    }
    return `${baseClasses} text-outline `;
};

const getNumberClasses = (stepIndex) => {
    const state = getStepState(stepIndex);
    const baseClasses = 'flex items-center justify-center w-5 h-5 me-2 text-xs border rounded-full shrink-0';
    
    if (state === 'active' || state === 'completed') {
        return `${baseClasses} border-primary`;
    }
    if (state === 'error') {
        return `${baseClasses} border-red-600 dark:border-red-400`;
    }
    return `${baseClasses} border-outline-variant`;
};
</script>

<template>
    <div class="w-full">
        <!-- Stepper Breadcrumb -->
        <nav aria-label="Progress">
            <ol class="flex items-center w-full p-3 space-x-2 text-sm font-medium text-center bg-surface-container  border border-outline-variant  rounded-lg shadow-sm sm:p-4 sm:space-x-4">
                <template v-for="(step, index) in steps" :key="index">
                    <li :class="getStepClasses(index)">
                        <button
                            type="button"
                            class="flex items-center select-none"
                            :class="[{ 'hover:opacity-80': isStepClickable(index) }, isStepClickable(index) ? 'cursor-pointer' : 'cursor-not-allowed']"
                            :disabled="!isStepClickable(index)"
                            @click="handleStepClick(index)"
                        >
                            <span :class="getNumberClasses(index)" class="pointer-events-none">
                                {{ index + 1 }}
                            </span>
                            <span class="hidden sm:inline-flex pointer-events-none">{{ step.label }}</span>
                            <span class="sm:hidden pointer-events-none">{{ step.label.split(' ')[0] }}</span>
                        </button>
                        
                        <!-- Arrow Icon (except last step) -->
                        <ChevronRightIcon 
                            v-if="index < steps.length - 1" 
                            class="mx-2 h-5 w-5 text-on-surface-variant sm:mx-4"
                            aria-hidden="true"
                        />
                    </li>
                </template>
            </ol>
        </nav>

        <!-- Error Messages -->
        <div v-if="Object.keys(errors).length > 0" class="mt-4 rounded-lg border border-error-container/50 bg-error-container/15 p-4">
            <p class="text-sm text-error">
                Alcuni campi contengono errori. Controlla gli step evidenziati.
            </p>
        </div>

        <!-- Step Content Panels -->
        <div class="mt-6">
            <template v-for="(step, index) in steps" :key="`content-${index}`">
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2"
                >
                    <div
                        v-show="currentStep === index + 1"
                        :id="`step-panel-${index + 1}`"
                        role="tabpanel"
                        :aria-label="step.label"
                        class="overflow-visible"
                    >
                        <slot :name="`step-${index + 1}`" />
                    </div>
                </transition>
            </template>
        </div>

        <!-- Actions Slot -->
        <div class="mt-6 flex items-center justify-between border-t border-outline-variant  pt-6">
            <slot name="actions" />
        </div>
    </div>
</template>
