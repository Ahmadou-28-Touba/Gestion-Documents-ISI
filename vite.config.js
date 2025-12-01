import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    server: {
        watch: {
            usePolling: true,
        },
        hmr: {
            host: 'localhost',
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                    isCustomElement: (tag) => ['md-linedivider'].includes(tag),
                }
            },
        }),
    ],
    optimizeDeps: {
        include: ['vue', 'vue-router', 'vuex'],
        esbuildOptions: {
            target: 'esnext',
            define: {
                global: 'globalThis',
            },
        },
    },
    build: {
        target: 'esnext',
        chunkSizeWarningLimit: 1600,
        rollupOptions: {
            output: {
                manualChunks: {
                    vue: ['vue', 'vue-router', 'vuex'],
                    vendor: ['axios', 'bootstrap'],
                },
            },
        },
    },
});
