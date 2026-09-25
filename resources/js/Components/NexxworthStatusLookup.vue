<script setup>
import { useForm } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    locale: { type: String, default: 'it' },
    initialCode: { type: String, default: '' },
});
const form = useForm({ code: props.initialCode });
const normalizeCode = (event) => {
    form.code = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
};
const submit = () => form.post(route(props.locale === 'en' ? 'nexxworth.en.status.lookup' : 'nexxworth.status.lookup'), { preserveScroll: false });
</script>
<template>
    <div class="rounded-[2rem] border border-[#dce4e8] bg-white p-7 shadow-xl shadow-[#10223a]/10 sm:p-10">
        <div class="flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-xl bg-[#e9f0f5] text-[#194a79]"><MagnifyingGlassIcon class="h-6 w-6" aria-hidden="true" /></span><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-[#b5322e]">{{ locale === 'en' ? 'Case tracking' : 'Controllo pratica' }}</p><h2 class="mt-1 [font-family:Georgia,serif] text-2xl text-[#132b47]">{{ locale === 'en' ? 'Enter your code' : 'Inserisci il tuo codice' }}</h2></div></div>
        <form class="mt-7" @submit.prevent="submit"><label for="nexxworth-tracking-code" class="block text-sm font-bold text-[#132b47]">{{ locale === 'en' ? 'Tracking code' : 'Codice pratica' }}</label><input id="nexxworth-tracking-code" :value="form.code" type="text" name="code" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="10" placeholder="A7B9C2D4E6" :aria-invalid="Boolean(form.errors.code)" aria-describedby="nexxworth-code-hint nexxworth-code-error" class="mt-2 block min-h-[56px] w-full rounded-xl border-[#cad5dc] bg-white px-4 font-mono text-lg font-semibold uppercase tracking-[0.13em] text-[#132b47] focus:border-[#194a79] focus:ring-[#194a79]" @input="normalizeCode" /><p id="nexxworth-code-hint" class="mt-2 text-xs leading-5 text-[#52657a]">{{ locale === 'en' ? '10 letters and numbers. Spaces and hyphens are removed.' : '10 caratteri, solo lettere e numeri. Spazi e trattini vengono rimossi.' }}</p><p v-if="form.errors.code" id="nexxworth-code-error" role="alert" class="mt-2 text-sm font-semibold text-[#a32925]">{{ form.errors.code }}</p><button type="submit" :disabled="form.processing || form.code.length !== 10" class="mt-6 inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-full bg-[#be322e] px-6 text-sm font-bold text-white transition hover:bg-[#a52a27] disabled:cursor-not-allowed disabled:opacity-60"><MagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" />{{ form.processing ? (locale === 'en' ? 'Checking…' : 'Controllo in corso…') : (locale === 'en' ? 'Check status' : 'Controlla lo stato') }}</button></form>
        <p class="mt-5 flex items-start gap-2 text-xs leading-5 text-[#52657a]"><LockClosedIcon class="mt-0.5 h-4 w-4 shrink-0 text-[#194a79]" aria-hidden="true" />{{ locale === 'en' ? 'Only the current status is shown. No personal details or documents are displayed.' : 'Mostriamo solo lo stato corrente. Nessun dato personale o documento viene visualizzato.' }}</p>
    </div>
</template>
