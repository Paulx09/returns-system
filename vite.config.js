import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    test: {
        environment: 'jsdom',
        pool: 'threads',
        minThreads: 1,
        maxThreads: 1,
        setupFiles: './resources/js/__tests__/setup.js',
        include: ['resources/js/__tests__/**/*.test.{js,jsx}'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html'],
            include: ['resources/js/Pages/Returns/**/*.jsx'],
            exclude: ['resources/js/Pages/Returns/Success.jsx'],
        },
    },
});
