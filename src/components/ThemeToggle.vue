<template>
  <div class="theme-toggle-container">
    <button class="theme-toggle-btn" @click="toggleTheme" :title="`Switch to ${currentTheme === 'modern' ? 'terminal' : 'modern'} theme`">
      <span v-if="currentTheme === 'modern'">[ Terminal Mode ]</span>
      <span v-else>[ Modern Mode ]</span>
    </button>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  name: 'ThemeToggle',
  setup() {
    const currentTheme = ref('modern')

    function loadTheme() {
      const savedTheme = localStorage.getItem('b-strack-theme') || 'modern'
      currentTheme.value = savedTheme
      document.documentElement.setAttribute('data-theme', savedTheme)
    }

    function toggleTheme() {
      const newTheme = currentTheme.value === 'modern' ? 'terminal' : 'modern'
      currentTheme.value = newTheme
      document.documentElement.setAttribute('data-theme', newTheme)
      localStorage.setItem('b-strack-theme', newTheme)
    }

    onMounted(() => {
      loadTheme()
    })

    return {
      currentTheme,
      toggleTheme
    }
  }
}
</script>

<style scoped>
.theme-toggle-container {
  display: flex;
  justify-content: center;
  padding: var(--spacing-lg);
  margin-top: var(--spacing-lg);
}

.theme-toggle-btn {
  padding: var(--spacing-sm) var(--spacing-lg);
  font-size: 12px;
  font-weight: bold;
  cursor: pointer;
  font-family: inherit;
  background-color: var(--bg-light);
  color: var(--text-light);
  border: 2px solid var(--border-color);
  transition: all 0.2s ease;
}

[data-theme="modern"] .theme-toggle-btn {
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}

[data-theme="terminal"] .theme-toggle-btn {
  border-radius: 0;
  box-shadow: none;
  font-family: 'Courier New', Courier, monospace;
}

[data-theme="modern"] .theme-toggle-btn:hover {
  background-color: var(--primary-color);
  color: white;
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

[data-theme="terminal"] .theme-toggle-btn:hover {
  background-color: var(--highlight-bg);
  color: var(--highlight-text);
}
</style>
