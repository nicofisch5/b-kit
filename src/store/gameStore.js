import { reactive, watch } from 'vue'

// Utility function to generate UUID
function generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    const r = Math.random() * 16 | 0
    const v = c === 'x' ? r : (r & 0x3 | 0x8)
    return v.toString(16)
  })
}

// Stat type enumerations
export const StatType = {
  TWO_PT_MADE: 'TWO_PT_MADE',
  TWO_PT_MISS: 'TWO_PT_MISS',
  THREE_PT_MADE: 'THREE_PT_MADE',
  THREE_PT_MISS: 'THREE_PT_MISS',
  FT_MADE: 'FT_MADE',
  FT_MISS: 'FT_MISS',
  OFF_REB: 'OFF_REB',
  DEF_REB: 'DEF_REB',
  ASSIST: 'ASSIST',
  STEAL: 'STEAL',
  BLOCK: 'BLOCK',
  FOUL: 'FOUL',
  TURNOVER: 'TURNOVER'
}

// Initialize default players (12 players)
function createDefaultPlayers() {
  const playerNames = ['Player A', 'Player B', 'Player C', 'Player D', 'Player E', 'Player F',
                       'Player G', 'Player H', 'Player I', 'Player J', 'Player K', 'Player L']
  return playerNames.map((name, index) => ({
    playerId: generateUUID(),
    jerseyNumber: (index + 1) * 4,
    name: name,
    totalPoints: 0,
    totalFouls: 0,
    statistics: []
  }))
}

// Initialize default quarters
function createDefaultQuarters() {
  return ['Q1', 'Q2', 'Q3', 'Q4'].map(quarter => ({
    quarterId: generateUUID(),
    quarterName: quarter,
    statistics: []
  }))
}

// Load game from localStorage or create new game
function loadGame() {
  const savedGame = localStorage.getItem('basketballGame')
  if (savedGame) {
    try {
      return JSON.parse(savedGame)
    } catch (e) {
      console.error('Error loading saved game:', e)
    }
  }

  return {
    gameId: generateUUID(),
    homeTeam: 'Home Team',
    oppositionTeam: 'Opposition',
    date: new Date().toISOString(),
    oppositionScore: 0,
    quarters: createDefaultQuarters(),
    players: createDefaultPlayers(),
    currentQuarter: 'Q1',
    overtimeCount: 0,
    history: []
  }
}

// Create reactive game state
export const gameState = reactive(loadGame())

// Auto-save to localStorage
let autoSaveTimer = null
function scheduleAutoSave() {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(() => {
    saveGame()
  }, 30000) // 30 seconds
}

// Watch for changes and schedule auto-save
watch(
  () => ({ ...gameState }),
  () => {
    scheduleAutoSave()
  },
  { deep: true }
)

// Save game to localStorage
export function saveGame() {
  try {
    localStorage.setItem('basketballGame', JSON.stringify(gameState))
    console.log('Game saved successfully')
    return true
  } catch (e) {
    console.error('Error saving game:', e)
    return false
  }
}

// Get current quarter object
export function getCurrentQuarter() {
  return gameState.quarters.find(q => q.quarterName === gameState.currentQuarter)
}

// Switch quarter
export function switchQuarter(quarterName) {
  // Add new overtime quarter if needed
  if (quarterName.startsWith('OT')) {
    const overtimeNum = quarterName === 'OT' ? 1 : parseInt(quarterName.replace('OT', ''))
    const existingOT = gameState.quarters.find(q => q.quarterName === quarterName)

    if (!existingOT) {
      gameState.quarters.push({
        quarterId: generateUUID(),
        quarterName: quarterName,
        statistics: []
      })
      if (overtimeNum > gameState.overtimeCount) {
        gameState.overtimeCount = overtimeNum
      }
    }
  }

  gameState.currentQuarter = quarterName
  saveGame()
}

