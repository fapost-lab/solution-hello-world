// Temporary hand-rolled build. A Vite preset (`@fapost/vite-plugin-solution`) will replace this file and
// scripts/write-manifest.mjs; until it exists a Solution builds its bundle in library mode with Core's shared
// specifiers kept external (docs.fapost.in/extending/console-ui, "Building the bundle").
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
    plugins: [vue()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        lib: {
            entry: { home: 'resources/js/pages/Home.vue' },
            formats: ['es'],
        },
        rollupOptions: {
            // One Vue, one Inertia and one kit on the page: the console's import map supplies them.
            external: ['vue', '@inertiajs/vue3', /^@fapost\/ui(\/|$)/],
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
})
