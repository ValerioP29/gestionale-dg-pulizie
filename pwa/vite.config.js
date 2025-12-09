import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

const pwaManifest = {
  name: 'DG Pulizie PWA',
  short_name: 'DG Pulizie',
  description: 'PWA mobile per DG Pulizie',
  theme_color: '#0f172a',
  background_color: '#f8fafc',
  display: 'standalone',
  start_url: '/',
  icons: [
    { src: '/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
    { src: '/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
    { src: '/mask-icon.svg', sizes: '512x512', type: 'image/svg+xml', purpose: 'any maskable' },
  ],
}

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.svg', 'apple-touch-icon.png', 'mask-icon.svg'],
      manifest: pwaManifest,
      workbox: {
        navigateFallback: '/index.html',
      },
    }),
  ],
  server: {
    port: 5174,
  },
});