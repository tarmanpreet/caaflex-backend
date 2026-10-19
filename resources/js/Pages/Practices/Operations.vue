<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
const props = defineProps({ operations: Object });
const data = ref(props.operations); const workloadExpanded=ref(false);
const search = ref(''); const results = ref([]); const searched = ref(false);
const busy = ref(false); const error = ref(''); const success = ref('');
const selected = ref([]); const action = ref('status'); const status = ref('in_lavorazione'); const operators = ref([]); const confirm = ref(false);
const states = { nuova: 'Nuova', in_lavorazione: 'In lavorazione', in_attesa_documenti: 'In attesa documenti', completata: 'Completata', annullata: 'Annullata', sospesa: 'Sospesa' };
const allSelected = computed(() => data.value?.practices.data.length && data.value.practices.data.every(row => selected.value.includes(row.id)));
async function lookup() {
    busy.value = true; error.value = ''; searched.value = true;
    try { results.value = (await axios.get(route('operations.search'), { params: { q: search.value } })).data.data; }
    catch (failure) { error.value = failure.response?.data?.message || 'Ricerca non riuscita.'; }
    finally { busy.value = false; }
}
async function page(number) {
    busy.value = true; error.value = '';
    try { data.value = (await axios.get(route('operations.data'), { params: { page: number, branch_id: data.value?.branch_id || undefined } })).data.data; }
    catch { error.value = 'Caricamento non riuscito.'; }
    finally { busy.value = false; }
}
function togglePage() {
    const ids = data.value.practices.data.map(row => row.id);
    selected.value = allSelected.value ? selected.value.filter(id => !ids.includes(id)) : [...new Set([...selected.value, ...ids])].slice(0, 100);
}
async function apply() {
    busy.value = true; error.value = ''; success.value = '';
    try {
        const response = await axios.post(route('operations.bulk'), { ids: selected.value, action: action.value, status: status.value, user_ids: operators.value });
        success.value = `${response.data.data.count} pratiche aggiornate.`; selected.value = []; confirm.value = false;
        await page(data.value.practices.current_page);
    } catch (failure) { error.value = Object.values(failure.response?.data?.errors ?? {}).flat().join(' ') || failure.response?.data?.message || 'Nessuna modifica applicata. Riprova.'; confirm.value = false; }
    finally { busy.value = false; }
}
</script>
<template>
    <AppLayout :title="data ? 'Ricerca e lavorazioni' : 'Ricerca'">
        <template #header><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ data ? 'Ricerca e lavorazioni' : 'Ricerca' }}</h2></template>
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <section class="space-y-4 rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><h3 class="text-lg font-semibold">Cerca in clienti, pratiche e documenti</h3><form @submit.prevent="lookup" class="flex flex-wrap gap-3"><label class="min-w-0 flex-1"><span class="sr-only">Nome, codice fiscale, tracciamento o documento</span><input v-model="search" required minlength="2" maxlength="100" placeholder="Nome, codice fiscale, tracciamento o documento" class="w-full rounded-xl border-gray-300 dark:bg-gray-900" /></label><PrimaryButton :disabled="busy || search.trim().length < 2">Cerca</PrimaryButton></form><p v-if="searched && !busy && !results.length" class="text-gray-500">Nessun risultato accessibile per questa ricerca.</p><ul class="divide-y dark:divide-gray-700"><li v-for="result in results" :key="`${result.resource}-${result.id}`"><Link :href="result.resource === 'client' ? route(data ? 'clients.show' : 'family.profile', result.id) : route('practices.workspace', result.practice_id || result.id)" class="block rounded-lg py-4 hover:bg-gray-50 dark:hover:bg-gray-900"><p class="font-semibold text-blue-700 dark:text-blue-300">{{ result.title }}</p><p class="mt-1 text-sm">{{ result.subtitle }}</p></Link></li></ul></section>
            <label v-if="data?.branches?.length" class="block text-sm dark:text-gray-100">Filiale<select v-model="data.branch_id" @change="selected=[]; page(1)" class="ml-3 rounded-lg border-gray-300 dark:bg-gray-900"><option :value="null">Tutte le filiali accessibili</option><option v-for="branch in data.branches" :key="branch.id" :value="branch.id">{{branch.name}}</option></select></label><p v-if="error" role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">{{ error }}</p><p v-if="success" role="status" class="rounded-xl bg-green-50 p-4 text-green-800">{{ success }}</p>
            <section v-if="data?.can_assign" class="space-y-4 rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><h3 class="text-lg font-semibold">Carico di lavoro</h3><p class="text-sm text-gray-500">Pratiche aperte, scadute e senza aggiornamenti da almeno 7 giorni, limitate alle filiali accessibili.</p><div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"><article v-for="user in (workloadExpanded?data.workload:data.workload.slice(0,9))" :key="user.id" class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900"><h4 class="font-semibold">{{ user.name }}</h4><dl class="mt-3 grid grid-cols-3 gap-3 text-sm"><div><dt>Aperte</dt><dd class="mt-1 text-xl font-semibold">{{ user.open_count }}</dd></div><div><dt>Scadute</dt><dd class="mt-1 text-xl font-semibold text-red-600">{{ user.overdue_count }}</dd></div><div><dt>Ferme</dt><dd class="mt-1 text-xl font-semibold text-amber-600">{{ user.stalled_count }}</dd></div></dl></article></div><button v-if="data.workload.length>9" @click="workloadExpanded=!workloadExpanded" class="min-h-12 rounded-lg border px-4">{{workloadExpanded?'Mostra meno operatori':`Mostra tutti i ${data.workload.length} operatori`}}</button></section>
            <section v-if="data" class="space-y-4 rounded-2xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-semibold">Lavorazioni multiple</h3><span>{{ selected.length }} selezionate · massimo 100</span></div>
                <template v-if="data.can_bulk"><button @click="togglePage" :disabled="busy" class="min-h-12 rounded-lg border px-4">{{ allSelected ? 'Deseleziona pagina' : 'Seleziona pagina' }}</button><div class="grid gap-4 sm:grid-cols-2"><label class="text-sm">Operazione<select v-model="action" class="mt-2 block w-full rounded-lg border-gray-300 dark:bg-gray-900"><option value="status">Cambia stato</option><option v-if="data.can_assign" value="assign">Assegna operatori</option></select></label><label v-if="action === 'status'" class="text-sm">Nuovo stato<select v-model="status" class="mt-2 block w-full rounded-lg border-gray-300 dark:bg-gray-900"><option v-for="(label, value) in states" :value="value" :key="value">{{ label }}</option></select></label><label v-else class="text-sm">Operatori (sostituisce le assegnazioni attuali)<select v-model="operators" multiple class="mt-2 block h-36 w-full rounded-lg border-gray-300 dark:bg-gray-900"><option v-for="user in data.operators" :key="user.id" :value="user.id">{{ user.name }}</option></select></label></div><PrimaryButton :disabled="busy || !selected.length || (action === 'assign' && !operators.length)" @click="confirm = true">Rivedi e applica</PrimaryButton></template>
                <ul class="divide-y dark:divide-gray-700"><li v-for="practice in data.practices.data" :key="practice.id" class="flex items-center gap-4 py-4"><label v-if="data.can_bulk" class="flex min-h-12 min-w-12 items-center justify-center"><span class="sr-only">Seleziona pratica {{ practice.tracking_code }}</span><input v-model="selected" type="checkbox" :value="practice.id" :disabled="busy || (!selected.includes(practice.id) && selected.length >= 100)" class="h-5 w-5 rounded border-gray-300 text-blue-600" /></label><Link :href="route('practices.workspace', practice.id)" class="min-w-0 flex-1"><strong>{{ practice.client?.first_name }} {{ practice.client?.last_name }}</strong><p class="mt-1 text-sm">{{ practice.type }} · {{ practice.tracking_code }} · {{ states[practice.status] }}</p></Link></li></ul><p v-if="!data.practices.data.length">Nessuna pratica accessibile.</p><div class="flex flex-wrap items-center justify-between gap-3"><button :disabled="busy || data.practices.current_page <= 1" @click="page(data.practices.current_page - 1)" class="min-h-12 rounded-lg border px-4 disabled:opacity-40">Precedente</button><span>Pagina {{ data.practices.current_page }} di {{ data.practices.last_page }}</span><button :disabled="busy || data.practices.current_page >= data.practices.last_page" @click="page(data.practices.current_page + 1)" class="min-h-12 rounded-lg border px-4 disabled:opacity-40">Successiva</button></div>
            </section>
        </div>
        <ConfirmationModal :show="confirm" @close="!busy && (confirm = false)"><template #title>Conferma lavorazione multipla</template><template #content>{{ action === 'status' ? `Impostare ${states[status]} su ${selected.length} pratiche?` : `Sostituire gli operatori assegnati su ${selected.length} pratiche?` }} Le autorizzazioni saranno ricontrollate su ogni pratica.</template><template #footer><button :disabled="busy" @click="confirm = false" class="min-h-12 rounded-lg border px-4">Annulla</button><PrimaryButton :disabled="busy" @click="apply" class="ml-3">{{ busy ? 'Applicazione…' : 'Conferma' }}</PrimaryButton></template></ConfirmationModal>
    </AppLayout>
</template>
