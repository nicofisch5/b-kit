<template>
  <div class="autosave-indicator" :class="statusClass">
    <span class="autosave-icon">{{ icon }}</span>
    <span class="autosave-text">{{ message }}</span>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const lastSaveTime = ref(null)
const isSaving = ref(false)
const timeSinceLastSave = ref('')
let intervalId = null

const statusClass = computed(() => {
  if (isSaving.value) return 'saving'
  if (!lastSaveTime.value) return 'pending'
  return 'saved'
})

const icon = computed(() => {
  if (isSaving.value) return '⏳'
  if (!lastSaveTime.value) return '💾'
  return '✓'
})

const message = computed(() => {
  if (isSaving.value) return 'Saving...'
  if (!lastSaveTime.value) return 'Not saved yet'
  return `Saved ${timeSinceLastSave.value}`
})

function updateTimeSince() {
  if (!lastSaveTime.value) {
    timeSinceLastSave.value = ''
    return
  }

  const now = Date.now()
  const diff = now - lastSaveTime.value
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)

  if (seconds < 10) {
    timeSinceLastSave.value = 'just now'
  } else if (seconds < 60) {
    timeSinceLastSave.value = `${seconds}s ago`
  } else if (minutes < 60) {
    timeSinceLastSave.value = `${minutes}m ago`
  } else {
    timeSinceLastSave.value = `${hours}h ago`
  }
}

// Listen to localStorage changes to detect saves
function checkForSaves() {
  const storageKey = 'basketballGame'
  const storageItem = localStorage.getItem(storageKey)

  if (storageItem && storageItem !== window.lastKnownStorage) {
    window.lastKnownStorage = storageItem
    lastSaveTime.value = Date.now()

    // Show saving state briefly
    isSaving.value = true
    setTimeout(() => {
      isSaving.value = false
    }, 500)
  }
}

onMounted(() => {
  // Initialize with current storage state
  window.lastKnownStorage = localStorage.getItem('basketballGame')

  // Check if there's existing data
  if (window.lastKnownStorage) {
    lastSaveTime.value = Date.now()
  }

  // Check for saves every second
  const saveCheckInterval = setInterval(checkForSaves, 1000)

  // Update time display every second
  intervalId = setInterval(updateTimeSince, 1000)

  // Initial update
  updateTimeSince()

  // Store interval IDs for cleanup
  window.saveCheckInterval = saveCheckInterval
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
  if (window.saveCheckInterval) clearInterval(window.saveCheckInterval)
})
</script>

<style scoped>
.autosave-indicator {
  position: fixed;
  top: var(--spacing-md);
  right: var(--spacing-md);
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-xs) var(--spacing-md);
  background-color: var(--bg-light);
  border: 2px solid var(--border-color);
  border-radius: var(--radius-md);
  font-size: 0.85rem;
  font-weight: 600;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-fast);
  opacity: 0.9;
}

.autosave-indicator:hover {
  opacity: 1;
  box-shadow: var(--shadow-md);
}

.autosave-icon {
  font-size: 1rem;
  display: flex;
  align-items: center;
}

.autosave-text {
  color: var(--text-muted);
  white-space: nowrap;
}

/* Status-specific styles */
.autosave-indicator.saving {
  border-color: var(--warning-color);
  background-color: rgba(244, 162, 97, 0.1);
}

.autosave-indicator.saving .autosave-text {
  color: var(--warning-color);
}

.autosave-indicator.saved {
  border-color: var(--success-color);
  background-color: rgba(45, 147, 108, 0.05);
}

.autosave-indicator.saved .autosave-text {
  color: var(--success-color);
}

.autosave-indicator.pending {
  border-color: var(--neutral-color);
}

/* Saving animation */
.autosave-indicator.saving .autosave-icon {
  animation: pulse 1s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.1);
  }
}

/* Terminal theme override */
[data-theme="terminal"] .autosave-indicator {
  border-radius: 0;
  box-shadow: none;
  border: 2px solid var(--border-color);
  background-color: var(--bg-color);
  font-family: 'Courier New', Courier, monospace;
}

[data-theme="terminal"] .autosave-indicator .autosave-text {
  color: var(--text-color);
}

[data-theme="terminal"] .autosave-indicator.saving,
[data-theme="terminal"] .autosave-indicator.saved {
  background-color: var(--bg-color);
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .autosave-indicator {
    top: var(--spacing-sm);
    right: var(--spacing-sm);
    font-size: 0.75rem;
    padding: 4px var(--spacing-sm);
  }

  .autosave-icon {
    font-size: 0.9rem;
  }
}

/* Small mobile - make it even more compact */
@media (max-width: 480px) {
  .autosave-indicator {
    padding: 3px var(--spacing-xs);
  }

  .autosave-text {
    font-size: 0.7rem;
  }
}
</style>
