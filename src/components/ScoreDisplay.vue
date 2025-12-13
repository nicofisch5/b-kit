<template>
  <div class="score-display-compact">
    <div class="score-item-compact">
      <span class="team-label-compact">{{ homeTeamName }}</span>
      <span class="score-value-compact">{{ homeScore }}</span>
    </div>

    <span class="score-separator-compact">-</span>

    <div class="score-item-compact score-item-opponent">
      <span class="score-value-compact">{{ oppositionScoreLocal }}</span>

      <!-- Buttons between score and label (stacked vertically) -->
      <div class="score-controls-compact score-controls-stacked">
        <button class="score-btn-compact score-btn-stacked" @click="incrementOpposition" title="Increase opposition score">+</button>
        <button class="score-btn-compact score-btn-stacked" @click="decrementOpposition" title="Decrease opposition score">-</button>
      </div>

      <span class="team-label-compact">{{ oppositionTeamName }}</span>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { gameState, getTotalHomeScore, updateOppositionScore } from '../store/gameStore'

export default {
  name: 'ScoreDisplay',
  setup() {
    const oppositionScoreLocal = ref(gameState.oppositionScore)

    const homeScore = computed(() => getTotalHomeScore())
    const homeTeamName = computed(() => gameState.homeTeam)
    const oppositionTeamName = computed(() => gameState.oppositionTeam)

    watch(
      () => gameState.oppositionScore,
      (newScore) => {
        oppositionScoreLocal.value = newScore
      }
    )

    function incrementOpposition() {
      oppositionScoreLocal.value++
      updateOppositionScore(oppositionScoreLocal.value)
    }

    function decrementOpposition() {
      if (oppositionScoreLocal.value > 0) {
        oppositionScoreLocal.value--
        updateOppositionScore(oppositionScoreLocal.value)
      }
    }

    function updateOpposition() {
      updateOppositionScore(oppositionScoreLocal.value)
    }

    return {
      homeScore,
      homeTeamName,
      oppositionTeamName,
      oppositionScoreLocal,
      incrementOpposition,
      decrementOpposition,
      updateOpposition
    }
  }
}
</script>
