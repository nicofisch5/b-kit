<template>
  <div class="action-bar">
    <button class="action-btn undo-btn" @click="handleUndo">
      <span class="btn-icon">↶</span>
      Undo Last
    </button>

    <button class="action-btn save-btn" @click="handleSave">
      <span class="btn-icon">💾</span>
      Save Game
    </button>

    <div class="export-dropdown">
      <button class="action-btn export-btn" @click="toggleExportMenu">
        <span class="btn-icon">📊</span>
        Export Stats
        <span class="dropdown-arrow">▼</span>
      </button>
      <div v-if="showExportMenu" class="export-menu">
        <button class="export-option" @click="handleExport('json')">
          Export as JSON
        </button>
        <button class="export-option" @click="handleExport('csv')">
          Export as CSV
        </button>
      </div>
    </div>

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
  emits: ['undo', 'save', 'export'],
  setup(props, { emit }) {
    const showExportMenu = ref(false)

    function handleUndo() {
      emit('undo')
    }

    function handleSave() {
      emit('save')
    }

    function toggleExportMenu() {
      showExportMenu.value = !showExportMenu.value
    }

    function handleExport(format) {
      emit('export', format)
      showExportMenu.value = false
    }

    function confirmReset() {
      if (confirm('Are you sure you want to start a new game? All current data will be lost.')) {
        resetGame()
        alert('New game started!')
      }
    }

    return {
      showExportMenu,
      handleUndo,
      handleSave,
      toggleExportMenu,
      handleExport,
      confirmReset
    }
  }
}
</script>
