<script setup>
import { router } from '@inertiajs/vue3';
import { BellIcon, CheckIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    sections: { type: Object, required: true },
});

const applyFilters = (event) => {
    const data = new FormData(event.currentTarget);
    router.get(route('notifications.index'), {
        section: data.get('section') || undefined,
        status: data.get('status') || undefined,
    }, { preserveState: true, replace: true });
};

const openNotification = async (notification) => {
    const response = await window.axios.post(route('notifications.open', notification.id));
    router.visit(response.data.data.redirect_url);
};

const markAllAsRead = () => router.post(route('notifications.read-all'), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['notifications'] }),
});

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('it-IT', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : '';
</script>

<template>
    <AppLayout title="Notifiche">
        <template #header>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-primary">Centro notifiche</p>
                <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Tutte le notifiche</h1>
            </div>
        </template>

        <div class="grid gap-5">
            <form class="app-toolbar" @change="applyFilters">
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <select name="section" class="app-select" :value="filters.section ?? ''">
                        <option value="">Tutte le sezioni</option>
                        <option v-for="(label, key) in sections" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <select name="status" class="app-select" :value="filters.status ?? ''">
                        <option value="">Lette e non lette</option>
                        <option value="unread">Non lette</option>
                        <option value="read">Lette</option>
                    </select>
                </div>
                <button type="button" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-primary-container px-4 text-sm font-bold text-on-primary-container" @click="markAllAsRead">
                    <CheckIcon class="h-5 w-5" /> Segna tutte come lette
                </button>
            </form>

            <section class="app-card">
                <div v-if="notifications.data.length === 0" class="grid place-items-center gap-3 px-6 py-16 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-surface-container-low text-on-surface-variant"><BellIcon class="h-7 w-7" /></span>
                    <div>
                        <p class="font-bold text-on-surface">Nessuna notifica</p>
                        <p class="mt-1 text-sm text-on-surface-variant">Non ci sono notifiche per i filtri selezionati.</p>
                    </div>
                </div>

                <button
                    v-for="notification in notifications.data"
                    v-else
                    :key="notification.id"
                    type="button"
                    class="flex w-full items-start gap-4 border-b border-outline-variant/25 px-5 py-5 text-left transition last:border-0 hover:bg-surface-container-low sm:px-6"
                    @click="openNotification(notification)"
                >
                    <span :class="notification.read_at ? 'bg-surface-container-high' : 'bg-primary'" class="mt-2 h-3 w-3 shrink-0 rounded-full" />
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <span class="font-bold text-on-surface">{{ notification.title }}</span>
                            <span class="text-xs text-on-surface-variant">{{ formatDate(notification.created_at) }}</span>
                        </span>
                        <span class="mt-1 block text-sm leading-6 text-on-surface-variant">{{ notification.body }}</span>
                        <span class="mt-2 inline-flex rounded-full bg-surface-container px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">{{ sections[notification.section] ?? notification.section }}</span>
                    </span>
                </button>
            </section>

            <Pagination :links="notifications.links" />
        </div>
    </AppLayout>
</template>
