<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { router, usePage, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SortableTable from '@/Components/SortableTable.vue';
import FullCalendar from '@fullcalendar/vue3';
import IconButton from '@/Components/IconButton.vue';
import Pagination from '@/Components/Pagination.vue';
import UiSectionCard from '@/Components/ui/UiSectionCard.vue';
import UiStatusBadge from '@/Components/ui/UiStatusBadge.vue';
import { formatDateTime } from '@/utils/date';
import { BoltIcon, EyeIcon } from '@heroicons/vue/24/outline';
import {
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    PlusIcon,
    VideoCameraIcon,
    FunnelIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import Multiselect from '@vueform/multiselect';
import ClientSelect from '@/Components/ClientSelect.vue';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import itLocale from '@fullcalendar/core/locales/it';

const listColumns = [
    { key: 'client', label: 'Cliente', sortable: false },
    { key: 'practice_type', label: 'Tipo Pratica', sortable: false },
    { key: 'scheduled_at', label: 'Data/Ora', sortable: true },
    { key: 'duration_minutes', label: 'Durata (min)', sortable: true },
    { key: 'status', label: 'Stato', sortable: true },
    { key: 'assigned_user', label: 'Assegnato a', sortable: false },
];

const props = defineProps({
    appointments: Object,
    calendarEvents: Array,
    filters: Object,
    statuses: Array,
    clients: Array,
    practiceTypes: Array,
    users: Array,
    branches: Array,
    autoConfirmSlots: Array,
});

const page = usePage();
const currentTab = ref(props.filters?.view === 'calendar' ? 'calendario' : 'lista');
const statusFilter = ref(props.filters?.status ?? '');
const fullCalendarRef = ref(null);
const selectedMiniDate = ref(null);

const canCreate = computed(() => page.props.auth.user?.permissions?.includes('appointments.create'));

const applyFilter = () => {
    router.get(route('appointments.index'), { status: statusFilter.value }, { preserveState: true, replace: true });
};

const resetFilter = () => {
    statusFilter.value = '';
    applyFilter();
};

watch(currentTab, (newTab) => {
    if (newTab === 'calendario') {
        if (!props.clients) {
            router.get(route('appointments.index'), { view: 'calendar' }, { preserveState: true, replace: true });
        }
    } else {
        router.get(route('appointments.index'), { status: statusFilter.value }, { preserveState: true, replace: true });
    }
});

function fetchCalendarEvents(fetchInfo, successCallback, failureCallback) {
    const from = fetchInfo.startStr.split('T')[0];
    const to = fetchInfo.endStr.split('T')[0];

    axios.get(route('appointments.calendarEvents'), { params: { from, to } })
        .then(({ data }) => successCallback(data))
        .catch(() => failureCallback());
}

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: itLocale,
    height: 680,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    editable: true,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    events: fetchCalendarEvents,
    eventClick: handleEventClick,
    select: handleDateSelect,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    datesSet: handleCalendarDatesSet,
};

function handleCalendarDatesSet({ start }) {
    miniCalendarDate.value = new Date(start.getFullYear(), start.getMonth(), 1);
}

async function handleEventDrop({ event, revert }) {
    try {
        await axios.patch(route('appointments.reschedule', event.id), {
            scheduled_at: event.startStr.replace('T', ' ').substring(0, 19),
            duration_minutes: event.end ? Math.round((event.end - event.start) / 60000) : event.extendedProps.duration_minutes,
        });
    } catch {
        revert();
    }
}

async function handleEventResize({ event, revert }) {
    try {
        await axios.patch(route('appointments.reschedule', event.id), {
            scheduled_at: event.startStr.replace('T', ' ').substring(0, 19),
            duration_minutes: Math.round((event.end - event.start) / 60000),
        });
    } catch {
        revert();
    }
}

const detailModal = ref(false);
const detailAppointment = ref(null);

function handleEventClick({ event }) {
    detailAppointment.value = {
        id: event.id,
        title: event.title,
        start: event.startStr,
        end: event.endStr,
        status: event.extendedProps.status,
        duration_minutes: event.extendedProps.duration_minutes,
        notes: event.extendedProps.notes,
    };
    detailModal.value = true;
}

function goToDetail() {
    router.visit(route('appointments.show', detailAppointment.value.id));
}

const createModal = ref(false);
const modalPractices = ref([]);
const loadingPractices = ref(false);

const createForm = useForm({
    client_profile_id: null,
    practice_type_id: null,
    practice_id: null,
    scheduled_at: '',
    duration_minutes: 60,
    notes: '',
    assigned_user_id: null,
    branch_id: null,
});

const showPracticeSelect = computed(() => createForm.client_profile_id && createForm.practice_type_id);

const DAYS_IT = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];

