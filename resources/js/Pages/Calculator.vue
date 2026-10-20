<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, ClipboardDocumentIcon, PrinterIcon } from '@heroicons/vue/24/outline';
import { computed, reactive, ref } from 'vue';

const initialValues = () => ({
    client: '', seller: '', plan: 'Start', billing: 'monthly', branches: 1,
    users: '', partner: 'no', training: '', service: '', startDate: '', vat: 22,
});
const form = reactive(initialValues());
const copyStatus = ref('');
const team = computed(() => form.plan === 'Team');
const annual = computed(() => team.value && form.billing === 'annual');
const branchCount = computed(() => Math.min(1000, Math.max(1, Math.floor(Number(form.branches) || 1))));
const extraBranches = computed(() => branchCount.value - 1);
const setup = computed(() => team.value ? (form.partner === 'yes' ? 1000 : 1500) : (form.partner === 'yes' ? 500 : 700));
const baseMonthly = computed(() => team.value ? 50 : 25);
const branchesMonthly = computed(() => extraBranches.value * 25);
const monthly = computed(() => baseMonthly.value + branchesMonthly.value);
const recurring = computed(() => (annual.value ? 500 : baseMonthly.value * 12) + branchesMonthly.value * 12);
const firstYear = computed(() => setup.value + recurring.value);
const vatRate = computed(() => Math.min(100, Math.max(0, Number(form.vat) || 0)) / 100);
const vatAmount = computed(() => firstYear.value * vatRate.value);
const gross = computed(() => firstYear.value + vatAmount.value);
const incomplete = computed(() => !form.client.trim() || !form.seller.trim() || !form.training.trim() || !form.service.trim() || (team.value && !String(form.users).trim()));
const money = value => new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value);
const reset = () => { Object.assign(form, initialValues()); copyStatus.value = ''; };
const printQuote = () => window.print();
const summary = computed(() => [
    `CAAFLEX ${team.value ? 'Team & Rete' : 'Start'} — ${incomplete.value ? 'calcolo indicativo' : 'riepilogo commerciale da approvare'}`,
    `Cliente: ${form.client || 'da indicare'}`,
    `Referente commerciale: ${form.seller || 'da indicare'}`,
    `Sedi totali: ${branchCount.value}; utenze: ${team.value ? (form.users || 'da definire') : '1'}`,
    `Attivazione con formazione: ${money(setup.value)}`,
    `Sede principale: ${money(annual.value ? 500 : baseMonthly.value)} ${annual.value ? 'annuali anticipati' : 'al mese'}`,
    `Sedi aggiuntive: ${extraBranches.value} × 25 €/mese = ${money(branchesMonthly.value)}/mese`,
    `Canone mensile ${annual.value ? 'delle sedi aggiuntive' : 'totale'}: ${money(annual.value ? branchesMonthly.value : monthly.value)}`,
    `Primi 12 mesi, IVA esclusa: ${money(firstYear.value)}`,
    `IVA di calcolo (${vatRate.value * 100}%): ${money(vatAmount.value)}`,
    `Primi 12 mesi, IVA inclusa: ${money(gross.value)}`,
    `Canoni successivi su 12 mesi a listino invariato: ${money(recurring.value)}`,
    `Formazione: ${form.training || 'programma e durata da concordare'}`,
    `Piano di servizio: ${form.service || 'da definire'}`,
    `Avvio: ${form.startDate || 'da concordare'}`,
    form.partner === 'yes' ? 'Prezzo Partner subordinato a collaborazione pubblicitaria concordata per iscritto.' : 'Attivazione standard.',
    'Server, backup e una landing standard per organizzazione inclusi. Dominio escluso e a carico del cliente.',
    'Riepilogo indicativo: pagamenti, durata, rinnovo, assistenza e privacy sono definiti nell’offerta.',
].join('\n'));

const copy = async () => {
    try {
        await navigator.clipboard.writeText(summary.value);
        copyStatus.value = 'Riepilogo copiato.';
    } catch {
        copyStatus.value = 'Copia manualmente il riepilogo oppure usa Stampa / PDF.';
    }
};
</script>

