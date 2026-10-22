import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'fs';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        cors: true,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
        {
            name: 'clean-hot-on-build',
            configureServer(server) {
                const cleanup = () => {
                    if (fs.existsSync('public/hot')) {
                        try { fs.unlinkSync('public/hot'); } catch (e) {}
                    }
                };
                process.on('SIGINT', () => { cleanup(); process.exit(); });
                process.on('SIGTERM', () => { cleanup(); process.exit(); });
                process.on('exit', cleanup);
                server.httpServer?.on('close', cleanup);
            },
            buildStart() {
                if (fs.existsSync('public/hot')) {
                    try { fs.unlinkSync('public/hot'); } catch (e) {}
                }
            },
            closeBundle() {
                if (fs.existsSync('public/hot')) {
                    try { fs.unlinkSync('public/hot'); } catch (e) {}
                }
            }
        }
    ],
});

