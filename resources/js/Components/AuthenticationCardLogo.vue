<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationMark from './ApplicationMark.vue';
</script>

<template>
    <Link :href="'/'">
        <ApplicationMark class="size-16 dark:brightness-0 dark:invert lg:brightness-0 lg:invert" />
    </Link>
</template>
