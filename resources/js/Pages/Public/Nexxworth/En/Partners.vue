<script setup>
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';
import { ShieldCheckIcon, BriefcaseIcon, TruckIcon, BuildingOffice2Icon, ScaleIcon, ChatBubbleBottomCenterTextIcon } from '@heroicons/vue/24/outline';

const services = [
    { title: 'Workplace safety', description: 'Courses on safety procedures, risk assessment and emergency management.', icon: ShieldCheckIcon },
    { title: 'Insurance', description: 'Guidance on insurance services available through our partners.', icon: BriefcaseIcon },
    { title: 'Forklift training', description: 'Training to operate a forklift safely and develop professional skills.', icon: TruckIcon },
    { title: 'Property investment', description: 'Information and advice for evaluating real estate opportunities.', icon: BuildingOffice2Icon },
    { title: 'Legal assistance', description: 'Help finding a suitable professional for your situation.', icon: ScaleIcon },
    { title: 'Language courses', description: 'Italian and English courses to communicate with confidence.', icon: ChatBubbleBottomCenterTextIcon },
];
</script>
<template>
    <NexxworthLayout>
        <section class="bg-[#10223a] text-white"><div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">Our network</p><h1 class="mt-5 max-w-4xl [font-family:Georgia,serif] text-5xl leading-[1.08] sm:text-6xl lg:text-7xl">More possibilities through collaboration.</h1><p class="mt-7 max-w-2xl text-lg leading-8 text-[#d2dfeb]">Alongside Nexxworth's services, our partners offer expertise in training, protection and new opportunities.</p></div></section>
        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28"><div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3"><article v-for="service in services" :key="service.title" class="rounded-[1.75rem] border border-[#e1e6e8] bg-white p-7 shadow-sm"><component :is="service.icon" class="h-10 w-10 text-[#194a79]" aria-hidden="true" /><h2 class="mt-8 text-xl font-bold text-[#132b47]">{{ service.title }}</h2><p class="mt-3 text-sm leading-7 text-[#52657a]">{{ service.description }}</p></article></div><div class="mt-12 rounded-[1.75rem] bg-[#eaf0f3] p-7 sm:flex sm:items-center sm:justify-between sm:gap-8 sm:p-10"><div><h2 class="[font-family:Georgia,serif] text-3xl text-[#132b47]">Would you like to know more?</h2><p class="mt-3 text-sm leading-7 text-[#52657a]">Write to us and we will help you find the right contact or service.</p></div><a :href="route('nexxworth.en.contact')" class="mt-6 inline-flex min-h-[48px] shrink-0 items-center rounded-full bg-[#b5322e] px-6 py-3 text-sm font-bold text-white sm:mt-0">Contact us</a></div></section>
    </NexxworthLayout>
</template>
