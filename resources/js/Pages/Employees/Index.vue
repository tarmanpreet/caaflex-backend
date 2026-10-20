<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { ClockIcon, ArrowRightOnRectangleIcon, ArrowLeftOnRectangleIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ attendance: Object });
const me = computed(() => usePage().props.auth.user.id);
const isOwn = computed(() => Number(props.attendance.target.id) === Number(me.value));
const showForm = ref(false);
const month = ref(props.attendance.month);
const target = ref(props.attendance.target.id);
const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Rome', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const absence = useForm({ type: 'ferie', start_date: today, end_date: today, all_day: true, time_from: '09:00', time_to: '10:00', note: '', certificate_number: '' });
const clock = useForm({ note: '' });
const action = useForm({ status: 'approved', review_note: '' });
const edit = useForm({ note: '', certificate_number: '' });
const correction = useForm({ user_id: null, started_at: '', ended_at: '', reason: '' });
const editing = ref(null);
const correcting = ref(null);
const confirmingCancel = ref(null);
const reviewing = ref(null);
const statuses = { pending: 'In attesa', reported: 'Malattia comunicata', approved: 'Confermata', rejected: 'Rifiutata', cancelled: 'Annullata' };
const fmt = (value) => value ? new Intl.DateTimeFormat('it-IT', { timeZone: 'Europe/Rome', dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : 'In corso';
const endLabel = (a) => fmt(a.all_day ? new Date(new Date(a.ends_at).getTime() - 1000) : a.ends_at);
const duration = (s) => { const m = Math.max(0, Math.floor((new Date(s.ended_at) - new Date(s.started_at)) / 60000)); return `${Math.floor(m / 60)}h ${m % 60}m`; };
const localDateTime = (v) => { if (!v) return ''; const parts = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Europe/Rome', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(v)); return parts.replace(' ', 'T'); };
const stale = computed(() => props.attendance.open_session && Date.now() - new Date(props.attendance.open_session.started_at).getTime() > 16 * 3600000);
function filter() { router.get(route('employees.index'), { month: month.value, user_id: target.value }, { preserveState: false, preserveScroll: true }); }
function sendAbsence() { absence.transform(data => ({ ...data, certificate_number: data.type === 'malattia' ? data.certificate_number : null })).post(route('employees.store'), { preserveScroll: true, onSuccess: () => { showForm.value = false; absence.reset(); } }); }
function openReview(row, status) { reviewing.value = row; action.reset(); action.status = status; action.clearErrors(); }
function review() { action.post(route('employees.review', reviewing.value.id), { preserveScroll: true, onSuccess: () => reviewing.value = null }); }
function openEdit(row) { editing.value = row; edit.note = row.note ?? ''; edit.certificate_number = row.certificate_number ?? ''; edit.clearErrors(); }
function saveEdit() { edit.patch(route('employees.update', editing.value.id), { preserveScroll: true, onSuccess: () => editing.value = null }); }
function openCorrection(row) { correcting.value = row; correction.user_id = null; correction.started_at = localDateTime(row.started_at); correction.ended_at = localDateTime(row.ended_at); correction.reason = ''; correction.clearErrors(); }
function openManual() { correcting.value = { id: null }; correction.reset(); correction.user_id = props.attendance.target.id; }
function saveCorrection() { correction[correcting.value.id ? 'patch' : 'post'](correcting.value.id ? route('employees.correct', correcting.value.id) : route('employees.manual'), { preserveScroll: true, onSuccess: () => correcting.value = null }); }
function cancel() { action.post(route('employees.cancel', confirmingCancel.value.id), { preserveScroll: true, onSuccess: () => confirmingCancel.value = null }); }
function exportCsv() {
    const safe = v => `"${String(v ?? '').replace(/^[=+@-]/, "'$&").replaceAll('"', '""')}"`;
    const rows = [['Dipendente', 'Ingresso', 'Uscita', 'Durata'], ...props.attendance.sessions.map(s => [props.attendance.target.name, fmt(s.started_at), s.ended_at ? fmt(s.ended_at) : 'In corso', s.ended_at ? duration(s) : ''])];
    const url = URL.createObjectURL(new Blob(['\ufeff' + rows.map(r => r.map(safe).join(';')).join('\r\n')], { type: 'text/csv;charset=utf-8' }));
    const a = document.createElement('a'); a.href = url; a.download = `presenze-${props.attendance.month}.csv`; a.click(); URL.revokeObjectURL(url);
}
</script>

<template>
    <AppLayout title="Presenze e assenze">
        <template #header><div><p class="text-sm text-gray-500 dark:text-gray-400">Il tuo lavoro, giorno per giorno</p><h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Presenze e assenze</h2></div></template>
        <div class="space-y-6 py-6 text-gray-900 dark:text-gray-100">
            <section class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
                    <div class="flex items-start gap-4"><span class="rounded-xl bg-blue-50 p-3 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"><ClockIcon class="h-7 w-7" /></span><div class="flex-1"><p class="text-sm text-gray-500 dark:text-gray-400">La mia giornata · orario di Roma</p><h3 class="mt-1 text-2xl font-semibold">{{ attendance.open_session ? 'Sei al lavoro' : 'Pronto per iniziare?' }}</h3><p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ attendance.open_session ? `Ingresso registrato: ${fmt(attendance.open_session.started_at)}` : 'Registra l’ingresso quando inizi e l’uscita quando finisci.' }}</p></div></div>
                    <p v-if="stale" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-900/30 dark:text-amber-200">La sessione è aperta da oltre 16 ore. Se hai dimenticato l’uscita, chiedi una rettifica all’amministratore.</p>
                    <form class="mt-5 space-y-3" @submit.prevent="clock.post(route(attendance.open_session ? 'employees.clockOut' : 'employees.clockIn'), { preserveScroll: true })">
                        <label v-if="!attendance.open_session" class="block text-sm">Nota ingresso (facoltativa)<input v-model="clock.note" maxlength="2000" class="field mt-1" placeholder="Es. lavoro da remoto" /></label>
                        <p v-for="(error, key) in clock.errors" :key="key" role="alert" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
                        <PrimaryButton :disabled="clock.processing" class="min-h-[48px] gap-2"><component :is="attendance.open_session ? ArrowRightOnRectangleIcon : ArrowLeftOnRectangleIcon" class="h-5 w-5" />{{ clock.processing ? 'Registrazione…' : attendance.open_session ? 'Timbra uscita' : 'Timbra ingresso' }}</PrimaryButton>
                    </form>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Puoi uscire e rientrare per una pausa. L’orario è registrato dal server; serve una connessione.</p>
                </div>
                <div class="flex flex-col justify-between rounded-2xl bg-blue-900 p-6 text-white"><CalendarDaysIcon class="h-8 w-8 text-blue-200" /><div><p class="mt-6 text-sm text-blue-200">Ore registrate nel mese selezionato</p><p class="mt-1 text-4xl font-semibold">{{ Math.floor(attendance.worked_minutes / 60) }}<span class="text-xl">h {{ attendance.worked_minutes % 60 }}m</span></p><p class="mt-2 text-sm text-blue-100">{{ attendance.target.name }} · solo sessioni concluse</p></div><button class="mt-6 min-h-[48px] rounded-xl bg-white px-4 py-3 text-sm font-semibold text-blue-900 hover:bg-blue-50 focus:ring-2 focus:ring-blue-300" @click="showForm = !showForm">{{ showForm ? 'Chiudi richiesta' : 'Richiedi un’assenza' }}</button></div>
            </section>

            <section v-if="showForm" class="panel">
                <h3 class="text-lg font-semibold">Nuova assenza personale</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ferie e permessi richiedono conferma. La malattia viene comunicata subito; puoi aggiungere il protocollo anche in seguito.</p>
                <form class="mt-5 grid gap-4 sm:grid-cols-2" @submit.prevent="sendAbsence">
                    <label class="text-sm">Tipo di assenza<select aria-label="Tipo di assenza" v-model="absence.type" class="field mt-1"><option v-for="(label, value) in attendance.types" :key="value" :value="value">{{ label }}</option></select></label>
                    <label class="flex min-h-[48px] items-center gap-3 text-sm"><input v-model="absence.all_day" type="checkbox" class="rounded" /> Giornata intera</label>
                    <label class="text-sm">Dal<input v-model="absence.start_date" type="date" required class="field mt-1" /></label><label class="text-sm">Al (compreso)<input v-model="absence.end_date" type="date" :min="absence.start_date" required class="field mt-1" /></label>
                    <template v-if="!absence.all_day"><label class="text-sm">Dalle<input v-model="absence.time_from" type="time" required class="field mt-1" /></label><label class="text-sm">Alle<input v-model="absence.time_to" type="time" required class="field mt-1" /></label></template>
                    <label v-if="absence.type === 'malattia'" class="text-sm sm:col-span-2">Numero protocollo certificato (facoltativo)<input v-model="absence.certificate_number" maxlength="100" class="field mt-1" placeholder="Inserisci il numero, quando disponibile" /></label>
                    <label class="text-sm sm:col-span-2">Note (facoltative)<textarea v-model="absence.note" maxlength="2000" rows="3" class="field mt-1" placeholder="Indicazioni utili, senza diagnosi o dettagli clinici" /></label>
                    <div class="sm:col-span-2"><p v-for="(error, key) in absence.errors" :key="key" role="alert" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p><PrimaryButton :disabled="absence.processing" class="min-h-[48px]">{{ absence.processing ? 'Invio…' : absence.type === 'malattia' ? 'Comunica malattia' : 'Invia richiesta' }}</PrimaryButton></div>
                </form>
            </section>

            <section class="panel">
                <div class="flex flex-wrap items-end justify-between gap-4"><div><h3 class="text-lg font-semibold">Storico mensile</h3><p class="text-sm text-gray-500 dark:text-gray-400">Tutti gli orari sono espressi nel fuso Europe/Rome.</p></div><div class="flex flex-wrap gap-3"><label class="text-sm">Mese<input v-model="month" type="month" class="field mt-1" @change="filter" /></label><label v-if="attendance.can_manage" class="text-sm">Dipendente<select aria-label="Dipendente" v-model="target" class="field mt-1" @change="filter"><option v-for="person in attendance.staff" :key="person.id" :value="person.id">{{ person.name }}{{ !person.is_active ? ' (disattivato)' : '' }}</option></select></label><SecondaryButton v-if="attendance.can_manage" class="min-h-[48px] self-end" @click="openManual">Aggiungi timbratura</SecondaryButton><SecondaryButton class="min-h-[48px] self-end" @click="exportCsv">Esporta CSV</SecondaryButton></div></div>
                <div class="mt-5 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400"><tr><th class="py-3 pr-4">Ingresso</th><th class="py-3 pr-4">Uscita</th><th class="py-3 pr-4">Durata</th><th class="py-3">Note e rettifiche</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700"><tr v-for="session in attendance.sessions" :key="session.id"><td class="whitespace-nowrap py-4 pr-4">{{ fmt(session.started_at) }}</td><td class="whitespace-nowrap py-4 pr-4">{{ fmt(session.ended_at) }}</td><td class="whitespace-nowrap py-4 pr-4">{{ session.ended_at ? duration(session) : 'In corso' }}</td><td class="py-4"><p class="whitespace-pre-wrap">{{ session.note }}</p><details v-if="session.corrections?.length" class="mt-2"><summary class="cursor-pointer text-blue-700 dark:text-blue-300">{{ session.corrections.length }} rettifiche</summary><p v-for="(entry, i) in session.corrections" :key="i" class="mt-2">{{ fmt(entry.at) }} · {{ entry.actor_name }}: {{ entry.reason }}<br />{{ fmt(entry.before.started_at) }} — {{ fmt(entry.before.ended_at) }} → {{ fmt(entry.after.started_at) }} — {{ fmt(entry.after.ended_at) }}</p></details><button v-if="attendance.can_manage" class="min-h-[44px] text-blue-700 underline dark:text-blue-300" @click="openCorrection(session)">Rettifica orari</button></td></tr><tr v-if="!attendance.sessions.length"><td colspan="4" class="py-10 text-center text-gray-500 dark:text-gray-400">Nessuna timbratura nel mese selezionato.</td></tr></tbody></table></div>
            </section>

            <section class="panel"><h3 class="text-lg font-semibold">Assenze di {{ attendance.target.name }}</h3><div v-if="!attendance.absences.length" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Nessuna assenza nel mese selezionato.</div><article v-for="row in attendance.absences" :key="row.id" class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><div class="flex flex-wrap items-center justify-between gap-2"><h4 class="font-semibold">{{ attendance.types[row.type] }}</h4><span class="rounded-full bg-gray-100 px-3 py-1 text-sm dark:bg-gray-700">{{ statuses[row.status] }}</span></div><p class="mt-2 text-sm">{{ fmt(row.starts_at) }} — {{ endLabel(row) }} · {{ row.all_day ? 'Giornata intera' : 'A ore' }}</p><p v-if="row.note" class="mt-2 whitespace-pre-wrap text-sm">{{ row.note }}</p><p v-if="row.type === 'malattia'" class="mt-2 text-sm">Protocollo: {{ row.certificate_number || 'Da aggiungere quando disponibile' }}</p><p v-if="row.review_note" class="mt-2 text-sm">Nota amministratore: {{ row.review_note }}</p><p v-if="row.reviewed_at" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gestita il {{ fmt(row.reviewed_at) }}</p><div v-if="isOwn && !['cancelled', 'rejected'].includes(row.status)" class="mt-3 flex flex-wrap gap-4"><button class="min-h-[44px] text-sm text-blue-700 underline dark:text-blue-300" @click="openEdit(row)">Aggiorna note{{ row.type === 'malattia' ? ' e protocollo' : '' }}</button><button class="min-h-[44px] text-sm text-red-700 underline dark:text-red-300" @click="confirmingCancel = row; action.clearErrors()">Annulla assenza</button></div></article></section>

            <section v-if="attendance.can_manage" class="panel"><h3 class="text-lg font-semibold">Da gestire <span class="ml-2 rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-900">{{ attendance.pending.length }}</span></h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Richieste del personale che puoi gestire, anche di altri mesi. Le tue richieste sono affidate a un altro amministratore.</p><p v-if="!attendance.pending.length" class="py-6 text-sm text-gray-500 dark:text-gray-400">Tutto in ordine: nessuna richiesta in attesa.</p><article v-for="row in attendance.pending" :key="row.id" class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><h4 class="font-semibold">{{ row.user.name }} · {{ attendance.types[row.type] }}</h4><p class="mt-1 text-sm">{{ fmt(row.starts_at) }} — {{ endLabel(row) }}</p><p v-if="row.note" class="mt-2 whitespace-pre-wrap text-sm">{{ row.note }}</p><p v-if="row.type === 'malattia'" class="mt-2 text-sm">Protocollo: {{ row.certificate_number || 'Non ancora indicato' }}</p><div class="mt-4 flex flex-wrap gap-3"><SecondaryButton class="min-h-[44px]" @click="openReview(row, 'approved')">{{ row.type === 'malattia' ? 'Conferma ricezione' : 'Approva' }}</SecondaryButton><button class="min-h-[44px] px-3 text-sm text-red-700 underline dark:text-red-300" @click="openReview(row, 'rejected')">{{ row.type === 'malattia' ? 'Segnala da correggere' : 'Rifiuta' }}</button></div></article></section>
        </div>

        <ConfirmationModal :show="!!editing" @close="editing = null"><template #title>Aggiorna assenza</template><template #content><div class="space-y-4"><label v-if="editing?.type === 'malattia'" class="block text-sm">Numero protocollo<input v-model="edit.certificate_number" maxlength="100" class="field mt-1" /></label><label class="block text-sm">Note<textarea v-model="edit.note" rows="3" maxlength="2000" class="field mt-1" /></label><p v-for="(error, key) in edit.errors" :key="key" role="alert" class="text-red-600 dark:text-red-400">{{ error }}</p></div></template><template #footer><SecondaryButton @click="editing = null">Chiudi</SecondaryButton><PrimaryButton class="ml-3" :disabled="edit.processing" @click="saveEdit">Salva</PrimaryButton></template></ConfirmationModal>
        <ConfirmationModal :show="!!reviewing" @close="reviewing = null"><template #title>{{ action.status === 'approved' ? 'Conferma richiesta' : 'Motiva il rifiuto' }}</template><template #content><label class="block text-sm">Nota per il dipendente {{ action.status === 'rejected' ? '(obbligatoria)' : '(facoltativa)' }}<textarea v-model="action.review_note" rows="3" maxlength="2000" class="field mt-1" /></label><p v-for="(error, key) in action.errors" :key="key" role="alert" class="mt-2 text-red-600 dark:text-red-400">{{ error }}</p></template><template #footer><SecondaryButton @click="reviewing = null">Chiudi</SecondaryButton><PrimaryButton class="ml-3" :disabled="action.processing" @click="review">Conferma</PrimaryButton></template></ConfirmationModal>
        <ConfirmationModal :show="!!confirmingCancel" @close="confirmingCancel = null"><template #title>Annullare questa assenza?</template><template #content>Rimarrà nello storico con lo stato “Annullata”.<p v-for="(error, key) in action.errors" :key="key" role="alert" class="mt-2 text-red-600">{{ error }}</p></template><template #footer><SecondaryButton @click="confirmingCancel = null">Indietro</SecondaryButton><PrimaryButton class="ml-3" :disabled="action.processing" @click="cancel">Annulla assenza</PrimaryButton></template></ConfirmationModal>
        <ConfirmationModal :show="!!correcting" @close="correcting = null"><template #title>{{ correcting?.id ? 'Rettifica timbratura' : 'Timbratura manuale per ' + attendance.target.name }}</template><template #content><div class="space-y-4"><p class="text-sm">Orari di Roma. Ogni modifica conserva orari precedenti, autore e motivazione.</p><label class="block text-sm">Ingresso<input v-model="correction.started_at" type="datetime-local" class="field mt-1" /></label><label class="block text-sm">Uscita (vuota per lasciare aperta)<input v-model="correction.ended_at" type="datetime-local" class="field mt-1" /></label><label class="block text-sm">Motivazione obbligatoria<textarea v-model="correction.reason" rows="3" maxlength="2000" class="field mt-1" /></label><p v-for="(error, key) in correction.errors" :key="key" role="alert" class="text-red-600 dark:text-red-400">{{ error }}</p></div></template><template #footer><SecondaryButton @click="correcting = null">Chiudi</SecondaryButton><PrimaryButton class="ml-3" :disabled="correction.processing" @click="saveCorrection">Registra rettifica</PrimaryButton></template></ConfirmationModal>
    </AppLayout>
</template>

<style scoped>
.panel { @apply rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6; }
.field { @apply block min-h-[48px] w-full rounded-xl border-gray-300 bg-white text-base text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white; }
</style>
