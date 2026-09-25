<script setup>
import { ArrowRightIcon, BriefcaseIcon, BuildingOffice2Icon, ChatBubbleBottomCenterTextIcon, ScaleIcon, ShieldCheckIcon, TruckIcon } from '@heroicons/vue/24/outline';
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';

const partnerServices = [
    { title: 'Sicurezza sul lavoro', description: 'Corsi dedicati alle procedure di sicurezza, alla valutazione dei rischi e alla gestione delle emergenze.', icon: ShieldCheckIcon },
    { title: 'Assicurazioni', description: 'Orientamento verso i servizi assicurativi disponibili tramite i nostri collaboratori.', icon: BriefcaseIcon },
    { title: 'Corso per carrellisti', description: 'Formazione per usare il carrello elevatore in modo sicuro e acquisire competenze professionali.', icon: TruckIcon },
    { title: 'Investimenti immobiliari', description: 'Informazioni e consulenza per valutare opportunità nel settore immobiliare.', icon: BuildingOffice2Icon },
    { title: 'Assistenza legale', description: 'Supporto nella ricerca di un professionista adatto alle esigenze del tuo caso.', icon: ScaleIcon },
    { title: 'Corsi di lingua', description: 'Percorsi di apprendimento dell’italiano e dell’inglese per comunicare con maggiore sicurezza.', icon: ChatBubbleBottomCenterTextIcon },
];
</script>

<template>
    <NexxworthLayout>
        <section class="bg-[#10223a] text-white"><div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">La nostra rete</p><h1 class="mt-5 max-w-4xl [font-family:Georgia,serif] text-5xl leading-[1.08] tracking-tight sm:text-6xl lg:text-7xl">Più possibilità, grazie alle collaborazioni.</h1><p class="mt-7 max-w-2xl text-lg leading-8 text-[#d2dfeb]">Accanto ai servizi Nexxworth, una rete di collaboratori offre competenze in formazione, tutela e nuove opportunità.</p></div></section>
        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3"><article v-for="service in partnerServices" :key="service.title" class="rounded-[1.75rem] border border-[#e1e6e8] bg-white p-7 shadow-sm"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-[#e9f0f5] text-[#194a79]"><component :is="service.icon" class="h-7 w-7" aria-hidden="true" /></span><h2 class="mt-8 text-xl font-bold text-[#132b47]">{{ service.title }}</h2><p class="mt-3 text-sm leading-7 text-[#52657a]">{{ service.description }}</p></article></div><div class="mt-12 rounded-[1.75rem] bg-[#eaf0f3] p-7 sm:flex sm:items-center sm:justify-between sm:gap-8 sm:p-10"><div><h2 class="[font-family:Georgia,serif] text-3xl text-[#132b47]">Vuoi saperne di più?</h2><p class="mt-3 text-sm leading-7 text-[#52657a]">Scrivici e ti aiutiamo a trovare il contatto o il servizio giusto.</p></div><a :href="route('nexxworth.contact')" class="mt-6 inline-flex min-h-[48px] shrink-0 items-center gap-2 rounded-full bg-[#b5322e] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#922925] sm:mt-0">Contattaci <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a></div></section>
    </NexxworthLayout>
</template>
