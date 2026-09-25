<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRightIcon, Bars3Icon, EnvelopeIcon, MapPinIcon, PhoneIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const menuOpen = ref(false);

const isEnglish = computed(() => page.url === '/en' || page.url.startsWith('/en/'));
const routeName = (key) => key === 'home' && !isEnglish.value ? 'home' : `nexxworth.${isEnglish.value ? 'en.' : ''}${key}`;
const navigation = computed(() => [
    { label: 'Home', href: route(routeName('home')) },
    { label: isEnglish.value ? 'About us' : 'Chi siamo', href: route(routeName('about')) },
    { label: isEnglish.value ? 'Services' : 'Servizi', href: route(routeName('services')) },
    { label: isEnglish.value ? 'Our network' : 'Collaborazioni', href: route(routeName('partners')) },
    { label: isEnglish.value ? 'Contact' : 'Contatti', href: route(routeName('contact')) },
    { label: isEnglish.value ? 'Check status' : 'Stato pratica', href: route(routeName('status')) },
]);
const pageKey = computed(() => {
    const current = page.url.split('?')[0];
    return ['home', 'about', 'services', 'partners', 'contact', 'privacy', 'status'].find((key) => new URL(route(routeName(key))).pathname === current) || 'home';
});
const otherLanguageRoute = computed(() => pageKey.value === 'home' && isEnglish.value ? 'home' : `nexxworth.${isEnglish.value ? '' : 'en.'}${pageKey.value}`);

const isCurrent = (href) => page.url.split('?')[0] === new URL(href).pathname;
</script>

