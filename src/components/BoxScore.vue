<template>
  <div class="box-score-container">
    <button class="box-score-btn" @click="showModal = true">Box Score</button>

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
import { ref, computed } from 'vue'
import { gameState, StatType } from '../store/gameStore'

export default {
  name: 'BoxScore',
  setup() {
    const showModal = ref(false)

    function closeModal() {
      showModal.value = false
    }

    const playerStats = computed(() => {
      return gameState.players.map(player => {
        const stats = player.statistics

        // Count each stat type
        const twoMade = stats.filter(s => s.statType === StatType.TWO_PT_MADE).length
        const twoMiss = stats.filter(s => s.statType === StatType.TWO_PT_MISS).length
        const threeMade = stats.filter(s => s.statType === StatType.THREE_PT_MADE).length
        const threeMiss = stats.filter(s => s.statType === StatType.THREE_PT_MISS).length
        const ftMade = stats.filter(s => s.statType === StatType.FT_MADE).length
        const ftMiss = stats.filter(s => s.statType === StatType.FT_MISS).length
        const offReb = stats.filter(s => s.statType === StatType.OFF_REB).length
        const defReb = stats.filter(s => s.statType === StatType.DEF_REB).length
        const assists = stats.filter(s => s.statType === StatType.ASSIST).length
        const steals = stats.filter(s => s.statType === StatType.STEAL).length
        const blocks = stats.filter(s => s.statType === StatType.BLOCK).length
        const turnovers = stats.filter(s => s.statType === StatType.TURNOVER).length

        // Calculate derived stats
        const FGM = twoMade + threeMade
        const FGA = twoMade + twoMiss + threeMade + threeMiss
        const FGP = FGA > 0 ? ((FGM / FGA) * 100).toFixed(1) + '%' : '0.0%'

        const TPA = threeMade + threeMiss
        const TPP = TPA > 0 ? ((threeMade / TPA) * 100).toFixed(1) + '%' : '0.0%'

        const FTA = ftMade + ftMiss
        const FTP = FTA > 0 ? ((ftMade / FTA) * 100).toFixed(1) + '%' : '0.0%'

        const REB = offReb + defReb

        return {
          playerId: player.playerId,
          jerseyNumber: player.jerseyNumber,
          name: player.name,
          PTS: player.totalPoints,
          FGM: FGM,
          FGA: FGA,
          FGP: FGP,
          TPM: threeMade,
          TPA: TPA,
          TPP: TPP,
          FTM: ftMade,
          FTA: FTA,
          FTP: FTP,
          OREB: offReb,
          DREB: defReb,
          REB: REB,
          AST: assists,
          STL: steals,
          BLK: blocks,
          TO: turnovers,
          PF: player.totalFouls
        }
      })
    })

    return {
      showModal,
      closeModal,
      playerStats
    }
  }
}
</script>
