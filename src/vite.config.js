import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    build: {
        manifest: true,
        outDir: path.resolve(__dirname, 'public/build'),
            rollupOptions: {
      input: path.resolve(__dirname, 'resources/js/app.js'),
    },
    },
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.js',
        ]),
        vue(),
    ],
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js'
        }
    },
    server: {
        host: '0.0.0.0',
        watch: {
            usePolling: true,
        },
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
    },
});
