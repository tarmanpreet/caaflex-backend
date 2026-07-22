<script setup>
import ApplicationMark from '@/Components/ApplicationMark.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    DocumentMagnifyingGlassIcon,
    LockClosedIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    searchedCode: {
        type: String,
        default: '',
    },
    result: {
        type: Object,
        default: null,
    },
    lookupError: {
        type: String,
        default: '',
    },
});

const form = useForm({
    code: props.searchedCode,
});

const statuses = {
    nuova: { label: 'Nuova', tone: 'blue' },
    in_lavorazione: { label: 'In lavorazione', tone: 'amber' },
    in_attesa_documenti: { label: 'In attesa di documenti', tone: 'violet' },
    completata: { label: 'Completata', tone: 'emerald' },
    annullata: { label: 'Annullata', tone: 'rose' },
    sospesa: { label: 'Sospesa', tone: 'slate' },
};

const currentStatus = computed(() => statuses[props.result?.status] ?? {
    label: props.result?.status?.replaceAll('_', ' ') ?? '',
    tone: 'slate',
});

const statusClasses = computed(() => ({
    blue: 'border-blue-300/25 bg-blue-400/10 text-blue-100',
    amber: 'border-amber-300/25 bg-amber-400/10 text-amber-100',
    violet: 'border-violet-300/25 bg-violet-400/10 text-violet-100',
    emerald: 'border-emerald-300/25 bg-emerald-400/10 text-emerald-100',
    rose: 'border-rose-300/25 bg-rose-400/10 text-rose-100',
    slate: 'border-slate-300/20 bg-slate-300/10 text-slate-100',
}[currentStatus.value.tone]));

const normalizeCode = (event) => {
    form.code = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
};

