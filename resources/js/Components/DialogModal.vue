<script setup>
import { getCurrentInstance } from 'vue';
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

const close = () => {
    emit('close');
};

const componentId = getCurrentInstance()?.uid;
const titleId = `dialog-modal-title-${componentId}`;
const contentId = `dialog-modal-content-${componentId}`;
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        :labelled-by="titleId"
        :described-by="contentId"
        @close="close"
    >
        <div class="px-6 py-6">
            <div :id="titleId" class="font-headline text-xl font-bold text-on-surface">
                <slot name="title" />
            </div>

            <div :id="contentId" class="mt-3 text-sm leading-6 text-on-surface-variant">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row flex-wrap justify-end gap-3 border-t border-outline-variant/35 bg-surface-container-low px-6 py-4 text-end">
            <slot name="footer" />
        </div>
    </Modal>
</template>
