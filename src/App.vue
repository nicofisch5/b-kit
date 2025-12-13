<!--
  B-Strack - Basketball Statistics Tracker

  IMPORTANT - DOCUMENTATION MAINTENANCE:
  When making changes to this app, always update:
  - SETUP_GUIDE.md (developer documentation)
  - Basketball_Stats_Tracker_Requirements.md (technical specs)
  - ONBOARDING.html (user guide)

  These files should always reflect the current implementation.
-->
<template>
  <div id="app" class="app-container">
    <UpdateNotification />

    <header class="app-header">
      <img src="/b-Strack_logo-t.png" alt="B-Strack Logo" class="app-logo" />
      <h1 class="app-title">B-Strack</h1>
    </header>

    <ScoreDisplay />
    <QuarterSelector />

    <div class="main-content">
      <PlayerRoster :selectedPlayerId="selectedPlayerId" @player-selected="handlePlayerSelected" />
      <StatsControlPanel @stat-clicked="handleStatClicked" />
      <QuarterLogs @action-reverted="handleActionReverted" @undo="handleUndo" />
    </div>

    <ActionBar @box-score="handleBoxScore" @export="handleExport" @import="handleImport" />

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

    <ThemeToggle />

    <footer class="app-footer">
      <a href="/ONBOARDING.html" target="_blank" class="footer-link">
        <span class="footer-icon">📖</span>
        User Guide & Help
      </a>
      <span class="footer-version">v1.1.0</span>
    </footer>
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
import ThemeToggle from './components/ThemeToggle.vue'
import UpdateNotification from './components/UpdateNotification.vue'
import { undoLastAction, exportJSON, exportCSV, importGame, gameState, StatType } from './store/gameStore'

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
    QuarterLogs,
    ThemeToggle,
    UpdateNotification
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
      }, 4000)
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
      const player = gameState.players.find(p => p.playerId === playerId)

      // Security: Validate player exists
      if (!player) {
        console.error('Player not found:', playerId)
        showNotification('Error: Player not found', 'error')
        showPlayerModal.value = false
        return
      }

      const assistPlayer = assistPlayerId ? gameState.players.find(p => p.playerId === assistPlayerId) : null

      // Security: Validate assist player exists if assistPlayerId provided
      if (assistPlayerId && !assistPlayer) {
        console.error('Assist player not found:', assistPlayerId)
        showNotification('Error: Assist player not found', 'error')
        showPlayerModal.value = false
        return
      }

      let message = ''
      const playerInfo = `#${player.jerseyNumber} ${player.name}`

      switch (pendingStatType.value) {
        case StatType.TWO_PT_MADE:
          message = `2pts from ${playerInfo}`
          break
        case StatType.THREE_PT_MADE:
          message = `3pts from ${playerInfo}`
          break
        case StatType.FT_MADE:
          message = `1pt from ${playerInfo}`
          break
        case StatType.TWO_PT_MISS:
          message = `2pts miss from ${playerInfo}`
          break
        case StatType.THREE_PT_MISS:
          message = `3pts miss from ${playerInfo}`
          break
        case StatType.FT_MISS:
          message = `Free throw miss from ${playerInfo}`
          break
        case StatType.OFF_REB:
          message = `Offensive rebound from ${playerInfo}`
          break
        case StatType.DEF_REB:
          message = `Defensive rebound from ${playerInfo}`
          break
        case StatType.STEAL:
          message = `Steal from ${playerInfo}`
          break
        case StatType.BLOCK:
          message = `Block from ${playerInfo}`
          break
        case StatType.FOUL:
          message = `Foul from ${playerInfo}`
          break
        case StatType.TURNOVER:
          message = `Turnover from ${playerInfo}`
          break
        default:
          message = `Stat from ${playerInfo}`
      }

      if (assistPlayer) {
        message += `, assist from #${assistPlayer.jerseyNumber} ${assistPlayer.name}`
      }

      showPlayerModal.value = false
      pendingStatType.value = null
      pendingStatLabel.value = ''
      selectedPlayerId.value = null
      showNotification(message)
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

    function handleExport(format) {
      if (format === 'json') {
        exportJSON()
        showNotification('Game exported as JSON')
      } else if (format === 'csv') {
        exportCSV()
        showNotification('Game exported as CSV')
      }
    }

    function handleImport(jsonData) {
      const result = importGame(jsonData)
      if (result.success) {
        showNotification(result.message)
        // Refresh the page state by clearing any selected player
        selectedPlayerId.value = null
      } else {
        showNotification(result.message, 'error')
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
      handleExport,
      handleImport,
      handleActionReverted,
      handleBoxScore,
      handleBoxScoreClose
    }
  }
}
</script>
