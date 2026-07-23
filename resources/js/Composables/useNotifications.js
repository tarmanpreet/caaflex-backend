import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useEcho } from '@/realtime.js';
import { useBrowserNotifications } from '@/Composables/useBrowserNotifications.js';

const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);
let initializedForUser = null;

async function refresh() {
    loading.value = true;

    try {
        const [feedResponse, countResponse] = await Promise.all([
            window.axios.get(route('notifications.feed'), { params: { per_page: 6 } }),
            window.axios.get(route('notifications.unread-count')),
        ]);
        notifications.value = feedResponse.data.data ?? [];
        unreadCount.value = countResponse.data.data?.unread_count ?? 0;
    } finally {
        loading.value = false;
    }
}

export function useNotifications() {
    const browserNotifications = useBrowserNotifications();

    const initialize = async (user, realtime, toast) => {
        if (!user || initializedForUser === user.id) {
            return;
        }

        initializedForUser = user.id;
        await refresh();

        const echo = useEcho(realtime);

        if (echo) {
            echo.private(`App.Models.User.${user.id}`).notification(async (notification) => {
                toast?.info(notification.title ?? 'Hai ricevuto una nuova notifica.');
                browserNotifications.show(notification);
                await refresh();
            });

            echo.connector?.pusher?.connection?.bind('connected', refresh);
        }
    };

    const open = async (notification) => {
        const response = await window.axios.post(route('notifications.open', notification.id));
        await refresh();
        router.visit(response.data.data.redirect_url);
    };

    const markAllAsRead = async () => {
        await window.axios.post(route('notifications.read-all'));
        await refresh();
    };

    return { notifications, unreadCount, loading, initialize, refresh, open, markAllAsRead };
}
