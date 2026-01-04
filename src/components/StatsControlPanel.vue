<template>
  <div class="stats-control-panel">
    <h2 class="panel-title">Statistics</h2>

    <div class="stats-section">
      <h3 class="section-title">Scoring</h3>
      <div class="stats-grid">
        <button
          v-for="stat in scoringStats"
          :key="stat.type"
          class="stat-btn"
          :class="stat.class"
          @click="handleStatClick(stat.type, stat.label)"
        >
          {{ stat.label }}
        </button>
      </div>
    </div>

    <div class="stats-section">
      <h3 class="section-title">Rebounding</h3>
      <div class="stats-grid">
        <button
          v-for="stat in reboundingStats"
          :key="stat.type"
          class="stat-btn"
          :class="stat.class"
          @click="handleStatClick(stat.type, stat.label)"
        >
          {{ stat.label }}
        </button>
      </div>
    </div>

    <div class="stats-section">
      <h3 class="section-title">Playmaking</h3>
      <div class="stats-grid">
        <button
          v-for="stat in playmakingStats"
          :key="stat.type"
          class="stat-btn"
          :class="stat.class"
          @click="handleStatClick(stat.type, stat.label)"
        >
          {{ stat.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { StatType } from '../store/gameStore'

export default {
  name: 'StatsControlPanel',
  emits: ['stat-clicked'],
  setup(props, { emit }) {
    const scoringStats = [
      { type: StatType.TWO_PT_MADE, label: '2PT Made', class: 'made' },
      { type: StatType.TWO_PT_MISS, label: '2PT Miss', class: 'miss' },
      { type: StatType.THREE_PT_MADE, label: '3PT Made', class: 'made' },
      { type: StatType.THREE_PT_MISS, label: '3PT Miss', class: 'miss' },
      { type: StatType.FT_MADE, label: 'FT Made', class: 'made' },
      { type: StatType.FT_MISS, label: 'FT Miss', class: 'miss' }
    ]

    const reboundingStats = [
      { type: StatType.DEF_REB, label: 'Def Reb', class: 'neutral' },
      { type: StatType.OFF_REB, label: 'Off Reb', class: 'neutral' }
    ]

    const playmakingStats = [
      { type: StatType.ASSIST, label: 'Assist', class: 'positive' },
      { type: StatType.STEAL, label: 'Steal', class: 'positive' },
      { type: StatType.BLOCK, label: 'Block', class: 'positive' },
      { type: StatType.TURNOVER, label: 'Turnover', class: 'negative' },
      { type: StatType.FOUL, label: 'Foul', class: 'negative' }
    ]

    function handleStatClick(statType, label) {
      emit('stat-clicked', { statType, label })
    }

    return {
      scoringStats,
      reboundingStats,
      playmakingStats,
      handleStatClick
    }
  }
}
</script>
