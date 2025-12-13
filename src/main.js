import { createApp } from 'vue'
import App from './App.vue'
import './assets/main.css'
import { registerSW } from 'virtual:pwa-register'

createApp(App).mount('#app')

// Register service worker with update handling
if ('serviceWorker' in navigator) {
  const updateSW = registerSW({
    onNeedRefresh() {
      // Dispatch custom event to notify UI about available update
      const event = new CustomEvent('swUpdated', {
        detail: () => {
          updateSW(true) // Force update
        }
      })
      window.dispatchEvent(event)
    },
    onOfflineReady() {
      console.log('App ready to work offline')
    },
    onRegistered(registration) {
      console.log('Service Worker registered')

      // Check for updates every hour
      if (registration) {
        setInterval(() => {
          registration.update()
        }, 60 * 60 * 1000) // Check every hour
      }

      // iOS-specific: Check for updates when app becomes visible
      // This helps iOS PWAs that don't always trigger standard SW events
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && registration) {
          registration.update().catch(err => {
            console.log('Update check failed:', err)
          })
        }
      })

      // iOS-specific: Also check on page focus
      window.addEventListener('focus', () => {
        if (registration) {
          registration.update().catch(err => {
            console.log('Update check failed:', err)
          })
        }
      })
    },
    onRegisterError(error) {
      console.error('Service Worker registration error:', error)
    }
  })
}
