<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
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
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="emit('close')"
    >
        <div class="bg-surface-container-lowest px-6 pb-5 pt-6 text-on-surface">
            <h3 class="mb-3 font-headline text-xl font-bold text-on-surface">
                <slot name="title" />
            </h3>
            <div class="text-sm leading-6 text-on-surface-variant">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row flex-wrap justify-end gap-3 border-t border-outline-variant/35 bg-surface-container-low px-6 py-4">
            <slot name="footer" />
        </div>
    </Modal>
</template>
