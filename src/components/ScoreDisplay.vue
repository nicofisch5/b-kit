<template>
  <div class="score-display-compact">
    <div class="score-item-compact">
      <span class="team-label-compact">{{ homeTeamName }}</span>
      <span class="score-value-compact">{{ homeScore }}</span>
    </div>

    <span class="score-separator-compact">-</span>

    <div class="score-item-compact">
      <span class="score-value-compact">{{ oppositionScoreLocal }}</span>
      <span class="team-label-compact">{{ oppositionTeamName }}</span>
    </div>

    <div class="score-controls-compact">
      <button class="score-btn-compact" @click="decrementOpposition" title="Decrease opposition score">-</button>
      <button class="score-btn-compact" @click="incrementOpposition" title="Increase opposition score">+</button>
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
