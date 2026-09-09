<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const markUrl = computed(() => page.props.branding.mark_url);
</script>

<template>
    <img :src="markUrl" alt="" class="object-contain" />
</template>
