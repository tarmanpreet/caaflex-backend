<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { useDarkMode } from '@/Composables/useDarkMode.js';
import { useSidebar } from '@/Composables/useSidebar.js';
import {
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    BuildingOfficeIcon,
    CalendarDaysIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
    FolderOpenIcon,
    MoonIcon,
    PlusIcon,
    Squares2X2Icon,
    SunIcon,
    TagIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    title: String,
});

const page = usePage();
const { isDark, toggleDark } = useDarkMode();
const { isCollapsed, toggleSidebar, isMobileOpen, openMobile, closeMobile } = useSidebar();

const logout = () => router.post(route('logout'));

const userName = computed(() => page.props.auth.user?.name ?? 'Utente');
const userInitials = computed(() => userName.value.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase());
const roles = computed(() => page.props.auth.user?.roles || []);
const isCliente = computed(() => roles.value.includes('cliente'));
const isAdmin = computed(() => roles.value.includes('admin') || roles.value.includes('superadmin'));
const canCreatePractice = computed(() => page.props.auth.user?.permissions?.includes('practices.create'));

const navItems = computed(() => {
    page.url;

    return [
        { name: 'Dashboard', icon: Squares2X2Icon, route: route('dashboard'), active: route().current('dashboard'), show: true },
        { name: 'Clienti', icon: UserGroupIcon, route: route('clients.index'), active: route().current('clients.*'), show: !isCliente.value },
        { name: 'Pratiche', icon: FolderOpenIcon, route: route('practices.index'), active: route().current('practices.*'), show: !isCliente.value },
        { name: 'Tipi pratica', icon: TagIcon, route: route('practice-types.index'), active: route().current('practice-types.*'), show: isAdmin.value },
        { name: 'Procedure', icon: ClipboardDocumentListIcon, route: route('procedures.index'), active: route().current('procedures.*'), show: isAdmin.value },
        { name: 'Appuntamenti', icon: CalendarDaysIcon, route: route('appointments.index'), active: route().current('appointments.*'), show: !isCliente.value },
        { name: 'Filiali', icon: BuildingOfficeIcon, route: route('branches.index'), active: route().current('branches.*'), show: isAdmin.value },
         { name: 'Auto-Conferma', icon: Cog6ToothIcon, route: route('auto-confirm-slots.index'), active: route().current('auto-confirm-slots.*'), show: isAdmin.value },
         { name: 'Utenti', icon: UsersIcon, route: route('users.index'), active: route().current('users.*'), show: isAdmin.value },
    ];
});
</script>

