import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo – supports both Reverb (self-hosted) and Pusher.
 * Switch via VITE_BROADCAST_DRIVER in your .env:
 *   VITE_BROADCAST_DRIVER=reverb   → uses Reverb WebSocket server
 *   VITE_BROADCAST_DRIVER=pusher   → uses Pusher cloud
 */
import Echo from 'laravel-echo';

const driver = import.meta.env.VITE_BROADCAST_DRIVER ?? 'reverb';

if (driver === 'pusher') {
    import('pusher-js').then(({ default: Pusher }) => {
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key:         import.meta.env.VITE_PUSHER_APP_KEY,
            cluster:     import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS:    true,
        });
    });
} else {
    // Default: Reverb
    import('pusher-js').then(({ default: Pusher }) => {
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster:      'reverb',
            key:              import.meta.env.VITE_REVERB_APP_KEY,
            wsHost:           import.meta.env.VITE_REVERB_HOST,
            wsPort:           import.meta.env.VITE_REVERB_PORT ?? 9000,
            wssPort:          import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS:         (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    });
}
