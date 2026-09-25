<script setup>
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';
import { ChatBubbleLeftRightIcon, LightBulbIcon, GlobeEuropeAfricaIcon } from '@heroicons/vue/24/outline';

const values = [
    { title: 'We listen', description: 'We start with your circumstances, questions and goals.', icon: ChatBubbleLeftRightIcon },
    { title: 'Clear guidance', description: 'We turn requirements and procedures into practical next steps.', icon: LightBulbIcon },
    { title: 'International outlook', description: 'We connect people, cultures and services to make settling in Italy easier.', icon: GlobeEuropeAfricaIcon },
];
</script>
<template>
    <NexxworthLayout>
        <section class="bg-[#10223a] text-white"><div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">Our story</p><h1 class="mt-5 max-w-4xl [font-family:Georgia,serif] text-5xl leading-[1.08] sm:text-6xl lg:text-7xl">Every journey starts with someone who listens.</h1><p class="mt-7 max-w-2xl text-lg leading-8 text-[#d2dfeb]">Lived experience, care for people and practical guidance: this is the foundation of Nexxworth Consulting.</p></div></section>
        <section class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 sm:px-8 lg:grid-cols-2 lg:gap-20 lg:py-28"><img src="/brand/nexxworth/italy-consulting.webp" alt="Italian symbols and consulting materials" class="w-full rounded-[2rem] object-cover" width="1024" height="603" /><div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">Why we exist</p><h2 class="mt-4 [font-family:Georgia,serif] text-4xl text-[#132b47] sm:text-5xl">Guidance born from personal experience.</h2><p class="mt-7 text-base leading-8 text-[#455b70]">Moving to a new country can mean learning a language, understanding unfamiliar rules and finding your place. Nexxworth's founder experienced these challenges first-hand and wanted to help others through them.</p><p class="mt-5 text-base leading-8 text-[#455b70]">We offer advice shaped around the needs of individuals and businesses, especially on immigration, relocation and cultural adjustment. We make complex steps easier to understand and manage.</p><a :href="route('nexxworth.en.contact')" class="mt-8 inline-flex min-h-[48px] items-center rounded-full bg-[#b5322e] px-6 py-3 text-sm font-bold text-white">Talk to us</a></div></section>
        <section class="bg-[#eaf0f3]"><div class="mx-auto max-w-7xl px-5 py-20 sm:px-8"><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">How we work</p><h2 class="mt-4 [font-family:Georgia,serif] text-4xl text-[#132b47] sm:text-5xl">Working together makes the difference.</h2><div class="mt-10 grid gap-5 md:grid-cols-3"><article v-for="value in values" :key="value.title" class="rounded-[1.75rem] bg-white p-7"><component :is="value.icon" class="h-9 w-9 text-[#194a79]" aria-hidden="true" /><h3 class="mt-7 text-xl font-bold">{{ value.title }}</h3><p class="mt-3 text-sm leading-7 text-[#52657a]">{{ value.description }}</p></article></div></div></section>
    </NexxworthLayout>
</template>
