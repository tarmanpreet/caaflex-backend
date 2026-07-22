<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
    size: {
        type: String,
        default: 'md',
    },
});

const sizeClasses = computed(() => props.size === 'sm' ? 'h-9 w-9 text-xs' : 'h-10 w-10 text-sm');

const baseClasses = 'inline-flex items-center justify-center rounded-xl border border-outline-variant/40 bg-surface-container-lowest text-on-surface-variant shadow-sm transition duration-200 hover:border-primary/40 hover:bg-primary-container/35 hover:text-primary focus-visible:outline-none';
const activeClasses = 'inline-flex items-center justify-center rounded-xl bg-primary font-semibold text-on-primary shadow-lg shadow-primary/15';
const disabledClasses = 'inline-flex items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant/50';
</script>

<template>
    <nav v-if="links && links.length > 3" aria-label="Page navigation">
        <ul class="flex flex-wrap items-center gap-2">
            <li>
                <Link v-if="links[0].url" :href="links[0].url" :class="[baseClasses, sizeClasses]">
                    <span class="sr-only">Precedente</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="m15 19-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </Link>
                <span v-else :class="[disabledClasses, sizeClasses]">
                    <span class="sr-only">Precedente</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="m15 19-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </li>

            <li v-for="(link, index) in links.slice(1, -1)" :key="index">
                <Link v-if="link.url && !link.active" :href="link.url" :class="[baseClasses, sizeClasses]" v-html="link.label" />
                <span v-else-if="link.active" :class="[activeClasses, sizeClasses]" aria-current="page" v-html="link.label" />
                <span v-else :class="[disabledClasses, sizeClasses]" v-html="link.label" />
            </li>

            <li>
                <Link v-if="links[links.length - 1].url" :href="links[links.length - 1].url" :class="[baseClasses, sizeClasses]">
                    <span class="sr-only">Successiva</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </Link>
                <span v-else :class="[disabledClasses, sizeClasses]">
                    <span class="sr-only">Successiva</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </li>
        </ul>
    </nav>
</template>