// Record a stat event
export function recordStat(playerId, statType, assistPlayerId = null) {
  const player = gameState.players.find(p => p.playerId === playerId)
  if (!player) return false

  const quarter = getCurrentQuarter()
  if (!quarter) return false

  const event = {
    eventId: generateUUID(),
    playerId: playerId,
    quarterId: quarter.quarterId,
    timestamp: new Date().toISOString(),
    statType: statType,
    value: 1
  }

  // Update player statistics
  switch (statType) {
    case StatType.TWO_PT_MADE:
      player.totalPoints += 2
      break
    case StatType.THREE_PT_MADE:
      player.totalPoints += 3
      break
    case StatType.FT_MADE:
      player.totalPoints += 1
      break
    case StatType.FOUL:
      player.totalFouls += 1
      break
  }

  // Add to player statistics
  player.statistics.push(event)

  // Add to quarter statistics
  quarter.statistics.push(event)

  // Add to history for undo
  const historyEntry = {
    event: event,
    playerId: playerId
  }

  // Record assist if provided
  if (assistPlayerId && (statType === StatType.TWO_PT_MADE || statType === StatType.THREE_PT_MADE || statType === StatType.FT_MADE)) {
    const assistEvent = recordStat(assistPlayerId, StatType.ASSIST)
    if (assistEvent) {
      historyEntry.assistEvent = assistEvent
      historyEntry.assistPlayerId = assistPlayerId
    }
  }

  gameState.history.push(historyEntry)

  saveGame()
  return event
}

// Undo last action
export function undoLastAction() {
  if (gameState.history.length === 0) return false

  const lastEntry = gameState.history.pop()
  const { event, playerId, assistEvent, assistPlayerId } = lastEntry

  // Find player and quarter
  const player = gameState.players.find(p => p.playerId === playerId)
  const quarter = gameState.quarters.find(q => q.quarterId === event.quarterId)

  if (!player || !quarter) return false

  // Revert player statistics
  switch (event.statType) {
    case StatType.TWO_PT_MADE:
      player.totalPoints -= 2
      break
    case StatType.THREE_PT_MADE:
      player.totalPoints -= 3
      break
    case StatType.FT_MADE:
      player.totalPoints -= 1
      break
    case StatType.FOUL:
      player.totalFouls -= 1
      break
  }

  // Remove from player statistics
  const playerStatIndex = player.statistics.findIndex(s => s.eventId === event.eventId)
  if (playerStatIndex !== -1) {
    player.statistics.splice(playerStatIndex, 1)
  }

  // Remove from quarter statistics
  const quarterStatIndex = quarter.statistics.findIndex(s => s.eventId === event.eventId)
  if (quarterStatIndex !== -1) {
    quarter.statistics.splice(quarterStatIndex, 1)
  }

  // Undo assist if present
  if (assistEvent && assistPlayerId) {
    const assistPlayer = gameState.players.find(p => p.playerId === assistPlayerId)
    const assistQuarter = gameState.quarters.find(q => q.quarterId === assistEvent.quarterId)

    if (assistPlayer && assistQuarter) {
      const assistPlayerStatIndex = assistPlayer.statistics.findIndex(s => s.eventId === assistEvent.eventId)
      if (assistPlayerStatIndex !== -1) {
        assistPlayer.statistics.splice(assistPlayerStatIndex, 1)
      }

      const assistQuarterStatIndex = assistQuarter.statistics.findIndex(s => s.eventId === assistEvent.eventId)
      if (assistQuarterStatIndex !== -1) {
        assistQuarter.statistics.splice(assistQuarterStatIndex, 1)
      }
    }
  }

  saveGame()
  return true
}

