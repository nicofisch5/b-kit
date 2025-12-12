<template>
  <div class="quarter-selector">
    <button
      v-for="quarter in baseQuarters"
      :key="quarter"
      class="quarter-btn"
      :class="{ active: currentQuarter === quarter }"
      @click="selectQuarter(quarter)"
    >
      {{ quarter }}
    </button>

    <button
      v-for="ot in overtimeQuarters"
      :key="ot"
      class="quarter-btn"
      :class="{ active: currentQuarter === ot }"
      @click="selectQuarter(ot)"
    >
      {{ ot }}
    </button>

    <button class="quarter-btn add-ot" @click="addOvertime" v-if="canAddOvertime">
      + OT
    </button>
  </div>
</template>

<script>
import { computed } from 'vue'
import { gameState, switchQuarter } from '../store/gameStore'

export default {
  name: 'QuarterSelector',
  setup() {
    const baseQuarters = ['Q1', 'Q2', 'Q3', 'Q4']

    const currentQuarter = computed(() => gameState.currentQuarter)

    const overtimeQuarters = computed(() => {
      return gameState.quarters
        .filter(q => q.quarterName.startsWith('OT'))
        .map(q => q.quarterName)
    })

    const canAddOvertime = computed(() => {
      // Can add overtime if current quarter is Q4 or an OT
      const quarter = gameState.currentQuarter
      return quarter === 'Q4' || quarter.startsWith('OT')
    })

    function selectQuarter(quarter) {
      switchQuarter(quarter)
    }

    function addOvertime() {
      const nextOT = gameState.overtimeCount === 0
        ? 'OT'
        : `OT${gameState.overtimeCount + 1}`
      switchQuarter(nextOT)
    }

    return {
      baseQuarters,
      currentQuarter,
      overtimeQuarters,
      canAddOvertime,
      selectQuarter,
      addOvertime
    }
  }
}
</script>
