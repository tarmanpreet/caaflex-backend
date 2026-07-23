<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { BellIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { useToast } from 'vue-toastification';
import Dropdown from '@/Components/Dropdown.vue';
import { useNotifications } from '@/Composables/useNotifications.js';

const props = defineProps({
    user: { type: Object, required: true },
    realtime: { type: Object, default: () => ({}) },
});

const toast = useToast();
const { notifications, unreadCount, loading, initialize, open, markAllAsRead } = useNotifications();

onMounted(() => initialize(props.user, props.realtime, toast));
</script>

<template>
    <Dropdown align="right" width="96" :content-classes="['bg-surface-container-lowest']">
        <template #trigger>
            <button type="button" class="app-icon-button relative" aria-label="Apri notifiche">
                <BellIcon class="h-5 w-5" />
                <span v-if="unreadCount" class="absolute right-1 top-1 grid min-h-5 min-w-5 place-items-center rounded-full bg-error px-1 text-[10px] font-bold text-on-error">
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="flex items-center justify-between gap-3 border-b border-outline-variant/30 px-4 py-3">
                <div>
                    <p class="font-bold text-on-surface">Notifiche</p>
                    <p class="text-xs text-on-surface-variant">{{ unreadCount }} da leggere</p>
                </div>
                <button v-if="unreadCount" type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-primary" @click.stop="markAllAsRead">
                    <CheckIcon class="h-4 w-4" /> Tutte lette
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <p v-if="loading" class="px-4 py-6 text-center text-sm text-on-surface-variant">Caricamento…</p>
                <p v-else-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-on-surface-variant">Nessuna notifica.</p>
                <button
                    v-for="notification in notifications"
                    v-else
                    :key="notification.id"
                    type="button"
                    class="block w-full border-b border-outline-variant/20 px-4 py-3 text-left transition hover:bg-surface-container-low"
                    @click="open(notification)"
                >
                    <span class="flex items-start gap-3">
                        <span :class="notification.read_at ? 'bg-surface-container-high' : 'bg-primary'" class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" />
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-on-surface">{{ notification.title }}</span>
                            <span class="mt-1 block line-clamp-2 text-xs leading-5 text-on-surface-variant">{{ notification.body }}</span>
                        </span>
                    </span>
                </button>
            </div>

            <div class="grid grid-cols-2 border-t border-outline-variant/30">
                <Link :href="route('notifications.index')" class="px-4 py-3 text-center text-sm font-semibold text-primary hover:bg-surface-container-low">Vedi tutte</Link>
                <Link :href="route('notification-settings.show')" class="border-l border-outline-variant/30 px-4 py-3 text-center text-sm font-semibold text-on-surface-variant hover:bg-surface-container-low">Impostazioni</Link>
            </div>
        </template>
    </Dropdown>
</template>
