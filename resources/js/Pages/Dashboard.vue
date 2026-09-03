<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatCard from '@/Components/ui/UiStatCard.vue';
import { formatDateTime } from '@/utils/date.js';
import {
    ArrowRightIcon,
    ArrowUpRightIcon,
    CalendarDaysIcon,
    ClockIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
    deadlines: {
        type: Array,
        default: () => [],
    },
    activities: {
        type: Array,
        default: () => [],
    },
    practices: {
        type: Array,
        default: () => [],
    },
    efficiency: {
        type: Object,
        default: () => ({ value: 0, caption: '', completed: 0, total: 0 }),
    },
});

const activityToneClass = (tone) => {
    const map = {
        primary: 'bg-primary',
        tertiary: 'bg-tertiary',
        neutral: 'bg-surface-container-highest',
    };

    return map[tone] ?? map.primary;
};

const deadlineStatusLabel = (status) => {
    const map = {
        pending: 'In attesa',
        in_progress: 'In corso',
        completed: 'Completata',
        cancelled: 'Annullata',
    };

    return map[status] ?? status;
};

const deadlinePriorityLabel = (priority) => {
    const map = {
        1: 'Urgente',
        2: 'Alta',
        3: 'Media',
        4: 'Bassa',
    };

    return map[priority] ?? 'Standard';
};

const deadlinePriorityClass = (priority) => {
    const map = {
        1: 'bg-error-container text-on-error-container ring-error/20',
        2: 'bg-primary-container text-on-primary-container ring-primary/20',
        3: 'bg-surface-container-high text-on-surface-variant ring-outline-variant/40',
        4: 'bg-surface-container-low text-on-surface-variant ring-outline-variant/30',
    };

    return map[priority] ?? map[3];
};

const deadlineStatusClass = (status) => {
    const map = {
        pending: 'bg-primary/10 text-primary ring-primary/20',
        in_progress: 'bg-tertiary-container text-on-tertiary-container ring-tertiary/20',
        completed: 'bg-secondary-container text-on-secondary-container ring-secondary/20',
        cancelled: 'bg-surface-container-high text-on-surface-variant ring-outline-variant/40',
    };

    return map[status] ?? map.pending;
};

const deadlineDayDifference = (value) => {
    const today = new Date();
    const deadline = new Date(value);

    today.setHours(0, 0, 0, 0);
    deadline.setHours(0, 0, 0, 0);

    return Math.round((deadline.getTime() - today.getTime()) / 86_400_000);
};

const deadlineTimingLabel = (value) => {
    const difference = deadlineDayDifference(value);

    if (difference < -1) return `Scaduta da ${Math.abs(difference)} giorni`;
    if (difference === -1) return 'Scaduta ieri';
    if (difference === 0) return 'Scade oggi';
    if (difference === 1) return 'Scade domani';

    return `Tra ${difference} giorni`;
};

const deadlineTimingClass = (value) => deadlineDayDifference(value) < 0
    ? 'text-error'
    : 'text-primary';

const deadlineAccentClass = (item) => {
    if (deadlineDayDifference(item.deadline_at) < 0 || Number(item.priority) === 1) {
        return 'bg-error';
    }

    if (deadlineDayDifference(item.deadline_at) <= 1) {
        return 'bg-primary';
    }

    return 'bg-tertiary';
};

const deadlineMonth = (value) => new Intl.DateTimeFormat('it-IT', { month: 'short' })
    .format(new Date(value))
    .replace('.', '');

const deadlineTime = (value) => new Intl.DateTimeFormat('it-IT', {
    hour: '2-digit',
    minute: '2-digit',
}).format(new Date(value));

const practiceStatusLabel = (status) => status ? status.replace(/_/g, ' ') : '—';
</script>

