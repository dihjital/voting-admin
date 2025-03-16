import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: [
                ...refreshPaths,
                'app/Http/Livewire/**',
            ],
        }),
    ],
    server: {
        host: '0.0.0.0',  // Allows access from outside the container
        port: 5173,       // Ensure it matches the Docker port
        strictPort: true,
        hmr: {
            host: 'localhost',
            clientPort: 5173,
        },
    },
});
