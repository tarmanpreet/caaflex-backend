<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    document: {
        type: Object,
        default: null,
    },
    updateUrl: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    expires_on: '',
});

watch(() => props.document, (document) => {
    form.expires_on = document?.expires_on?.slice(0, 10) ?? '';
    form.clearErrors();
});

const close = () => {
    if (form.processing) return;

    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (!props.updateUrl) return;

    form.patch(props.updateUrl, {
        preserveScroll: true,
        onSuccess: close,
    });
};
</script>

<template>
    <ConfirmationModal :show="show" :closeable="!form.processing" max-width="md" @close="close">
        <template #title>
            Scadenza documento
        </template>

        <template #content>
            <form id="document-expiration-form" class="space-y-4" @submit.prevent="submit">
                <p class="break-words font-semibold text-on-surface">{{ document?.original_name }}</p>

                <div>
                    <InputLabel for="document_expires_on" value="Data di scadenza" />
                    <input
                        id="document_expires_on"
                        v-model="form.expires_on"
                        type="date"
                        class="app-input mt-1 block min-h-[44px] w-full rounded-xl"
                    >
                    <p class="mt-1.5 text-xs text-on-surface-variant">Lascia vuoto se il documento non ha scadenza.</p>
                    <InputError :message="form.errors.expires_on" class="mt-1" />
                </div>
            </form>
        </template>

        <template #footer>
            <SecondaryButton :disabled="form.processing" @click="close">
                Annulla
            </SecondaryButton>
            <PrimaryButton type="submit" form="document-expiration-form" :disabled="form.processing">
                {{ form.processing ? 'Salvataggio…' : 'Salva scadenza' }}
            </PrimaryButton>
        </template>
    </ConfirmationModal>
</template>
