<template>
  <div class="quarter-logs">
    <div class="logs-header">
      <div class="logs-header-left">
        <h2 class="logs-title">{{ currentQuarterName }} Logs</h2>
        <span class="logs-count">{{ logs.length }} action{{ logs.length !== 1 ? 's' : '' }}</span>
      </div>
      <button class="undo-last-btn" @click="handleUndo" title="Undo last action">
        <span class="btn-icon">↶</span>
        Undo Last
      </button>
    </div>
    <div class="logs-list">
      <div v-if="logs.length === 0" class="no-logs">
        No actions recorded in this quarter yet
      </div>
      <div
        v-for="log in logs"
        :key="log.eventId"
        class="log-item"
      >
        <div class="log-info">
          <div class="log-player">#{{ log.jerseyNumber }} {{ log.playerName }}</div>
          <div class="log-details">
            <span class="log-stat" :class="getStatClass(log.statType)">{{ formatStatType(log.statType) }}</span>
            <span class="log-time">{{ formatTime(log.timestamp) }}</span>
          </div>
        </div>
        <button
          class="log-revert-btn"
          @click="revertAction(log.eventId)"
          title="Revert this action"
        >
          ↶
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { gameState, getCurrentQuarter, revertStatEvent, undoLastAction, StatType } from '../store/gameStore'

export default {
  name: 'QuarterLogs',
  emits: ['action-reverted', 'undo'],
  setup(props, { emit }) {
    const currentQuarterName = computed(() => gameState.currentQuarter)

    const logs = computed(() => {
      const quarter = getCurrentQuarter()
      if (!quarter) return []

      // Get all events from current quarter
      return quarter.statistics.map(stat => {
        const player = gameState.players.find(p => p.playerId === stat.playerId)
        return {
          eventId: stat.eventId,
          playerId: stat.playerId,
          playerName: player ? player.name : 'Unknown',
          jerseyNumber: player ? player.jerseyNumber : '?',
          statType: stat.statType,
          timestamp: stat.timestamp
        }
      }).reverse() // Most recent first
    })

    function formatStatType(statType) {
      const labels = {
        [StatType.TWO_PT_MADE]: '2PT Made',
        [StatType.TWO_PT_MISS]: '2PT Miss',
        [StatType.THREE_PT_MADE]: '3PT Made',
        [StatType.THREE_PT_MISS]: '3PT Miss',
        [StatType.FT_MADE]: 'FT Made',
        [StatType.FT_MISS]: 'FT Miss',
        [StatType.OFF_REB]: 'Off Rebound',
        [StatType.DEF_REB]: 'Def Rebound',
        [StatType.ASSIST]: 'Assist',
        [StatType.STEAL]: 'Steal',
        [StatType.BLOCK]: 'Block',
        [StatType.FOUL]: 'Foul',
        [StatType.TURNOVER]: 'Turnover'
      }
      return labels[statType] || statType
    }

    function getStatClass(statType) {
      if (statType.includes('MADE')) return 'stat-made'
      if (statType.includes('MISS')) return 'stat-miss'
      if (statType === StatType.FOUL || statType === StatType.TURNOVER) return 'stat-negative'
      return 'stat-neutral'
    }

    function formatTime(timestamp) {
      const date = new Date(timestamp)
      return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
    }

    function revertAction(eventId) {
      if (confirm('Are you sure you want to revert this action?')) {
        const success = revertStatEvent(eventId)
        if (success) {
          emit('action-reverted')
        }
      }
    }

    function handleUndo() {
      emit('undo')
    }

    return {
      currentQuarterName,
      logs,
      formatStatType,
      getStatClass,
      formatTime,
      revertAction,
      handleUndo
    }
  }
}
</script>