const submit = () => {
    form.post(route('practice-status.lookup'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Controlla la tua pratica">
        <meta name="description" content="Controlla in modo sicuro lo stato della tua pratica usando il codice di 10 caratteri ricevuto dal CAF." />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div class="status-page relative min-h-screen w-full max-w-full [overflow-x:clip] bg-slate-950 font-sans text-white selection:bg-blue-300 selection:text-slate-950">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute left-1/2 top-[-18rem] h-[36rem] w-[36rem] -translate-x-1/2 rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="absolute bottom-[-16rem] right-[-12rem] h-[34rem] w-[34rem] rounded-full bg-cyan-500/10 blur-3xl"></div>
            <div class="status-grid absolute inset-0 opacity-40"></div>
        </div>

        <header class="relative z-10 px-5 py-5 sm:px-8 sm:py-7">
            <nav class="mx-auto flex min-w-0 max-w-6xl items-center justify-between gap-3" aria-label="Navigazione controllo pratica">
                <Link :href="route('home')" class="group flex min-h-[44px] items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-500 text-white shadow-lg shadow-blue-500/20 transition duration-200 group-hover:scale-105 motion-reduce:transform-none">
                        <ApplicationMark class="h-7 w-7" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-sm font-bold">CAF Gestionale</span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Area pubblica</span>
                    </span>
                </Link>

                <Link :href="route('home')" class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 text-sm font-semibold text-slate-200 transition duration-200 hover:border-white/20 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300">
                    <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
                    <span class="hidden sm:inline">Torna alla home</span>
                    <span class="sm:hidden">Home</span>
                </Link>
            </nav>
        </header>

        <main class="relative z-10 flex min-h-[calc(100dvh-96px)] items-center px-5 pb-16 pt-6 sm:px-8 sm:pb-24">
            <div class="mx-auto grid min-w-0 w-full max-w-6xl items-center gap-12 lg:grid-cols-[0.82fr_1.18fr] lg:gap-20">
                <section class="status-enter order-2 min-w-0 max-w-full text-center lg:order-none lg:text-left" aria-labelledby="page-title">
                    <div class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-sm font-semibold text-cyan-100">
                        <LockClosedIcon class="h-4 w-4" aria-hidden="true" />
                        Consultazione sicura
                    </div>
                    <h1 id="page-title" class="mt-6 text-balance font-headline text-4xl font-black leading-tight tracking-[-0.04em] sm:text-5xl lg:text-6xl">
                        Segui la tua pratica,
                        <span class="block bg-gradient-to-r from-blue-300 to-cyan-200 bg-clip-text text-transparent">senza accedere.</span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-xl text-pretty text-base leading-7 text-slate-300 sm:text-lg lg:mx-0">
                        Inserisci il codice di 10 caratteri che ti è stato consegnato. Vedrai esclusivamente lo stato corrente della pratica.
                    </p>

                    <div class="mt-7 flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 text-left text-sm leading-6 text-slate-300 backdrop-blur lg:max-w-lg">
                        <LockClosedIcon class="mt-0.5 h-5 w-5 shrink-0 text-emerald-300" aria-hidden="true" />
                        <p>Nessun dato personale, documento o dettaglio del cliente viene mostrato in questa pagina.</p>
                    </div>
                </section>

                <section class="status-enter status-enter-delay order-1 min-w-0 max-w-full rounded-[28px] border border-white/10 bg-white/[0.07] p-2 shadow-[0_30px_90px_-30px_rgba(37,99,235,0.5)] backdrop-blur-xl lg:order-none" aria-label="Ricerca stato pratica">
                    <div class="rounded-[22px] border border-white/10 bg-slate-900/95 p-6 sm:p-9">
                        <div class="flex items-start gap-4">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-blue-500/15 text-blue-200 ring-1 ring-blue-300/20">
                                <DocumentMagnifyingGlassIcon class="h-6 w-6" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Controllo pratica</p>
                                <h2 class="mt-1 font-headline text-2xl font-bold">Inserisci il tuo codice</h2>
                            </div>
                        </div>

                        <form class="mt-7" @submit.prevent="submit">
                            <label for="tracking-code" class="block text-sm font-semibold text-slate-200">Codice pratica</label>
                            <div class="relative mt-2">
                                <input
                                    id="tracking-code"
                                    :value="form.code"
                                    type="text"
                                    name="code"
                                    inputmode="text"
                                    autocomplete="off"
                                    autocapitalize="characters"
                                    spellcheck="false"
                                    maxlength="10"
                                    placeholder="ES. A7B9C2D4E6"
                                    class="min-h-[56px] w-full rounded-2xl border border-white/15 bg-slate-950/70 px-4 pr-14 font-mono text-base font-semibold uppercase tracking-[0.16em] text-white shadow-inner outline-none placeholder:text-sm placeholder:tracking-[0.08em] placeholder:text-slate-600 transition duration-200 hover:border-white/25 focus:border-blue-400 focus:ring-4 focus:ring-blue-400/15 sm:text-lg"
                                    :class="{ 'border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/15': form.errors.code }"
                                    aria-describedby="tracking-code-hint tracking-code-error"
                                    @input="normalizeCode"
                                />
                                <MagnifyingGlassIcon class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" aria-hidden="true" />
                            </div>
                            <p id="tracking-code-hint" class="mt-2 text-xs leading-5 text-slate-400">10 caratteri, solo lettere e numeri. Spazi e trattini vengono rimossi.</p>
                            <p v-if="form.errors.code" id="tracking-code-error" class="mt-2 text-sm font-medium text-rose-300" role="alert">{{ form.errors.code }}</p>

                            <button
                                type="submit"
                                class="mt-5 inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-400 px-6 font-bold text-white shadow-lg shadow-blue-500/20 transition duration-200 hover:-translate-y-0.5 hover:shadow-blue-500/35 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 disabled:pointer-events-none disabled:opacity-60 motion-reduce:transform-none"
                                :disabled="form.processing || form.code.length !== 10"
                            >
                                <span v-if="form.processing" class="h-5 w-5 animate-spin rounded-full border-2 border-white/35 border-t-white motion-reduce:animate-none" aria-hidden="true"></span>
                                <MagnifyingGlassIcon v-else class="h-5 w-5" aria-hidden="true" />
                                {{ form.processing ? 'Controllo in corso…' : 'Controlla lo stato' }}
                            </button>
                        </form>

                        <div v-if="result" class="mt-7 rounded-2xl border border-emerald-300/20 bg-emerald-400/[0.07] p-5" aria-live="polite">
                            <div class="flex items-center gap-2 text-sm font-semibold text-emerald-200">
                                <CheckCircleIcon class="h-5 w-5" aria-hidden="true" />
                                Pratica trovata
                            </div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Codice</p>
                                    <p class="mt-1 font-mono text-lg font-bold tracking-[0.12em] text-white">{{ result.code }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Stato attuale</p>
                                    <span class="mt-2 inline-flex rounded-full border px-3 py-1.5 text-sm font-bold" :class="statusClasses">{{ currentStatus.label }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="lookupError" class="mt-7 rounded-2xl border border-rose-300/20 bg-rose-400/[0.07] p-5" role="alert">
                            <p class="font-semibold text-rose-100">Codice non riconosciuto</p>
                            <p class="mt-1 text-sm leading-6 text-slate-300">{{ lookupError }}</p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
.status-grid {
    background-image:
        linear-gradient(rgba(148, 163, 184, 0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(148, 163, 184, 0.045) 1px, transparent 1px);
    background-size: 42px 42px;
    mask-image: linear-gradient(to bottom, black, transparent 82%);
}

.status-enter {
    animation: status-enter 550ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.status-enter-delay {
    animation-delay: 100ms;
}

@keyframes status-enter {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .status-enter {
        animation: none;
    }
}
</style>
