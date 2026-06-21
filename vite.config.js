import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const env = loadEnv('', process.cwd(), '');
const serverHost = env.VITE_SERVER_IP || '0.0.0.0';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        origin: serverHost === '0.0.0.0' ? undefined : `http://${serverHost}:5173`,
        hmr: {
            host: serverHost === '0.0.0.0' ? '0.0.0.0' : serverHost,
        },
        cors: true,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
