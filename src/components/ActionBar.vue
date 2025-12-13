<template>
  <div class="action-bar">
    <button class="action-btn box-score-btn" @click="handleBoxScore">
      <span class="btn-icon">📊</span>
      Box Score
    </button>

    <div class="export-dropdown">
      <button class="action-btn export-btn" @click="toggleExportMenu">
        <span class="btn-icon">📊</span>
        Export Game
        <span class="dropdown-arrow">▼</span>
      </button>
      <div v-if="showExportMenu" class="export-menu">
        <button class="export-option" @click="handleExport('json')">
          Export as JSON
        </button>
        <button class="export-option" @click="handleExport('csv')">
          Export as Stats CSV
        </button>
      </div>
    </div>

    <button class="action-btn import-btn" @click="triggerFileInput">
      <span class="btn-icon">📥</span>
      Import Game
    </button>
    <input
      ref="fileInput"
      type="file"
      accept=".json"
      @change="handleFileSelect"
      style="display: none"
    />

    <button class="action-btn reset-btn" @click="confirmReset">
      <span class="btn-icon">🔄</span>
      New Game
    </button>
  </div>
</template>

<script>
import { ref } from 'vue'
import { resetGame } from '../store/gameStore'

export default {
  name: 'ActionBar',
  emits: ['box-score', 'export', 'import'],
  setup(props, { emit }) {
    const showExportMenu = ref(false)
    const fileInput = ref(null)

    function handleBoxScore() {
      emit('box-score')
    }

    function toggleExportMenu() {
      showExportMenu.value = !showExportMenu.value
    }

    function handleExport(format) {
      emit('export', format)
      showExportMenu.value = false
    }

    function triggerFileInput() {
      fileInput.value.click()
    }

    function handleFileSelect(event) {
      const file = event.target.files[0]
      if (!file) return

      // Security validation: file size limit (5MB)
      const MAX_FILE_SIZE = 5 * 1024 * 1024 // 5MB in bytes
      if (file.size > MAX_FILE_SIZE) {
        alert('File too large. Maximum size is 5MB.')
        event.target.value = ''
        return
      }

      // Security validation: MIME type
      if (file.type && file.type !== 'application/json') {
        alert('Invalid file type. Please select a JSON file.')
        event.target.value = ''
        return
      }

      // Security validation: file extension
      if (!file.name.endsWith('.json')) {
        alert('Invalid file extension. Please select a .json file.')
        event.target.value = ''
        return
      }

      const reader = new FileReader()
      reader.onload = (e) => {
        const jsonData = e.target.result
        emit('import', jsonData)
        // Reset file input so same file can be selected again
        event.target.value = ''
      }
      reader.onerror = () => {
        alert('Error reading file. Please try again.')
        event.target.value = ''
      }

      try {
        reader.readAsText(file)
      } catch (error) {
        alert('Failed to read file.')
        event.target.value = ''
      }
    }

    function confirmReset() {
      if (confirm('Are you sure you want to start a new game? All statistics will be reset.')) {
        // Ask if they want to keep the current players
        const keepPlayers = confirm('Do you want to keep the current player names and numbers?\n\nClick OK to keep players, or Cancel to reset to default players.')
        resetGame(keepPlayers)
        alert('New game started!')
      }
    }

    return {
      showExportMenu,
      fileInput,
      handleBoxScore,
      toggleExportMenu,
      handleExport,
      triggerFileInput,
      handleFileSelect,
      confirmReset
    }
  }
}
</script>
