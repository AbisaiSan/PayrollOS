import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

const raiz = import.meta.dirname;

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(raiz, 'resources/js'),
            'ziggy-js': path.resolve(raiz, 'vendor/tightenco/ziggy'),
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
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