// Revert a specific stat event by eventId
export function revertStatEvent(eventId) {
  // Find the event in any quarter
  let foundEvent = null
  let foundQuarter = null

  for (const quarter of gameState.quarters) {
    const event = quarter.statistics.find(s => s.eventId === eventId)
    if (event) {
      foundEvent = event
      foundQuarter = quarter
      break
    }
  }

  if (!foundEvent || !foundQuarter) return false

  // Find the player
  const player = gameState.players.find(p => p.playerId === foundEvent.playerId)
  if (!player) return false

  // Revert player statistics
  switch (foundEvent.statType) {
    case StatType.TWO_PT_MADE:
      player.totalPoints -= 2
      break
    case StatType.THREE_PT_MADE:
      player.totalPoints -= 3
      break
    case StatType.FT_MADE:
      player.totalPoints -= 1
      break
    case StatType.FOUL:
      player.totalFouls -= 1
      break
  }

  // Remove from player statistics
  const playerStatIndex = player.statistics.findIndex(s => s.eventId === eventId)
  if (playerStatIndex !== -1) {
    player.statistics.splice(playerStatIndex, 1)
  }

  // Remove from quarter statistics
  const quarterStatIndex = foundQuarter.statistics.findIndex(s => s.eventId === eventId)
  if (quarterStatIndex !== -1) {
    foundQuarter.statistics.splice(quarterStatIndex, 1)
  }

  // Remove from history if present
  const historyIndex = gameState.history.findIndex(h => h.event.eventId === eventId)
  if (historyIndex !== -1) {
    // Check if there's an associated assist to also remove
    const historyEntry = gameState.history[historyIndex]
    if (historyEntry.assistEvent) {
      revertStatEvent(historyEntry.assistEvent.eventId)
    }
    gameState.history.splice(historyIndex, 1)
  }

  saveGame()
  return true
}

// Update opposition score
export function updateOppositionScore(score) {
  gameState.oppositionScore = Math.max(0, score)
  saveGame()
}

// Update player information
export function updatePlayer(playerId, updates) {
  const player = gameState.players.find(p => p.playerId === playerId)
  if (!player) return false

  if (updates.name !== undefined) {
    player.name = updates.name
  }
  if (updates.jerseyNumber !== undefined) {
    player.jerseyNumber = parseInt(updates.jerseyNumber)
  }

  saveGame()
  return true
}

// Calculate total home score
export function getTotalHomeScore() {
  return gameState.players.reduce((sum, player) => sum + player.totalPoints, 0)
}

// Export game data as JSON
export function exportJSON() {
  const data = JSON.stringify(gameState, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `basketball-game-${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
}

// Export game data as CSV
export function exportCSV() {
  let csv = 'Player,Jersey Number,Total Points,Total Fouls'

  // Add quarter columns for each stat type
  const statTypes = Object.keys(StatType)
  gameState.quarters.forEach(quarter => {
    statTypes.forEach(type => {
      csv += `,${quarter.quarterName} ${type.replace(/_/g, ' ')}`
    })
  })
  csv += '\n'

  // Add player data
  gameState.players.forEach(player => {
    csv += `${player.name},${player.jerseyNumber},${player.totalPoints},${player.totalFouls}`

    gameState.quarters.forEach(quarter => {
      statTypes.forEach(type => {
        const count = player.statistics.filter(s =>
          s.quarterId === quarter.quarterId && s.statType === StatType[type]
        ).length
        csv += `,${count}`
      })
    })
    csv += '\n'
  })

  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `basketball-game-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

// Reset game
export function resetGame() {
  const newGame = {
    gameId: generateUUID(),
    homeTeam: 'Home Team',
    oppositionTeam: 'Opposition',
    date: new Date().toISOString(),
    oppositionScore: 0,
    quarters: createDefaultQuarters(),
    players: createDefaultPlayers(),
    currentQuarter: 'Q1',
    overtimeCount: 0,
    history: []
  }

  Object.assign(gameState, newGame)
  saveGame()
}

// Get player statistics for current quarter
export function getPlayerQuarterStats(playerId) {
  const currentQuarter = getCurrentQuarter()
  if (!currentQuarter) return []

  return gameState.players
    .find(p => p.playerId === playerId)
    ?.statistics.filter(s => s.quarterId === currentQuarter.quarterId) || []
}
