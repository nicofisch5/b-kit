<template>
  <div class="modal-overlay" @click.self="cancel">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ modalTitle }}</h2>
        <button class="close-btn" @click="cancel">&times;</button>
      </div>

      <div class="modal-body">
        <div v-if="!selectedScoringPlayerId">
          <p class="instruction">Select player:</p>
          <div class="player-grid">
            <button
              v-for="player in players"
              :key="player.playerId"
              class="player-select-btn"
              :class="{ 'fouled-out': player.totalFouls >= 5 && isFieldGoalMade }"
              :disabled="player.totalFouls >= 5 && isFieldGoalMade"
              @click="selectPlayer(player.playerId)"
            >
              <div class="player-number-large">#{{ player.jerseyNumber }}</div>
              <div class="player-name-small">{{ player.name }}</div>
              <div class="player-stats-small" v-if="player.totalFouls >= 5">
                <span class="fouled-out-label">FOULED OUT</span>
              </div>
            </button>
          </div>
        </div>

        <div v-if="showAssistSelection && selectedScoringPlayerId" class="assist-section-only">
          <p class="instruction">Was there an assist? (Optional)</p>
          <div class="assist-buttons">
            <button class="assist-option-btn" @click="recordWithoutAssist">No Assist</button>
          </div>
          <div class="player-grid">
            <button
              v-for="player in assistPlayers"
              :key="player.playerId"
              class="player-select-btn assist-btn"
              @click="selectAssistPlayer(player.playerId)"
            >
              <div class="player-number-large">#{{ player.jerseyNumber }}</div>
              <div class="player-name-small">{{ player.name }}</div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { gameState, StatType, recordStat } from '../store/gameStore'

export default {
  name: 'PlayerSelectionModal',
  props: {
    statType: {
      type: String,
      required: true
    },
    statLabel: {
      type: String,
      required: true
    },
    preSelectedPlayerId: {
      type: String,
      default: null
    }
  },
  emits: ['player-selected', 'cancel'],
  setup(props, { emit }) {
    const selectedScoringPlayerId = ref(null)

    // Handle pre-selected player
    if (props.preSelectedPlayerId) {
      const isFieldGoalMadeValue = props.statType === StatType.TWO_PT_MADE ||
                                     props.statType === StatType.THREE_PT_MADE ||
                                     props.statType === StatType.FT_MADE

      if (isFieldGoalMadeValue) {
        // For made shots, pre-select the player and show assist prompt
        selectedScoringPlayerId.value = props.preSelectedPlayerId
      } else {
        // For non-scoring stats, record immediately and close
        setTimeout(() => {
          recordStat(props.preSelectedPlayerId, props.statType)
          emit('player-selected', { playerId: props.preSelectedPlayerId })
        }, 0)
      }
    }

    const players = computed(() => gameState.players)

    const isFieldGoalMade = computed(() => {
      return props.statType === StatType.TWO_PT_MADE ||
             props.statType === StatType.THREE_PT_MADE ||
             props.statType === StatType.FT_MADE
    })

    const showAssistSelection = computed(() => {
      return isFieldGoalMade.value && selectedScoringPlayerId.value !== null
    })

    const assistPlayers = computed(() => {
      return gameState.players.filter(p => p.playerId !== selectedScoringPlayerId.value)
    })

    const modalTitle = computed(() => {
      return showAssistSelection.value ? 'Assist?' : props.statLabel
    })

    function selectPlayer(playerId) {
      if (isFieldGoalMade.value) {
        selectedScoringPlayerId.value = playerId
      } else {
        // For non-scoring stats, record immediately
        recordStat(playerId, props.statType)
        emit('player-selected', { playerId })
      }
    }

    function selectAssistPlayer(assistPlayerId) {
      recordStat(selectedScoringPlayerId.value, props.statType, assistPlayerId)
      emit('player-selected', {
        playerId: selectedScoringPlayerId.value,
        assistPlayerId
      })
    }

    function recordWithoutAssist() {
      recordStat(selectedScoringPlayerId.value, props.statType)
      emit('player-selected', { playerId: selectedScoringPlayerId.value })
    }

    function cancel() {
      emit('cancel')
    }

    return {
      players,
      isFieldGoalMade,
      showAssistSelection,
      selectedScoringPlayerId,
      assistPlayers,
      modalTitle,
      selectPlayer,
      selectAssistPlayer,
      recordWithoutAssist,
      cancel
    }
  }
}
</script>
