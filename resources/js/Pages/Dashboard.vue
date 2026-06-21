<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatCard from '@/Components/ui/UiStatCard.vue';
import { formatDate, formatDateTime } from '@/utils/date';

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

const practiceStatusLabel = (status) => status ? status.replace(/_/g, ' ') : '—';
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Overview operativa</p>
                <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Dashboard principale</h1>
                <p class="mt-2 text-sm text-on-surface-variant">Panoramica premium delle priorità di studio, ispirata ai nuovi template web.</p>
            </div>
        </template>

        <div class="space-y-8">
            <section class="grid gap-6 md:grid-cols-3">
                <UiStatCard v-for="stat in props.stats" :key="stat.title" :title="stat.title" :value="stat.value" :caption="stat.caption" :tone="stat.tone" />
            </section>

            <section class="grid gap-8 lg:grid-cols-12">
                <UiSectionCard class="lg:col-span-8" title="Scadenze in primo piano" eyebrow="Focus di giornata">
                    <div v-if="props.deadlines.length" class="space-y-4">
                        <div v-for="item in props.deadlines" :key="item.id" class="flex flex-col gap-4 rounded-[1.25rem] px-4 py-4 transition hover:bg-surface-container-low md:flex-row md:items-center md:justify-between">
                            <div class="w-24 text-left md:text-center">
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-primary">{{ formatDate(item.deadline_at).split(' ')[1] || '—' }}</p>
                                <p class="mt-1 font-headline text-2xl font-extrabold text-on-surface">{{ new Date(item.deadline_at).getDate() }}</p>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-on-surface">{{ item.title }}</h3>
                                <p class="mt-1 text-sm text-on-surface-variant">{{ item.practice.client_name }} · {{ item.practice.type || 'Pratica' }}</p>
                                <p v-if="item.notes" class="mt-2 text-sm text-on-surface-variant">{{ item.notes }}</p>
                                <p v-else-if="item.assignee?.name" class="mt-2 text-sm text-on-surface-variant">Assegnata a {{ item.assignee.name }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 md:justify-end">
                                <span class="rounded-full bg-surface-container-high px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-on-surface-variant">{{ deadlinePriorityLabel(item.priority) }}</span>
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-primary">{{ deadlineStatusLabel(item.status) }}</span>
                                <span class="text-xs text-on-surface-variant">{{ formatDateTime(item.deadline_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-on-surface-variant">Nessuna scadenza aperta nel perimetro visibile.</p>
                </UiSectionCard>

                <div class="space-y-6 lg:col-span-4">
                    <UiSectionCard title="Attività recente" eyebrow="Timeline studio">
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
