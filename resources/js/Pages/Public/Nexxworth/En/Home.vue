<script setup>
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';
import NexxworthStatusLookup from '@/Components/NexxworthStatusLookup.vue';
import { ArrowRightIcon, DocumentTextIcon, GlobeEuropeAfricaIcon, LanguageIcon, CalculatorIcon } from '@heroicons/vue/24/outline';

const services = [
    { title: 'Documents and procedures', description: 'Practical guidance on the paperwork and services needed to live and work in Italy.', icon: DocumentTextIcon },
    { title: 'Visas and family reunification', description: 'Support with visa applications and the documents required to bring your family together.', icon: GlobeEuropeAfricaIcon },
    { title: 'Translations and legalization', description: 'Help with translations, authentication, apostilles and document legalization.', icon: LanguageIcon },
    { title: 'Tax assistance', description: 'Support with tax returns and related CAF services.', icon: CalculatorIcon },
];
</script>

<template>
    <NexxworthLayout>
        <section class="bg-[#10223a] text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:py-28">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">Support for international residents · Mantua</p>
                    <h1 class="mt-6 [font-family:Georgia,serif] text-5xl leading-[1.08] tracking-tight sm:text-6xl lg:text-7xl">A new beginning, <span class="text-[#f2a49c]">with the right answers.</span></h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-[#d2dfeb]">Nexxworth Consulting helps you navigate procedures, documents and opportunities in Italy. We support individuals, families and businesses with clear, practical advice.</p>
                    <div class="mt-9 flex flex-wrap gap-3"><a :href="route('nexxworth.en.services')" class="inline-flex min-h-[50px] items-center gap-2 rounded-full bg-[#c83c35] px-6 py-3 text-sm font-bold text-white hover:bg-[#aa2d29]">Explore our services <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a><a :href="route('nexxworth.en.contact')" class="inline-flex min-h-[50px] items-center rounded-full border border-white/30 px-6 py-3 text-sm font-bold text-white hover:bg-white/10">Contact us</a></div>
                    <p class="mt-10 border-t border-white/15 pt-6 text-sm text-[#bfd0e0]">Personal guidance · Multilingual support</p>
                </div>
                <img src="/brand/nexxworth/italy-consulting.webp" alt="Consulting documents and symbols of Italy" class="h-[380px] w-full rounded-[2rem] object-cover shadow-2xl sm:h-[510px]" width="1024" height="603" fetchpriority="high" />
            </div>
        </section>
        <section class="border-b border-[#dce4e8] bg-[#eaf0f3]"><div class="mx-auto grid max-w-7xl items-center gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20 lg:py-20"><div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">Stay up to date</p><h2 class="mt-4 [font-family:Georgia,serif] text-4xl leading-tight text-[#132b47] sm:text-5xl">See how your case is progressing.</h2><p class="mt-5 max-w-lg text-base leading-8 text-[#52657a]">Received a tracking code? Enter it here to see the latest status, without signing in.</p><a :href="route('nexxworth.en.status')" class="mt-5 inline-flex text-sm font-bold text-[#194a79] underline underline-offset-4">How case tracking works</a></div><NexxworthStatusLookup locale="en" /></div></section>
        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">What we do</p><h2 class="mt-4 max-w-2xl [font-family:Georgia,serif] text-4xl text-[#132b47] sm:text-5xl">Support for every important step.</h2><div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4"><article v-for="service in services" :key="service.title" class="rounded-[1.75rem] border border-[#e1e6e8] bg-white p-7 shadow-sm"><component :is="service.icon" class="h-9 w-9 text-[#194a79]" aria-hidden="true" /><h3 class="mt-8 text-xl font-bold">{{ service.title }}</h3><p class="mt-3 text-sm leading-7 text-[#52657a]">{{ service.description }}</p><a :href="route('nexxworth.en.services')" class="mt-6 inline-block text-sm font-bold text-[#b5322e] underline">Learn more</a></article></div></section>
        <section class="bg-[#eaf0f3]"><div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 sm:px-8 lg:grid-cols-2 lg:py-28"><img src="/brand/nexxworth/italy-services.webp" alt="Illustration of international consulting services" class="w-full rounded-[2rem] object-cover shadow-xl" width="1024" height="603" loading="lazy" /><div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">Our story</p><h2 class="mt-4 [font-family:Georgia,serif] text-4xl text-[#132b47] sm:text-5xl">We know what it means to feel at home.</h2><p class="mt-6 text-base leading-8 text-[#455b70]">Nexxworth grew from first-hand experience of the challenges of moving to a new country. We combine attentive listening, knowledge of procedures and care for each person's circumstances.</p><p class="mt-4 text-base leading-8 text-[#455b70]">From immigration and relocation to cultural adjustment, our goal is to help you move forward with confidence.</p><a :href="route('nexxworth.en.about')" class="mt-8 inline-flex min-h-[48px] items-center gap-2 rounded-full bg-[#173554] px-6 py-3 text-sm font-bold text-white">About us <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a></div></div></section>
        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8"><div class="rounded-[2rem] bg-[#10223a] px-7 py-12 text-white sm:px-12 lg:flex lg:items-center lg:justify-between lg:gap-12"><div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">Take the first step</p><h2 class="mt-4 [font-family:Georgia,serif] text-4xl">Tell us what you need.</h2><p class="mt-4 text-sm leading-7 text-[#c7d5e3]">We will listen and help you find a suitable way forward.</p></div><a :href="route('nexxworth.en.contact')" class="mt-8 inline-flex min-h-[50px] shrink-0 items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-[#132b47] lg:mt-0">Get in touch <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a></div></section>
    </NexxworthLayout>
</template>