<template>
    <div class="app-shell">
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-background text-on-surface">
            <div class="flex min-h-screen">
                <aside
                    :class="[
                        isCollapsed ? 'w-[92px]' : 'w-[280px]',
                        'hidden shrink-0 border-r border-outline-variant/40 bg-surface-container-lowest transition-[width] duration-300 motion-reduce:transition-none lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col',
                    ]"
                >
                    <div class="flex h-full flex-col px-4 py-5">
                        <div class="mb-8 flex items-center gap-3 px-3">
                            <Link :href="route('dashboard')" class="flex items-center gap-3 overflow-hidden">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1.1rem] bg-primary text-on-primary shadow-[0px_16px_30px_rgba(0,86,210,0.22)]">
                                    <ApplicationMark class="h-7 w-7" />
                                </div>

                                <div v-if="!isCollapsed" class="min-w-0">
                                    <p class="truncate font-headline text-lg font-extrabold tracking-tight text-on-surface">CAF Gestionale</p>
                                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-on-surface-variant">Workspace operativo</p>
                                </div>
                            </Link>
                        </div>

                        <nav class="flex-1 space-y-1.5">
                            <template v-for="item in navItems" :key="item.name">
                                <Link
                                    v-if="item.show"
                                    :href="item.route"
                                    @click="closeMobile"
                                    :title="isCollapsed ? item.name : ''"
                                    :class="[
                                        'group flex min-h-[46px] items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition duration-200',
                                        item.active
                                            ? 'bg-primary-container text-on-primary-container shadow-sm'
                                            : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface',
                                    ]"
                                >
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                    <span v-if="!isCollapsed" class="truncate">{{ item.name }}</span>
                                </Link>
                            </template>
                        </nav>

                        <div class="mt-6 space-y-3">
                            <Link
                                v-if="canCreatePractice"
                                :href="route('practices.create')"
                                class="flex min-h-[46px] items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-on-primary shadow-lg shadow-primary/20 transition duration-200 hover:bg-primary-dim focus-visible:outline-none"
                            >
                                <PlusIcon class="h-5 w-5" />
                                <span v-if="!isCollapsed">Nuova pratica</span>
                            </Link>

                            <div class="flex items-center justify-between rounded-xl border border-outline-variant/35 bg-surface-container-low px-2 py-2">
                                <button type="button" @click="toggleSidebar" class="flex min-h-[40px] items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-lowest hover:text-primary" :aria-label="isCollapsed ? 'Espandi navigazione' : 'Compatta navigazione'">
                                    <component :is="isCollapsed ? ChevronRightIcon : ChevronLeftIcon" class="h-5 w-5" />
                                    <span v-if="!isCollapsed">Compatta</span>
                                </button>

                                <button type="button" @click="toggleDark" class="grid h-10 w-10 place-items-center rounded-lg text-on-surface-variant transition hover:bg-surface-container-lowest hover:text-primary" :aria-label="isDark ? 'Attiva modalità chiara' : 'Attiva modalità scura'" :title="isDark ? 'Modalità chiara' : 'Modalità scura'">
                                    <SunIcon v-if="isDark" class="h-5 w-5" />
                                    <MoonIcon v-else class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="min-w-0 flex-1">
                    <header class="sticky top-0 z-30 border-b border-outline-variant/35 bg-background/90 backdrop-blur-xl">
                        <div class="mx-auto flex h-[72px] max-w-[1600px] items-center gap-4 px-4 sm:px-6 lg:px-8">
                            <button type="button" @click="openMobile" class="app-icon-button lg:hidden" aria-label="Apri navigazione">
                                <Bars3Icon class="h-5 w-5" />
                            </button>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-on-surface">{{ title }}</p>
                                <p class="mt-0.5 hidden text-xs text-on-surface-variant sm:block">Gestisci il lavoro della tua rete in modo semplice e sicuro.</p>
                            </div>

                            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                                <button type="button" @click="toggleDark" class="app-icon-button hidden sm:inline-flex" :aria-label="isDark ? 'Attiva modalità chiara' : 'Attiva modalità scura'" :title="isDark ? 'Modalità chiara' : 'Modalità scura'">
                                    <SunIcon v-if="isDark" class="h-5 w-5" />
                                    <MoonIcon v-else class="h-5 w-5" />
                                </button>

                                <Dropdown align="right" width="48" :content-classes="['py-2', 'bg-surface-container-lowest']">
                                    <template #trigger>
                                        <button type="button" class="flex min-h-[48px] items-center gap-3 rounded-xl border border-outline-variant/30 bg-surface-container-lowest px-2.5 py-2 text-left shadow-sm transition duration-200 hover:bg-surface-container-low">
                                            <img
                                                v-if="$page.props.jetstream.managesProfilePhotos"
                                                class="h-10 w-10 rounded-2xl object-cover ring-2 ring-primary/10"
                                                :src="$page.props.auth.user.profile_photo_url"
                                                :alt="$page.props.auth.user.name"
                                            >
                                            <div v-else class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-container text-sm font-bold text-on-primary-container">
                                                {{ userInitials }}
                                            </div>

                                            <div class="hidden min-w-0 sm:block">
                                                <p class="truncate text-sm font-bold text-on-surface">{{ userName }}</p>
                                                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-on-surface-variant">Area personale</p>
                                            </div>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">Account</div>
                                        <DropdownLink :href="route('profile.show')">Profilo</DropdownLink>
                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">Token API</DropdownLink>
                                        <div class="my-1 border-t border-outline-variant/10" />
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">Esci</DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </header>

                    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                        <section class="mb-6 overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-container-lowest px-5 py-5 shadow-sm sm:px-6 sm:py-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-primary">CAF Gestionale</p>
                                    <div class="mt-2">
                                        <slot name="header">
                                            <h1 class="font-headline text-3xl font-extrabold tracking-tight text-on-surface">{{ title }}</h1>
                                        </slot>
                                    </div>
                                </div>

                            </div>
                        </section>

                        <slot />
                    </main>
                </div>
            </div>

            <div v-if="isMobileOpen" class="fixed inset-0 z-50 flex lg:hidden">
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="closeMobile" />

                <aside class="relative flex w-[86vw] max-w-sm flex-col bg-surface-container-low px-4 py-5 shadow-2xl">
                    <div class="mb-8 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-[1rem] bg-primary text-on-primary">
                                <ApplicationMark class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="font-headline text-lg font-extrabold text-on-surface">CAF Gestionale</p>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-on-surface-variant">Workspace operativo</p>
                            </div>
                        </div>

                        <button type="button" @click="closeMobile" class="app-icon-button" aria-label="Chiudi navigazione">
                            <ChevronLeftIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <nav class="space-y-1.5">
                        <template v-for="item in navItems" :key="item.name">
                            <Link
                                v-if="item.show"
                                :href="item.route"
                                @click="closeMobile"
                                :class="[
                                    'flex min-h-[46px] items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition duration-200',
                                    item.active
                                        ? 'bg-primary-container text-on-primary-container shadow-sm'
                                        : 'text-on-surface-variant hover:bg-surface-container-high',
                                ]"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                                {{ item.name }}
                            </Link>
                        </template>
                    </nav>

                    <div class="mt-auto space-y-3 pt-6">
                        <Link v-if="canCreatePractice" :href="route('practices.create')" class="flex min-h-[46px] items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-on-primary">
                            <PlusIcon class="h-5 w-5" />
                            Nuova pratica
                        </Link>

                        <button type="button" @click="toggleDark" class="flex min-h-[46px] w-full items-center justify-center gap-2 rounded-xl border border-outline-variant/35 bg-surface-container-lowest px-4 py-3 text-sm font-semibold text-on-surface-variant">
                            <SunIcon v-if="isDark" class="h-5 w-5" />
                            <MoonIcon v-else class="h-5 w-5" />
                            {{ isDark ? 'Modalità chiara' : 'Modalità scura' }}
                        </button>

                        <button type="button" @click="logout" class="flex min-h-[46px] w-full items-center justify-center gap-2 rounded-xl bg-error-container px-4 py-3 text-sm font-semibold text-on-error-container">
                            <ArrowRightOnRectangleIcon class="h-5 w-5" />
                            Esci
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>
