<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-on-surface-variant transition duration-150 ease-in-out select-none hover:bg-surface-container-low hover:text-on-surface focus:bg-surface-container-low focus:outline-none">
            <slot />
        </button>

        <a v-else-if="as =='a'" :href="href" class="block px-4 py-2 text-sm leading-5 text-on-surface-variant transition duration-150 ease-in-out select-none hover:bg-surface-container-low hover:text-on-surface focus:bg-surface-container-low focus:outline-none">
            <slot />
        </a>

        <Link v-else :href="href" class="block px-4 py-2 text-sm leading-5 text-on-surface-variant transition duration-150 ease-in-out select-none hover:bg-surface-container-low hover:text-on-surface focus:bg-surface-container-low focus:outline-none">
            <slot />
        </Link>
    </div>
</template>