const autoConfirmSlotsFormatted = computed(() => {
    return (props.autoConfirmSlots ?? []).map((slot) => ({
        ...slot,
        dayLabel: DAYS_IT[slot.day_of_week] ?? slot.day_of_week,
        timeRange: slot.time_from.substring(0, 5) + ' – ' + slot.time_to.substring(0, 5),
    }));
});

const selectedDateMatchesAutoConfirm = computed(() => {
    if (!createForm.scheduled_at || !props.autoConfirmSlots?.length) return false;
    const dt = new Date(createForm.scheduled_at);
    const dow = dt.getDay();
    const timeStr = dt.toTimeString().substring(0, 8); // HH:MM:SS
    return props.autoConfirmSlots.some(
        (slot) => slot.day_of_week === dow && timeStr >= slot.time_from && timeStr < slot.time_to,
    );
});

async function loadModalPractices() {
    if (!createForm.client_profile_id || !createForm.practice_type_id) {
        modalPractices.value = [];
        return;
    }

    loadingPractices.value = true;
    try {
        const { data } = await axios.get(route('appointments.practicesForModal'), {
            params: {
                client_id: createForm.client_profile_id,
                practice_type_id: createForm.practice_type_id,
            },
        });
        modalPractices.value = data;
    } catch {
        modalPractices.value = [];
    } finally {
        loadingPractices.value = false;
    }
}

function handleDateSelect(info) {
    if (!canCreate.value) return;
    const dt = info.startStr.length > 10 ? info.startStr.substring(0, 16) : `${info.startStr}T09:00`;
    createForm.reset();
    modalPractices.value = [];
    createForm.scheduled_at = dt;
    createModal.value = true;
}

function openCreateModal() {
    createForm.reset();
    modalPractices.value = [];
    createModal.value = true;
}

function onPracticeTypeChange(value) {
    const type = props.practiceTypes?.find((item) => item.id == value);
    if (type?.duration_minutes) {
        createForm.duration_minutes = type.duration_minutes;
    }
    createForm.practice_id = null;
}

watch(() => [createForm.client_profile_id, createForm.practice_type_id], () => loadModalPractices());

function submitCreate() {
    createForm.post(route('appointments.store'), {
        onSuccess: () => {
            createModal.value = false;
            createForm.reset();
            modalPractices.value = [];
        },
    });
}

const confirmingDelete = ref(false);
const appointmentToDelete = ref(null);

const deleteAppointment = () => {
    router.delete(route('appointments.destroy', appointmentToDelete.value.id), {
        onFinish: () => {
            confirmingDelete.value = false;
            appointmentToDelete.value = null;
        },
    });
};

const formatStatus = (status) => status ? status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : '';
const upcomingAppointments = computed(() => (props.appointments?.data ?? []).slice(0, 3));

