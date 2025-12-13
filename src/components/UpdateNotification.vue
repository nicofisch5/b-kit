<template>
  <transition name="slide-down">
    <div v-if="showUpdate" class="update-notification">
      <div class="update-content">
        <span class="update-icon">🔄</span>
        <div class="update-message">
          <strong>New version available!</strong>
          <p>A new version of B-Strack is ready to install.</p>
        </div>
        <div class="update-actions">
          <button @click="updateApp" class="btn-update">
            Update Now
          </button>
          <button @click="dismissUpdate" class="btn-dismiss">
            Later
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const showUpdate = ref(false)
let updateHandler = null
let dismissTimeout = null
let refreshing = false

onMounted(() => {
  // Listen for update available event
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('controllerchange', handleControllerChange)
  }

  // Listen for custom update event from main.js
  window.addEventListener('swUpdated', handleUpdateAvailable)

  // iOS-specific: Listen for page visibility changes
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

onUnmounted(() => {
  // Clean up event listeners and timeout
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.removeEventListener('controllerchange', handleControllerChange)
  }
  window.removeEventListener('swUpdated', handleUpdateAvailable)
  document.removeEventListener('visibilitychange', handleVisibilityChange)

  if (dismissTimeout) {
    clearTimeout(dismissTimeout)
  }
})

function handleControllerChange() {
  // Prevent infinite reload loop
  if (refreshing) return
  refreshing = true

  // New service worker has taken control
  if (!window.location.hash.includes('skipReload')) {
    // Force reload from server to get new version
    window.location.reload(true)
  }
}

function handleUpdateAvailable(event) {
  showUpdate.value = true
  updateHandler = event.detail
}

function handleVisibilityChange() {
  // iOS-specific: Check for updates when app becomes visible again
  if (document.visibilityState === 'visible' && 'serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistration().then(registration => {
      if (registration) {
        registration.update()
      }
    })
  }
}

function updateApp() {
  // Prevent multiple clicks
  if (refreshing) return
  refreshing = true

  showUpdate.value = false

  if (updateHandler) {
    // Call the update handler from service worker
    updateHandler()
  } else {
    // Fallback: Force reload from server
    // iOS Safari needs true parameter for hard reload
    window.location.reload(true)
  }

  // iOS fallback: If reload doesn't work within 1 second, try again
  setTimeout(() => {
    if (!document.hidden) {
      window.location.href = window.location.href
    }
  }, 1000)
}

function dismissUpdate() {
  showUpdate.value = false

  // Clear any existing timeout
  if (dismissTimeout) {
    clearTimeout(dismissTimeout)
  }

  // Show again after 1 hour if user dismisses
  dismissTimeout = setTimeout(() => {
    // Only show if handler is still available
    if (updateHandler) {
      showUpdate.value = true
    }
  }, 60 * 60 * 1000)
}
</script>

<style scoped>
.update-notification {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  animation: slideDown 0.3s ease-out;
}

.update-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.update-icon {
  font-size: 32px;
  animation: rotate 2s linear infinite;
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.update-message {
  flex: 1;
  min-width: 200px;
}

.update-message strong {
  display: block;
  font-size: 16px;
  margin-bottom: 4px;
}

.update-message p {
  margin: 0;
  font-size: 14px;
  opacity: 0.9;
}

.update-actions {
  display: flex;
  gap: 12px;
}

.btn-update,
.btn-dismiss {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-update {
  background: white;
  color: #667eea;
}

.btn-update:hover {
  background: #f0f0f0;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-dismiss {
  background: transparent;
  color: white;
  border: 2px solid white;
}

.btn-dismiss:hover {
  background: rgba(255, 255, 255, 0.1);
}

/* Animation */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}

.slide-down-enter-from {
  transform: translateY(-100%);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}

@keyframes slideDown {
  from {
    transform: translateY(-100%);
  }
  to {
    transform: translateY(0);
  }
}

/* Mobile responsiveness */
@media (max-width: 640px) {
  .update-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .update-icon {
    font-size: 24px;
  }

  .update-message strong {
    font-size: 14px;
  }

  .update-message p {
    font-size: 13px;
  }

  .update-actions {
    width: 100%;
  }

  .btn-update,
  .btn-dismiss {
    flex: 1;
    padding: 12px 16px;
  }
}
</style>
