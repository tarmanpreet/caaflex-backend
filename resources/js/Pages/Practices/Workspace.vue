<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
const props = defineProps({ workspace: Object });
const data = ref(props.workspace);
watch(() => props.workspace, value => { data.value = value; });
const tab = ref('documents');
const busy = ref(false);
const error = ref('');
const message = ref('');
const attachment = ref(null);
const fileKey = ref(0);
const activityLabels = { 'message.created': 'Messaggio inviato', 'document.requested': 'Documento richiesto', 'document.submitted': 'Documento caricato', 'document.approved': 'Documento approvato', 'document.rejected': 'Sostituzione richiesta', 'document.downloaded': 'Documento scaricato', 'practice.renewed':'Rinnovo annuale creato', 'practice.created_from_renewal':'Pratica creata da rinnovo', 'practice.bulk_status':'Stato aggiornato in blocco','practice.bulk_assign':'Operatori assegnati in blocco','practice.assigned':'Assegnazioni modificate','practice.created':'Pratica creata','practice.updated':'Pratica modificata','document.created':'Documento aggiunto','document.updated':'Documento modificato','document.deleted':'Documento eliminato' };
const visibility = ref('public');
const renewalYear = ref(Math.max(new Date().getFullYear(), (props.workspace.practice.reference_year || new Date().getFullYear()) + 1));
const requestForm = ref({ title: '', instructions: '', due_on: '' });
const reasons = ref({});
const labels = { requested: 'Da caricare', submitted: 'Da verificare', approved: 'Approvato', rejected: 'Da sostituire', pending: 'Da verificare' };
const base = () => `/practices/${data.value.practice.id}/workspace`;
async function run(method, path, payload, done) {
    if (busy.value) return;
    busy.value = true; error.value = '';
    try {
        const response = await axios({ method, url: base() + path, data: payload, headers: { Accept: 'application/json' } });
        data.value = response.data.data;
        done?.();
    } catch (failure) {
        error.value = Object.values(failure.response?.data?.errors ?? {}).flat().join(' ') || failure.response?.data?.message || 'Operazione non riuscita. Riprova.';
    } finally { busy.value = false; }
}
function send() {
    let payload = { body: message.value, visibility: visibility.value };
    if (attachment.value) {
        payload = new FormData(); payload.append('body', message.value); payload.append('visibility', visibility.value); payload.append('file', attachment.value);
    }
    return run('post', '/messages', payload, () => { message.value = ''; attachment.value = null; fileKey.value++; });
}
function requestDocument() { return run('post', '/requests', { ...requestForm.value, due_on: requestForm.value.due_on || null }, () => { requestForm.value = { title: '', instructions: '', due_on: '' }; }); }
function upload(request, event) {
    const file = event.target.files?.[0]; if (!file) return;
    const form = new FormData(); form.append('file', file);
    return run('post', `/requests/${request.id}/documents`, form, () => { event.target.value = ''; });
}
function review(request, document, status) { return run('patch', `/requests/${request.id}/documents/${document.id}/review`, { status, reason: reasons.value[request.id] || null }); }
async function older() {
    if (busy.value || !data.value.messages.length) return;
    busy.value = true; error.value = '';
    try {
        const response = await axios.get(base() + '/data', { params: { before: data.value.messages[0].id } });
        data.value = { ...response.data.data, messages: [...response.data.data.messages, ...data.value.messages] };
    } catch { error.value = 'Impossibile caricare i messaggi precedenti.'; }
    finally { busy.value = false; }
}
async function olderActivity(){if(busy.value||!data.value.activity.length)return;busy.value=true;try{const response=await axios.get(base()+'/data',{params:{activity_before:data.value.activity.at(-1).id}});data.value={...data.value,activity:[...data.value.activity,...response.data.data.activity],has_older_activity:response.data.data.has_older_activity};}catch{error.value='Impossibile caricare lo storico.';}finally{busy.value=false;}}
function date(value) { return new Date(value).toLocaleString('it-IT'); }
</script>
<template>
    <AppLayout title="Documenti e messaggi">
        <template #header><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Documenti e messaggi · {{ data.practice.type }}</h2><Link :href="route('dashboard')" class="app-button rounded-lg border border-gray-300 px-4 py-3 text-sm dark:text-gray-100">Torna alla panoramica</Link></div></template>
        <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
            <p class="text-sm text-gray-600 dark:text-gray-300">Pratica {{ data.practice.tracking_code }} · {{ data.is_staff ? 'I messaggi interni sono visibili solo allo studio.' : 'Qui puoi inviare i documenti richiesti e parlare con lo studio.' }}</p>
            <form v-if="data.can_renew" @submit.prevent="run('post', '/renew', { reference_year: Number(renewalYear) }, () => router.visit(route('practices.workspace', data.practice.id)))" class="flex flex-wrap items-end gap-4 rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><label class="text-sm">Rinnovo annuale<input v-model="renewalYear" type="number" :min="Math.max(new Date().getFullYear(), (data.practice.reference_year || new Date().getFullYear()) + 1)" :max="new Date().getFullYear() + 5" required class="mt-2 block rounded-lg border-gray-300 dark:bg-gray-900" /></label><PrimaryButton :disabled="busy">Crea rinnovo</PrimaryButton><p class="w-full text-sm text-gray-500">Mantiene cliente, tipo pratica e richieste documentali. I documenti devono essere aggiornati.</p></form><nav aria-label="Sezioni pratica" class="flex flex-wrap gap-3"><button v-for="entry in [{ id: 'documents', name: 'Documenti richiesti' }, { id: 'messages', name: 'Messaggi' }, ...(data.is_staff ? [{ id: 'activity', name: 'Storico attività' }] : [])]" :key="entry.id" type="button" :aria-pressed="tab === entry.id" @click="tab = entry.id" class="app-button min-h-12 rounded-xl border px-5 py-3 text-sm font-semibold" :class="tab === entry.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-100'">{{ entry.name }}</button></nav>
            <p v-if="error" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{{ error }}</p>
            <section v-if="tab === 'documents'" class="space-y-5" aria-label="Documenti richiesti">
                <form v-if="data.can_manage" @submit.prevent="requestDocument" class="space-y-4 rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    <h3 class="text-lg font-semibold">Richiedi un documento</h3>
                    <label class="block text-sm">Documento richiesto<input v-model="requestForm.title" required maxlength="255" class="mt-2 block w-full rounded-lg border-gray-300 dark:bg-gray-900" /></label>
                    <label class="block text-sm">Istruzioni<textarea v-model="requestForm.instructions" maxlength="5000" rows="3" class="mt-2 block w-full rounded-lg border-gray-300 dark:bg-gray-900" /></label>
                    <label class="block text-sm">Da caricare entro (facoltativo)<input v-model="requestForm.due_on" type="date" class="mt-2 block rounded-lg border-gray-300 dark:bg-gray-900" /></label>
                    <PrimaryButton :disabled="busy">{{ busy ? 'Invio…' : 'Invia richiesta' }}</PrimaryButton>
                </form>
                <p v-if="!data.requests.length" class="rounded-2xl border bg-white p-8 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-300">Non ci sono documenti richiesti per questa pratica.</p>
                <article v-for="request in data.requests" :key="request.id" class="space-y-4 rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-semibold">{{ request.title }}</h3><span class="rounded-full px-3 py-1 text-sm" :class="request.status === 'approved' ? 'bg-green-100 text-green-800' : request.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'">{{ labels[request.status] }}</span></div>
                    <p v-if="request.instructions" class="whitespace-pre-wrap text-sm">{{ request.instructions }}</p><p v-if="request.due_on" class="text-sm">Scadenza: {{ request.due_on }}</p><p v-if="request.review_reason" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ request.review_reason }}</p>
                    <ul class="space-y-3"><li v-for="(document, index) in request.documents" :key="document.id" class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900"><a :href="`${base()}/documents/${document.id}/download`" class="font-medium text-blue-700 underline dark:text-blue-300">{{ document.original_name }}</a><p class="mt-1 text-sm">{{ labels[document.review_status] }} · {{ date(document.created_at) }}</p>
                        <div v-if="data.can_manage && index === 0 && request.status === 'submitted'" class="mt-4 space-y-3"><label class="block text-sm">Motivazione se da sostituire<textarea v-model="reasons[request.id]" maxlength="2000" rows="2" class="mt-2 block w-full rounded-lg border-gray-300 dark:bg-gray-800" /></label><div class="flex flex-wrap gap-3"><button :disabled="busy" @click="review(request, document, 'approved')" class="app-button min-h-12 rounded-lg bg-green-700 px-4 text-white disabled:opacity-50">Approva</button><button :disabled="busy || !reasons[request.id]?.trim()" @click="review(request, document, 'rejected')" class="app-button min-h-12 rounded-lg border border-red-600 px-4 text-red-700 disabled:opacity-50 dark:text-red-300">Richiedi sostituzione</button></div></div>
                    </li></ul>
                    <label v-if="data.can_upload && request.status !== 'approved'" class="block rounded-xl border border-dashed border-blue-300 p-4 text-sm">{{ request.documents.length ? 'Carica una nuova versione' : 'Carica documento' }}<input type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" :disabled="busy" @change="upload(request, $event)" class="mt-3 block max-w-full" /><span class="mt-2 block text-xs text-gray-500">PDF, immagini o Word · massimo 20 MB</span></label>
                </article>
            </section>
            <section v-else-if="tab === 'messages'" class="space-y-5" aria-label="Messaggi della pratica">
                <button v-if="data.has_older_messages" :disabled="busy" @click="older" class="app-button min-h-12 rounded-lg border px-4 dark:text-gray-100">Carica messaggi precedenti</button>
                <p v-if="!data.messages.length" class="text-gray-500 dark:text-gray-300">Nessun messaggio. Scrivi il primo aggiornamento.</p>
                <article v-for="entry in data.messages" :key="entry.id" class="rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><div class="flex flex-wrap justify-between gap-2 text-sm"><strong>{{ entry.author?.name || 'Utente non più disponibile' }}</strong><span>{{ date(entry.created_at) }}</span></div><span v-if="entry.visibility === 'internal'" class="mt-2 inline-block rounded bg-amber-100 px-2 py-1 text-xs text-amber-900">Nota interna · solo studio</span><p class="mt-3 whitespace-pre-wrap break-words">{{ entry.body }}</p><a v-if="entry.document" :href="`${base()}/documents/${entry.document.id}/download`" class="mt-3 inline-flex min-h-12 items-center text-blue-700 underline dark:text-blue-300">{{ entry.document.original_name }}</a></article>
                <form v-if="data.can_message" @submit.prevent="send" class="space-y-4 rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><label class="block text-sm">Nuovo messaggio<textarea v-model="message" :required="!attachment" maxlength="5000" rows="4" class="mt-2 block w-full rounded-xl border-gray-300 dark:bg-gray-900" /></label><label v-if="data.is_staff" class="block text-sm">Destinatari<select v-model="visibility" class="ml-3 rounded-lg border-gray-300 dark:bg-gray-900"><option value="public">Cliente e studio</option><option value="internal">Solo studio</option></select></label><label v-if="data.can_upload" class="block text-sm">Allegato (facoltativo)<input :key="fileKey" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" :disabled="busy" @change="attachment = $event.target.files?.[0] ?? null" class="mt-2 block max-w-full" /></label><PrimaryButton :disabled="busy || (!message.trim() && !attachment)">{{ busy ? 'Invio…' : 'Invia messaggio' }}</PrimaryButton></form>
            </section>
            <section v-else-if="data.is_staff" class="rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" aria-label="Storico attività"><h3 class="text-lg font-semibold">Storico attività</h3><p v-if="!data.activity.length" class="mt-4 text-gray-500">Nessuna attività registrata.</p><ol class="mt-4 divide-y dark:divide-gray-700"><li v-for="entry in data.activity" :key="entry.id" class="py-4"><strong>{{ entry.actor?.name || 'Utente non più disponibile' }}</strong><p>{{ activityLabels[entry.action] || 'Attività pratica' }}</p><time class="text-sm text-gray-500">{{ date(entry.created_at) }}</time><p v-if="entry.metadata?.fields?.length" class="text-sm text-gray-500">Campi: {{entry.metadata.fields.join(', ')}}</p></li></ol><button v-if="data.has_older_activity" :disabled="busy" @click="olderActivity" class="app-button min-h-12 rounded-lg border px-4">Carica attività precedenti</button></section>
        </div>
    </AppLayout>
</template>