<template>
    <AppLayout title="Pannello di controllo">
        <template #header>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-primary">Panoramica operativa</p>
                <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Pannello principale</h1>
                <p class="mt-2 text-sm text-on-surface-variant">Priorità, scadenze e attività della rete in un’unica vista.</p>
            </div>
        </template>

        <div class="space-y-8">
            <section class="grid gap-6 md:grid-cols-3">
                <UiStatCard v-for="stat in props.stats" :key="stat.title" :title="stat.title" :value="stat.value" :caption="stat.caption" :tone="stat.tone" />
            </section>

            <section class="grid gap-8 lg:grid-cols-12">
                <UiSectionCard class="lg:col-span-8" title="Scadenze in primo piano" eyebrow="Focus di giornata">
                    <template #actions>
                        <Link :href="route('deadlines.index')" class="inline-flex min-h-[44px] items-center gap-2 rounded-xl px-3 text-sm font-semibold text-primary transition hover:bg-primary/10 focus-visible:outline-none">
                            Vedi tutte
                            <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                    </template>

                    <div v-if="props.deadlines.length" class="grid gap-3">
                        <article v-for="item in props.deadlines" :key="item.id" class="group relative overflow-hidden rounded-2xl border border-outline-variant/70 bg-surface-container-lowest p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-outline hover:shadow-md motion-reduce:transform-none sm:p-5">
                            <span :class="['absolute inset-y-0 left-0 w-1', deadlineAccentClass(item)]" aria-hidden="true" />

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <time :datetime="item.deadline_at" class="flex h-[88px] w-full shrink-0 items-center justify-center gap-3 rounded-2xl bg-surface-container-low text-center ring-1 ring-inset ring-outline-variant/50 sm:w-[88px] sm:flex-col sm:gap-0">
                                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-primary">{{ deadlineMonth(item.deadline_at) }}</span>
                                    <span class="font-headline text-3xl font-extrabold leading-none text-on-surface">{{ new Date(item.deadline_at).getDate() }}</span>
                                    <span class="text-[11px] font-semibold text-on-surface-variant">{{ new Date(item.deadline_at).getFullYear() }}</span>
                                </time>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span :class="['rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ring-1 ring-inset', deadlinePriorityClass(item.priority)]">
                                            Priorità {{ deadlinePriorityLabel(item.priority) }}
                                        </span>
                                        <span :class="['rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ring-1 ring-inset', deadlineStatusClass(item.status)]">
                                            {{ deadlineStatusLabel(item.status) }}
                                        </span>
                                    </div>

                                    <h4 class="mt-3 font-headline text-lg font-bold leading-snug text-on-surface">{{ item.title }}</h4>
                                    <p class="mt-1 text-sm font-medium text-on-surface-variant">{{ item.practice.client_name }} <span aria-hidden="true">·</span> {{ item.practice.type || 'Pratica' }}</p>
                                    <p v-if="item.notes" class="mt-2 line-clamp-2 text-sm leading-6 text-on-surface-variant">{{ item.notes }}</p>

                                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-outline-variant/40 pt-3 text-xs text-on-surface-variant">
                                        <span :class="['inline-flex items-center gap-1.5 font-bold', deadlineTimingClass(item.deadline_at)]">
                                            <CalendarDaysIcon class="h-4 w-4" />
                                            {{ deadlineTimingLabel(item.deadline_at) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <ClockIcon class="h-4 w-4" />
                                            Ore {{ deadlineTime(item.deadline_at) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <UserCircleIcon class="h-4 w-4" />
                                            {{ item.assignee?.name || 'Non assegnata' }}
                                        </span>
                                    </div>
                                </div>

                                <Link
                                    v-if="item.practice.id"
                                    :href="route('practices.show', item.practice.id)"
                                    :aria-label="`Apri la pratica della scadenza ${item.title}`"
                                    class="inline-flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center self-end rounded-xl bg-surface-container-low text-on-surface-variant transition hover:bg-primary hover:text-on-primary focus-visible:outline-none sm:self-center"
                                >
                                    <ArrowUpRightIcon class="h-5 w-5" />
                                </Link>
                            </div>
                        </article>
                    </div>
                    <p v-else class="text-sm text-on-surface-variant">Nessuna scadenza aperta nel perimetro visibile.</p>
                </UiSectionCard>

                <div class="space-y-6 lg:col-span-4">
                    <UiSectionCard title="Attività recente" eyebrow="Cronologia dello studio">
                        <div v-if="props.activities.length" class="relative space-y-6 pl-6 before:absolute before:bottom-0 before:left-[9px] before:top-2 before:w-px before:bg-outline-variant/40">
                            <div v-for="activity in props.activities" :key="activity.id" class="relative">
                                <span :class="['absolute -left-6 top-1.5 h-4 w-4 rounded-full ring-4 ring-surface-container-low', activityToneClass(activity.tone)]" />
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-on-surface-variant">{{ activity.label }} · {{ formatDateTime(activity.occurred_at) }}</p>
                                <p class="mt-2 font-semibold text-on-surface">{{ activity.title }}</p>
                                <p class="mt-1 text-sm text-on-surface-variant">{{ activity.detail }}</p>
                                <p class="mt-1 text-xs text-on-surface-variant/80">{{ activity.meta }}</p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-on-surface-variant">Nessuna attività recente disponibile.</p>
                    </UiSectionCard>

                    <div class="overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-primary to-primary-dim p-6 text-on-primary shadow-[0px_20px_40px_rgba(0,86,210,0.22)]">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/75">Efficienza settimanale</p>
                        <p class="mt-3 font-headline text-4xl font-extrabold">{{ props.efficiency.value }}%</p>
                        <p class="mt-3 max-w-xs text-sm text-white/85">{{ props.efficiency.caption }}</p>
                        <p class="mt-2 text-xs text-white/70">{{ props.efficiency.completed }} completate su {{ props.efficiency.total }} scadenze nel periodo.</p>
                    </div>
                </div>
            </section>

            <UiSectionCard title="Pratiche attive" eyebrow="Vista operativa" :padded="false">
                <div v-if="props.practices.length" class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-left">
                        <thead class="bg-surface-container-low text-on-surface-variant">
                            <tr>
                                <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-[0.18em]">Cliente</th>
                                <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-[0.18em]">Codice fiscale</th>
                                <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-[0.18em]">Tipo</th>
                                <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-[0.18em]">Stato</th>
                                <th class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em]">Ultimo aggiornamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(practice, index) in props.practices" :key="practice.id" :class="index % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface/70'">
                                <td class="px-6 py-5 font-semibold text-on-surface">{{ practice.client_name }}</td>
                                <td class="px-6 py-5 text-sm text-on-surface-variant">{{ practice.tax_id }}</td>
                                <td class="px-6 py-5"><span class="rounded-full bg-surface-container-high px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-on-surface-variant">{{ practice.type }}</span></td>
                                <td class="px-6 py-5 text-sm font-semibold capitalize text-on-surface">{{ practiceStatusLabel(practice.status) }}</td>
                                <td class="px-6 py-5 text-right text-sm text-on-surface-variant">{{ formatDateTime(practice.updated_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="p-6 text-sm text-on-surface-variant">Nessuna pratica attiva nel perimetro visibile.</p>
            </UiSectionCard>
        </div>
    </AppLayout>
</template>
