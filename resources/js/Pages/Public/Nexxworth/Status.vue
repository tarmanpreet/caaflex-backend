<script setup>
import { computed } from 'vue';
import { ArrowRightIcon, CheckCircleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';
import NexxworthStatusLookup from '@/Components/NexxworthStatusLookup.vue';

const props = defineProps({
    locale: { type: String, default: 'it' },
    searchedCode: { type: String, default: '' },
    result: { type: Object, default: null },
    lookupError: { type: String, default: '' },
});

const statuses = {
    nuova: {
        it: { label: 'Nuova', description: 'Abbiamo ricevuto la pratica. La lavorazione deve ancora iniziare.' },
        en: { label: 'New', description: 'We have received your case. Work has not started yet.' },
        tone: 'border-[#c9dbe9] bg-[#e8f1f8] text-[#194a79]',
    },
    in_lavorazione: {
        it: { label: 'In lavorazione', description: 'La tua pratica è in lavorazione. Puoi tornare qui per controllare gli aggiornamenti.' },
        en: { label: 'In progress', description: 'Your case is in progress. You can return here to check for updates.' },
        tone: 'border-[#efd7a7] bg-[#fff2d9] text-[#805100]',
    },
    in_attesa_documenti: {
        it: { label: 'In attesa di documenti', description: 'La pratica è in attesa di documenti. Contattaci per sapere cosa occorre.' },
        en: { label: 'Awaiting documents', description: 'Your case is awaiting documents. Contact us to find out what is needed.' },
        tone: 'border-[#ddd2f1] bg-[#f1ebfa] text-[#60448d]',
    },
    completata: {
        it: { label: 'Completata', description: 'La lavorazione della pratica è conclusa.' },
        en: { label: 'Completed', description: 'Work on your case is complete.' },
        tone: 'border-[#bee4c9] bg-[#e5f5e9] text-[#17613e]',
    },
    annullata: {
        it: { label: 'Annullata', description: 'La pratica è stata annullata. Contattaci per chiarimenti.' },
        en: { label: 'Cancelled', description: 'Your case has been cancelled. Contact us if you need clarification.' },
        tone: 'border-[#edc5c5] bg-[#fae8e8] text-[#9b2d2d]',
    },
    sospesa: {
        it: { label: 'Sospesa', description: 'La lavorazione è temporaneamente sospesa. Contattaci per sapere come procedere.' },
        en: { label: 'On hold', description: 'Work is temporarily on hold. Contact us to find out how to proceed.' },
        tone: 'border-[#d5dfe6] bg-[#edf1f4] text-[#43536a]',
    },
};

const currentStatus = computed(() => statuses[props.result?.status]);
const statusLabel = computed(() => currentStatus.value?.[props.locale].label ?? props.result?.status?.replaceAll('_', ' ') ?? '');
const statusDescription = computed(() => currentStatus.value?.[props.locale].description ?? '');
const statusTone = computed(() => currentStatus.value?.tone ?? 'border-[#d5dfe6] bg-[#edf1f4] text-[#43536a]');
</script>

<template>
    <NexxworthLayout>
        <section class="bg-[#10223a] text-white">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:py-20">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">{{ locale === 'en' ? 'Case tracking' : 'Controllo pratica' }}</p>
                <h1 class="mt-5 max-w-4xl [font-family:Georgia,serif] text-5xl leading-[1.08] sm:text-6xl">{{ locale === 'en' ? 'Check your case status.' : 'Controlla lo stato della tua pratica.' }}</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-[#d2dfeb]">{{ locale === 'en' ? 'Enter the 10-character code you received to see the current status.' : 'Inserisci il codice di 10 caratteri che hai ricevuto per vedere lo stato corrente.' }}</p>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-10 px-5 py-20 sm:px-8 lg:grid-cols-[1fr_0.85fr] lg:items-start lg:py-28">
            <NexxworthStatusLookup :locale="locale" :initial-code="searchedCode" />

            <div class="rounded-[2rem] border border-[#dce4e8] bg-[#eaf0f3] p-8 sm:p-10">
                <template v-if="result">
                    <div class="flex items-center gap-3 text-[#17613e]"><CheckCircleIcon class="h-7 w-7" aria-hidden="true" /><p class="text-xs font-bold uppercase tracking-[0.2em]">{{ locale === 'en' ? 'Case found' : 'Pratica trovata' }}</p></div>
                    <h2 class="mt-5 [font-family:Georgia,serif] text-3xl leading-tight text-[#132b47]">{{ locale === 'en' ? 'Current case status' : 'Stato attuale della pratica' }}</h2>

                    <div class="mt-7 rounded-2xl border border-[#dce4e8] bg-white p-5 sm:p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#52657a]">{{ locale === 'en' ? 'Tracking code' : 'Codice pratica' }}</p>
                        <p class="mt-2 break-all font-mono text-lg font-bold tracking-[0.12em] text-[#132b47]">{{ result.code }}</p>
                    </div>

                    <div class="mt-6 rounded-2xl border p-5 sm:p-6" :class="statusTone" role="status" aria-live="polite">
                        <p class="text-xs font-bold uppercase tracking-[0.16em]">{{ locale === 'en' ? 'Status' : 'Stato' }}</p>
                        <p class="mt-2 [font-family:Georgia,serif] text-3xl font-bold leading-tight">{{ statusLabel }}</p>
                        <p v-if="statusDescription" class="mt-3 text-sm font-medium leading-7">{{ statusDescription }}</p>
                    </div>
                </template>

                <template v-else-if="lookupError">
                    <div class="flex items-center gap-3 text-[#a32925]"><InformationCircleIcon class="h-7 w-7" aria-hidden="true" /><h2 class="[font-family:Georgia,serif] text-2xl">{{ locale === 'en' ? 'Code not found' : 'Codice non trovato' }}</h2></div>
                    <p role="alert" class="mt-5 text-base leading-8 text-[#793532]">{{ lookupError }}</p>
                </template>

                <template v-else>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#b5322e]">{{ locale === 'en' ? 'Simple and secure' : 'Semplice e sicuro' }}</p>
                    <h2 class="mt-4 [font-family:Georgia,serif] text-3xl text-[#132b47]">{{ locale === 'en' ? 'Your update in a moment.' : 'Un aggiornamento in pochi istanti.' }}</h2>
                    <p class="mt-5 leading-8 text-[#52657a]">{{ locale === 'en' ? 'The result contains only your tracking code and current status. No personal details or documents are shown.' : 'Il risultato mostra soltanto il codice e lo stato corrente. Non vengono mostrati dati personali o documenti.' }}</p>
                </template>

                <div class="mt-10 border-t border-[#c9d8df] pt-8">
                    <h3 class="text-base font-bold text-[#132b47]">{{ locale === 'en' ? 'Need more information?' : 'Hai bisogno di chiarimenti?' }}</h3>
                    <p class="mt-2 text-sm leading-7 text-[#52657a]">{{ locale === 'en' ? 'Our team can help you understand the next steps.' : 'Il nostro team può aiutarti a capire i prossimi passi.' }}</p>
                    <a :href="route(locale === 'en' ? 'nexxworth.en.contact' : 'nexxworth.contact')" class="mt-5 inline-flex min-h-[46px] items-center gap-2 rounded-full border border-[#194a79] px-5 text-sm font-bold text-[#194a79] transition hover:bg-white">
                        {{ locale === 'en' ? 'Contact us' : 'Contattaci' }} <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
                    </a>
                </div>
            </div>
        </section>
    </NexxworthLayout>
</template>
