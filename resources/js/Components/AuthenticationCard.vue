<script setup>
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { useDarkMode } from '@/Composables/useDarkMode.js';

const { isDark, toggleDark } = useDarkMode();
</script>

<template>
    <div class="auth-shell relative flex min-h-screen bg-background text-on-surface">
        <button type="button" class="app-icon-button absolute right-4 top-4 z-20 border border-outline-variant/35 bg-surface-container-lowest shadow-sm sm:right-6 sm:top-6" :aria-label="isDark ? 'Attiva modalità chiara' : 'Attiva modalità scura'" :title="isDark ? 'Modalità chiara' : 'Modalità scura'" @click="toggleDark">
            <SunIcon v-if="isDark" class="h-5 w-5" />
            <MoonIcon v-else class="h-5 w-5" />
        </button>

        <div class="relative hidden overflow-hidden bg-slate-950 lg:flex lg:w-[46%] lg:flex-col lg:items-center lg:justify-center lg:p-12 lg:text-white">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_25%_20%,rgba(37,99,235,0.35),transparent_38%),radial-gradient(circle_at_80%_75%,rgba(14,165,233,0.18),transparent_35%)]" aria-hidden="true" />
            <div class="absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(255,255,255,.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.08)_1px,transparent_1px)] [background-size:44px_44px]" aria-hidden="true" />
            <div class="relative flex flex-col items-center">
            <slot name="logo" />
                <h1 class="mt-7 font-headline text-4xl font-extrabold tracking-tight">CAF Gestionale</h1>
                <p class="mt-4 max-w-sm text-center text-base leading-7 text-slate-300">Clienti, pratiche, appuntamenti e filiali in un unico spazio operativo sicuro.</p>
            </div>
        </div>

        <div class="flex w-full items-center justify-center bg-background p-5 sm:p-8 lg:w-[54%]">
            <div class="w-full max-w-md rounded-3xl border border-outline-variant/35 bg-surface-container-lowest p-6 shadow-[0_24px_70px_rgba(15,23,42,0.10)] sm:p-9">
                <div class="mb-8 flex justify-center lg:hidden">
                    <slot name="logo" />
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
