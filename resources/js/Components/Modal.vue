<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
});

const emit = defineEmits(['close']);
const showSlot = ref(props.show);

watch(() => props.show, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
        showSlot.value = true;
    } else {
        document.body.style.overflow = null;
        setTimeout(() => { showSlot.value = false; }, 200);
    }
});

const close = () => {
    if (props.closeable) emit('close');
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
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
        <div v-if="showSlot" class="fixed inset-0 z-50 flex items-start justify-center pt-8 pb-8 px-4 sm:px-0">
            <!-- Backdrop -->
            <transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" class="fixed inset-0 bg-gray-500/75 dark:bg-black/75" @click="close" />
            </transition>

            <!-- Card -->
            <transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="relative z-10 w-full bg-white dark:bg-gray-800 rounded-xl shadow-2xl transform transition-all sm:mx-auto"
                    :class="maxWidthClass"
                >
                    <slot v-if="showSlot" />
                </div>
            </transition>
        </div>
    </Teleport>
</template>
