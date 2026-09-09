<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const branding = computed(() => page.props.branding);
</script>

<template>
    <img :src="branding.logo_url" :alt="branding.name" />
</template>
