import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/js/setup.js'],
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
      '@/components': resolve(__dirname, './resources/js/components'),
      '@/pages': resolve(__dirname, './resources/js/pages'),
      '@/services': resolve(__dirname, './resources/js/services'),
    },
  },
})
