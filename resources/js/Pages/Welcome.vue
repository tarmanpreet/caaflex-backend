<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {
    ArrowRightIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    DocumentTextIcon,
    LockClosedIcon,
    ShieldCheckIcon,
    SparklesIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const features = [
    {
        icon: BuildingOffice2Icon,
        eyebrow: 'Organizzazione',
        title: 'Sedi organizzate, rete coordinata',
        description: 'Ogni filiale lavora nel proprio ramo. La sede principale mantiene una visione della rete.',
        accent: 'blue',
    },
    {
        icon: ClipboardDocumentCheckIcon,
        eyebrow: 'Operatività',
        title: 'Pratiche leggibili, passo dopo passo',
        description: 'Stati, step, documenti, note e responsabili raccolti nello stesso flusso.',
        accent: 'amber',
    },
    {
        icon: UsersIcon,
        eyebrow: 'Relazioni',
        title: 'Clienti, con tutto il contesto',
        description: 'Anagrafica, storico e documenti collegati, disponibili agli operatori autorizzati.',
        accent: 'cyan',
    },
    {
        icon: CalendarDaysIcon,
        eyebrow: 'Tempo',
        title: 'Un’agenda collegata al lavoro',
        description: 'Appuntamenti e disponibilità coordinati con le pratiche.',
        accent: 'violet',
    },
];

const workflow = [
    { number: '01', title: 'Configuriamo', text: 'Definiamo organizzazione, utenze, sedi e configurazione del servizio.' },
    { number: '02', title: 'Formiamo', text: 'Formazione iniziale sul flusso operativo. Programma e durata sono concordati nell’offerta.' },
    { number: '03', title: 'Presentiamo', text: 'Una landing standard inclusa, anche con Start. Il dominio lo acquisti e lo rinnovi tu.' },
];

const faq = [
    { question: 'La landing è inclusa anche nel piano Start?', answer: 'Sì, una pagina standard per organizzazione, senza costo aggiuntivo. Il costo di acquisto e rinnovo del dominio resta al cliente. Contenuti e perimetro della pagina sono concordati durante l’avvio.' },
    { question: 'Come viene definito il costo del primo anno?', answer: 'Il primo anno comprende l’attivazione e i canoni per le sedi previste. L’importo dell’attivazione, le modalità di pagamento e le eventuali condizioni Partner vengono specificati nell’offerta. IVA e dominio sono esclusi.' },
    { question: 'Posso aggiungere sedi anche con Start?', answer: 'Sì. Ogni sede aggiuntiva costa 25 €/mese in entrambi i piani. La sede principale è pagata con il canone del piano. Il prezzo delle sedi non modifica automaticamente le utenze concordate.' },
    { question: 'Quante utenze comprende Team?', answer: 'Il numero di utenze e la configurazione per ciascuna sede sono indicati nell’offerta. Non viene promesso un numero illimitato di operatori.' },
    { question: 'Che formazione è prevista?', answer: 'Formazione iniziale sull’utilizzo di CAAFLEX, inclusa nell’attivazione. Sessioni, durata e partecipanti sono concordati. Non è un corso fiscale o abilitante.' },
    { question: 'Che cosa copre il backup?', answer: 'Il perimetro, la frequenza e la conservazione delle copie vengono specificati nel piano di servizio. I backup operativi non sono presentati come conservazione digitale a norma.' },
    { question: 'CAAFLEX sostituisce il software per gli invii fiscali?', answer: 'Questa proposta riguarda l’organizzazione operativa di clienti, pratiche, documenti e appuntamenti. Invii telematici e integrazioni specifiche devono essere verificati espressamente prima dell’acquisto.' },
];

const demo = ref({ name: '', organization: '', contact: '', operators: '', branches: '', plan: 'Da valutare', notes: '' });
const demoText = ref('');
const demoCopyStatus = ref('');
const prepareDemo = () => {
    demoText.value = `Richiesta demo CAAFLEX\nReferente: ${demo.value.name}\nOrganizzazione: ${demo.value.organization}\nRecapito: ${demo.value.contact}\nOperatori: ${demo.value.operators || 'da definire'}\nSedi: ${demo.value.branches || 'da definire'}\nPiano: ${demo.value.plan}\nDa organizzare meglio: ${demo.value.notes || 'da definire'}`;
    demoCopyStatus.value = '';
};
const copyDemo = async () => {
    try {
        await navigator.clipboard.writeText(demoText.value);
        demoCopyStatus.value = 'Testo copiato.';
    } catch {
        demoCopyStatus.value = 'Seleziona e copia il testo preparato.';
    }
};
</script>

<template>
    <Head title="Più ordine. Più servizio.">
        <meta name="description" content="CAAFLEX: clienti, pratiche, documenti e agenda per CAF e CAAF. Formazione iniziale nell’attivazione, server e backup nel canone, landing inclusa." />
    </Head>

    <div class="min-h-screen overflow-x-hidden bg-slate-950 font-sans text-white selection:bg-amber-300 selection:text-slate-950">
        <a href="#top" class="fixed left-4 top-4 z-[60] -translate-y-24 rounded-xl bg-white px-4 py-3 font-semibold text-slate-950 shadow-xl transition focus:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 motion-reduce:transition-none">
            Vai al contenuto principale
        </a>
        <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/90 px-4 py-3 shadow-sm backdrop-blur-xl sm:px-6 lg:px-8">
            <nav class="mx-auto flex min-h-[60px] max-w-7xl items-center justify-between gap-4" aria-label="Navigazione principale">
                <a href="#top" class="inline-flex min-h-11 items-center rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"><ApplicationLogo class="h-12 w-[190px] object-contain" /></a>
                <div class="hidden items-center gap-7 text-sm font-semibold text-slate-600 md:flex">
                    <a href="#funzionalita" class="hover:text-blue-700">Funzionalità</a>
                    <a href="#prezzi" class="hover:text-blue-700">Prezzi</a>
                    <a href="#metodo" class="hover:text-blue-700">Avvio</a>
                    <a href="#faq" class="hover:text-blue-700">Domande</a>
                    <Link :href="route('caaflex.calculator')" class="hover:text-blue-700">Preventivatore</Link>
                </div>
                <a href="#demo" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-4 text-sm font-bold text-white shadow-md shadow-blue-500/20 hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 sm:px-5">Richiedi la demo</a>
            </nav>
        </header>

        <main id="top">
            <section class="relative isolate overflow-hidden bg-white px-5 pb-20 pt-36 text-slate-950 sm:px-8 lg:px-12 lg:pb-28 lg:pt-44">
                <div class="pointer-events-none absolute -right-40 -top-20 -z-10 h-[45rem] w-[45rem] rounded-full bg-cyan-100/70 blur-3xl" aria-hidden="true"></div>
                <div class="mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-[0.94fr_1.06fr] lg:gap-16">
                    <div class="relative z-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700"><SparklesIcon class="h-4 w-4" aria-hidden="true" />Il gestionale operativo per CAF e CAAF</p>
                        <h1 class="mt-8 text-balance text-5xl font-black leading-[1.02] tracking-[-0.055em] text-slate-950 sm:text-6xl lg:text-7xl">Più ordine.<br>Più servizio.<span class="mt-2 block bg-gradient-to-r from-blue-700 to-cyan-500 bg-clip-text text-transparent">Parti con un metodo.</span></h1>
                        <p class="mt-7 max-w-xl text-pretty text-lg leading-8 text-slate-600">Clienti, pratiche, documenti e appuntamenti nello stesso spazio. Un avvio accompagnato, dal singolo ufficio alla rete di sedi.</p>
                        <div class="mt-9 flex flex-wrap gap-3"><a href="#demo" class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-6 font-bold text-white shadow-xl shadow-blue-500/20 hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">Richiedi la demo <ArrowRightIcon class="h-5 w-5" /></a><a href="#prezzi" class="inline-flex min-h-[52px] items-center justify-center rounded-xl border border-slate-300 bg-white px-6 font-bold text-slate-800 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">Scopri i prezzi</a></div>
                        <div class="mt-9 flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium text-slate-600"><span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-emerald-600" />Formazione iniziale</span><span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-emerald-600" />Server e backup</span><span class="inline-flex items-center gap-2"><CheckCircleIcon class="h-5 w-5 text-emerald-600" />Landing inclusa</span></div>
                    </div>
                    <div class="relative rounded-[32px] border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50 p-6 shadow-[0_30px_80px_-40px_rgba(37,99,235,0.35)] sm:p-9">
                        <div class="absolute -right-2 -top-2 h-20 w-20 rounded-full bg-cyan-200/60 blur-2xl" aria-hidden="true"></div>
                        <div class="relative"><span class="text-xs font-black uppercase tracking-[0.22em] text-blue-700">Un unico ritmo per tutta l’organizzazione</span><h2 class="mt-4 max-w-md text-balance text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Ogni informazione collegata a quella giusta.</h2><p class="mt-4 max-w-md text-base leading-7 text-slate-600">Un percorso operativo, senza informazioni sparse tra persone e sedi.</p></div>
                        <div class="relative mt-9 grid gap-3 sm:grid-cols-2">
                            <div v-for="(item, index) in [{ icon: UsersIcon, title: 'Clienti', text: 'Anagrafica e storico', color: 'bg-cyan-100 text-cyan-700' }, { icon: ClipboardDocumentCheckIcon, title: 'Pratiche', text: 'Step e responsabili', color: 'bg-blue-100 text-blue-700' }, { icon: DocumentTextIcon, title: 'Documenti', text: 'Allegati e note', color: 'bg-amber-100 text-amber-700' }, { icon: CalendarDaysIcon, title: 'Agenda', text: 'Appuntamenti', color: 'bg-emerald-100 text-emerald-700' }]" :key="item.title" class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md motion-reduce:transform-none"><span :class="item.color" class="grid h-12 w-12 shrink-0 place-items-center rounded-xl"><component :is="item.icon" class="h-6 w-6" aria-hidden="true" /></span><span><strong class="block text-base text-slate-950">{{ item.title }}</strong><span class="block text-sm text-slate-500">{{ item.text }}</span></span><span class="sr-only">{{ index + 1 }}</span></div>
                        </div>
                        <div class="relative mt-6 flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800"><span class="h-2.5 w-2.5 rounded-full bg-cyan-500"></span>Una visione chiara, dalla sede alla rete.</div>
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
                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-slate-600">Anagrafica, pratiche, documenti e appuntamenti si ritrovano nello stesso percorso operativo, dalla singola sede alla rete di filiali.</p>
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

            <section id="prezzi" class="bg-slate-950 px-5 py-24 sm:px-8 lg:px-12 lg:py-28">
                <div class="mx-auto max-w-7xl">
                    <div class="max-w-3xl"><p class="text-sm font-black uppercase tracking-[0.2em] text-cyan-300">Una proposta leggibile</p><h2 class="mt-5 text-balance text-4xl font-black tracking-tight sm:text-5xl">Scegli la dimensione del tuo CAF.</h2><p class="mt-6 text-lg leading-8 text-slate-300">Entrambi i piani includono server, backup e una landing page standard per organizzazione. La formazione iniziale è compresa nell’attivazione.</p></div>
                    <div class="mt-12 grid gap-6 lg:grid-cols-2">
                        <article class="rounded-[28px] border border-white/15 bg-white/5 p-7 sm:p-9"><span class="rounded-full bg-cyan-300/10 px-3 py-2 text-xs font-bold uppercase tracking-widest text-cyan-200">Start · una utenza</span><h3 class="mt-7 text-3xl font-black">CAAFLEX Start</h3><p class="mt-4 text-5xl font-black">25 €<span class="text-lg font-normal text-slate-400"> /mese</span></p><p class="mt-5 min-h-[3rem] text-slate-300">Canone della sede principale, una utenza. Ogni sede aggiuntiva: +25 €/mese.</p><ul class="mt-7 grid gap-3 border-t border-white/10 pt-7 text-sm text-slate-200"><li>✓ Clienti, pratiche, documenti e agenda</li><li>✓ Server e backup nel canone</li><li>✓ Una landing page standard inclusa</li><li>✓ Formazione iniziale nell’attivazione</li></ul><p class="mt-7 border-t border-white/10 pt-7 text-slate-300">Attivazione con formazione iniziale. Condizioni economiche definite nell’offerta.</p><a href="#demo" class="mt-7 inline-flex min-h-12 items-center rounded-xl border border-cyan-300/30 bg-cyan-300/10 px-5 font-bold text-cyan-100 hover:bg-cyan-300/20">Parliamo di Start</a></article>
                        <article class="rounded-[28px] border border-cyan-300/40 bg-gradient-to-br from-blue-950 to-slate-900 p-7 shadow-2xl shadow-blue-950/30 sm:p-9"><span class="rounded-full bg-cyan-300/10 px-3 py-2 text-xs font-bold uppercase tracking-widest text-cyan-200">Più operatori · più sedi</span><h3 class="mt-7 text-3xl font-black">CAAFLEX Team & Rete</h3><p class="mt-4 text-5xl font-black">50 €<span class="text-lg font-normal text-slate-400"> /mese</span></p><p class="mt-5 min-h-[3rem] text-slate-300">Oppure <strong class="text-white">500 €/anno anticipati</strong> sul canone base: 100 € in meno rispetto a 12 canoni mensili.</p><ul class="mt-7 grid gap-3 border-t border-white/10 pt-7 text-sm text-slate-200"><li>✓ Sede principale: 50 €/mese</li><li>✓ Ruoli e visibilità per filiale</li><li>✓ Server, backup e una landing inclusi</li><li>✓ Formazione iniziale nell’attivazione</li></ul><p class="mt-7 border-t border-white/10 pt-7 text-slate-300">Attivazione con formazione iniziale. Condizioni economiche definite nell’offerta.</p><a href="#demo" class="mt-7 inline-flex min-h-12 items-center rounded-xl bg-gradient-to-r from-blue-500 to-cyan-400 px-5 font-bold text-white hover:brightness-110">Parliamo di Team</a></article>
                    </div>
                    <div class="mt-7 rounded-2xl border border-white/10 bg-white/5 p-6"><h3 class="text-xl font-bold">Ogni sede in più: 25 €/mese.</h3><p class="mt-2 text-sm leading-6 text-slate-300">Vale sia per Start sia per Team. La sede principale è pagata con il canone del piano. Utenze e configurazione nell’offerta; una landing per organizzazione.</p></div>
                    <p class="mt-5 text-xs leading-6 text-slate-400">Prezzi IVA esclusa. Attivazione una tantum. Annuale Team riferito al solo canone base: le filiali restano a 25 €/mese ciascuna. Acquisto e rinnovo del dominio a carico del cliente. Piano di servizio, spazio, backup, formazione e assistenza definiti nell’offerta. Partner Visibilità subordinato a una collaborazione pubblicitaria concordata per iscritto.</p>
                    <Link :href="route('caaflex.calculator')" class="mt-7 inline-flex min-h-12 items-center gap-2 rounded-xl border border-white/20 px-6 font-bold hover:bg-white/10">Apri il preventivatore <ArrowRightIcon class="h-5 w-5" /></Link>
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
                        <span class="text-sm font-black uppercase tracking-[0.2em] text-blue-600">Non solo installazione</span>
                        <h2 class="mt-5 text-balance text-4xl font-black tracking-[-0.04em] sm:text-5xl">Ti accompagniamo nell’avvio.</h2>
                        <p class="mt-6 text-lg leading-8 text-slate-600">Partiamo dal tuo modo di lavorare: configurazione, formazione iniziale e presenza online sono parte della proposta.</p>
                        <div class="mt-8 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                            <div class="flex items-start gap-3"><ShieldCheckIcon class="mt-0.5 h-6 w-6 shrink-0 text-blue-600" /><p class="text-sm leading-6 text-blue-900"><strong>Partner Visibilità:</strong> le condizioni dell’attivazione vengono definite nell’offerta. Canali, attività e durata della collaborazione sono concordati per iscritto, senza obblighi di recensioni positive.</p></div>
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
                        <h2 class="mt-6 text-balance text-4xl font-black tracking-[-0.04em] text-white sm:text-5xl lg:text-6xl">Il servizio include server e backup.</h2>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">Ruoli e permessi limitano l’accesso agli operatori autorizzati. Spazio, frequenza e conservazione dei backup vengono specificati nel piano di servizio dell’offerta.</p>
                    </div>

                    <div data-reveal class="reveal grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div v-for="item in [{ icon: ShieldCheckIcon, title: 'Visibilità per filiale', text: 'Ogni filiale lavora nel proprio ramo.' }, { icon: UserGroupIcon, title: 'Operatori autorizzati', text: 'Il contesto è disponibile a chi lavora sulla pratica.' }, { icon: DocumentTextIcon, title: 'Piano di servizio', text: 'Backup e assistenza sono definiti nell’offerta.' }]" :key="item.title" class="flex items-start gap-4 rounded-2xl border border-cyan-200/15 bg-slate-900/60 p-5 shadow-[0_20px_60px_-30px_rgba(34,211,238,0.35)] backdrop-blur-xl transition duration-300 hover:border-cyan-200/35 hover:bg-slate-900/75">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-cyan-300/15 text-cyan-200"><component :is="item.icon" class="h-5 w-5" /></span>
                            <span><strong class="block text-base text-white">{{ item.title }}</strong><span class="mt-1 block text-sm leading-6 text-slate-300">{{ item.text }}</span></span>
                        </div>
                    </div>
                </div>
            </section>

            <section id="faq" class="bg-slate-50 px-5 py-24 text-slate-950 sm:px-8 lg:px-12">
                <div class="mx-auto max-w-5xl"><p class="text-sm font-black uppercase tracking-[0.2em] text-blue-600">Prima di scegliere</p><h2 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Le risposte essenziali.</h2><div class="mt-9 divide-y divide-slate-200"> <details v-for="item in faq" :key="item.question" class="group py-5"><summary class="cursor-pointer text-lg font-bold marker:text-blue-600">{{ item.question }}</summary><p class="mt-4 max-w-3xl leading-7 text-slate-600">{{ item.answer }}</p></details></div></div>
            </section>

            <section id="demo" class="bg-slate-950 px-5 py-24 sm:px-8 lg:px-12">
                <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                    <div><p class="text-sm font-black uppercase tracking-[0.2em] text-cyan-300">Vediamolo nel tuo contesto</p><h2 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Raccontaci il tuo ufficio.</h2><p class="mt-5 text-lg leading-8 text-slate-300">Quante persone lavorano sulle pratiche? Quante sedi gestite? Prepariamo una demo sul flusso utile al tuo CAF.</p><p class="mt-5 text-sm leading-6 text-slate-400">Questa pagina prepara il testo della richiesta sul tuo dispositivo. Non invia dati automaticamente e non li salva su un server. L’invio avviene soltanto dal canale che scegli.</p></div>
                    <div class="rounded-[28px] border border-white/10 bg-slate-900 p-6 sm:p-8">
                        <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="prepareDemo"><label class="text-sm font-semibold">Nome del referente<input v-model="demo.name" required type="text" class="mt-2 w-full rounded-xl border-slate-600 bg-slate-950 text-white" /></label><label class="text-sm font-semibold">Organizzazione<input v-model="demo.organization" required type="text" class="mt-2 w-full rounded-xl border-slate-600 bg-slate-950 text-white" /></label><label class="text-sm font-semibold sm:col-span-2">Recapito per la risposta<input v-model="demo.contact" required type="text" class="mt-2 w-full rounded-xl border-slate-600 bg-slate-950 text-white" /></label><label class="text-sm font-semibold">Numero operatori<input v-model="demo.operators" min="1" type="number" class="mt-2 w-full rounded-xl border-slate-600 bg-slate-950 text-white" /></label><label class="text-sm font-semibold">Numero sedi<input v-model="demo.branches" min="1" type="number" class="mt-2 w-full rounded-xl border-slate-600 bg-slate-950 text-white" /></label><label class="text-sm font-semibold sm:col-span-2">Piano di interesse<SearchableSelect v-model="demo.plan" class="mt-2 w-full rounded-xl border-slate-600 bg-slate-950 text-white"><option>Da valutare</option><option>Start</option><option>Team</option></SearchableSelect></label><label class="text-sm font-semibold sm:col-span-2">Cosa vuoi organizzare meglio?<textarea v-model="demo.notes" rows="3" class="mt-2 w-full rounded-xl border-slate-600 bg-slate-950 text-white"></textarea></label><button type="submit" class="min-h-12 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-400 px-6 font-bold text-white sm:col-span-2">Prepara la richiesta demo</button></form>
                        <div v-if="demoText" class="mt-7 rounded-xl border border-cyan-300/20 bg-cyan-300/10 p-5"><p class="font-bold text-cyan-100">Testo pronto. Non è stato inviato.</p><pre class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-slate-200">{{ demoText }}</pre><button type="button" class="mt-4 rounded-xl border border-cyan-300/30 px-4 py-2 text-sm font-bold text-cyan-100" @click="copyDemo">Copia il testo</button><p v-if="demoCopyStatus" class="mt-2 text-sm text-cyan-200" role="status">{{ demoCopyStatus }}</p></div>
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

.reveal { opacity: 1; transform: none; }

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
