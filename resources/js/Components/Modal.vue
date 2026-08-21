<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    labelledBy: {
        type: String,
        default: undefined,
    },
    describedBy: {
        type: String,
        default: undefined,
    },
});

const emit = defineEmits(['close']);
const showSlot = ref(props.show);
const modalContent = ref(null);
let previouslyFocusedElement = null;
let previousBodyOverflow = '';

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const focusModal = async () => {
    await nextTick();

    if (!modalContent.value || modalContent.value.contains(document.activeElement)) return;

    const firstFocusableElement = modalContent.value.querySelector(focusableSelector);
    (firstFocusableElement ?? modalContent.value).focus();
};

const restoreFocus = () => {
    if (previouslyFocusedElement?.isConnected) {
        previouslyFocusedElement.focus();
    }

    previouslyFocusedElement = null;
};

watch(() => props.show, async (val) => {
    if (val) {
        previouslyFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        showSlot.value = true;
        await focusModal();
    } else {
        document.body.style.overflow = previousBodyOverflow;
        restoreFocus();
        setTimeout(() => { showSlot.value = false; }, 200);
    }
}, { immediate: true });

const close = () => {
    if (props.closeable) emit('close');
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

const trapFocus = (event) => {
    if (event.key !== 'Tab' || !props.show || !modalContent.value) return;

    const focusableElements = Array.from(modalContent.value.querySelectorAll(focusableSelector));

    if (focusableElements.length === 0) {
        event.preventDefault();
        modalContent.value.focus();
        return;
    }

    const firstFocusableElement = focusableElements[0];
    const lastFocusableElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && (document.activeElement === firstFocusableElement || !modalContent.value.contains(document.activeElement))) {
        event.preventDefault();
        lastFocusableElement.focus();
    } else if (!event.shiftKey && document.activeElement === lastFocusableElement) {
        event.preventDefault();
        firstFocusableElement.focus();
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('keydown', trapFocus);
});
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('keydown', trapFocus);
    document.body.style.overflow = previousBodyOverflow;
    restoreFocus();
});

const maxWidthClass = computed(() => ({
    'sm':  'sm:max-w-sm',
    'md':  'sm:max-w-md',
    'lg':  'sm:max-w-lg',
    'xl':  'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth]));
</script>

<template>
    <Teleport to="body">
        <div v-if="showSlot" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 sm:p-6">
            <!-- Backdrop -->
            <transition
                enter-active-class="ease-out duration-300 motion-reduce:transition-none"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200 motion-reduce:transition-none"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" aria-hidden="true" class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm" @click="close" />
            </transition>

            <!-- Card -->
            <transition
                enter-active-class="ease-out duration-300 motion-reduce:transition-none"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200 motion-reduce:transition-none"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    ref="modalContent"
                    v-show="show"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="labelledBy"
                    :aria-describedby="describedBy"
                    tabindex="-1"
                    class="relative z-10 w-full overflow-hidden rounded-2xl border border-outline-variant/40 bg-surface-container-lowest text-on-surface shadow-2xl shadow-slate-950/25 transform transition-all motion-reduce:transition-none sm:mx-auto"
                    :class="maxWidthClass"
                >
                    <slot v-if="showSlot" />
                </div>
            </transition>
        </div>
    </Teleport>
</template>
