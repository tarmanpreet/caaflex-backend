<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" type="submit" class="block min-h-[44px] w-full px-4 py-2.5 text-start text-sm font-medium leading-5 text-on-surface-variant transition duration-200 select-none hover:bg-surface-container-low hover:text-on-surface focus:bg-surface-container-low focus:outline-none">
            <slot />
        </button>

        <a v-else-if="as =='a'" :href="href" class="block min-h-[44px] px-4 py-2.5 text-sm font-medium leading-5 text-on-surface-variant transition duration-200 select-none hover:bg-surface-container-low hover:text-on-surface focus:bg-surface-container-low focus:outline-none">
            <slot />
        </a>

        <Link v-else :href="href" class="block min-h-[44px] px-4 py-2.5 text-sm font-medium leading-5 text-on-surface-variant transition duration-200 select-none hover:bg-surface-container-low hover:text-on-surface focus:bg-surface-container-low focus:outline-none">
            <slot />
        </Link>
    </div>
</template>
