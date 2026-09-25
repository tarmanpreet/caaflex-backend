<script setup>
import { ArrowRightIcon, ArrowUpRightIcon, CalculatorIcon, CheckCircleIcon, DocumentTextIcon, GlobeEuropeAfricaIcon, LanguageIcon, UsersIcon } from '@heroicons/vue/24/outline';
import NexxworthLayout from '@/Layouts/NexxworthLayout.vue';
import NexxworthStatusLookup from '@/Components/NexxworthStatusLookup.vue';

const services = [
    { title: 'Pratiche e burocrazia', description: 'Orientamento tra documenti, richieste e procedure per vivere e lavorare in Italia.', icon: DocumentTextIcon },
    { title: 'Visti e ricongiungimento', description: 'Supporto nella preparazione delle domande di visto e nelle richieste per la famiglia.', icon: GlobeEuropeAfricaIcon },
    { title: 'Traduzioni e legalizzazioni', description: 'Assistenza per traduzioni, autenticazioni, apostille e documenti da presentare.', icon: LanguageIcon },
    { title: 'Servizi CAF', description: 'Aiuto nella compilazione delle dichiarazioni fiscali e negli adempimenti collegati.', icon: CalculatorIcon },
];
</script>

<template>
    <NexxworthLayout>

        <section class="relative overflow-hidden bg-[#10223a] text-white">
            <div class="pointer-events-none absolute -left-48 top-12 h-[36rem] w-[36rem] rounded-full bg-[#245d8f]/25 blur-3xl" aria-hidden="true" />
            <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 pb-20 pt-16 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:pb-28 lg:pt-24">
                <div>
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-[#bcd6ea]"><span class="h-2 w-2 rounded-full bg-[#eb776d]" /> Assistenza stranieri · Mantova</div>
                    <h1 class="max-w-[680px] [font-family:Georgia,serif] text-[clamp(2.9rem,5.6vw,5.8rem)] leading-[1.05] tracking-[-0.045em]">Un nuovo inizio, <span class="text-[#f2a49c]">con le risposte giuste.</span></h1>
                    <p class="mt-7 max-w-xl text-base leading-8 text-[#d2dfeb] sm:text-lg">Nexxworth Consulting ti accompagna tra pratiche, documenti e opportunità. Per chi arriva in Italia, per le famiglie e per le imprese che cercano una guida chiara.</p>
                    <div class="mt-10 flex flex-wrap gap-3">
                        <a :href="route('nexxworth.services')" class="inline-flex min-h-[50px] items-center gap-2 rounded-full bg-[#c83c35] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#aa2d29] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Scopri i servizi <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a>
                        <a :href="route('nexxworth.contact')" class="inline-flex min-h-[50px] items-center gap-2 rounded-full border border-white/30 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Contattaci <ArrowUpRightIcon class="h-4 w-4" aria-hidden="true" /></a>
                    </div>
                    <div class="mt-14 flex flex-wrap items-center gap-x-7 gap-y-3 border-t border-white/15 pt-6 text-sm text-[#bfd0e0]">
                        <span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-[#f2a49c]" aria-hidden="true" /> Consulenza su misura</span>
                        <span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-[#f2a49c]" aria-hidden="true" /> Supporto multilingue</span>
                    </div>
                </div>
                <div class="relative lg:pl-4">
                    <div class="overflow-hidden rounded-[2rem] border border-white/15 bg-[#173554] p-2 shadow-2xl shadow-black/30 sm:rounded-[2.5rem]">
                        <img src="/brand/nexxworth/italy-consulting.webp" alt="Documenti e strumenti di consulenza con i colori dell'Italia" class="h-[380px] w-full rounded-[1.5rem] object-cover sm:h-[510px] sm:rounded-[2rem]" width="1024" height="603" fetchpriority="high" />
                    </div>
                    <div class="absolute -bottom-7 left-0 max-w-[260px] rounded-2xl bg-white p-5 text-[#173554] shadow-xl sm:-left-6">
                        <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#e8eff5]"><GlobeEuropeAfricaIcon class="h-6 w-6" aria-hidden="true" /></span><span class="text-xs font-bold uppercase tracking-[0.15em] text-[#a33632]">Il nostro approccio</span></div>
                        <p class="mt-3 text-sm font-semibold leading-6">Le persone prima delle pratiche.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-b border-[#dce4e8] bg-[#eaf0f3]">
            <div class="mx-auto grid max-w-7xl items-center gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20 lg:py-20">
                <div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">La tua pratica, sotto controllo</p><h2 class="mt-4 [font-family:Georgia,serif] text-4xl leading-tight text-[#132b47] sm:text-5xl">Scopri a che punto siamo.</h2><p class="mt-5 max-w-lg text-base leading-8 text-[#52657a]">Hai ricevuto un codice pratica? Inseriscilo qui per vedere subito lo stato aggiornato, senza accedere all'area clienti.</p><a :href="route('nexxworth.status')" class="mt-5 inline-flex text-sm font-bold text-[#194a79] underline underline-offset-4">Come funziona il controllo pratica</a></div>
                <NexxworthStatusLookup locale="it" />
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 pb-20 pt-28 sm:px-8 lg:pb-28 lg:pt-32">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">Cosa facciamo</p><h2 class="mt-4 max-w-2xl [font-family:Georgia,serif] text-4xl leading-tight tracking-tight text-[#132b47] sm:text-5xl">Un riferimento per ogni passaggio importante.</h2></div>
                <a :href="route('nexxworth.services')" class="inline-flex items-center gap-2 self-start border-b-2 border-[#bd3630] pb-1 text-sm font-bold text-[#173554] transition hover:text-[#bd3630]">Tutti i servizi <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a>
            </div>
            <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article v-for="(service, index) in services" :key="service.title" class="group flex h-full flex-col rounded-[1.75rem] border border-[#e1e6e8] bg-white p-7 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl motion-reduce:transform-none">
                    <div class="flex items-start justify-between"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-[#e9f0f5] text-[#194a79]"><component :is="service.icon" class="h-7 w-7" aria-hidden="true" /></span><span class="text-xs font-bold tracking-[0.15em] text-[#a7b6c1]">0{{ index + 1 }}</span></div>
                    <h3 class="mt-10 text-xl font-bold tracking-tight text-[#132b47]">{{ service.title }}</h3>
                    <p class="mt-3 flex-1 text-sm leading-7 text-[#52657a]">{{ service.description }}</p>
                    <a :href="route('nexxworth.services')" class="mt-7 inline-flex items-center gap-2 text-sm font-bold text-[#b5322e] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#194a79]">Approfondisci <ArrowUpRightIcon class="h-4 w-4" aria-hidden="true" /></a>
                </article>
            </div>
        </section>

        <section class="bg-[#eaf0f3]">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-2 lg:items-center lg:gap-20 lg:py-28">
                <div class="relative"><img src="/brand/nexxworth/italy-services.webp" alt="Illustrazione dedicata a consulenza e pratiche tra Paesi" class="aspect-[1.2] w-full rounded-[2rem] object-cover shadow-xl" width="1024" height="603" loading="lazy" /><div class="absolute -bottom-5 right-5 rounded-2xl bg-[#c43b34] px-6 py-4 text-sm font-bold text-white shadow-lg">Informazioni · Consulenza · Soluzioni</div></div>
                <div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b5322e]">La nostra storia</p><h2 class="mt-4 [font-family:Georgia,serif] text-4xl leading-tight tracking-tight text-[#132b47] sm:text-5xl">Conosciamo il valore di sentirsi a casa.</h2><p class="mt-6 text-base leading-8 text-[#455b70]">Nexxworth nasce dall’esperienza diretta delle difficoltà che si incontrano entrando in un nuovo Paese. Per questo unisce ascolto, conoscenza delle procedure e attenzione alle esigenze di ogni persona.</p><p class="mt-4 text-base leading-8 text-[#455b70]">Dall’immigrazione al trasferimento, fino all’adattamento culturale: il nostro obiettivo è aiutarti a muoverti con più sicurezza.</p><a :href="route('nexxworth.about')" class="mt-8 inline-flex min-h-[48px] items-center gap-2 rounded-full bg-[#173554] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#245d8f]">Scopri chi siamo <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a></div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28">
            <div class="rounded-[2rem] bg-[#10223a] px-7 py-12 text-white sm:px-12 lg:flex lg:items-center lg:justify-between lg:gap-16 lg:px-16 lg:py-16">
                <div><p class="text-xs font-bold uppercase tracking-[0.22em] text-[#ee938a]">Facciamo il primo passo</p><h2 class="mt-4 max-w-2xl [font-family:Georgia,serif] text-3xl leading-tight tracking-tight sm:text-5xl">Raccontaci di cosa hai bisogno.</h2><p class="mt-5 max-w-xl text-sm leading-7 text-[#c7d5e3]">Parliamo della tua situazione e troviamo insieme il percorso più adatto.</p></div>
                <a :href="route('nexxworth.contact')" class="mt-8 inline-flex min-h-[50px] shrink-0 items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-[#132b47] transition hover:bg-[#f2d9d5] lg:mt-0">Vai ai contatti <ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></a>
            </div>
        </section>
    </NexxworthLayout>
</template>
