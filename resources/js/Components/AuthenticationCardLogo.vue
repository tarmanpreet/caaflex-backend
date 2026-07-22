<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationMark from './ApplicationMark.vue';
</script>

<template>
    <Link :href="'/'">
        <span class="grid size-16 place-items-center rounded-2xl bg-primary text-on-primary shadow-xl shadow-primary/20 lg:bg-white lg:text-blue-600">
            <ApplicationMark class="size-10" />
        </span>
    </Link>
</template>
