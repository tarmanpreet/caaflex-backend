import { computed, ref } from 'vue';

const supported = ref(false);
const permission = ref('default');

const refreshPermission = () => {
    supported.value = typeof window !== 'undefined' && 'Notification' in window;
    permission.value = supported.value ? window.Notification.permission : 'unsupported';
};

export function useBrowserNotifications() {
    refreshPermission();

    const isEnabled = computed(() => permission.value === 'granted');
    const isDenied = computed(() => permission.value === 'denied');

    const requestPermission = async () => {
        refreshPermission();

        if (!supported.value || permission.value === 'denied') {
            return permission.value;
        }

        permission.value = await window.Notification.requestPermission();

        return permission.value;
    };

    const show = (notification) => {
        refreshPermission();

        if (!isEnabled.value) {
            return null;
        }

        let browserNotification;

        try {
            browserNotification = new window.Notification(
                notification.title ?? 'Nuova notifica',
                {
                    body: notification.body ?? 'Hai ricevuto una nuova notifica.',
                    icon: '/favicon.ico',
                    tag: [notification.event_key, notification.subject_id].filter(Boolean).join('-'),
                },
            );
        } catch {
            return null;
        }

        browserNotification.onclick = () => {
            window.focus();

            if (notification.action_url?.startsWith('/') && !notification.action_url.startsWith('//')) {
                window.location.assign(notification.action_url);
            }

            browserNotification.close();
        };

        return browserNotification;
    };

    return {
        supported,
        permission,
        isEnabled,
        isDenied,
        requestPermission,
        refreshPermission,
        show,
    };
}