// Mini calendar state
const miniCalendarDate = ref(new Date());
const miniCalendarDays = computed(() => {
    const year = miniCalendarDate.value.getFullYear();
    const month = miniCalendarDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startPadding = (firstDay.getDay() + 6) % 7;
    
    const days = [];
    for (let i = 0; i < startPadding; i++) {
        days.push({ day: null, isCurrentMonth: false });
    }
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(year, month, i);
        const isToday = date.toDateString() === new Date().toDateString();
        days.push({ day: i, isCurrentMonth: true, isToday, date: date.toISOString().split('T')[0] });
    }
    return days;
});

const miniCalendarMonthName = computed(() => {
    return miniCalendarDate.value.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' });
});

function prevMiniMonth() {
    miniCalendarDate.value = new Date(miniCalendarDate.value.getFullYear(), miniCalendarDate.value.getMonth() - 1, 1);
    const calendarApi = fullCalendarRef.value?.getApi();
    if (calendarApi) {
        calendarApi.prev();
    }
}

function nextMiniMonth() {
    miniCalendarDate.value = new Date(miniCalendarDate.value.getFullYear(), miniCalendarDate.value.getMonth() + 1, 1);
    const calendarApi = fullCalendarRef.value?.getApi();
    if (calendarApi) {
        calendarApi.next();
    }
}

async function selectMiniDate(day) {
    if (!day.isCurrentMonth) return;

    selectedMiniDate.value = day.date;
    miniCalendarDate.value = new Date(day.date);
    currentTab.value = 'calendario';

    await nextTick();

    const calendarApi = fullCalendarRef.value?.getApi();
    if (calendarApi) {
        calendarApi.changeView('timeGridDay', day.date);
    }
}
</script>

