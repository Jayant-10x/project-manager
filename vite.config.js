import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'fs';

export default defineConfig({
    server: {
        host: '0.0.0.0',
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