<template>
    <Head title="Preventivatore CAAFLEX">
        <meta name="description" content="Calcola una proposta indicativa CAAFLEX per sedi, piano e attivazione." />
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div class="min-h-screen bg-slate-50 font-sans text-slate-950 selection:bg-cyan-200 selection:text-slate-950">
        <div class="print-hide border-b border-slate-200 bg-white px-5 py-5 sm:px-8">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4" aria-label="Navigazione preventivatore">
                <Link :href="route('home')" class="inline-flex items-center rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"><ApplicationLogo class="h-12 w-[190px] object-contain" /></Link>
                <Link :href="route('home')" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><ArrowLeftIcon class="h-4 w-4" />Torna alla home</Link>
            </nav>
        </div>

        <main class="relative isolate overflow-hidden px-5 py-14 sm:px-8 lg:py-20">
            <div class="print-hide pointer-events-none absolute -left-20 top-0 -z-10 h-96 w-96 rounded-full bg-blue-200/50 blur-3xl"></div>
            <div class="print-hide pointer-events-none absolute -right-20 top-28 -z-10 h-96 w-96 rounded-full bg-cyan-200/60 blur-3xl"></div>
            <div class="mx-auto max-w-7xl">
                <div class="print-hide mb-10 max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-blue-700">Strumento commerciale interno</p>
                    <h1 class="mt-4 text-balance text-4xl font-black tracking-tight sm:text-5xl">Configura il <span class="bg-gradient-to-r from-blue-700 to-cyan-500 bg-clip-text text-transparent">preventivo.</span></h1>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Il calcolo è automatico. Il dominio non entra nei totali perché viene pagato dal cliente al proprio fornitore. I dati restano in questa pagina: non vengono inviati o salvati sul server.</p>
                </div>

                <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
                    <form class="print-hide grid gap-5 rounded-[28px] border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/60 sm:grid-cols-2 sm:p-8" @submit.prevent>
                        <label class="field sm:col-span-2">Cliente / organizzazione<input v-model="form.client" type="text" autocomplete="organization" /></label>
                        <label class="field sm:col-span-2">Referente commerciale e recapiti<input v-model="form.seller" type="text" autocomplete="off" /></label>
                        <label class="field">Piano<SearchableSelect v-model="form.plan" @change="!team && (form.billing = 'monthly')"><option value="Start">Start</option><option value="Team">Team & Rete</option></SearchableSelect></label>
                        <label class="field">Pagamento canone<SearchableSelect v-model="form.billing" :disabled="!team"><option value="monthly">Mensile</option><option value="annual">Annuale Team</option></SearchableSelect></label>
                        <label class="field">Sedi totali<input v-model.number="form.branches" type="number" min="1" max="1000" step="1" /></label>
                        <label class="field">Utenze Team concordate<input v-model="form.users" type="text" :disabled="!team" :placeholder="team ? 'Da definire nell’offerta' : '1 utenza inclusa'" /></label>
                        <label class="field">Attivazione<SearchableSelect v-model="form.partner"><option value="no">Standard</option><option value="yes">Partner Visibilità · accordo scritto</option></SearchableSelect></label>
                        <label class="field">Data prevista di avvio<input v-model="form.startDate" type="date" /></label>
                        <label class="field sm:col-span-2">Programma / durata formazione concordata<input v-model="form.training" type="text" /></label>
                        <label class="field sm:col-span-2">Riferimento piano di servizio e condizioni<input v-model="form.service" type="text" /></label>
                        <label class="field sm:col-span-2">IVA di calcolo (%)<input v-model.number="form.vat" type="number" min="0" max="100" step="0.01" /><span class="mt-2 block text-xs font-normal leading-5 text-slate-500">22% è un’ipotesi modificabile per il calcolo. Verifica la fatturazione con il consulente.</span></label>
                        <div class="sm:col-span-2"><button type="button" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold hover:bg-slate-50" @click="reset">Ripristina</button></div>
                    </form>

                    <section class="quote rounded-[28px] border border-blue-200 bg-white p-6 shadow-xl shadow-blue-100/60 sm:p-9" aria-label="Riepilogo preventivo">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">{{ incomplete ? 'Calcolo indicativo · campi da completare' : 'Riepilogo commerciale · da approvare' }}</p>
                        <h2 class="mt-4 text-3xl font-black">CAAFLEX {{ team ? 'Team & Rete' : 'Start' }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ form.client ? `Cliente: ${form.client}` : 'Cliente da indicare' }}</p>
                        <div class="mt-8 grid gap-3 border-y border-slate-200 py-6 text-sm">
                            <div class="quote-row"><span>Attivazione con formazione</span><strong>{{ money(setup) }}</strong></div>
                            <div class="quote-row"><span>Sede principale · canone {{ annual ? 'annuale anticipato' : 'mensile' }}</span><strong>{{ money(annual ? 500 : baseMonthly) }}</strong></div>
                            <div class="quote-row"><span>{{ extraBranches }} sedi aggiuntive · canone mensile</span><strong>{{ money(branchesMonthly) }}</strong></div>
                            <div class="quote-row font-bold text-slate-950"><span>{{ annual ? 'Sedi aggiuntive · mensile (base già annuale)' : 'Canone mensile totale' }}</span><strong>{{ money(annual ? branchesMonthly : monthly) }}</strong></div>
                        </div>
                        <p class="mt-5 text-sm leading-6 text-slate-500">La sede principale è pagata con il canone del piano. Ogni sede aggiuntiva costa 25 €/mese, con Start e con Team.</p>
                        <div class="mt-7 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                            <p class="text-sm font-semibold text-blue-800">Costo dei primi 12 mesi · al netto IVA</p>
                            <p class="mt-2 text-4xl font-black text-slate-950">{{ money(firstYear) }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-600">Attivazione + canoni per 12 mesi completi. Il totale non significa pagamento tutto subito con modalità mensile.</p>
                        </div>
                        <div class="mt-6 grid gap-3 text-sm">
                            <div class="quote-row"><span>IVA di calcolo sui 12 mesi</span><strong>{{ money(vatAmount) }}</strong></div>
                            <div class="quote-row"><span>Totale 12 mesi, IVA compresa</span><strong>{{ money(gross) }}</strong></div>
                            <div class="quote-row"><span>Canoni successivi su 12 mesi, a listino invariato</span><strong>{{ money(recurring) }}</strong></div>
                        </div>
                        <p class="mt-6 text-sm leading-6 text-slate-600">Sedi totali: {{ branchCount }}. Utenze: {{ team ? (form.users || 'da definire') : '1' }}. Formazione: {{ form.training || 'iniziale inclusa, programma e durata da concordare' }}. Piano di servizio: {{ form.service || 'da definire' }}. Avvio: {{ form.startDate || 'da concordare' }}.</p>
                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ form.partner === 'yes' ? 'Prezzo Partner subordinato a collaborazione pubblicitaria concordata per iscritto.' : 'Attivazione standard.' }} {{ annual ? 'I 500 € annuali anticipati pagano la sede principale Team; le sedi aggiuntive restano mensili a 25 € ciascuna.' : 'Ogni sede aggiuntiva costa 25 €/mese in entrambi i piani.' }}</p>
                        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700"><strong class="block text-slate-950">Incluso nella proposta</strong>Formazione iniziale nell’attivazione. Server e backup nel canone. Una landing page standard per organizzazione. Acquisto e rinnovo dominio esclusi e a carico del cliente.</div>
                        <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">{{ incomplete ? 'Completare cliente, recapiti del fornitore, utenze Team, programma formazione e riferimento del piano di servizio. Questo calcolo non sostituisce il contratto.' : 'Riepilogo da approvare con offerta e allegati: specificare pagamenti, durata, rinnovo, assistenza, privacy e condizioni Partner. Nessuna accettazione o firma viene raccolta da questa pagina.' }}</p>
                        <div class="print-hide mt-7 flex flex-wrap gap-3"><button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-5 font-bold text-white" @click="printQuote"><PrinterIcon class="h-4 w-4" />Stampa / PDF</button><button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 px-5 font-semibold hover:bg-slate-50" @click="copy"><ClipboardDocumentIcon class="h-4 w-4" />Copia riepilogo</button></div>
                        <p v-if="copyStatus" class="print-hide mt-3 text-sm text-blue-700" role="status">{{ copyStatus }}</p>
                    </section>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.field { display: block; color: #334155; font-size: .875rem; font-weight: 600; }
.field input, .field select { display: block; width: 100%; margin-top: .5rem; border: 1px solid #cbd5e1; border-radius: .75rem; background: #fff; padding: .75rem 1rem; color: #0f172a; }
.field input:focus, .field select:focus { border-color: #2563eb; outline: 2px solid #2563eb; outline-offset: 2px; }
.field :disabled { opacity: .55; }
.quote-row { display: flex; justify-content: space-between; gap: 1rem; color: #475569; }
.quote-row strong { white-space: nowrap; color: #0f172a; }
@media print {
    .print-hide { display: none !important; }
    .quote { border: 0; box-shadow: none; background: white; color: #0f172a; }
    .quote :deep(*) { color: #0f172a !important; }
    @page { size: A4; margin: 16mm; }
}
</style>
