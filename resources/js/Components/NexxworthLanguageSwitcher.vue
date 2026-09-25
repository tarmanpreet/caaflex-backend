<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { CheckIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    locale: { type: String, required: true },
    pageKey: { type: String, required: true },
});

const menu = ref(null);
const languages = computed(() => [
    { code: 'it', short: 'IT', name: 'Italiano', flag: '🇮🇹', route: props.pageKey === 'home' ? 'home' : `nexxworth.${props.pageKey}` },
    { code: 'en', short: 'EN', name: 'English', flag: '🇬🇧', route: `nexxworth.en.${props.pageKey}` },
]);
const currentLanguage = computed(() => languages.value.find((language) => language.code === props.locale));

const closeOnOutsideClick = (event) => {
    if (!menu.value?.contains(event.target)) {
        menu.value?.removeAttribute('open');
    }
};

onMounted(() => document.addEventListener('click', closeOnOutsideClick));
onUnmounted(() => document.removeEventListener('click', closeOnOutsideClick));
</script>

<template>
    <details ref="menu" class="group relative shrink-0" @keydown.esc.prevent="menu?.removeAttribute('open')">
        <summary
            class="flex min-h-[44px] cursor-pointer list-none items-center gap-1.5 rounded-full border border-[#d5e0e7] bg-white px-2.5 text-sm font-bold text-[#173554] transition hover:border-[#9bb5ca] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c5284] sm:px-3 [&::-webkit-details-marker]:hidden"
            :aria-label="locale === 'en' ? `Current language: ${currentLanguage.name}. Change language` : `Lingua attuale: ${currentLanguage.name}. Cambia lingua`"
        >
            <span class="text-lg leading-none" aria-hidden="true">{{ currentLanguage.flag }}</span>
            <span class="sm:hidden">{{ currentLanguage.short }}</span>
            <span class="hidden sm:inline">{{ currentLanguage.name }}</span>
            <ChevronDownIcon class="h-4 w-4 transition group-open:rotate-180" aria-hidden="true" />
        </summary>
        <div class="absolute right-0 z-[80] mt-2 w-44 overflow-hidden rounded-2xl border border-[#d5e0e7] bg-white p-1.5 shadow-xl shadow-[#10223a]/15" :aria-label="locale === 'en' ? 'Choose language' : 'Scegli la lingua'">
            <a
                v-for="language in languages"
                :key="language.code"
                :href="route(language.route)"
                :lang="language.code"
                :aria-current="locale === language.code ? 'page' : undefined"
                :class="[locale === language.code ? 'bg-[#e8edf2] text-[#164c7d]' : 'text-[#2e4053] hover:bg-[#f3f6f8]', 'flex min-h-[44px] items-center gap-2 rounded-xl px-3 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#1c5284]']"
            >
                <span class="text-lg leading-none" aria-hidden="true">{{ language.flag }}</span>
                <span class="flex-1">{{ language.name }}</span>
                <CheckIcon v-if="locale === language.code" class="h-4 w-4" aria-hidden="true" />
            </a>
        </div>
    </details>
</template>