<template>
    <AppLayout title="Appuntamenti">
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Calendario / Prenotazioni</p>
                    <h1 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-on-surface">Centro Appuntamenti</h1>
                    <p class="mt-2 text-sm text-on-surface-variant">Gestisci le tue consulenze fiscali con esperti consulenti.</p>
                </div>

                <button
                    v-if="canCreate"
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-br from-primary to-primary-dim px-5 py-3 text-sm font-bold text-on-primary shadow-[0px_20px_40px_rgba(0,86,210,0.18)] hover:scale-[1.02] active:scale-95 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nuovo appuntamento
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <!-- Bento Grid Layout -->
                <div class="grid grid-cols-12 gap-6 items-start">
                    <!-- Left Sidebar: Mini Calendar & Filters -->
                    <div class="col-span-12 lg:col-span-4 xl:col-span-3 space-y-6">
                        <!-- Mini Calendar Card -->
                        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                            <div class="flex items-center justify-between bg-surface-container-low/80 px-4 py-4">
                                <h3 class="font-headline text-lg font-bold text-on-surface">{{ miniCalendarMonthName }}</h3>
                                <div class="flex gap-1">
                                    <button @click="prevMiniMonth" class="p-1.5 hover:bg-surface-container-high rounded-lg transition-colors text-on-surface-variant hover:text-on-surface">
                                        <ChevronLeftIcon class="h-4 w-4" />
                                    </button>
                                    <button @click="nextMiniMonth" class="p-1.5 hover:bg-surface-container-high rounded-lg transition-colors text-on-surface-variant hover:text-on-surface">
                                        <ChevronRightIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div class="p-4">
                                <!-- Day Headers -->
                                <div class="grid grid-cols-7 text-center mb-2">
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant pb-2">Lun</div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant pb-2">Mar</div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant pb-2">Mer</div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant pb-2">Gio</div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant pb-2">Ven</div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant pb-2">Sab</div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant pb-2">Dom</div>
                                </div>
                                <!-- Calendar Days -->
                                <div class="grid grid-cols-7 gap-1">
                                    <template v-for="(day, idx) in miniCalendarDays" :key="idx">
                                        <div 
                                            v-if="day.day"
                                            @click="selectMiniDate(day)"
                                            :class="[
                                                'h-9 flex items-center justify-center text-sm rounded-lg cursor-pointer transition-all',
                                                selectedMiniDate === day.date
                                                    ? 'bg-primary text-white font-bold shadow-sm'
                                                    : day.isToday
                                                        ? 'bg-primary-container text-on-primary-container font-bold'
                                                        : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
                                            ]"
                                        >
                                            {{ day.day }}
                                        </div>
                                        <div v-else class="h-9"></div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Card -->
                        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                            <div class="flex items-center gap-3 border-b border-outline-variant/10 bg-surface-container-low/80 px-4 py-4">
                                <FunnelIcon class="h-5 w-5 text-primary" />
                                <h3 class="font-headline text-base font-bold text-on-surface">Filtri</h3>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant">Stato</label>
                                    <select 
                                        v-model="statusFilter" 
                                        class="block w-full rounded-xl border-0 bg-surface-container-high px-4 py-3 text-sm text-on-surface focus:ring-2 focus:ring-primary/25 transition-all"
                                    >
                                        <option value="">Tutti gli stati</option>
                                        <option v-for="status in statuses" :key="status" :value="status">{{ formatStatus(status) }}</option>
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button 
                                        type="button" 
                                        @click="applyFilter" 
                                        class="flex-1 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition-all hover:bg-primary-dim active:scale-95"
                                    >
                                        Applica
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="resetFilter" 
                                        class="rounded-xl bg-surface-container-high px-4 py-2.5 text-sm font-semibold text-on-surface transition-all hover:bg-surface-container-highest"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats Card -->
                        <div class="overflow-hidden rounded-xl bg-gradient-to-br from-tertiary-container to-tertiary-fixed-dim p-5 shadow-sm text-on-tertiary-container">
                            <div class="flex items-center gap-3 mb-3">
                                <CalendarDaysIcon class="h-5 w-5 text-tertiary" />
                                <span class="text-[10px] font-bold uppercase tracking-widest">Riepilogo</span>
                            </div>
                            <p class="text-2xl font-extrabold mb-1">{{ appointments?.total ?? 0 }}</p>
                            <p class="text-xs font-medium opacity-80">Appuntamenti totali</p>
                            <div class="mt-4 h-1.5 w-full bg-surface-container-lowest/30 rounded-full overflow-hidden">
                                <div class="h-full w-3/4 bg-tertiary rounded-full"></div>
                            </div>
                        </div>

                        <!-- Location Card -->
                        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                            <div class="h-32 relative bg-gradient-to-br from-primary/10 to-tertiary-container flex items-center justify-center">
                                <MapPinIcon class="h-12 w-12 text-primary/40" />
                            </div>
                            <div class="p-4">
                                <h4 class="font-headline font-bold text-sm text-on-surface">Sede Principale</h4>
                                <p class="mt-1 text-xs text-on-surface-variant">Milano · Corso Vittorio Emanuele II, 15</p>
                                <div class="mt-3 flex items-center gap-2 text-xs text-on-surface-variant">
                                    <VideoCameraIcon class="h-4 w-4" />
                                    Sala virtuale disponibile
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Main: FullCalendar & List -->
                    <div class="col-span-12 lg:col-span-8 xl:col-span-9 space-y-6">
                        <!-- View Toggle -->
                        <div class="flex items-center justify-between">
                            <div class="inline-flex rounded-2xl bg-surface-container-low p-1 ring-1 ring-outline-variant/10">
                                <button
                                    type="button"
                                    @click="currentTab = 'calendario'"
                                    :class="[
                                        'rounded-xl px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.18em] transition-all',
                                        currentTab === 'calendario'
                                            ? 'bg-surface-container-lowest text-primary shadow-[0px_12px_30px_rgba(12,15,16,0.06)]'
                                            : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
                                    ]"
                                >
                                    Calendario
                                </button>
                                <button
                                    type="button"
                                    @click="currentTab = 'lista'"
                                    :class="[
                                        'rounded-xl px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.18em] transition-all',
                                        currentTab === 'lista'
                                            ? 'bg-surface-container-lowest text-primary shadow-[0px_12px_30px_rgba(12,15,16,0.06)]'
                                            : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
                                    ]"
                                >
                                    Lista
                                </button>
                            </div>
                            <span class="text-xs text-on-surface-variant font-medium">
                                {{ appointments?.total ?? 0 }} appuntamenti
                            </span>
                        </div>

                        <!-- Calendar View -->
                        <div v-if="currentTab === 'calendario'" class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                            <div class="p-6">
                                <FullCalendar ref="fullCalendarRef" :options="calendarOptions" />
                            </div>
                        </div>

                        <!-- List View -->
                        <div v-else class="space-y-6">
                            <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                                <div class="p-6">
                                    <SortableTable 
                                        :columns="listColumns" 
                                        :rows="appointments.data || []" 
                                        empty-message="Nessun appuntamento trovato."
                                    >
                                        <template #cell-client="{ row }">
                                            <Link 
                                                v-if="row.client" 
                                                :href="route('clients.show', row.client.id)" 
                                                class="font-semibold text-on-surface transition hover:text-primary"
                                            >
                                                {{ row.client.first_name }} {{ row.client.last_name }}
                                            </Link>
                                            <span v-else class="text-on-surface-variant">—</span>
                                        </template>

                                        <template #cell-practice_type="{ row }">
                                            <span
                                                v-if="row.practice_type"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em]"
                                                :style="{
                                                    backgroundColor: row.practice_type.color ? row.practice_type.color + '22' : undefined,
                                                    color: row.practice_type.color ?? undefined,
                                                    boxShadow: row.practice_type.color ? 'inset 0 0 0 1px ' + row.practice_type.color + '44' : undefined,
                                                }"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full flex-shrink-0"
                                                    :style="{ backgroundColor: row.practice_type.color ?? '#888' }"
                                                ></span>
                                                {{ row.practice_type.name }}
                                            </span>
                                            <span v-else class="text-on-surface-variant">—</span>
                                        </template>

                                        <template #cell-scheduled_at="{ row }">{{ formatDateTime(row.scheduled_at) }}</template>
                                        <template #cell-status="{ row }">
                                            <UiStatusBadge :label="formatStatus(row.status)" :status="row.status" />
                                        </template>
                                        <template #cell-assigned_user="{ row }">{{ row.assigned_user?.name ?? '—' }}</template>

                                        <template #actions="{ row }">
                                            <IconButton 
                                                :as="Link" 
                                                :href="route('appointments.show', row.id)" 
                                                tooltip="Visualizza" 
                                                class="rounded-xl bg-surface-container-low p-2 text-primary transition hover:bg-primary-container"
                                            >
                                                <EyeIcon class="h-5 w-5" />
                                            </IconButton>
                                        </template>
                                    </SortableTable>
                                </div>
                                <div v-if="appointments.links && appointments.links.length > 3" class="px-6 pb-6">
                                    <Pagination :links="appointments.links" />
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Appointments Preview -->
                        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/10">
                            <div class="flex items-center justify-between border-b border-outline-variant/10 bg-surface-container-low/80 px-6 py-4">
                                <h3 class="font-headline text-lg font-bold text-on-surface">Prossimi Appuntamenti</h3>
                                <span class="rounded-full bg-primary-container px-2.5 py-0.5 text-[10px] font-bold text-on-primary-container">
                                    {{ upcomingAppointments.length }} Attivi
                                </span>
                            </div>
                            <div class="p-6 space-y-4">
                                <article 
                                    v-for="appointment in upcomingAppointments" 
                                    :key="appointment.id" 
                                    class="rounded-xl bg-surface-container-low p-4 border-l-4 border-primary"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <p class="text-[10px] font-bold text-primary uppercase tracking-wider">
                                                {{ appointment.practice_type?.name ?? 'Appuntamento' }}
                                            </p>
                                            <p class="mt-1 font-headline font-bold text-sm text-on-surface">
                                                {{ formatDateTime(appointment.scheduled_at) }}
                                            </p>
                                        </div>
                                        <UiStatusBadge :label="formatStatus(appointment.status)" :status="appointment.status" size="sm" />
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-on-surface-variant">
                                        <span class="flex items-center gap-1.5">
                                            <CalendarDaysIcon class="h-3.5 w-3.5" />
                                            {{ appointment.client?.first_name }} {{ appointment.client?.last_name }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <ClockIcon class="h-3.5 w-3.5" />
                                            {{ appointment.duration_minutes }} min
                                        </span>
                                    </div>
                                </article>
                                <p v-if="upcomingAppointments.length === 0" class="text-center text-sm text-on-surface-variant py-4">
                                    Nessun appuntamento in programma
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal - Bento Style -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="detailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-on-surface/30 backdrop-blur-sm" @click="detailModal = false"></div>
                    
                    <!-- Modal Content -->
                    <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-surface-container-lowest shadow-[0px_24px_60px_rgba(12,15,16,0.2)] ring-1 ring-outline-variant/10 transition-all">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-outline-variant/10 bg-surface-container-low/80 px-6 py-4">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-on-surface-variant">Dettaglio</p>
                                <h3 class="mt-1 font-headline text-xl font-bold text-on-surface">
                                    Appuntamento #{{ detailAppointment?.id }}
                                </h3>
                            </div>
                            <button 
                                @click="detailModal = false" 
                                class="rounded-xl p-2 text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-all"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div v-if="detailAppointment" class="p-6 space-y-5">
                            <!-- Title Card -->
                            <div class="rounded-xl bg-surface-container-low p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant mb-2">Titolo</p>
                                <p class="font-headline font-semibold text-on-surface">{{ detailAppointment.title }}</p>
                            </div>

                            <!-- Status -->
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant">Stato</p>
                                <UiStatusBadge :label="formatStatus(detailAppointment.status)" :status="detailAppointment.status" />
                            </div>

                            <!-- Time Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-xl bg-surface-container-low p-4">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant mb-1">Inizio</p>
                                    <p class="font-semibold text-sm text-on-surface">
                                        {{ detailAppointment.start?.replace('T', ' ').substring(0, 16) }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-surface-container-low p-4">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant mb-1">Fine</p>
                                    <p class="font-semibold text-sm text-on-surface">
                                        {{ detailAppointment.end?.replace('T', ' ').substring(0, 16) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Duration -->
                            <div class="flex items-center gap-3 rounded-xl bg-surface-container-low p-4">
                                <ClockIcon class="h-5 w-5 text-primary" />
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant">Durata</p>
                                    <p class="font-semibold text-on-surface">{{ detailAppointment.duration_minutes }} minuti</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div v-if="detailAppointment.notes" class="rounded-xl bg-surface-container-low p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant mb-2">Note</p>
                                <p class="text-sm text-on-surface-variant">{{ detailAppointment.notes }}</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 border-t border-outline-variant/10 bg-surface-container-low/50 px-6 py-4">
                            <button 
                                @click="detailModal = false" 
                                class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-surface-container-high transition-all"
                            >
                                Chiudi
                            </button>
                            <button 
                                @click="goToDetail" 
                                class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-primary-dim transition-all"
                            >
                                Apri scheda
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Create Modal - Bento Style -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="createModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-on-surface/30 backdrop-blur-sm" @click="createModal = false"></div>
                    
                    <!-- Modal Content -->
                    <div class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-surface-container-lowest shadow-[0px_24px_60px_rgba(12,15,16,0.2)] ring-1 ring-outline-variant/10 transition-all">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-outline-variant/10 bg-surface-container-low/80 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-dim text-white">
                                    <PlusIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-on-surface-variant">Nuovo</p>
                                    <h3 class="font-headline text-xl font-bold text-on-surface">
                                        Appuntamento
                                    </h3>
                                </div>
                            </div>
                            <button 
                                @click="createModal = false; createForm.reset(); modalPractices = []" 
                                class="rounded-xl p-2 text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-all"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="max-h-[70vh] overflow-y-auto p-6 space-y-5">
                            <!-- Client -->
                            <div>
                                <InputLabel for="cm_client" value="Cliente" class="mb-2" />
                                <ClientSelect v-model="createForm.client_profile_id" class="w-full" />
                                <InputError :message="createForm.errors.client_profile_id" class="mt-1" />
                            </div>

                            <!-- Practice Type -->
                            <div>
                                <InputLabel for="cm_type" value="Tipo Pratica" class="mb-2" />
                                <Multiselect
                                    v-model="createForm.practice_type_id"
                                    :options="(practiceTypes ?? []).map((t) => ({ value: t.id, label: t.name }))"
                                    mode="single"
                                    :searchable="true"
                                    value-prop="value"
                                    label="label"
                                    track-by="label"
                                    placeholder="Seleziona tipo pratica..."
                                    no-options-text="Nessun tipo trovato"
                                    no-results-text="Nessun risultato"
                                    class="w-full"
                                    @select="onPracticeTypeChange"
                                />
                                <InputError :message="createForm.errors.practice_type_id" class="mt-1" />
                            </div>

                            <!-- Practice (conditional) -->
                            <div v-if="showPracticeSelect">
                                <InputLabel for="cm_practice" value="Pratica" class="mb-2" />
                                <div v-if="loadingPractices" class="text-sm text-on-surface-variant">Caricamento pratiche...</div>
                                <Multiselect
                                    v-else
                                    v-model="createForm.practice_id"
                                    :options="[{ value: null, label: 'Nuova pratica' }, ...modalPractices.map((p) => ({ value: p.id, label: '#' + p.id + ' · ' + p.type + ' · ' + p.reference_year }))]"
                                    mode="single"
                                    :searchable="true"
                                    value-prop="value"
                                    label="label"
                                    track-by="label"
                                    placeholder="Seleziona pratica..."
                                    no-options-text="Nessuna pratica trovata"
                                    no-results-text="Nessun risultato"
                                    class="w-full"
                                />
                                <InputError :message="createForm.errors.practice_id" class="mt-1" />
                            </div>

                            <!-- Date & Time -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="cm_scheduled_at" value="Data e Ora" class="mb-2" />
                                    <TextInput 
                                        id="cm_scheduled_at" 
                                        type="datetime-local" 
                                        v-model="createForm.scheduled_at" 
                                        class="w-full rounded-xl border-0 bg-surface-container-high" 
                                        required 
                                    />
                                    <InputError :message="createForm.errors.scheduled_at" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel for="cm_duration" value="Durata (min)" class="mb-2" />
                                    <TextInput 
                                        id="cm_duration" 
                                        type="number" 
                                        min="5" 
                                        v-model="createForm.duration_minutes" 
                                        class="w-full rounded-xl border-0 bg-surface-container-high" 
                                        required 
                                    />
                                    <InputError :message="createForm.errors.duration_minutes" class="mt-1" />
                                </div>
                            </div>

                            <!-- Auto-confirm slots info -->
                            <div v-if="autoConfirmSlotsFormatted.length">
                                <!-- Dynamic feedback when a date is selected -->
                                <Transition
                                    enter-active-class="transition duration-200 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition duration-150 ease-in"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <div
                                        v-if="createForm.scheduled_at"
                                        :class="[
                                            'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold',
                                            selectedDateMatchesAutoConfirm
                                                ? 'bg-tertiary-container text-on-tertiary-container'
                                                : 'bg-surface-container-high text-on-surface-variant',
                                        ]"
                                    >
                                        <BoltIcon class="h-4 w-4 shrink-0" />
                                        <span v-if="selectedDateMatchesAutoConfirm">
                                            Questo orario rientra in uno slot auto-confermato: l'appuntamento sarà confermato automaticamente.
                                        </span>
                                        <span v-else>
                                            Questo orario non rientra in nessuno slot auto-confermato: l'appuntamento resterà in attesa di conferma.
                                        </span>
                                    </div>
                                </Transition>

                                <!-- Slots list -->
                                <div class="mt-3 rounded-xl bg-surface-container-low p-4">
                                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-on-surface-variant">Slot auto-confermati</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="slot in autoConfirmSlotsFormatted"
                                            :key="slot.id"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-surface-container-high px-3 py-1.5 text-xs font-medium text-on-surface"
                                        >
                                            <BoltIcon class="h-3 w-3 text-tertiary" />
                                            <span class="font-semibold">{{ slot.dayLabel }}</span>
                                            <span class="text-on-surface-variant">{{ slot.timeRange }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned User -->
                            <div>
                                <InputLabel for="cm_user" value="Utente Assegnato" class="mb-2" />
                                <Multiselect
                                    v-model="createForm.assigned_user_id"
                                    :options="[{ value: null, label: 'Nessuno' }, ...(users ?? []).map((u) => ({ value: u.id, label: u.name }))]"
                                    mode="single"
                                    :searchable="true"
                                    value-prop="value"
                                    label="label"
                                    track-by="label"
                                    placeholder="Seleziona utente..."
                                    no-options-text="Nessun utente trovato"
                                    no-results-text="Nessun risultato"
                                    class="w-full"
                                />
                                <InputError :message="createForm.errors.assigned_user_id" class="mt-1" />
                            </div>

                            <!-- Branch -->
                            <div>
                                <InputLabel for="cm_branch" value="Filiale" class="mb-2" />
                                <Multiselect
                                    v-model="createForm.branch_id"
                                    :options="[{ value: null, label: 'Nessuna' }, ...(branches ?? []).map((b) => ({ value: b.id, label: b.name + ' - ' + b.city + ' (' + b.province + ')' }))]"
                                    mode="single"
                                    :searchable="true"
                                    value-prop="value"
                                    label="label"
                                    track-by="label"
                                    placeholder="Seleziona filiale..."
                                    no-options-text="Nessuna filiale trovata"
                                    no-results-text="Nessun risultato"
                                    class="w-full"
                                />
                                <InputError :message="createForm.errors.branch_id" class="mt-1" />
                            </div>

                            <!-- Notes -->
                            <div>
                                <InputLabel for="cm_notes" value="Note" class="mb-2" />
                                <textarea 
                                    id="cm_notes" 
                                    v-model="createForm.notes" 
                                    rows="3" 
                                    class="w-full rounded-xl border-0 bg-surface-container-high px-4 py-3 text-sm text-on-surface focus:ring-2 focus:ring-primary/25" 
                                    placeholder="Aggiungi note..."
                                ></textarea>
                                <InputError :message="createForm.errors.notes" class="mt-1" />
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 border-t border-outline-variant/10 bg-surface-container-low/50 px-6 py-4">
                            <button 
                                @click="createModal = false; createForm.reset(); modalPractices = []" 
                                class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-surface-container-high transition-all"
                            >
                                Annulla
                            </button>
                            <button 
                                @click="submitCreate" 
                                :disabled="createForm.processing"
                                :class="[
                                    'rounded-xl px-6 py-2.5 text-sm font-bold shadow-sm transition-all',
                                    createForm.processing 
                                        ? 'bg-primary/50 text-white/70 cursor-not-allowed' 
                                        : 'bg-gradient-to-br from-primary to-primary-dim text-white hover:scale-[1.02] active:scale-95'
                                ]"
                            >
                                Salva Appuntamento
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>Elimina Appuntamento</template>
            <template #content>
                <p class="text-on-surface-variant">Sei sicuro di voler eliminare questo appuntamento? L'operazione non è reversibile.</p>
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">Annulla</SecondaryButton>
                <DangerButton class="ml-3" @click="deleteAppointment">Elimina</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
