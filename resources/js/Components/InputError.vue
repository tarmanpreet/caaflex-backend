<script setup>
defineProps({
    message: String,
});
</script>

<template>
    <div v-show="message" role="alert">
        <p class="text-sm font-medium text-error">
            {{ message }}
        </p>
    </div>
</template>
