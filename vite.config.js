import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vite'

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
    build: {
        lib: {
            entry: resolve(__dirname, 'assets/main.js'),
            name: 'PostLockdown',
            fileName: () => `postlockdown.js`,
            cssFileName: 'postlockdown',
            formats: ['iife'],
        },
        outDir: 'view/assets'
    },
})
