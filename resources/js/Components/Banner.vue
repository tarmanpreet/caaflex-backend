<script setup>
import { ref, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(true);
const style = ref('success');
const message = ref('');

watchEffect(async () => {
    style.value = page.props.jetstream.flash?.bannerStyle || 'success';
    message.value = page.props.jetstream.flash?.banner || '';
    show.value = true;
});
</script>

<template>
    <div class="fixed inset-x-0 top-3 z-[70] px-3 sm:px-6">
        <div v-if="show && message" class="mx-auto max-w-3xl overflow-hidden rounded-2xl border shadow-2xl backdrop-blur-xl" :class="{ 'border-emerald-300/40 bg-emerald-600 text-white': style == 'success', 'border-red-300/40 bg-red-600 text-white': style == 'danger' }" role="status" aria-live="polite">
            <div class="px-3 py-2.5 sm:px-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="w-0 flex-1 flex items-center min-w-0">
                        <span class="flex rounded-xl bg-white/15 p-2">
                            <svg v-if="style == 'success'" class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <svg v-if="style == 'danger'" class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </span>

                        <p class="ms-3 font-medium text-sm text-white truncate">
                            {{ message }}
                        </p>
                    </div>

                    <div class="shrink-0">
                            <button
                                type="button"
                                class="grid h-10 w-10 place-items-center rounded-xl transition hover:bg-white/15 focus:outline-none"
                                aria-label="Chiudi notifica"
                                @click.prevent="show = false"
                            >
                            <svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
