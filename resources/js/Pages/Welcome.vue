<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {
    ArrowRightIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ChevronRightIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    DocumentMagnifyingGlassIcon,
    DocumentTextIcon,
    LockClosedIcon,
    ShieldCheckIcon,
    SparklesIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const hero = ref(null);
let observer;

const handlePointerMove = (event) => {
    if (!hero.value || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const bounds = hero.value.getBoundingClientRect();
    hero.value.style.setProperty('--pointer-x', `${event.clientX - bounds.left}px`);
    hero.value.style.setProperty('--pointer-y', `${event.clientY - bounds.top}px`);
};

onMounted(() => {
    const revealElements = document.querySelectorAll('[data-reveal]');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealElements.forEach(element => element.classList.add('is-visible'));
        return;
    }

    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px' });

    revealElements.forEach(element => observer.observe(element));
});

onBeforeUnmount(() => observer?.disconnect());

const features = [
    {
        icon: BuildingOffice2Icon,
        eyebrow: 'Organizzazione',
        title: 'Filiali davvero gerarchiche',
        description: 'La sede principale governa l’intera rete. Ogni filiale accede solo al proprio ramo e ai livelli sottostanti.',
        accent: 'blue',
    },
    {
        icon: ClipboardDocumentCheckIcon,
        eyebrow: 'Operatività',
        title: 'Pratiche sotto controllo',
        description: 'Stati, step, documenti, note e responsabili riuniti in un flusso chiaro e sempre aggiornato.',
        accent: 'amber',
    },
    {
        icon: UsersIcon,
        eyebrow: 'Relazioni',
        title: 'Un cliente, tutto il contesto',
        description: 'Anagrafica, storico e documenti restano connessi, protetti e immediatamente disponibili agli operatori autorizzati.',
        accent: 'cyan',
    },
    {
        icon: CalendarDaysIcon,
        eyebrow: 'Tempo',
        title: 'Agenda che lavora con te',
        description: 'Appuntamenti, disponibilità e conferme convivono con le pratiche per ridurre attese e passaggi manuali.',
        accent: 'violet',
    },
];

const workflow = [
    { number: '01', title: 'Accogli il cliente', text: 'Crea o ritrova subito il profilo nella filiale corretta.' },
    { number: '02', title: 'Avvia la pratica', text: 'Assegna tipo, procedura, responsabili e step in pochi passaggi.' },
    { number: '03', title: 'Lavora in squadra', text: 'Documenti, note e attività restano leggibili da chi ne ha davvero bisogno.' },
    { number: '04', title: 'Chiudi con sicurezza', text: 'Lo storico conserva ogni passaggio e rende il lavoro verificabile.' },
];
</script>

<template>
    <Head title="Il gestionale per CAF moderni">
        <meta name="description" content="Gestisci clienti, pratiche e appuntamenti sul tuo server. I dati restano della tua organizzazione e non sono condivisi con altri clienti." />
    </Head>

    <div class="min-h-screen overflow-x-hidden bg-slate-950 font-sans text-white selection:bg-amber-300 selection:text-slate-950">
        <a href="#top" class="fixed left-4 top-4 z-[60] -translate-y-24 rounded-xl bg-white px-4 py-3 font-semibold text-slate-950 shadow-xl transition focus:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transition-none">
            Vai al contenuto principale
        </a>
        <header class="fixed inset-x-0 top-0 z-50 px-4 pt-4 sm:px-6 lg:px-8">
            <nav class="mx-auto flex min-h-[64px] max-w-7xl items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-950/70 px-4 shadow-2xl shadow-slate-950/20 backdrop-blur-xl sm:px-6" aria-label="Navigazione principale">
                <a href="#top" class="group flex min-h-[44px] items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">
                    <ApplicationLogo light class="h-12 w-[205px] object-contain drop-shadow-lg transition duration-300 group-hover:scale-105 motion-reduce:transform-none" />
                </a>

                <div class="hidden items-center gap-7 text-sm font-medium text-slate-300 md:flex">
                    <a href="#funzionalita" class="rounded-lg py-3 transition duration-200 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Funzionalità</a>
                    <a href="#metodo" class="rounded-lg py-3 transition duration-200 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Come funziona</a>
                    <a href="#sicurezza" class="rounded-lg py-3 transition duration-200 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Privacy dei dati</a>
                    <Link :href="route('practice-status.index')" prefetch class="rounded-lg py-3 transition duration-200 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Controlla pratica</Link>
                </div>

                <div v-if="canLogin" class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        prefetch
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-white px-4 text-sm font-semibold text-slate-950 shadow-lg transition duration-200 hover:-translate-y-0.5 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none"
                    >
                        <span class="hidden min-[400px]:inline">Vai alla dashboard</span>
                        <span class="min-[400px]:hidden">Pannello</span>
                        <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" prefetch class="inline-flex min-h-[44px] items-center rounded-xl px-3 text-sm font-semibold text-white transition duration-200 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 sm:px-4">
                            Accedi
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" prefetch class="hidden min-h-[44px] items-center gap-2 rounded-xl bg-white px-4 text-sm font-semibold text-slate-950 shadow-lg transition duration-200 hover:-translate-y-0.5 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none sm:inline-flex">
                            Inizia ora
                            <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <main id="top">
            <section ref="hero" class="hero-grid relative isolate min-h-screen overflow-hidden px-5 pb-20 pt-36 sm:px-8 lg:px-12 lg:pb-28 lg:pt-44" @pointermove="handlePointerMove">
                <div class="pointer-glow absolute inset-0 -z-10" aria-hidden="true"></div>
                <div class="orb orb-blue absolute -left-24 top-32 -z-20 h-80 w-80 rounded-full bg-blue-600/30 blur-3xl" aria-hidden="true"></div>
                <div class="orb orb-cyan absolute -right-20 top-20 -z-20 h-96 w-96 rounded-full bg-cyan-500/20 blur-3xl" aria-hidden="true"></div>
                <div class="orb orb-amber absolute bottom-0 left-1/3 -z-20 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true"></div>

                <div class="mx-auto grid max-w-7xl items-center gap-16 lg:grid-cols-[0.9fr_1.1fr] lg:gap-14">
                    <div class="relative z-10 text-center lg:text-left">
                        <div class="hero-enter hero-delay-1 inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-sm font-semibold text-cyan-100 shadow-lg shadow-cyan-950/20 backdrop-blur">
                            <SparklesIcon class="h-4 w-4" aria-hidden="true" />
                            Il lavoro del CAF, finalmente connesso
                        </div>

                        <h1 class="hero-enter hero-delay-2 mt-7 text-balance text-5xl font-black leading-[0.98] tracking-[-0.055em] text-white sm:text-6xl lg:text-7xl">
                            Ogni pratica al posto giusto.
                            <span class="mt-2 block bg-gradient-to-r from-cyan-300 via-blue-300 to-amber-200 bg-clip-text text-transparent">Ogni filiale, in perfetto controllo.</span>
                        </h1>

                        <p class="hero-enter hero-delay-3 mx-auto mt-7 max-w-2xl text-pretty text-lg leading-8 text-slate-300 lg:mx-0 lg:max-w-xl">
                            Un unico spazio per coordinare clienti, pratiche, appuntamenti e documenti. Più chiarezza per gli operatori, più velocità per i cittadini, più controllo per tutta la rete.
                        </p>

                        <div class="hero-enter hero-delay-4 mx-auto mt-7 flex max-w-xl items-start gap-4 rounded-2xl border border-cyan-300/35 bg-slate-900/80 p-4 text-left shadow-lg shadow-cyan-950/20 lg:mx-0">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-cyan-300/15 text-cyan-200"><ShieldCheckIcon class="h-6 w-6" aria-hidden="true" /></span>
                            <div>
                                <p class="text-base font-bold text-white">I tuoi dati restano tuoi, sul tuo server.</p>
                                <p class="mt-1 text-sm leading-6 text-slate-300">Pratiche e documenti restano nell’infrastruttura della tua organizzazione e non sono condivisi con altri clienti.</p>
                            </div>
                        </div>

                        <div class="hero-enter hero-delay-4 mt-9 flex flex-col justify-center gap-3 sm:flex-row sm:flex-wrap sm:gap-2.5 lg:justify-start">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                prefetch
                                class="cta-shine inline-flex min-h-[52px] items-center justify-center gap-2 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-400 px-6 font-bold text-white shadow-xl shadow-blue-500/25 transition duration-200 hover:-translate-y-1 hover:shadow-blue-500/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none sm:min-h-[48px] sm:flex-none sm:whitespace-nowrap sm:rounded-xl sm:px-5 sm:text-sm"
                            >
                                <span class="sm:hidden">Apri il tuo spazio di lavoro</span>
                                <span class="hidden sm:inline">Spazio di lavoro</span>
                                <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <Link
                                v-else-if="canLogin"
                                :href="route('login')"
                                prefetch
                                class="cta-shine inline-flex min-h-[52px] items-center justify-center gap-2 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-400 px-6 font-bold text-white shadow-xl shadow-blue-500/25 transition duration-200 hover:-translate-y-1 hover:shadow-blue-500/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none sm:min-h-[48px] sm:flex-none sm:whitespace-nowrap sm:rounded-xl sm:px-5 sm:text-sm"
                            >
                                <span class="sm:hidden">Accedi al gestionale</span>
                                <span class="hidden sm:inline">Accedi</span>
                                <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <Link
                                :href="route('practice-status.index')"
                                prefetch
                                class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl border border-cyan-300/25 bg-cyan-300/10 px-6 font-semibold text-cyan-50 backdrop-blur transition duration-200 hover:-translate-y-0.5 hover:border-cyan-200/40 hover:bg-cyan-300/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none sm:min-h-[48px] sm:flex-none sm:whitespace-nowrap sm:rounded-xl sm:px-5 sm:text-sm"
                            >
                                <DocumentMagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" />
                                <span class="sm:hidden">Controlla pratica</span>
                                <span class="hidden sm:inline">Stato pratica</span>
                            </Link>
                            <a href="#funzionalita" class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/5 px-6 font-semibold text-white backdrop-blur transition duration-200 hover:border-white/30 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 sm:min-h-[48px] sm:flex-none sm:whitespace-nowrap sm:rounded-xl sm:border-transparent sm:bg-transparent sm:px-3 sm:text-sm sm:text-slate-300 sm:backdrop-blur-0 sm:hover:border-transparent sm:hover:bg-white/5 sm:hover:text-white">
                                <span class="sm:hidden">Scopri la piattaforma</span>
                                <span class="hidden sm:inline">Scopri</span>
                                <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                            </a>
                        </div>

                        <div class="hero-enter hero-delay-5 mt-9 flex flex-wrap justify-center gap-x-6 gap-y-3 text-sm text-slate-400 lg:justify-start">
                            <span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-emerald-400" />Dati separati per filiale</span>
                            <span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-emerald-400" />Flussi tracciabili</span>
                            <span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-emerald-400" />Portale cliente</span>
                        </div>
                    </div>

                    <div class="hero-enter hero-delay-3 relative mx-auto w-full max-w-3xl lg:mx-0">
                        <div class="dashboard-shell relative rounded-[28px] border border-white/15 bg-white/10 p-2 shadow-[0_35px_100px_-30px_rgba(37,99,235,0.55)] backdrop-blur-xl sm:p-3">
                            <div class="overflow-hidden rounded-[22px] border border-white/10 bg-slate-900/95">
                                <div class="flex h-11 items-center gap-2 border-b border-white/10 px-4">
                                    <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                    <span class="ml-3 h-5 flex-1 rounded-md bg-white/5"></span>
                                </div>

                                <div class="grid min-h-[430px] grid-cols-[64px_1fr] sm:grid-cols-[150px_1fr]">
                                    <aside class="border-r border-white/10 bg-slate-950/60 p-3 sm:p-4">
                                        <div class="mb-6 hidden items-center gap-2 sm:flex">
                                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-500"><BuildingOffice2Icon class="h-4 w-4" /></span>
                                            <span class="text-xs font-bold">Spazio CAF</span>
                                        </div>
                                        <div class="grid gap-2">
                                            <div v-for="(item, index) in ['Pannello', 'Pratiche', 'Clienti', 'Agenda']" :key="item" :class="index === 0 ? 'bg-blue-500/15 text-blue-200' : 'text-slate-500'" class="flex h-9 items-center gap-2 rounded-lg px-2 text-[11px] font-medium sm:px-3">
                                                <span class="h-4 w-4 rounded bg-current opacity-20"></span>
                                                <span class="hidden sm:inline">{{ item }}</span>
                                            </div>
                                        </div>
                                    </aside>

                                    <div class="p-3 sm:p-5">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Panoramica operativa</p>
                                                <p class="mt-1 text-base font-bold sm:text-xl">Buongiorno, Martina</p>
                                            </div>
                                            <div class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-amber-200 to-amber-400 text-xs font-black text-slate-900">MR</div>
                                        </div>

                                        <div class="mt-5 grid grid-cols-3 gap-2 sm:gap-3">
                                            <div v-for="stat in [{ value: '128', label: 'Attive', color: 'text-cyan-300' }, { value: '24', label: 'Step', color: 'text-amber-300' }, { value: '91%', label: 'Efficienza', color: 'text-emerald-300' }]" :key="stat.label" class="rounded-xl border border-white/10 bg-white/[0.04] p-2.5 sm:p-3">
                                                <p :class="stat.color" class="text-lg font-black sm:text-2xl">{{ stat.value }}</p>
                                                <p class="mt-1 text-[9px] text-slate-500 sm:text-[11px]">{{ stat.label }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid gap-3 sm:grid-cols-[1.3fr_0.7fr]">
                                            <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[11px] font-semibold text-slate-300">Carico pratiche</span>
                                                    <span class="text-[9px] text-emerald-300">+12% questo mese</span>
                                                </div>
                                                <div class="mt-4 flex h-24 items-end gap-2">
                                                    <span v-for="(height, index) in [38, 62, 48, 78, 58, 88, 70, 96]" :key="index" class="chart-bar flex-1 rounded-t bg-gradient-to-t from-blue-600 to-cyan-300" :style="{ height: `${height}%`, animationDelay: `${index * 80}ms` }"></span>
                                                </div>
                                            </div>

                                            <div class="hidden rounded-xl border border-white/10 bg-white/[0.04] p-3 sm:block">
                                                <p class="text-[11px] font-semibold text-slate-300">Rete filiali</p>
                                                <div class="mt-4 grid gap-2 text-[9px] text-slate-400">
                                                    <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-400"></span>Sede centrale</div>
                                                    <div class="ml-3 flex items-center gap-2 border-l border-blue-400/30 pl-3"><span class="h-2 w-2 rounded-full bg-cyan-400"></span>Milano Nord</div>
                                                    <div class="ml-3 flex items-center gap-2 border-l border-blue-400/30 pl-3"><span class="h-2 w-2 rounded-full bg-amber-300"></span>Monza Centro</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 rounded-xl border border-white/10 bg-white/[0.04] p-3">
                                            <div class="flex items-center justify-between"><span class="text-[11px] font-semibold text-slate-300">Attività recenti</span><span class="text-[9px] text-blue-300">Vedi tutte</span></div>
                                            <div class="mt-3 grid gap-2">
                                                <div v-for="row in [{ name: 'Rossi Mario', type: '730', state: 'In lavorazione' }, { name: 'Bianchi Sara', type: 'ISEE', state: 'Documenti ricevuti' }]" :key="row.name" class="grid grid-cols-[1fr_auto] items-center gap-3 rounded-lg bg-slate-950/45 px-3 py-2">
                                                    <div class="min-w-0"><p class="truncate text-[10px] font-semibold text-slate-200">{{ row.name }}</p><p class="mt-0.5 text-[8px] text-slate-500">{{ row.type }} · {{ row.state }}</p></div>
                                                    <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.8)]"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="float-card float-card-left absolute -left-5 top-20 hidden items-center gap-3 rounded-2xl border border-white/15 bg-slate-900/80 p-3 shadow-2xl backdrop-blur-xl sm:flex">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-400/15 text-emerald-300"><ShieldCheckIcon class="h-5 w-5" /></span>
                            <span><span class="block text-xs font-bold">Accesso protetto</span><span class="block text-[10px] text-slate-400">Visibilità per filiale</span></span>
                        </div>
                        <div class="float-card float-card-right absolute -right-3 bottom-12 hidden items-center gap-3 rounded-2xl border border-white/15 bg-slate-900/80 p-3 shadow-2xl backdrop-blur-xl sm:flex">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-300/15 text-amber-200"><ClockIcon class="h-5 w-5" /></span>
                            <span><span class="block text-xs font-bold">Step gestito</span><span class="block text-[10px] text-slate-400">Promemoria automatico</span></span>
                        </div>
                    </div>
                </div>

                <div class="mx-auto mt-20 max-w-7xl border-y border-white/10 py-6">
                    <p class="text-center text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Un unico ritmo per tutta l’organizzazione</p>
                    <div class="mt-5 grid grid-cols-2 gap-4 text-center text-sm font-semibold text-slate-300 sm:grid-cols-4">
                        <span>Clienti connessi</span><span>Pratiche tracciate</span><span>Filiali coordinate</span><span>Step presidiati</span>
                    </div>
                </div>
            </section>

            <section id="funzionalita" class="light-features relative isolate overflow-hidden px-5 py-24 text-slate-950 sm:px-8 lg:px-12 lg:py-32">
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="light-features-halo absolute -left-40 top-20 h-[34rem] w-[34rem] rounded-full"></div>
                    <div class="light-features-halo light-features-halo-alt absolute -right-40 bottom-4 h-[38rem] w-[38rem] rounded-full"></div>
                    <div class="light-features-grid absolute inset-0"></div>
                    <svg class="light-features-network absolute inset-0 h-full w-full" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g class="light-network-traces" stroke="#38BDF8" stroke-opacity="0.25" stroke-width="1.5">
                            <path d="M-40 198H188L290 300H472L598 426H720" />
                            <path d="M1480 154H1228L1120 262H954L816 400H720" />
                            <path d="M-40 714H210L348 576H516L648 444H720" />
                            <path d="M1480 748H1230L1110 628H924L762 466H720" />
                        </g>
                        <g class="light-network-signals" stroke="#0284C7" stroke-opacity="0.55" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="12 45">
                            <path d="M-40 198H188L290 300H472L598 426H720" />
                            <path d="M1480 154H1228L1120 262H954L816 400H720" />
                            <path d="M-40 714H210L348 576H516L648 444H720" />
                            <path d="M1480 748H1230L1110 628H924L762 466H720" />
                        </g>
                        <g class="light-network-nodes" fill="#E0F2FE" stroke="#38BDF8" stroke-width="2">
                            <circle cx="290" cy="300" r="8" /><circle cx="1120" cy="262" r="8" />
                            <circle cx="348" cy="576" r="8" /><circle cx="1110" cy="628" r="8" />
                            <circle cx="720" cy="444" r="13" fill="#BAE6FD" />
                        </g>
                    </svg>
                    <div class="absolute inset-0 bg-gradient-to-b from-slate-50/60 via-transparent to-white/60"></div>
                </div>
                <div class="relative mx-auto max-w-7xl">
                    <div data-reveal class="reveal mx-auto max-w-3xl text-center">
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700"><SparklesIcon class="h-4 w-4" />Tutto ciò che serve, senza rumore</span>
                        <h2 class="mt-6 text-balance text-4xl font-black tracking-[-0.04em] text-slate-950 sm:text-5xl lg:text-6xl">Il lavoro complesso diventa un flusso naturale.</h2>
                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-slate-600">Ogni modulo nasce per togliere attrito alle attività quotidiane e restituire una visione completa, dalla singola pratica all’intera rete di filiali.</p>
                    </div>

                    <div class="mt-16 grid gap-5 md:grid-cols-2">
                        <article v-for="(feature, index) in features" :key="feature.title" data-reveal class="reveal feature-card group relative overflow-hidden rounded-[28px] border border-slate-200 bg-white p-7 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-2xl motion-reduce:transform-none sm:p-9" :style="{ transitionDelay: `${index * 70}ms` }">
                            <div class="absolute -right-20 -top-20 h-52 w-52 rounded-full bg-blue-100/60 blur-3xl transition duration-500 group-hover:scale-125 motion-reduce:transform-none" aria-hidden="true"></div>
                            <div class="relative">
                                <div class="flex items-start justify-between gap-5">
                                    <span :class="{
                                        'bg-blue-100 text-blue-700': feature.accent === 'blue',
                                        'bg-amber-100 text-amber-700': feature.accent === 'amber',
                                        'bg-cyan-100 text-cyan-700': feature.accent === 'cyan',
                                        'bg-violet-100 text-violet-700': feature.accent === 'violet',
                                    }" class="grid h-14 w-14 place-items-center rounded-2xl transition duration-300 group-hover:rotate-3 group-hover:scale-110 motion-reduce:transform-none">
                                        <component :is="feature.icon" class="h-7 w-7" aria-hidden="true" />
                                    </span>
                                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">{{ feature.eyebrow }}</span>
                                </div>
                                <h3 class="mt-8 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ feature.title }}</h3>
                                <p class="mt-4 max-w-xl leading-7 text-slate-600">{{ feature.description }}</p>
                                <p class="mt-7 text-sm font-bold text-blue-700">Progettato per lavorare meglio</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section id="metodo" class="light-method relative isolate overflow-hidden px-5 py-24 text-slate-950 sm:px-8 lg:px-12 lg:py-32">
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="light-method-glow absolute -right-44 top-0 h-[45rem] w-[45rem] rounded-full"></div>
                    <div class="light-method-grid absolute inset-0"></div>
                    <svg class="light-method-path absolute inset-0 h-full w-full" viewBox="0 0 1440 860" preserveAspectRatio="xMidYMid slice" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M-40 752C230 732 294 648 441 616S654 560 764 486S951 354 1107 292S1300 220 1480 180" stroke="#0EA5E9" stroke-opacity="0.17" stroke-width="36" />
                        <path d="M-40 752C230 732 294 648 441 616S654 560 764 486S951 354 1107 292S1300 220 1480 180" stroke="#0284C7" stroke-opacity="0.3" stroke-width="2" />
                        <path class="light-method-signal" d="M-40 752C230 732 294 648 441 616S654 560 764 486S951 354 1107 292S1300 220 1480 180" stroke="#0284C7" stroke-opacity="0.75" stroke-width="3" stroke-linecap="round" stroke-dasharray="18 100" />
                        <g class="light-method-nodes" fill="#FFFFFF" stroke="#38BDF8" stroke-width="2">
                            <circle cx="441" cy="616" r="12" /><circle cx="764" cy="486" r="12" /><circle cx="1107" cy="292" r="12" />
                        </g>
                    </svg>
                    <div class="absolute inset-0 bg-gradient-to-r from-white/75 via-white/25 to-white/50"></div>
                </div>
                <div class="relative mx-auto grid max-w-7xl gap-16 lg:grid-cols-[0.72fr_1.28fr] lg:items-start">
                    <div data-reveal class="reveal lg:sticky lg:top-28">
                        <span class="text-sm font-black uppercase tracking-[0.2em] text-blue-600">Dal primo contatto alla chiusura</span>
                        <h2 class="mt-5 text-balance text-4xl font-black tracking-[-0.04em] sm:text-5xl">Un metodo semplice. Una squadra più veloce.</h2>
                        <p class="mt-6 text-lg leading-8 text-slate-600">Il gestionale accompagna il lavoro senza imporre rigidità: ogni informazione arriva nel momento giusto, alla persona giusta.</p>
                        <div class="mt-8 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                            <div class="flex items-start gap-3"><ShieldCheckIcon class="mt-0.5 h-6 w-6 shrink-0 text-blue-600" /><p class="text-sm leading-6 text-blue-900"><strong>La gerarchia segue l’organizzazione:</strong> i padri vedono il proprio ramo completo, i figli non risalgono mai verso dati superiori o paralleli.</p></div>
                        </div>
                    </div>

                    <div class="method-timeline relative grid gap-5 pl-8 sm:pl-10">
                        <span class="method-progress-line absolute bottom-7 left-0 top-7 w-[3px]" aria-hidden="true"></span>
                        <article v-for="(step, index) in workflow" :key="step.number" data-reveal class="reveal method-step group relative grid grid-cols-[56px_1fr] gap-5 rounded-[26px] border border-slate-200 bg-slate-50 p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-sky-300 hover:bg-white hover:shadow-xl motion-reduce:transform-none sm:grid-cols-[72px_1fr] sm:p-7" :style="{ transitionDelay: `${index * 140}ms`, '--step-index': index }">
                            <span class="method-step-number relative z-10 grid h-14 w-14 place-items-center rounded-2xl text-sm font-black text-white transition duration-300 sm:h-[72px] sm:w-[72px]">{{ step.number }}</span>
                            <div class="pt-1 sm:pt-2">
                                <h3 class="text-xl font-black text-slate-950 sm:text-2xl">{{ step.title }}</h3>
                                <p class="mt-3 leading-7 text-slate-600">{{ step.text }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section id="sicurezza" class="privacy-section relative isolate overflow-hidden px-5 py-24 sm:px-8 lg:px-12 lg:py-28">
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="privacy-aurora absolute -inset-24"></div>
                    <div class="privacy-floor absolute inset-x-[-15%] bottom-[-35%] h-[90%]"></div>
                    <svg class="privacy-network absolute inset-0 h-full w-full" viewBox="0 0 1440 600" preserveAspectRatio="xMidYMid slice" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="privacy-trace" x1="0" y1="0" x2="1440" y2="600" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#22D3EE" stop-opacity="0" />
                                <stop offset="0.28" stop-color="#22D3EE" stop-opacity="0.55" />
                                <stop offset="0.7" stop-color="#A78BFA" stop-opacity="0.5" />
                                <stop offset="1" stop-color="#A78BFA" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="privacy-vault" x1="626" y1="180" x2="824" y2="423" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#67E8F9" />
                                <stop offset="0.55" stop-color="#38BDF8" />
                                <stop offset="1" stop-color="#A78BFA" />
                            </linearGradient>
                            <filter id="privacy-glow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="8" />
                            </filter>
                        </defs>

                        <g class="privacy-traces" stroke="url(#privacy-trace)" stroke-width="1.4">
                            <path d="M0 104H252L346 198H594" />
                            <path d="M0 298H185L320 433H585" />
                            <path d="M0 506H308L413 401H582" />
                            <path d="M1440 86H1192L1077 201H849" />
                            <path d="M1440 311H1253L1134 430H862" />
                            <path d="M1440 510H1196L1091 405H861" />
                        </g>
                        <g class="privacy-signals" stroke="url(#privacy-trace)" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="16 45">
                            <path d="M0 104H252L346 198H594" />
                            <path d="M0 298H185L320 433H585" />
                            <path d="M0 506H308L413 401H582" />
                            <path d="M1440 86H1192L1077 201H849" />
                            <path d="M1440 311H1253L1134 430H862" />
                            <path d="M1440 510H1196L1091 405H861" />
                        </g>
                        <g fill="#67E8F9">
                            <circle cx="346" cy="198" r="4" /><circle cx="320" cy="433" r="4" /><circle cx="413" cy="401" r="4" />
                            <circle cx="1077" cy="201" r="4" /><circle cx="1134" cy="430" r="4" /><circle cx="1091" cy="405" r="4" />
                        </g>

                        <circle class="privacy-orbit" cx="720" cy="300" r="168" stroke="#67E8F9" stroke-opacity="0.22" stroke-width="1.5" stroke-dasharray="5 15" />
                        <circle class="privacy-orbit privacy-orbit-reverse" cx="720" cy="300" r="204" stroke="#A78BFA" stroke-opacity="0.16" stroke-width="1" stroke-dasharray="2 17" />
                        <path d="M720 172C762 198 790 203 821 212V292C821 360 783 405 720 434C657 405 619 360 619 292V212C650 203 678 198 720 172Z" stroke="url(#privacy-vault)" stroke-width="4" stroke-opacity="0.7" filter="url(#privacy-glow)" />
                        <path class="privacy-shield" d="M720 172C762 198 790 203 821 212V292C821 360 783 405 720 434C657 405 619 360 619 292V212C650 203 678 198 720 172Z" fill="#07172B" fill-opacity="0.5" stroke="url(#privacy-vault)" stroke-width="2" />
                        <g class="privacy-server" stroke="url(#privacy-vault)" stroke-width="2">
                            <rect x="671" y="244" width="98" height="33" rx="7" />
                            <rect x="671" y="284" width="98" height="33" rx="7" />
                            <rect x="671" y="324" width="98" height="33" rx="7" />
                            <circle cx="686" cy="260.5" r="3" fill="#67E8F9" stroke="none" />
                            <circle cx="686" cy="300.5" r="3" fill="#67E8F9" stroke="none" />
                            <circle cx="686" cy="340.5" r="3" fill="#67E8F9" stroke="none" />
                            <path d="M709 260.5H750M709 300.5H750M709 340.5H750" stroke-opacity="0.5" />
                        </g>
                    </svg>
                    <div class="absolute inset-0 bg-gradient-to-r from-[#070c19]/85 via-[#070c19]/35 to-[#070c19]/70"></div>
                </div>
                <div class="relative mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-[1fr_0.85fr]">
                    <div data-reveal class="reveal">
                        <span class="inline-flex items-center gap-2 rounded-full border border-cyan-300/25 bg-cyan-300/10 px-4 py-2 text-sm font-bold text-cyan-100 backdrop-blur"><LockClosedIcon class="h-4 w-4" />Privacy dei dati</span>
                        <h2 class="mt-6 text-balance text-4xl font-black tracking-[-0.04em] text-white sm:text-5xl lg:text-6xl">I dati sono tuoi. Restano sul tuo server.</h2>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">CAFlex conserva anagrafiche, pratiche e documenti nell’infrastruttura della tua organizzazione. I dati non vengono condivisi con altri clienti. Ruoli e permessi limitano l’accesso agli operatori autorizzati.</p>
                    </div>

                    <div data-reveal class="reveal grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div v-for="item in [{ icon: ShieldCheckIcon, title: 'Filiali isolate', text: 'Nessuna visibilità verso padri o rami fratelli.' }, { icon: UserGroupIcon, title: 'Responsabilità chiare', text: 'Azioni e pratiche restano associate agli operatori.' }, { icon: DocumentTextIcon, title: 'Storico completo', text: 'Documenti, note e stati conservano il contesto.' }]" :key="item.title" class="flex items-start gap-4 rounded-2xl border border-cyan-200/15 bg-slate-900/60 p-5 shadow-[0_20px_60px_-30px_rgba(34,211,238,0.35)] backdrop-blur-xl transition duration-300 hover:border-cyan-200/35 hover:bg-slate-900/75">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-cyan-300/15 text-cyan-200"><component :is="item.icon" class="h-5 w-5" /></span>
                            <span><strong class="block text-base text-white">{{ item.title }}</strong><span class="mt-1 block text-sm leading-6 text-slate-300">{{ item.text }}</span></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-slate-950 px-5 py-24 sm:px-8 lg:px-12 lg:py-28">
                <div data-reveal class="reveal relative mx-auto max-w-6xl overflow-hidden rounded-[36px] border border-white/10 bg-gradient-to-br from-white/10 to-white/[0.03] px-6 py-14 text-center shadow-2xl backdrop-blur-xl sm:px-12 sm:py-20">
                    <div class="absolute inset-x-0 top-0 mx-auto h-40 w-2/3 rounded-full bg-blue-500/20 blur-3xl" aria-hidden="true"></div>
                    <div class="relative">
                        <span class="inline-flex items-center gap-2 rounded-full bg-amber-300 px-4 py-2 text-sm font-black text-slate-950"><SparklesIcon class="h-4 w-4" />Il prossimo passo è più semplice</span>
                        <h2 class="mx-auto mt-7 max-w-4xl text-balance text-4xl font-black tracking-[-0.045em] text-white sm:text-5xl lg:text-6xl">Porta ordine nella rete. Libera tempo per le persone.</h2>
                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-slate-300">Entra nello spazio di lavoro e trasforma ogni attività quotidiana in un processo più chiaro, coordinato e sicuro.</p>
                        <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" prefetch class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-white px-7 font-bold text-slate-950 transition duration-200 hover:-translate-y-1 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none">Vai alla dashboard<ArrowRightIcon class="h-5 w-5" /></Link>
                            <Link v-else-if="canLogin" :href="route('login')" prefetch class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-white px-7 font-bold text-slate-950 transition duration-200 hover:-translate-y-1 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none">Accedi al gestionale<ArrowRightIcon class="h-5 w-5" /></Link>
                            <Link v-if="!$page.props.auth.user && canRegister" :href="route('register')" prefetch class="inline-flex min-h-[52px] items-center justify-center rounded-2xl border border-white/20 bg-white/5 px-7 font-bold text-white transition duration-200 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Crea un account</Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/10 bg-slate-950 px-5 py-8 sm:px-8">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 text-center text-sm text-slate-500 sm:flex-row sm:text-left">
                <div class="flex items-center gap-3"><ApplicationMark class="h-10 w-10 drop-shadow-md" aria-hidden="true" /><span><strong class="block text-white">{{ $page.props.branding.name }}</strong><span>Più ordine. Più servizio.</span></span></div>
                <p>Clienti, pratiche e filiali in un unico spazio operativo.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
:global(html) { scroll-behavior: smooth; }

.hero-grid {
    --pointer-x: 50%;
    --pointer-y: 30%;
    background-image:
        linear-gradient(rgba(148, 163, 184, 0.055) 1px, transparent 1px),
        linear-gradient(90deg, rgba(148, 163, 184, 0.055) 1px, transparent 1px);
    background-size: 48px 48px;
}

.pointer-glow {
    background: radial-gradient(500px circle at var(--pointer-x) var(--pointer-y), rgba(56, 189, 248, 0.12), transparent 55%);
}

.light-features { background: #f8fbff; }
.light-features-halo {
    background: radial-gradient(circle, rgba(56, 189, 248, .22), rgba(125, 211, 252, .08) 48%, transparent 70%);
    filter: blur(10px);
    animation: light-halo-drift 17s ease-in-out infinite alternate;
}
.light-features-halo-alt {
    background: radial-gradient(circle, rgba(167, 139, 250, .18), rgba(186, 230, 253, .08) 50%, transparent 70%);
    animation-delay: -8s;
    animation-direction: alternate-reverse;
}
.light-features-grid {
    background-image: radial-gradient(circle, rgba(37, 99, 235, .2) 1px, transparent 1px);
    background-size: 32px 32px;
    opacity: .44;
}
.light-features-network { opacity: .8; }
.light-network-signals { animation: light-flow 13s linear infinite; }
.light-network-nodes { animation: light-node-pulse 4.5s ease-in-out infinite; }

.light-method { background: #fff; }
.light-method-glow {
    background: radial-gradient(circle, rgba(14, 165, 233, .17), rgba(186, 230, 253, .08) 52%, transparent 72%);
    filter: blur(8px);
    animation: light-halo-drift 20s ease-in-out infinite alternate-reverse;
}
.light-method-grid {
    background-image:
        linear-gradient(rgba(56, 189, 248, .09) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56, 189, 248, .09) 1px, transparent 1px);
    background-size: 64px 64px;
    opacity: .55;
}
.light-method-path { opacity: .8; }
.light-method-signal { animation: light-flow 15s linear infinite reverse; }
.light-method-nodes { animation: light-node-pulse 5s ease-in-out infinite; }
.method-progress-line {
    background: linear-gradient(to bottom, transparent, #38bdf8 12%, #0ea5e9 50%, #67e8f9 88%, transparent);
    border-radius: 9999px;
    box-shadow: 0 0 18px rgba(14, 165, 233, .35);
}
.method-progress-line::before {
    content: '';
    position: absolute;
    inset: 0 -6px;
    background: linear-gradient(to bottom, transparent, rgba(56, 189, 248, .45), transparent);
    filter: blur(8px);
}
.method-progress-line::after {
    content: '';
    position: absolute;
    left: -3px;
    top: -18%;
    height: 16%;
    width: 9px;
    border-radius: 9999px;
    background: linear-gradient(to bottom, transparent, #0284c7, #67e8f9, transparent);
    box-shadow: 0 0 20px rgba(14, 165, 233, .85);
    animation: light-progress 7s linear infinite;
}
.method-step::before {
    content: '';
    position: absolute;
    left: -32px;
    top: 47px;
    width: 32px;
    height: 3px;
    background: linear-gradient(to right, #0ea5e9, #93c5fd);
    box-shadow: 0 0 12px rgba(14, 165, 233, .45);
}
.method-step::after {
    content: '';
    position: absolute;
    left: -39px;
    top: 41px;
    width: 15px;
    height: 15px;
    border: 3px solid #0ea5e9;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 0 0 5px rgba(14, 165, 233, .11), 0 0 18px rgba(14, 165, 233, .4);
    animation: method-node-pulse 5s ease-in-out infinite;
    animation-delay: calc(var(--step-index) * 1s);
}
.method-step-number {
    background: linear-gradient(145deg, #0f172a, #1e40af);
    box-shadow: 0 12px 28px rgba(30, 64, 175, .22), inset 0 1px 0 rgba(255, 255, 255, .18);
    animation: method-badge-pulse 5s ease-in-out infinite;
    animation-delay: calc(var(--step-index) * 1s);
}
.method-step:hover .method-step-number {
    background: linear-gradient(145deg, #1e40af, #0284c7);
}

@media (min-width: 640px) {
    .method-step::before { left: -40px; top: 63px; width: 40px; }
    .method-step::after { left: -47px; top: 57px; }
}

@keyframes light-halo-drift {
    from { transform: translate3d(-4%, -2%, 0) scale(.96); }
    to { transform: translate3d(8%, 5%, 0) scale(1.1); }
}
@keyframes light-flow {
    from { stroke-dashoffset: 0; }
    to { stroke-dashoffset: -560; }
}
@keyframes light-node-pulse {
    0%, 100% { opacity: .45; }
    50% { opacity: 1; }
}
@keyframes light-progress {
    to { top: 103%; }
}
@keyframes method-node-pulse {
    0%, 100% { box-shadow: 0 0 0 5px rgba(14, 165, 233, .11), 0 0 18px rgba(14, 165, 233, .4); }
    45% { box-shadow: 0 0 0 10px rgba(14, 165, 233, .02), 0 0 24px rgba(14, 165, 233, .75); }
}
@keyframes method-badge-pulse {
    0%, 100% { box-shadow: 0 12px 28px rgba(30, 64, 175, .22), inset 0 1px 0 rgba(255, 255, 255, .18); }
    45% { box-shadow: 0 14px 32px rgba(14, 165, 233, .36), 0 0 0 4px rgba(14, 165, 233, .07), inset 0 1px 0 rgba(255, 255, 255, .25); }
}

.privacy-section {
    background: #070c19;
}

.privacy-aurora {
    background:
        radial-gradient(ellipse at 50% 48%, rgba(8, 145, 178, .32), transparent 36%),
        radial-gradient(ellipse at 78% 12%, rgba(109, 40, 217, .27), transparent 34%),
        radial-gradient(ellipse at 12% 85%, rgba(14, 116, 144, .23), transparent 40%);
    animation: privacy-aurora-drift 18s ease-in-out infinite alternate;
}

.privacy-floor {
    background-image:
        linear-gradient(rgba(103, 232, 249, .18) 1px, transparent 1px),
        linear-gradient(90deg, rgba(103, 232, 249, .18) 1px, transparent 1px);
    background-size: 68px 68px;
    opacity: .34;
    transform: perspective(540px) rotateX(67deg);
    transform-origin: center bottom;
    animation: privacy-grid-flow 12s linear infinite;
}

.privacy-network { opacity: .72; }
.privacy-traces { opacity: .58; }
.privacy-signals { animation: privacy-signal-flow 14s linear infinite; }
.privacy-orbit {
    transform-origin: 720px 300px;
    animation: privacy-orbit-spin 46s linear infinite;
}
.privacy-orbit-reverse { animation-direction: reverse; animation-duration: 60s; }
.privacy-shield { animation: privacy-vault-pulse 5s ease-in-out infinite; }
.privacy-server { animation: privacy-server-pulse 4s ease-in-out infinite alternate; }

@keyframes privacy-aurora-drift {
    from { transform: translate3d(-2%, 2%, 0) scale(1); }
    to { transform: translate3d(3%, -3%, 0) scale(1.08); }
}
@keyframes privacy-grid-flow {
    from { background-position: 0 0; }
    to { background-position: 0 68px; }
}
@keyframes privacy-signal-flow {
    from { stroke-dashoffset: 0; }
    to { stroke-dashoffset: -488; }
}
@keyframes privacy-orbit-spin {
    to { transform: rotate(360deg); }
}
@keyframes privacy-vault-pulse {
    0%, 100% { stroke-opacity: .45; }
    50% { stroke-opacity: .95; }
}
@keyframes privacy-server-pulse {
    from { opacity: .5; }
    to { opacity: 1; }
}

.hero-enter { animation: hero-enter 800ms cubic-bezier(.16, 1, .3, 1) both; }
.hero-delay-1 { animation-delay: 80ms; }
.hero-delay-2 { animation-delay: 170ms; }
.hero-delay-3 { animation-delay: 260ms; }
.hero-delay-4 { animation-delay: 350ms; }
.hero-delay-5 { animation-delay: 440ms; }

.dashboard-shell { animation: dashboard-enter 1000ms 300ms cubic-bezier(.16, 1, .3, 1) both; }
.float-card-left { animation: float-left 5s 1.3s ease-in-out infinite; }
.float-card-right { animation: float-right 5.8s 900ms ease-in-out infinite; }
.orb-blue { animation: orb-drift-a 14s ease-in-out infinite alternate; }
.orb-cyan { animation: orb-drift-b 17s ease-in-out infinite alternate; }
.orb-amber { animation: orb-drift-a 19s 2s ease-in-out infinite alternate-reverse; }
.chart-bar { transform-origin: bottom; animation: bar-grow 800ms cubic-bezier(.16, 1, .3, 1) both; }

.cta-shine::before {
    content: '';
    position: absolute;
    inset: 0;
    transform: translateX(-130%) skewX(-20deg);
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
    transition: transform 600ms ease;
}
.cta-shine { position: relative; }
.cta-shine:hover::before { transform: translateX(130%) skewX(-20deg); }

.reveal {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 700ms cubic-bezier(.16, 1, .3, 1), transform 700ms cubic-bezier(.16, 1, .3, 1);
}
.reveal.is-visible { opacity: 1; transform: translateY(0); }

@keyframes hero-enter {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes dashboard-enter {
    from { opacity: 0; transform: perspective(1000px) rotateY(-8deg) rotateX(4deg) translateY(35px) scale(.96); }
    to { opacity: 1; transform: perspective(1000px) rotateY(0) rotateX(0) translateY(0) scale(1); }
}
@keyframes float-left { 0%, 100% { transform: translate3d(0,0,0) rotate(-2deg); } 50% { transform: translate3d(0,-10px,0) rotate(1deg); } }
@keyframes float-right { 0%, 100% { transform: translate3d(0,0,0) rotate(2deg); } 50% { transform: translate3d(0,9px,0) rotate(-1deg); } }
@keyframes orb-drift-a { from { transform: translate3d(-3%, -4%, 0) scale(.94); } to { transform: translate3d(12%, 9%, 0) scale(1.08); } }
@keyframes orb-drift-b { from { transform: translate3d(5%, -7%, 0) scale(1); } to { transform: translate3d(-10%, 12%, 0) scale(.9); } }
@keyframes bar-grow { from { transform: scaleY(0); opacity: .2; } to { transform: scaleY(1); opacity: 1; } }

@media (prefers-reduced-motion: reduce) {
    :global(html) { scroll-behavior: auto; }
    .hero-enter,
    .dashboard-shell,
    .float-card-left,
    .float-card-right,
    .orb,
    .chart-bar,
    .privacy-aurora,
    .privacy-floor,
    .privacy-signals,
    .privacy-orbit,
    .privacy-shield,
    .privacy-server,
    .light-features-halo,
    .light-network-signals,
    .light-network-nodes,
    .light-method-glow,
    .light-method-signal,
    .light-method-nodes,
    .method-progress-line::after,
    .method-step::after,
    .method-step-number { animation: none !important; }
    .reveal { opacity: 1; transform: none; transition: none; }
    .cta-shine::before { display: none; }
}
</style>
