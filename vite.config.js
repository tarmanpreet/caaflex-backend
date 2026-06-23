import { defineConfig, loadEnv } from 'vite';
import { fileURLToPath, URL } from 'node:url';
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
    resolve: {
        // Resolve "@" to a real filesystem path. laravel-vite-plugin's default
        // alias ("@": "/resources/js") is URL-relative and works in dev, but in
        // `vite build` (rollup) it is treated as an absolute FS path and skips
        // extension resolution — so extensionless imports like "@/composables/x"
        // fail with ENOENT. Array form takes precedence over the plugin default.
        alias: [
            { find: '@', replacement: fileURLToPath(new URL('./resources/js', import.meta.url)) },
        ],
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
