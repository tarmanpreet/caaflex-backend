<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const branding = computed(() => page.props.branding);
defineProps({ light: Boolean });
</script>

<template>
    <img :src="light ? branding.logo_light_url || branding.logo_url : branding.logo_url" :alt="branding.name" />
</template>
