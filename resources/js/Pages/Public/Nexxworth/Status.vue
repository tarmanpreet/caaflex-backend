<script setup>
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';
import NexxworthStatusLookup from '@/Components/NexxworthStatusLookup.vue';
import { computed } from 'vue';

const props = defineProps({
    locale: { type: String, default: 'it' },
    searchedCode: { type: String, default: '' },
    result: { type: Object, default: null },
    lookupError: { type: String, default: '' },
});
const statuses = {
    nuova: { it: 'Nuova', en: 'New' },
    in_lavorazione: { it: 'In lavorazione', en: 'In progress' },
    in_attesa_documenti: { it: 'In attesa di documenti', en: 'Awaiting documents' },
    completata: { it: 'Completata', en: 'Completed' },
    annullata: { it: 'Annullata', en: 'Cancelled' },
    sospesa: { it: 'Sospesa', en: 'On hold' },
};
const statusLabel = computed(() => statuses[props.result?.status]?.[props.locale] ?? props.result?.status?.replaceAll('_', ' ') ?? '');
</script>
<template>
    <NexxworthLayout>
        <section class="bg-[#10223a] text-white"><div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:py-20"><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">{{ locale === 'en' ? 'Case tracking' : 'Controllo pratica' }}</p><h1 class="mt-5 max-w-4xl [font-family:Georgia,serif] text-5xl leading-[1.08] sm:text-6xl">{{ locale === 'en' ? 'Check your case status.' : 'Controlla lo stato della tua pratica.' }}</h1><p class="mt-6 max-w-2xl text-lg leading-8 text-[#d2dfeb]">{{ locale === 'en' ? 'Enter the 10-character code you received to see the current status.' : 'Inserisci il codice di 10 caratteri che hai ricevuto per vedere lo stato corrente.' }}</p></div></section>
        <section class="mx-auto grid max-w-7xl gap-10 px-5 py-20 sm:px-8 lg:grid-cols-[1fr_0.8fr] lg:items-start lg:py-28"><NexxworthStatusLookup :locale="locale" :initial-code="searchedCode" /><div class="rounded-[2rem] bg-[#eaf0f3] p-8 sm:p-10"><template v-if="result"><p class="text-xs font-bold uppercase tracking-[0.2em] text-[#b5322e]">{{ locale === 'en' ? 'Case found' : 'Pratica trovata' }}</p><h2 class="mt-4 [font-family:Georgia,serif] text-3xl text-[#132b47]">{{ locale === 'en' ? 'Current status' : 'Stato attuale' }}</h2><p class="mt-7 text-xs font-bold uppercase tracking-[0.16em] text-[#52657a]">{{ locale === 'en' ? 'Tracking code' : 'Codice pratica' }}</p><p class="mt-1 font-mono text-xl font-bold tracking-widest text-[#132b47]">{{ result.code }}</p><span role="status" class="mt-6 inline-flex rounded-full bg-[#194a79] px-5 py-2 text-sm font-bold text-white">{{ statusLabel }}</span></template><template v-else-if="lookupError"><p role="alert" class="text-base font-semibold leading-8 text-[#a32925]">{{ lookupError }}</p></template><template v-else><p class="text-xs font-bold uppercase tracking-[0.2em] text-[#b5322e]">{{ locale === 'en' ? 'Simple and secure' : 'Semplice e sicuro' }}</p><h2 class="mt-4 [font-family:Georgia,serif] text-3xl text-[#132b47]">{{ locale === 'en' ? 'Your update in a moment.' : 'Un aggiornamento in pochi istanti.' }}</h2><p class="mt-5 leading-8 text-[#52657a]">{{ locale === 'en' ? 'The result contains only your tracking code and current status. For more information, contact our team.' : 'Il risultato mostra soltanto il codice e lo stato corrente. Per altre informazioni, contatta il nostro team.' }}</p></template><a :href="route(locale === 'en' ? 'nexxworth.en.contact' : 'nexxworth.contact')" class="mt-8 inline-flex text-sm font-bold text-[#194a79] underline underline-offset-4">{{ locale === 'en' ? 'Contact us' : 'Contattaci' }}</a></div></section>
    </NexxworthLayout>
</template>
