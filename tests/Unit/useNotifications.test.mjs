import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { createContext, SourceTextModule, SyntheticModule } from 'node:vm';

test('opening a notification navigates before refreshing so the explanation survives', async () => {
    const calls = [];
    let visitOptions;
    const context = createContext({
        route: (name) => name,
        window: {
            axios: {
                post: async () => {
                    calls.push('open');
                    return { data: { data: { redirect_url: '/notifications' } } };
                },
                get: async (url) => {
                    calls.push(url);
                    return { data: { data: [] } };
                },
            },
        },
    });
    const dependencies = {
        vue: { ref: (value) => ({ value }) },
        '@inertiajs/vue3': {
            router: {
                visit: (url, options) => {
                    calls.push(url);
                    visitOptions = options;
                },
            },
        },
        '@/realtime.js': { useEcho: () => null },
        '@/Composables/useBrowserNotifications.js': { useBrowserNotifications: () => ({}) },
    };
    const source = await readFile(new URL('../../resources/js/Composables/useNotifications.js', import.meta.url), 'utf8');
    const module = new SourceTextModule(source, { context });
    await module.link((name) => {
        const exports = dependencies[name];
        return new SyntheticModule(Object.keys(exports), function () {
            for (const [key, value] of Object.entries(exports)) {
                this.setExport(key, value);
            }
        }, { context });
    });
    await module.evaluate();
    const notifications = module.namespace.useNotifications();

    await notifications.open({ id: 'notification-id' });
    assert.deepEqual(calls, ['open', '/notifications']);

    await visitOptions.onFinish();
    assert.deepEqual(calls, ['open', '/notifications', 'notifications.feed', 'notifications.unread-count']);
});
