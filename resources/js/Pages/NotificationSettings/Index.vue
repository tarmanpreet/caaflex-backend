<script setup>
import { onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { BellAlertIcon, ComputerDesktopIcon, EnvelopeIcon, SignalIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useBrowserNotifications } from '@/Composables/useBrowserNotifications.js';

const props = defineProps({
    sections: { type: Object, required: true },
    reminderOptions: { type: Array, required: true },
});

const form = useForm({
    sections: Object.fromEntries(Object.entries(props.sections).map(([key, section]) => [key, {
        enabled: section.enabled,
        mail_enabled: section.mail_enabled,
        realtime_enabled: section.realtime_enabled,
        ...(section.supports_reminders ? { reminder_offsets: [...section.reminder_offsets] } : {}),
    }])),
});

const {
    supported: browserNotificationsSupported,
    permission: browserPermission,
    isEnabled: browserNotificationsEnabled,
    isDenied: browserNotificationsDenied,
    requestPermission: requestBrowserPermission,
    refreshPermission: refreshBrowserPermission,
} = useBrowserNotifications();

onMounted(refreshBrowserPermission);

const toggleReminder = (section, minutes) => {
    const offsets = form.sections[section].reminder_offsets;
    form.sections[section].reminder_offsets = offsets.includes(minutes)
        ? offsets.filter((value) => value !== minutes)
        : [...offsets, minutes];
};

const submit = () => form.put(route('notification-settings.update'), {
    preserveScroll: true,
});
</script>

<template>
    <AppLayout title="Impostazioni notifiche">
        <template #header>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-primary">Preferenze personali</p>
                <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Impostazioni notifiche</h1>
            </div>
        </template>

        <form class="grid gap-5" @submit.prevent="submit">
            <section class="app-card p-5 sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-secondary-container text-on-secondary-container">
                            <ComputerDesktopIcon class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="font-headline text-lg font-bold text-on-surface">Notifiche del browser</h2>
                            <p class="mt-1 text-sm leading-6 text-on-surface-variant">
                                Mostra un avviso desktop quando arriva una notifica in tempo reale su questo dispositivo.
                            </p>
                            <p v-if="browserNotificationsDenied" class="mt-2 text-xs font-semibold text-error">
                                Permesso bloccato. Riattivalo dalle impostazioni del sito nel browser.
                            </p>
                            <p v-else-if="!browserNotificationsSupported" class="mt-2 text-xs font-semibold text-error">
                                Questo browser non supporta le notifiche desktop.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        :disabled="!browserNotificationsSupported || browserNotificationsDenied || browserNotificationsEnabled"
                        class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition disabled:cursor-not-allowed"
                        :class="browserNotificationsEnabled
                            ? 'bg-primary-container text-on-primary-container'
                            : 'bg-secondary-container text-on-secondary-container hover:brightness-95 disabled:opacity-60'"
                        @click="requestBrowserPermission"
                    >
                        {{ browserNotificationsEnabled ? 'Browser attivo' : browserPermission === 'default' ? 'Attiva sul browser' : 'Non disponibile' }}
                    </button>
                </div>
            </section>

            <section v-for="section in sections" :key="section.key" class="app-card p-5 sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-primary-container text-on-primary-container">
                            <BellAlertIcon class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="font-headline text-lg font-bold text-on-surface">{{ section.label }}</h2>
                            <p class="mt-1 text-sm leading-6 text-on-surface-variant">{{ section.description }}</p>
                        </div>
                    </div>

                    <label class="inline-flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl bg-surface-container-low px-4 py-2 text-sm font-semibold text-on-surface">
                        <input v-model="form.sections[section.key].enabled" type="checkbox" class="rounded border-outline-variant text-primary focus:ring-primary" />
                        Sezione attiva
                    </label>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-outline-variant/40 bg-surface-container-low p-4" :class="{ 'opacity-50': !form.sections[section.key].enabled }">
                        <EnvelopeIcon class="h-5 w-5 text-primary" />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold text-on-surface">Email</span>
                            <span class="block text-xs text-on-surface-variant">Invio alla tua casella email</span>
                        </span>
                        <input v-model="form.sections[section.key].mail_enabled" type="checkbox" :disabled="!form.sections[section.key].enabled" class="rounded border-outline-variant text-primary focus:ring-primary" />
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-outline-variant/40 bg-surface-container-low p-4" :class="{ 'opacity-50': !form.sections[section.key].enabled }">
                        <SignalIcon class="h-5 w-5 text-secondary" />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold text-on-surface">Tempo reale</span>
                            <span class="block text-xs text-on-surface-variant">Avviso immediato via WebSocket</span>
                        </span>
                        <input v-model="form.sections[section.key].realtime_enabled" type="checkbox" :disabled="!form.sections[section.key].enabled" class="rounded border-outline-variant text-primary focus:ring-primary" />
                    </label>
                </div>

                <div v-if="section.supports_reminders" class="mt-5 border-t border-outline-variant/30 pt-5">
                    <p class="text-sm font-bold text-on-surface">Quando ricevere i promemoria</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="option in reminderOptions"
                            :key="option.value"
                            type="button"
                            :disabled="!form.sections[section.key].enabled"
                            :class="form.sections[section.key].reminder_offsets.includes(option.value)
                                ? 'border-primary bg-primary-container text-on-primary-container'
                                : 'border-outline-variant/50 bg-surface-container-lowest text-on-surface-variant'"
                            class="min-h-[42px] rounded-xl border px-4 py-2 text-sm font-semibold transition disabled:opacity-50"
                            @click="toggleReminder(section.key, option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-end gap-4">
                <ActionMessage :on="form.recentlySuccessful">Preferenze salvate.</ActionMessage>
                <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Salvataggio…' : 'Salva impostazioni' }}</PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
