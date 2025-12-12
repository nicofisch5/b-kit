<template>
  <div class="score-display">
    <div class="score-box home-score">
      <div class="team-label">Home Team</div>
      <div class="score">{{ homeScore }}</div>
    </div>

    <div class="score-separator">-</div>

    <div class="score-box opposition-score">
      <div class="team-label">Opposition</div>
      <div class="score">{{ oppositionScoreLocal }}</div>
      <div class="score-controls">
        <button class="score-btn" @click="decrementOpposition">-</button>
        <button class="score-btn" @click="incrementOpposition">+</button>
      </div>
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
      oppositionScoreLocal,
      incrementOpposition,
      decrementOpposition,
      updateOpposition
    }
  }
}
</script>
