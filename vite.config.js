/**
 * Vite Configuration for B-Strack PWA
 *
 * IMPORTANT - DOCUMENTATION MAINTENANCE:
 * When making changes to PWA configuration, build settings, or manifest, always update:
 * - SETUP_GUIDE.md (deployment and configuration instructions)
 * - Basketball_Stats_Tracker_Requirements.md (technical requirements section)
 * - ONBOARDING.html (installation instructions)
 */

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'prompt',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'masked-icon.svg'],
      manifest: {
        name: 'B-Strack',
        short_name: 'B-Strack',
        description: 'Basketball statistics tracker',
        theme_color: '#ff6b35',
        background_color: '#1a1a1a',
        display: 'standalone',
        orientation: 'any',
        icons: [
          {
            src: 'icon-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'icon-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: 'icon-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'google-fonts-cache',
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
        ]
      }
    })
  ],
  base: './',
  server: {
    proxy: {
      '/api/v1': 'http://localhost:8080'
    }
  }
})
