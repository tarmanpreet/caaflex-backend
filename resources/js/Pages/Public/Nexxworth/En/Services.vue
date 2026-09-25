<script setup>
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';
import { DocumentTextIcon, GlobeEuropeAfricaIcon, LanguageIcon, DocumentCheckIcon, CalculatorIcon } from '@heroicons/vue/24/outline';

const services = [
    { title: 'Administrative assistance', description: 'Information and guidance for people living in Italy who need to navigate administrative procedures and access services.', icon: DocumentTextIcon },
    { title: 'Visa applications', description: 'Support with preparing visa application documents, requirements and the application process.', icon: GlobeEuropeAfricaIcon },
    { title: 'Translations and legalization', description: 'Help with document translations, legalization, authentication and apostilles when needed for an application.', icon: LanguageIcon },
    { title: 'Family reunification', description: 'Guidance on clearance applications and documents needed to reunite with family members.', icon: DocumentCheckIcon },
    { title: 'Tax returns and CAF services', description: 'Assistance with tax returns and other requirements connected with CAF services.', icon: CalculatorIcon },
];
</script>
<template>
    <NexxworthLayout>
        <section class="bg-[#10223a] text-white"><div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">Our services</p><h1 class="mt-5 max-w-4xl [font-family:Georgia,serif] text-5xl leading-[1.08] sm:text-6xl lg:text-7xl">Practical solutions to move forward.</h1><p class="mt-7 max-w-2xl text-lg leading-8 text-[#d2dfeb]">Support to overcome language and administrative barriers, access services and build your future in Italy.</p></div></section>
        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><div class="grid gap-5 md:grid-cols-2"><article v-for="(service, index) in services" :key="service.title" class="rounded-[1.75rem] border border-[#e1e6e8] bg-white p-7 shadow-sm sm:p-9"><div class="flex items-start justify-between"><component :is="service.icon" class="h-10 w-10 text-[#194a79]" aria-hidden="true" /><span class="text-xs font-bold text-[#a8b7c5]">0{{ index + 1 }}</span></div><h2 class="mt-8 text-2xl font-bold text-[#132b47]">{{ service.title }}</h2><p class="mt-4 text-base leading-8 text-[#52657a]">{{ service.description }}</p></article><article class="flex flex-col justify-between rounded-[1.75rem] bg-[#c43b34] p-7 text-white sm:p-9"><div><p class="text-xs font-bold uppercase tracking-[0.2em]">Have a specific question?</p><h2 class="mt-8 [font-family:Georgia,serif] text-3xl">Let's start with your situation.</h2><p class="mt-4 leading-8 text-[#ffe7e3]">Tell us what you need and we can help identify the right service.</p></div><a :href="route('nexxworth.en.contact')" class="mt-8 inline-flex min-h-[48px] items-center self-start rounded-full bg-white px-6 py-3 text-sm font-bold text-[#9b302b]">Contact us</a></article></div></section>
        <section class="bg-[#eaf0f3]"><div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-16 sm:px-8 lg:flex-row lg:items-center lg:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-[#b5322e]">Language support</p><h2 class="mt-3 [font-family:Georgia,serif] text-3xl text-[#132b47]">We speak your language.</h2><p class="mt-3 text-sm leading-7 text-[#52657a]">Italian, English, Hindi, Punjabi, French and German.</p></div><a :href="route('nexxworth.en.partners')" class="inline-flex min-h-[48px] items-center self-start rounded-full border border-[#194a79] px-6 py-3 text-sm font-bold text-[#194a79]">Explore our network</a></div></section>
    </NexxworthLayout>
</template>
