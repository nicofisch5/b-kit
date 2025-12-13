<template>
  <div>
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="box-score-modal" @click.stop>
        <div class="modal-header">
          <h2>Box Score</h2>
          <button class="close-btn" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="table-container">
            <table class="box-score-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>PTS</th>
                  <th>FGM</th>
                  <th>FGA</th>
                  <th>FG%</th>
                  <th>3PM</th>
                  <th>3PA</th>
                  <th>3P%</th>
                  <th>FTM</th>
                  <th>FTA</th>
                  <th>FT%</th>
                  <th>OREB</th>
                  <th>DREB</th>
                  <th>REB</th>
                  <th>AST</th>
                  <th>STL</th>
                  <th>BLK</th>
                  <th>TO</th>
                  <th>PF</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="player in playerStats" :key="player.playerId">
                  <td>{{ player.jerseyNumber }}</td>
                  <td class="player-name-col">{{ player.name }}</td>
                  <td>{{ player.PTS }}</td>
                  <td>{{ player.FGM }}</td>
                  <td>{{ player.FGA }}</td>
                  <td>{{ player.FGP }}</td>
                  <td>{{ player.TPM }}</td>
                  <td>{{ player.TPA }}</td>
                  <td>{{ player.TPP }}</td>
                  <td>{{ player.FTM }}</td>
                  <td>{{ player.FTA }}</td>
                  <td>{{ player.FTP }}</td>
                  <td>{{ player.OREB }}</td>
                  <td>{{ player.DREB }}</td>
                  <td>{{ player.REB }}</td>
                  <td>{{ player.AST }}</td>
                  <td>{{ player.STL }}</td>
                  <td>{{ player.BLK }}</td>
                  <td>{{ player.TO }}</td>
                  <td>{{ player.PF }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { gameState } from '../store/gameStore'
import { calculatePlayerStats } from '../utils/statsCalculator'

export default {
  name: 'BoxScore',
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const showModal = ref(props.show)

    watch(() => props.show, (newVal) => {
      showModal.value = newVal
    })

    function closeModal() {
      showModal.value = false
      emit('close')
    }

    // Only compute stats when modal is visible to improve performance
    const playerStats = computed(() => {
      if (!showModal.value) return []
      return gameState.players.map(player => calculatePlayerStats(player))
    })

    return {
      showModal,
      closeModal,
      playerStats
    }
  }
}
</script>
