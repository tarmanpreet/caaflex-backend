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
    BellIcon,
    BuildingOfficeIcon,
    CalendarDaysIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
    FolderOpenIcon,
    MagnifyingGlassIcon,
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
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-background text-on-surface">
            <div class="flex min-h-screen">
                <aside
                    :class="[
                        isCollapsed ? 'w-[92px]' : 'w-[280px]',
                        'hidden shrink-0 border-r border-outline-variant/10 bg-surface-container-low/90 backdrop-blur-xl transition-all duration-300 lg:flex lg:flex-col',
                    ]"
                >
                    <div class="flex h-full flex-col px-4 py-5">
                        <div class="mb-8 flex items-center gap-3 px-3">
                            <Link :href="route('dashboard')" class="flex items-center gap-3 overflow-hidden">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1.1rem] bg-gradient-to-br from-primary to-primary-dim text-on-primary shadow-[0px_16px_30px_rgba(0,86,210,0.22)]">
                                    <ApplicationMark class="h-7 w-7" />
                                </div>

                                <div v-if="!isCollapsed" class="min-w-0">
                                    <p class="truncate font-headline text-lg font-extrabold tracking-tight text-primary">Fiscal Clarity</p>
                                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.28em] text-on-surface-variant">Workspace CAF</p>
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
                                        'group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition-all',
                                        item.active
                                            ? 'bg-surface-container-lowest text-primary shadow-[0px_12px_30px_rgba(12,15,16,0.06)] ring-1 ring-primary/10'
                                            : 'text-on-surface-variant hover:bg-surface-container-high/70 hover:text-on-surface',
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
                                class="flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-br from-primary to-primary-dim px-4 py-3 text-sm font-bold text-on-primary shadow-[0px_20px_40px_rgba(0,86,210,0.22)] transition hover:scale-[0.99]"
                            >
                                <PlusIcon class="h-5 w-5" />
                                <span v-if="!isCollapsed">Nuova pratica</span>
                            </Link>

                            <div class="flex items-center justify-between rounded-2xl bg-surface-container-lowest px-3 py-2.5 ring-1 ring-outline-variant/10">
                                <button @click="toggleSidebar" class="flex items-center gap-2 rounded-xl px-2 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-low hover:text-primary">
                                    <component :is="isCollapsed ? ChevronRightIcon : ChevronLeftIcon" class="h-5 w-5" />
                                    <span v-if="!isCollapsed">Compatta</span>
                                </button>

                                <button @click="toggleDark" class="rounded-xl p-2 text-on-surface-variant transition hover:bg-surface-container-low hover:text-primary">
                                    <SunIcon v-if="isDark" class="h-5 w-5" />
                                    <MoonIcon v-else class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="min-w-0 flex-1">
                    <header class="sticky top-0 z-30 border-b border-outline-variant/10 bg-background/85 backdrop-blur-xl">
                        <div class="flex h-20 items-center gap-4 px-4 sm:px-6 lg:px-8">
                            <button @click="openMobile" class="rounded-2xl bg-surface-container-low p-3 text-on-surface-variant transition hover:text-primary lg:hidden">
                                <Bars3Icon class="h-5 w-5" />
                            </button>

                            <div class="hidden flex-1 items-center gap-4 lg:flex">
                                <div class="relative max-w-xl flex-1">
                                    <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                                    <input
                                        type="text"
                                        placeholder="Cerca pratiche, clienti o documenti..."
                                        class="h-12 w-full rounded-2xl border-0 bg-surface-container-high pl-12 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/25"
                                    >
                                </div>
                            </div>

                            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                                <button class="rounded-2xl bg-surface-container-low p-3 text-on-surface-variant transition hover:text-primary">
                                    <BellIcon class="h-5 w-5" />
                                </button>
                                <button class="rounded-2xl bg-surface-container-low p-3 text-on-surface-variant transition hover:text-primary">
                                    <Cog6ToothIcon class="h-5 w-5" />
                                </button>

                                <Dropdown align="right" width="48" :content-classes="['py-2', 'bg-surface-container-lowest']">
                                    <template #trigger>
                                        <button type="button" class="flex items-center gap-3 rounded-[1.25rem] bg-surface-container-low px-3 py-2.5 text-left transition hover:bg-surface-container-high">
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
                                        <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">API Tokens</DropdownLink>
                                        <div class="my-1 border-t border-outline-variant/10" />
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">Log Out</DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </header>

                    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                        <section class="mb-8 rounded-[2rem] bg-surface-container-low px-6 py-6 shadow-[0px_20px_40px_rgba(12,15,16,0.04)]">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Workspace</p>
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
                <div class="absolute inset-0 bg-inverse-surface/50 backdrop-blur-sm" @click="closeMobile" />

                <aside class="relative flex w-[86vw] max-w-sm flex-col bg-surface-container-low px-4 py-5 shadow-2xl">
                    <div class="mb-8 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-[1rem] bg-gradient-to-br from-primary to-primary-dim text-on-primary">
                                <ApplicationMark class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="font-headline text-lg font-extrabold text-primary">Fiscal Clarity</p>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-on-surface-variant">Workspace CAF</p>
                            </div>
                        </div>

                        <button @click="closeMobile" class="rounded-2xl bg-surface-container-lowest p-2 text-on-surface-variant">
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
                                    'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition-all',
                                    item.active
                                        ? 'bg-surface-container-lowest text-primary shadow-[0px_12px_30px_rgba(12,15,16,0.06)] ring-1 ring-primary/10'
                                        : 'text-on-surface-variant hover:bg-surface-container-high',
                                ]"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                                {{ item.name }}
                            </Link>
                        </template>
                    </nav>

                    <div class="mt-auto space-y-3 pt-6">
                        <Link v-if="canCreatePractice" :href="route('practices.create')" class="flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-br from-primary to-primary-dim px-4 py-3 text-sm font-bold text-on-primary">
                            <PlusIcon class="h-5 w-5" />
                            Nuova pratica
                        </Link>

                        <button @click="toggleDark" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-surface-container-lowest px-4 py-3 text-sm font-semibold text-on-surface-variant">
                            <SunIcon v-if="isDark" class="h-5 w-5" />
                            <MoonIcon v-else class="h-5 w-5" />
                            Tema
                        </button>

                        <button @click="logout" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-error-container/15 px-4 py-3 text-sm font-semibold text-error">
                            <ArrowRightOnRectangleIcon class="h-5 w-5" />
                            Log Out
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>