<template>
    <div class="nexxworth-site min-h-screen bg-[#f7f7f4] text-[#13233a]">
        <a href="#contenuto" class="sr-only rounded-lg bg-white px-4 py-2 text-[#13233a] focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70]">{{ isEnglish ? 'Skip to content' : 'Vai al contenuto' }}</a>

        <div class="bg-[#10223a] text-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-1 px-5 py-2 text-[11px] font-semibold tracking-[0.12em] sm:px-8">
                <span class="uppercase">Nexxworth Consulting · Mantova</span>
                <a href="tel:+393664300443" class="inline-flex items-center gap-2 transition hover:text-[#f5c5bd] focus-visible:rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    <PhoneIcon class="h-3.5 w-3.5" aria-hidden="true" /> +39 366 430 0443
                </a>
            </div>
        </div>

        <header class="sticky top-0 z-50 border-b border-[#dfe5e9] bg-[#f7f7f4]/95 backdrop-blur-xl">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-5 px-5 sm:px-8">
                <a :href="route(routeName('home'))" class="shrink-0 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1c5284]" :aria-label="isEnglish ? 'Nexxworth Consulting, back to home' : 'Nexxworth Consulting, torna alla home'">
                    <img :src="$page.props.branding.logo_url" alt="Nexxworth Consulting" class="h-14 w-[182px] object-contain object-left sm:w-[204px]" width="204" height="72" />
                </a>

                <nav class="hidden items-center gap-1 xl:flex" :aria-label="isEnglish ? 'Main navigation' : 'Navigazione principale'">
                    <a v-for="item in navigation" :key="item.href" :href="item.href" :aria-current="isCurrent(item.href) ? 'page' : undefined" :class="[isCurrent(item.href) ? 'bg-[#e8edf2] text-[#164c7d]' : 'text-[#2e4053] hover:bg-[#e8edf2]', 'rounded-full px-4 py-2.5 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c5284]']">
                        {{ item.label }}
                    </a>
                </nav>

                <div class="hidden items-center gap-3 xl:flex">
                    <a :href="route(otherLanguageRoute)" :lang="isEnglish ? 'it' : 'en'" class="rounded-full px-3 py-2 text-sm font-bold text-[#234365] hover:text-[#be322e]">{{ isEnglish ? 'IT' : 'EN' }}</a>
                    <a :href="route('login')" class="rounded-full px-3 py-2 text-sm font-semibold text-[#234365] transition hover:text-[#be322e] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c5284]">{{ isEnglish ? 'Client area' : 'Area clienti' }}</a>
                    <a :href="route(routeName('contact'))" class="inline-flex min-h-[44px] items-center gap-2 rounded-full bg-[#be322e] px-5 py-2 text-sm font-bold text-white shadow-lg shadow-[#be322e]/15 transition hover:bg-[#a52a27] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#be322e]">
                        {{ isEnglish ? 'Get in touch' : 'Parliamone' }} <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
                    </a>
                </div>

                <button type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-[#dfe5e9] text-[#13233a] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#1c5284] xl:hidden" :aria-expanded="menuOpen" aria-controls="nexxworth-mobile-menu" :aria-label="menuOpen ? (isEnglish ? 'Close menu' : 'Chiudi il menu') : (isEnglish ? 'Open menu' : 'Apri il menu')" @click="menuOpen = !menuOpen">
                    <XMarkIcon v-if="menuOpen" class="h-6 w-6" aria-hidden="true" />
                    <Bars3Icon v-else class="h-6 w-6" aria-hidden="true" />
                </button>
            </div>

            <nav v-if="menuOpen" id="nexxworth-mobile-menu" class="border-t border-[#dfe5e9] bg-[#f7f7f4] px-5 pb-5 pt-3 xl:hidden" :aria-label="isEnglish ? 'Mobile navigation' : 'Navigazione mobile'">
                <div class="mx-auto flex max-w-7xl flex-col gap-1">
                    <a v-for="item in navigation" :key="item.href" :href="item.href" :aria-current="isCurrent(item.href) ? 'page' : undefined" class="min-h-[44px] rounded-xl px-4 py-3 text-sm font-semibold transition hover:bg-[#e8edf2]">{{ item.label }}</a>
                    <a :href="route(otherLanguageRoute)" :lang="isEnglish ? 'it' : 'en'" class="min-h-[44px] rounded-xl px-4 py-3 text-sm font-bold text-[#164c7d]">{{ isEnglish ? 'Italiano' : 'English' }}</a>
                    <a :href="route('login')" class="min-h-[44px] rounded-xl px-4 py-3 text-sm font-semibold text-[#164c7d]">{{ isEnglish ? 'Client area' : 'Area clienti' }}</a>
                </div>
            </nav>
        </header>

        <main id="contenuto"><slot /></main>

        <footer class="bg-[#10223a] text-white">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:px-8 lg:grid-cols-[1.25fr_0.7fr_1fr] lg:gap-16">
                <div>
                    <div class="inline-flex rounded-2xl bg-white p-3"><img :src="$page.props.branding.logo_url" alt="Nexxworth Consulting" class="h-14 w-[204px] object-contain" width="204" height="72" /></div>
                    <p class="mt-6 max-w-sm text-sm leading-7 text-[#c7d5e3]">{{ isEnglish ? 'Information, advice and solutions to navigate documents, procedures and new opportunities in Italy.' : 'Informazioni, consulenza e soluzioni per orientarsi tra pratiche, documenti e nuove opportunità in Italia.' }}</p>
                    <a :href="route(routeName('status'))" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-white underline decoration-[#ee8981] underline-offset-4 hover:text-[#f5c5bd]">{{ isEnglish ? 'Check a case' : 'Controlla una pratica' }} <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-[#ee8981]">{{ isEnglish ? 'Explore' : 'Esplora' }}</h2>
                    <nav class="mt-5 flex flex-col items-start gap-3" :aria-label="isEnglish ? 'Footer navigation' : 'Navigazione footer'">
                        <a v-for="item in navigation" :key="item.href" :href="item.href" class="text-sm text-[#dbe5ee] transition hover:text-white hover:underline">{{ item.label }}</a>
                    </nav>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-[#ee8981]">{{ isEnglish ? 'Contact' : 'Contatti' }}</h2>
                    <div class="mt-5 space-y-4 text-sm leading-6 text-[#dbe5ee]">
                        <p class="flex items-start gap-3"><MapPinIcon class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />Via Cremona 29A/int. 3<br>{{ isEnglish ? 'Mantua, Italy' : 'Mantova, Italia' }}</p>
                        <a href="tel:+393664300443" class="flex items-center gap-3 hover:text-white"><PhoneIcon class="h-5 w-5 shrink-0" aria-hidden="true" />+39 366 430 0443</a>
                        <a href="mailto:ksd.servizi@gmail.com" class="flex items-center gap-3 break-all hover:text-white"><EnvelopeIcon class="h-5 w-5 shrink-0" aria-hidden="true" />ksd.servizi@gmail.com</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-5 text-xs text-[#b4c5d6] sm:px-8">
                    <span>© {{ new Date().getFullYear() }} Nexxworth Consulting di Singh Kamaljeet · P. IVA 02553520202</span>
                    <a :href="route(routeName('privacy'))" class="underline underline-offset-4 hover:text-white">{{ isEnglish ? 'Privacy notice' : 'Informativa privacy' }}</a>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.nexxworth-site { font-family: 'Avenir Next', Avenir, 'Segoe UI', sans-serif; }
</style>
