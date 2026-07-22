<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <section class="app-card">
        <div class="border-b border-outline-variant/35 bg-surface-container-low px-5 py-5 sm:px-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>
        </div>

        <div>
            <form @submit.prevent="$emit('submitted')">
                <div class="grid grid-cols-6 gap-6 p-5 sm:p-6">
                    <slot name="form" />
                </div>

                <div v-if="hasActions" class="flex flex-wrap items-center justify-end gap-3 border-t border-outline-variant/35 bg-surface-container-low px-5 py-4 text-end sm:px-6">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </section>
</template>
