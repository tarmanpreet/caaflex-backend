<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import {
    ArrowRightIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ChevronRightIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
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
        description: 'Stati, scadenze, documenti, note e responsabili riuniti in un flusso chiaro e sempre aggiornato.',
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
    { number: '02', title: 'Avvia la pratica', text: 'Assegna tipo, procedura, responsabili e scadenze in pochi passaggi.' },
    { number: '03', title: 'Lavora in squadra', text: 'Documenti, note e attività restano leggibili da chi ne ha davvero bisogno.' },
    { number: '04', title: 'Chiudi con sicurezza', text: 'Lo storico conserva ogni passaggio e rende il lavoro verificabile.' },
];
</script>

<template>
    <Head title="Il gestionale per CAF moderni">
        <meta name="description" content="Gestisci clienti, pratiche, filiali e appuntamenti in un unico spazio di lavoro sicuro e organizzato." />
    </Head>

    <div class="min-h-screen overflow-x-hidden bg-slate-950 font-sans text-white selection:bg-amber-300 selection:text-slate-950">
        <a href="#top" class="fixed left-4 top-4 z-[60] -translate-y-24 rounded-xl bg-white px-4 py-3 font-semibold text-slate-950 shadow-xl transition focus:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transition-none">
            Vai al contenuto principale
        </a>
        <header class="fixed inset-x-0 top-0 z-50 px-4 pt-4 sm:px-6 lg:px-8">
            <nav class="mx-auto flex min-h-[64px] max-w-7xl items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-950/70 px-4 shadow-2xl shadow-slate-950/20 backdrop-blur-xl sm:px-6" aria-label="Navigazione principale">
                <a href="#top" class="group flex min-h-[44px] items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-500/20 transition duration-300 group-hover:rotate-3 group-hover:scale-105 motion-reduce:transform-none">
                        <ApplicationMark class="h-7 w-7" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-sm font-bold tracking-tight text-white">CAF Gestionale</span>
                        <span class="block text-[11px] font-medium tracking-[0.16em] text-slate-400">WORKSPACE</span>
                    </span>
                </a>

                <div class="hidden items-center gap-7 text-sm font-medium text-slate-300 md:flex">
                    <a href="#funzionalita" class="rounded-lg py-3 transition duration-200 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Funzionalità</a>
                    <a href="#metodo" class="rounded-lg py-3 transition duration-200 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Come funziona</a>
                    <a href="#sicurezza" class="rounded-lg py-3 transition duration-200 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">Sicurezza</a>
                </div>

                <div v-if="canLogin" class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        prefetch
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-white px-4 text-sm font-semibold text-slate-950 shadow-lg transition duration-200 hover:-translate-y-0.5 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none"
                    >
                        Vai alla dashboard
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

                        <div class="hero-enter hero-delay-4 mt-9 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                prefetch
                                class="cta-shine inline-flex min-h-[52px] items-center justify-center gap-2 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-400 px-6 font-bold text-white shadow-xl shadow-blue-500/25 transition duration-200 hover:-translate-y-1 hover:shadow-blue-500/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none"
                            >
                                Apri il tuo workspace
                                <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <Link
                                v-else-if="canLogin"
                                :href="route('login')"
                                prefetch
                                class="cta-shine inline-flex min-h-[52px] items-center justify-center gap-2 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-400 px-6 font-bold text-white shadow-xl shadow-blue-500/25 transition duration-200 hover:-translate-y-1 hover:shadow-blue-500/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transform-none"
                            >
                                Accedi al gestionale
                                <ArrowRightIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <a href="#funzionalita" class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/5 px-6 font-semibold text-white backdrop-blur transition duration-200 hover:border-white/30 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">
                                Scopri la piattaforma
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
                                            <span class="text-xs font-bold">CAF Workspace</span>
                                        </div>
                                        <div class="grid gap-2">
                                            <div v-for="(item, index) in ['Dashboard', 'Pratiche', 'Clienti', 'Agenda']" :key="item" :class="index === 0 ? 'bg-blue-500/15 text-blue-200' : 'text-slate-500'" class="flex h-9 items-center gap-2 rounded-lg px-2 text-[11px] font-medium sm:px-3">
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
                                            <div v-for="stat in [{ value: '128', label: 'Attive', color: 'text-cyan-300' }, { value: '24', label: 'Scadenze', color: 'text-amber-300' }, { value: '91%', label: 'Efficienza', color: 'text-emerald-300' }]" :key="stat.label" class="rounded-xl border border-white/10 bg-white/[0.04] p-2.5 sm:p-3">
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
                            <span><span class="block text-xs font-bold">Scadenza gestita</span><span class="block text-[10px] text-slate-400">Promemoria automatico</span></span>
                        </div>
                    </div>
                </div>

                <div class="mx-auto mt-20 max-w-7xl border-y border-white/10 py-6">
                    <p class="text-center text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Un unico ritmo per tutta l’organizzazione</p>
                    <div class="mt-5 grid grid-cols-2 gap-4 text-center text-sm font-semibold text-slate-300 sm:grid-cols-4">
                        <span>Clienti connessi</span><span>Pratiche tracciate</span><span>Filiali coordinate</span><span>Scadenze presidiate</span>
                    </div>
                </div>
            </section>

            <section id="funzionalita" class="relative bg-slate-50 px-5 py-24 text-slate-950 sm:px-8 lg:px-12 lg:py-32">
                <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-slate-950 to-transparent opacity-10" aria-hidden="true"></div>
                <div class="mx-auto max-w-7xl">
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

            <section id="metodo" class="relative overflow-hidden bg-white px-5 py-24 text-slate-950 sm:px-8 lg:px-12 lg:py-32">
                <div class="mx-auto grid max-w-7xl gap-16 lg:grid-cols-[0.72fr_1.28fr] lg:items-start">
                    <div data-reveal class="reveal lg:sticky lg:top-28">
                        <span class="text-sm font-black uppercase tracking-[0.2em] text-blue-600">Dal primo contatto alla chiusura</span>
                        <h2 class="mt-5 text-balance text-4xl font-black tracking-[-0.04em] sm:text-5xl">Un metodo semplice. Una squadra più veloce.</h2>
                        <p class="mt-6 text-lg leading-8 text-slate-600">Il gestionale accompagna il lavoro senza imporre rigidità: ogni informazione arriva nel momento giusto, alla persona giusta.</p>
                        <div class="mt-8 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                            <div class="flex items-start gap-3"><ShieldCheckIcon class="mt-0.5 h-6 w-6 shrink-0 text-blue-600" /><p class="text-sm leading-6 text-blue-900"><strong>La gerarchia segue l’organizzazione:</strong> i padri vedono il proprio ramo completo, i figli non risalgono mai verso dati superiori o paralleli.</p></div>
                        </div>
                    </div>

                    <div class="relative grid gap-5 before:absolute before:bottom-10 before:left-[27px] before:top-10 before:w-px before:bg-gradient-to-b before:from-blue-400 before:via-cyan-300 before:to-transparent sm:before:left-[35px]">
                        <article v-for="(step, index) in workflow" :key="step.number" data-reveal class="reveal group relative grid grid-cols-[56px_1fr] gap-5 rounded-[26px] border border-slate-200 bg-slate-50 p-5 transition duration-300 hover:border-blue-200 hover:bg-white hover:shadow-xl motion-reduce:transform-none sm:grid-cols-[72px_1fr] sm:p-7" :style="{ transitionDelay: `${index * 80}ms` }">
                            <span class="relative z-10 grid h-14 w-14 place-items-center rounded-2xl bg-slate-950 text-sm font-black text-white shadow-lg shadow-slate-300 transition duration-300 group-hover:bg-blue-600 group-hover:shadow-blue-200 sm:h-[72px] sm:w-[72px]">{{ step.number }}</span>
                            <div class="pt-1 sm:pt-2">
                                <h3 class="text-xl font-black text-slate-950 sm:text-2xl">{{ step.title }}</h3>
                                <p class="mt-3 leading-7 text-slate-600">{{ step.text }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section id="sicurezza" class="relative overflow-hidden bg-blue-700 px-5 py-24 sm:px-8 lg:px-12 lg:py-28">
                <div class="absolute inset-0 security-pattern opacity-20" aria-hidden="true"></div>
                <div class="orb orb-cyan absolute -right-20 -top-24 h-96 w-96 rounded-full bg-cyan-300/20 blur-3xl" aria-hidden="true"></div>
                <div class="relative mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-[1fr_0.85fr]">
                    <div data-reveal class="reveal">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-blue-50 backdrop-blur"><LockClosedIcon class="h-4 w-4" />Accesso consapevole ai dati</span>
                        <h2 class="mt-6 text-balance text-4xl font-black tracking-[-0.04em] text-white sm:text-5xl lg:text-6xl">Visibilità dove serve. Riservatezza dove conta.</h2>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-blue-100">Ruoli, permessi e filiali lavorano insieme: ogni operatore trova ciò che gli serve senza attraversare confini organizzativi che non gli appartengono.</p>
                    </div>

                    <div data-reveal class="reveal grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div v-for="item in [{ icon: ShieldCheckIcon, title: 'Filiali isolate', text: 'Nessuna visibilità verso padri o rami fratelli.' }, { icon: UserGroupIcon, title: 'Responsabilità chiare', text: 'Azioni e pratiche restano associate agli operatori.' }, { icon: DocumentTextIcon, title: 'Storico completo', text: 'Documenti, note e stati conservano il contesto.' }]" :key="item.title" class="flex items-start gap-4 rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-xl transition duration-300 hover:bg-white/15">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-blue-700"><component :is="item.icon" class="h-5 w-5" /></span>
                            <span><strong class="block text-base text-white">{{ item.title }}</strong><span class="mt-1 block text-sm leading-6 text-blue-100">{{ item.text }}</span></span>
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
                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-slate-300">Entra nel workspace e trasforma ogni attività quotidiana in un processo più chiaro, coordinato e sicuro.</p>
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
                <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-600 text-white"><ApplicationMark class="h-6 w-6" aria-hidden="true" /></span><span><strong class="block text-white">CAF Gestionale</strong><span>Più ordine. Più servizio.</span></span></div>
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

.security-pattern {
    background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.4) 1px, transparent 0);
    background-size: 28px 28px;
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
    .chart-bar { animation: none !important; }
    .reveal { opacity: 1; transform: none; transition: none; }
    .cta-shine::before { display: none; }
}
</style>
