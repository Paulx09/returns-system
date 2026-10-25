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
        // Uso de procesos fork secuenciales para evitar deadlocks/timeouts de workers en Windows con JSDOM
        pool: 'forks',
        fileParallelism: false,
        maxWorkers: 1,
        setupFiles: './resources/js/__tests__/setup.js',
        // Suites unitarias, de integración y auditoría de accesibilidad (axe-core)
        include: [
            'resources/js/__tests__/**/*.test.{js,jsx}',
            'resources/js/Pages/**/__tests__/**/*.test.{js,jsx}',
        ],
        // Cobertura V8 delimitada a las vistas transaccionales del módulo de devoluciones
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html'],
            include: ['resources/js/Pages/Returns/**/*.jsx'],
            exclude: ['resources/js/Pages/Returns/Success.jsx'],
        },
    },
});
