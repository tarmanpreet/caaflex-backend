import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echo = null;

export function useEcho(config) {
    if (echo || !config?.enabled || !config?.key) {
        return echo;
    }

    window.Pusher = Pusher;
    const secure = window.location.protocol === 'https:';

    echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: window.location.hostname,
        wsPort: secure ? 443 : Number(window.location.port || 80),
        wssPort: 443,
        forceTLS: secure,
        enabledTransports: ['ws', 'wss'],
    });

    return echo;
}
