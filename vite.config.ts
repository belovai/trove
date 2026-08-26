import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        // Bound to all interfaces so the dev server is reachable from outside
        // the trove-node container; HMR still connects back via localhost.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
        // The app is served from a different origin (nginx) than the dev
        // server, so assets are cross-origin requests during development.
        cors: {
            origin: [/^https?:\/\/(?:[\w-]+\.)*(?:localhost|127\.0\.0\.1|\[::1\]|[\w-]+\.nip\.io|[\w-]+\.test)(?::\d+)?$/],
        },
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Bind mounts on macOS/Windows do not deliver inotify events.
            usePolling: true,
            interval: 300,
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
        },
    },
});
