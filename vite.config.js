import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vite'

const __dirname = dirname(fileURLToPath(import.meta.url))

// Each script is its own IIFE bundle. Vite can't emit multiple IIFE entries in
// a single pass, so the build script runs once per target (see package.json),
// selecting the entry via BUILD_TARGET.
const entries = {
    postlockdown: 'assets/main.js',
    'block-editor': 'assets/block-editor.js',
}

const target = process.env.BUILD_TARGET || 'postlockdown'

export default defineConfig({
    build: {
        // Don't wipe the output of the other target's pass.
        emptyOutDir: false,
        outDir: 'view/assets',
        lib: {
            entry: resolve(__dirname, entries[target]),
            name: 'PostLockdown',
            fileName: () => `${target}.js`,
            cssFileName: 'postlockdown',
            formats: ['iife'],
        },
    },
})
