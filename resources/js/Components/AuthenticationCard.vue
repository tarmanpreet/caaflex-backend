<template>
    <div class="min-h-screen flex">
        <!-- Left branding panel: hidden on mobile, visible lg+ -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 flex-col items-center justify-center p-12 text-white">
            <!-- Logo + app name + tagline -->
            <slot name="logo" />
            <h1 class="mt-6 text-3xl font-bold">CAF Gestionale</h1>
            <p class="mt-2 text-indigo-200 text-center max-w-sm">Gestisci clienti, pratiche e appuntamenti in un'unica piattaforma.</p>
        </div>
        
        <!-- Right form panel -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white dark:bg-gray-900">
            <div class="w-full max-w-md">
                <!-- Show logo on mobile only (when left panel is hidden) -->
                <div class="lg:hidden flex justify-center mb-8">
                    <slot name="logo" />
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
