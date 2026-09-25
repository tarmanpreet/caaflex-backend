<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from './ApplicationLogo.vue';
</script>

<template>
    <Link :href="'/'" class="block">
        <ApplicationLogo class="h-20 w-[270px] object-contain drop-shadow-lg" />
    </Link>
</template>
