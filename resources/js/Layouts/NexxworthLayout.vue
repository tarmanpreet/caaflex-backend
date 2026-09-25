<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRightIcon, Bars3Icon, EnvelopeIcon, MapPinIcon, PhoneIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const menuOpen = ref(false);

const navigation = [
    { label: 'Home', href: route('home') },
    { label: 'Chi siamo', href: route('nexxworth.about') },
    { label: 'Servizi', href: route('nexxworth.services') },
    { label: 'Collaborazioni', href: route('nexxworth.partners') },
    { label: 'Contatti', href: route('nexxworth.contact') },
];

const isCurrent = (href) => page.url.split('?')[0] === new URL(href).pathname;
</script>

<template>
    <div class="nexxworth-site min-h-screen bg-[#f7f7f4] text-[#13233a]">
        <a href="#contenuto" class="sr-only rounded-lg bg-white px-4 py-2 text-[#13233a] focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70]">Vai al contenuto</a>

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
                <Link :href="route('home')" class="shrink-0 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1c5284]" aria-label="Nexxworth Consulting, torna alla home">
                    <img :src="$page.props.branding.logo_url" alt="Nexxworth Consulting" class="h-14 w-[182px] object-contain object-left sm:w-[204px]" width="204" height="72" />
                </Link>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigazione principale">
                    <Link v-for="item in navigation" :key="item.href" :href="item.href" :aria-current="isCurrent(item.href) ? 'page' : undefined" :class="[isCurrent(item.href) ? 'bg-[#e8edf2] text-[#164c7d]' : 'text-[#2e4053] hover:bg-[#e8edf2]', 'rounded-full px-4 py-2.5 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c5284]']">
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="hidden items-center gap-3 lg:flex">
                    <Link :href="route('login')" class="rounded-full px-3 py-2 text-sm font-semibold text-[#234365] transition hover:text-[#be322e] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1c5284]">Area clienti</Link>
                    <Link :href="route('nexxworth.contact')" class="inline-flex min-h-[44px] items-center gap-2 rounded-full bg-[#be322e] px-5 py-2 text-sm font-bold text-white shadow-lg shadow-[#be322e]/15 transition hover:bg-[#a52a27] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#be322e]">
                        Parliamone <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </div>

                <button type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-[#dfe5e9] text-[#13233a] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#1c5284] lg:hidden" :aria-expanded="menuOpen" aria-controls="nexxworth-mobile-menu" :aria-label="menuOpen ? 'Chiudi il menu' : 'Apri il menu'" @click="menuOpen = !menuOpen">
                    <XMarkIcon v-if="menuOpen" class="h-6 w-6" aria-hidden="true" />
                    <Bars3Icon v-else class="h-6 w-6" aria-hidden="true" />
                </button>
            </div>

            <nav v-if="menuOpen" id="nexxworth-mobile-menu" class="border-t border-[#dfe5e9] bg-[#f7f7f4] px-5 pb-5 pt-3 lg:hidden" aria-label="Navigazione mobile">
                <div class="mx-auto flex max-w-7xl flex-col gap-1">
                    <Link v-for="item in navigation" :key="item.href" :href="item.href" :aria-current="isCurrent(item.href) ? 'page' : undefined" class="min-h-[44px] rounded-xl px-4 py-3 text-sm font-semibold transition hover:bg-[#e8edf2]" @click="menuOpen = false">{{ item.label }}</Link>
                    <Link :href="route('login')" class="min-h-[44px] rounded-xl px-4 py-3 text-sm font-semibold text-[#164c7d]" @click="menuOpen = false">Area clienti</Link>
                </div>
            </nav>
        </header>

        <main id="contenuto"><slot /></main>

        <footer class="bg-[#10223a] text-white">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:px-8 lg:grid-cols-[1.25fr_0.7fr_1fr] lg:gap-16">
                <div>
                    <div class="inline-flex rounded-2xl bg-white p-3"><img :src="$page.props.branding.logo_url" alt="Nexxworth Consulting" class="h-14 w-[204px] object-contain" width="204" height="72" /></div>
                    <p class="mt-6 max-w-sm text-sm leading-7 text-[#c7d5e3]">Informazioni, consulenza e soluzioni per orientarsi tra pratiche, documenti e nuove opportunità in Italia.</p>
                    <Link :href="route('practice-status.index')" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-white underline decoration-[#ee8981] underline-offset-4 hover:text-[#f5c5bd]">Controlla una pratica <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-[#ee8981]">Esplora</h2>
                    <nav class="mt-5 flex flex-col items-start gap-3" aria-label="Navigazione footer">
                        <Link v-for="item in navigation" :key="item.href" :href="item.href" class="text-sm text-[#dbe5ee] transition hover:text-white hover:underline">{{ item.label }}</Link>
                    </nav>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-[#ee8981]">Contatti</h2>
                    <div class="mt-5 space-y-4 text-sm leading-6 text-[#dbe5ee]">
                        <p class="flex items-start gap-3"><MapPinIcon class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />Via Cremona 29A/int. 3<br>Mantova, Italia</p>
                        <a href="tel:+393664300443" class="flex items-center gap-3 hover:text-white"><PhoneIcon class="h-5 w-5 shrink-0" aria-hidden="true" />+39 366 430 0443</a>
                        <a href="mailto:ksd.servizi@gmail.com" class="flex items-center gap-3 break-all hover:text-white"><EnvelopeIcon class="h-5 w-5 shrink-0" aria-hidden="true" />ksd.servizi@gmail.com</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-5 text-xs text-[#b4c5d6] sm:px-8">
                    <span>© {{ new Date().getFullYear() }} Nexxworth Consulting di Singh Kamaljeet · P. IVA 02553520202</span>
                    <Link :href="route('nexxworth.privacy')" class="underline underline-offset-4 hover:text-white">Informativa privacy</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.nexxworth-site { font-family: 'Avenir Next', Avenir, 'Segoe UI', sans-serif; }
</style>
