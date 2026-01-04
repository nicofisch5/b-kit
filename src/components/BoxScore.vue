<template>
  <div>
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="box-score-modal" @click.stop>
        <div class="modal-header">
          <h2>Box Score</h2>
          <div class="modal-header-actions">
            <button class="export-pdf-btn" @click="exportToPDF" title="Export to PDF">
              📄 Export PDF
            </button>
            <button class="close-btn" @click="closeModal">&times;</button>
          </div>
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
                <tr v-if="teamTotals" class="totals-row">
                  <td></td>
                  <td class="player-name-col">{{ teamTotals.name }}</td>
                  <td>{{ teamTotals.PTS }}</td>
                  <td>{{ teamTotals.FGM }}</td>
                  <td>{{ teamTotals.FGA }}</td>
                  <td>{{ teamTotals.FGP }}</td>
                  <td>{{ teamTotals.TPM }}</td>
                  <td>{{ teamTotals.TPA }}</td>
                  <td>{{ teamTotals.TPP }}</td>
                  <td>{{ teamTotals.FTM }}</td>
                  <td>{{ teamTotals.FTA }}</td>
                  <td>{{ teamTotals.FTP }}</td>
                  <td>{{ teamTotals.OREB }}</td>
                  <td>{{ teamTotals.DREB }}</td>
                  <td>{{ teamTotals.REB }}</td>
                  <td>{{ teamTotals.AST }}</td>
                  <td>{{ teamTotals.STL }}</td>
                  <td>{{ teamTotals.BLK }}</td>
                  <td>{{ teamTotals.TO }}</td>
                  <td>{{ teamTotals.PF }}</td>
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
import { calculatePlayerStats, calculateTeamTotals } from '../utils/statsCalculator'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

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

    const teamTotals = computed(() => {
      if (!showModal.value) return null
      return calculateTeamTotals(playerStats.value)
    })

    function exportToPDF() {
      const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4'
      })

      // Add title
      doc.setFontSize(18)
      doc.setFont('helvetica', 'bold')
      doc.text('B-Strack Box Score', 14, 15)

      // Add game info
      doc.setFontSize(11)
      doc.setFont('helvetica', 'normal')
      const date = new Date(gameState.date).toLocaleDateString()
      doc.text(`${gameState.homeTeam} vs ${gameState.oppositionTeam}`, 14, 23)
      doc.text(`Date: ${date}`, 14, 29)

      // Get home team total score
      const homeScore = gameState.players.reduce((sum, p) => sum + p.totalPoints, 0)
      doc.text(`Score: ${gameState.homeTeam} ${homeScore} - ${gameState.oppositionScore} ${gameState.oppositionTeam}`, 14, 35)

      // Prepare table data
      const headers = [['#', 'Name', 'PTS', 'FGM', 'FGA', 'FG%', '3PM', '3PA', '3P%', 'FTM', 'FTA', 'FT%', 'OREB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'TO', 'PF']]

      const data = playerStats.value.map(player => [
        player.jerseyNumber,
        player.name,
        player.PTS,
        player.FGM,
        player.FGA,
        player.FGP,
        player.TPM,
        player.TPA,
        player.TPP,
        player.FTM,
        player.FTA,
        player.FTP,
        player.OREB,
        player.DREB,
        player.REB,
        player.AST,
        player.STL,
        player.BLK,
        player.TO,
        player.PF
      ])

      // Add team totals row
      if (teamTotals.value) {
        data.push([
          '',
          teamTotals.value.name,
          teamTotals.value.PTS,
          teamTotals.value.FGM,
          teamTotals.value.FGA,
          teamTotals.value.FGP,
          teamTotals.value.TPM,
          teamTotals.value.TPA,
          teamTotals.value.TPP,
          teamTotals.value.FTM,
          teamTotals.value.FTA,
          teamTotals.value.FTP,
          teamTotals.value.OREB,
          teamTotals.value.DREB,
          teamTotals.value.REB,
          teamTotals.value.AST,
          teamTotals.value.STL,
          teamTotals.value.BLK,
          teamTotals.value.TO,
          teamTotals.value.PF
        ])
      }

      // Add table using autoTable
      autoTable(doc, {
        head: headers,
        body: data,
        startY: 40,
        theme: 'grid',
        headStyles: {
          fillColor: [255, 107, 53], // B-Strack orange
          textColor: 255,
          fontStyle: 'bold',
          halign: 'center'
        },
        bodyStyles: {
          halign: 'center',
          fontSize: 9
        },
        columnStyles: {
          0: { cellWidth: 10 },
          1: { cellWidth: 30, halign: 'left' }
        },
        alternateRowStyles: {
          fillColor: [245, 245, 245]
        },
        didParseCell: function(data) {
          // Style the totals row (last row)
          if (data.row.index === playerStats.value.length) {
            data.cell.styles.fontStyle = 'bold'
            data.cell.styles.fillColor = [220, 220, 220]
          }
        }
      })

      // Add footer
      const pageCount = doc.internal.pages.length - 1
      doc.setFontSize(8)
      doc.setFont('helvetica', 'italic')
      for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i)
        doc.text(
          `Generated by B-Strack - Page ${i} of ${pageCount}`,
          doc.internal.pageSize.width / 2,
          doc.internal.pageSize.height - 10,
          { align: 'center' }
        )
      }

      // Save the PDF
      const gameId = gameState.gameId.substring(0, 8)
      const filename = `b-strack-boxscore-${gameId}-${new Date().toISOString().split('T')[0]}.pdf`
      doc.save(filename)
    }

    return {
      showModal,
      closeModal,
      playerStats,
      teamTotals,
      exportToPDF
    }
  }
}
</script>

<style scoped>
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg);
  border-bottom: 2px solid var(--border-color);
}

.modal-header-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.export-pdf-btn {
  padding: var(--spacing-sm) var(--spacing-md);
  background-color: var(--success-color);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.export-pdf-btn:hover {
  background-color: #25845d;
  transform: translateY(-2px);
}

/* Terminal theme override */
[data-theme="terminal"] .export-pdf-btn {
  border-radius: 0;
  border: 2px solid var(--border-color);
  background-color: var(--bg-color);
  color: var(--text-color);
}

[data-theme="terminal"] .export-pdf-btn:hover {
  background-color: var(--highlight-bg);
  color: var(--highlight-text);
  transform: none;
}

@media (max-width: 640px) {
  .modal-header {
    flex-wrap: wrap;
    gap: var(--spacing-sm);
  }

  .export-pdf-btn {
    font-size: 0.85rem;
    padding: var(--spacing-xs) var(--spacing-sm);
  }
}

.totals-row {
  font-weight: bold;
  background-color: #dcdcdc;
  border-top: 2px solid #666;
}

.totals-row td {
  font-weight: bold;
}

[data-theme="terminal"] .totals-row {
  background-color: var(--highlight-bg);
  border-top: 2px solid var(--border-color);
}
</style>
