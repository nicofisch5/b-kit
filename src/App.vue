<template>
  <div id="app" class="app-container">
    <header class="app-header">
      <h1 class="app-title">B-Strack</h1>
    </header>

    <ScoreDisplay />
    <QuarterSelector />

    <div class="main-content">
      <PlayerRoster :selectedPlayerId="selectedPlayerId" @player-selected="handlePlayerSelected" />
      <StatsControlPanel @stat-clicked="handleStatClicked" />
      <QuarterLogs @action-reverted="handleActionReverted" @undo="handleUndo" />
    </div>

    <ActionBar @box-score="handleBoxScore" @save="handleSave" @export="handleExport" />

    <BoxScore :show="showBoxScore" @close="handleBoxScoreClose" />

    <PlayerSelectionModal
      v-if="showPlayerModal"
      :statType="pendingStatType"
      :statLabel="pendingStatLabel"
      :preSelectedPlayerId="selectedPlayerId"
      @player-selected="handlePlayerSelectedFromModal"
      @cancel="handleModalCancel"
    />

    <div v-if="notification" class="notification" :class="notification.type">
      {{ notification.message }}
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import ScoreDisplay from './components/ScoreDisplay.vue'
import BoxScore from './components/BoxScore.vue'
import QuarterSelector from './components/QuarterSelector.vue'
import PlayerRoster from './components/PlayerRoster.vue'
import StatsControlPanel from './components/StatsControlPanel.vue'
import PlayerSelectionModal from './components/PlayerSelectionModal.vue'
import ActionBar from './components/ActionBar.vue'
import QuarterLogs from './components/QuarterLogs.vue'
import { undoLastAction, saveGame, exportJSON, exportCSV } from './store/gameStore'

export default {
  name: 'App',
  components: {
    ScoreDisplay,
    BoxScore,
    QuarterSelector,
    PlayerRoster,
    StatsControlPanel,
    PlayerSelectionModal,
    ActionBar,
    QuarterLogs
  },
  setup() {
    const selectedPlayerId = ref(null)
    const showPlayerModal = ref(false)
    const showBoxScore = ref(false)
    const pendingStatType = ref(null)
    const pendingStatLabel = ref('')
    const notification = ref(null)

    function showNotification(message, type = 'success') {
      notification.value = { message, type }
      setTimeout(() => {
        notification.value = null
      }, 2000)
    }

    function handlePlayerSelected(playerId) {
      selectedPlayerId.value = playerId
    }

    function handleStatClicked({ statType, label }) {
      pendingStatType.value = statType
      pendingStatLabel.value = label
      showPlayerModal.value = true
    }

    function handlePlayerSelectedFromModal({ playerId, assistPlayerId }) {
      // This is handled in the modal component which calls recordStat
      showPlayerModal.value = false
      pendingStatType.value = null
      pendingStatLabel.value = ''
      showNotification('Stat recorded successfully')
    }

    function handleModalCancel() {
      showPlayerModal.value = false
      pendingStatType.value = null
      pendingStatLabel.value = ''
    }

    function handleUndo() {
      const success = undoLastAction()
      if (success) {
        showNotification('Last action undone')
      } else {
        showNotification('Nothing to undo', 'error')
      }
    }

    function handleSave() {
      const success = saveGame()
      if (success) {
        showNotification('Game saved successfully')
      } else {
        showNotification('Error saving game', 'error')
      }
    }

    function handleExport(format) {
      if (format === 'json') {
        exportJSON()
        showNotification('Game exported as JSON')
      } else if (format === 'csv') {
        exportCSV()
        showNotification('Game exported as CSV')
      }
    }

    function handleActionReverted() {
      showNotification('Action reverted successfully')
    }

    function handleBoxScore() {
      showBoxScore.value = true
    }

    function handleBoxScoreClose() {
      showBoxScore.value = false
    }

    return {
      selectedPlayerId,
      showPlayerModal,
      showBoxScore,
      pendingStatType,
      pendingStatLabel,
      notification,
      handlePlayerSelected,
      handleStatClicked,
      handlePlayerSelectedFromModal,
      handleModalCancel,
      handleUndo,
      handleSave,
      handleExport,
      handleActionReverted,
      handleBoxScore,
      handleBoxScoreClose
    }
  }
}
</script>
