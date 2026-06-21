<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <div class="space-y-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0">
            <form @submit.prevent="$emit('submitted')">
                <div class="grid grid-cols-6 gap-6">
                    <slot name="form" />
                </div>

                <div v-if="hasActions" class="flex items-center justify-end px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 text-end">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
