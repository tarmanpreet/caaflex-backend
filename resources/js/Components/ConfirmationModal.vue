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
        <div class="bg-surface-container-lowest px-6 pt-6 pb-4 text-on-surface">
            <h3 class="mb-4 text-lg font-semibold text-on-surface">
                <slot name="title" />
            </h3>
            <div class="text-sm text-on-surface-variant">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end gap-2 rounded-b-xl bg-surface-container/50 px-6 py-4">
            <slot name="footer" />
        </div>
    </Modal>
</template>
