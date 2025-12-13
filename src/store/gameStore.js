/**
 * B-Strack Game Store - Central state management for basketball statistics
 *
 * IMPORTANT - DOCUMENTATION MAINTENANCE:
 * When making changes to game logic, data structures, or features, always update:
 * - SETUP_GUIDE.md (developer documentation)
 * - Basketball_Stats_Tracker_Requirements.md (technical specs and data models)
 * - ONBOARDING.html (user guide)
 *
 * These files should always reflect the current implementation.
 */

import { reactive, watch } from 'vue'
import { calculatePlayerStats } from '../utils/statsCalculator'

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

// Auto-save to localStorage (increased interval for better performance)
let autoSaveTimer = null
function scheduleAutoSave() {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(() => {
    saveGame()
  }, 60000) // 60 seconds (increased from 30s for performance)
}

// Watch for changes and schedule auto-save (optimized - removed spread operator and deep watch)
watch(
  () => gameState.history.length, // Watch history length as proxy for changes
  () => {
    scheduleAutoSave()
  }
)

// Save game to localStorage (async to avoid blocking UI)
export function saveGame() {
  try {
    // Defer to next event loop to avoid blocking UI thread
    setTimeout(() => {
      try {
        localStorage.setItem('basketballGame', JSON.stringify(gameState))
        console.log('Game saved successfully')
      } catch (e) {
        console.error('Error saving game:', e)
      }
    }, 0)
    return true
  } catch (e) {
    console.error('Error preparing game save:', e)
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

// Revert a specific stat event by eventId (optimized with filter instead of splice)
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

  if (!foundEvent || !foundQuarter) {
    console.warn('Event not found:', eventId)
    return false
  }

  // Find the player
  const player = gameState.players.find(p => p.playerId === foundEvent.playerId)
  if (!player) {
    console.warn('Player not found for event:', foundEvent.playerId)
    return false
  }

  // Check for associated assist event before removing
  const historyEntry = gameState.history.find(h => h.event.eventId === eventId)
  const assistEventId = historyEntry?.assistEvent?.eventId

  // Revert player statistics
  switch (foundEvent.statType) {
    case StatType.TWO_PT_MADE:
      player.totalPoints = Math.max(0, player.totalPoints - 2)
      break
    case StatType.THREE_PT_MADE:
      player.totalPoints = Math.max(0, player.totalPoints - 3)
      break
    case StatType.FT_MADE:
      player.totalPoints = Math.max(0, player.totalPoints - 1)
      break
    case StatType.FOUL:
      player.totalFouls = Math.max(0, player.totalFouls - 1)
      break
  }

  // Remove from player statistics (using filter for better performance than splice)
  player.statistics = player.statistics.filter(s => s.eventId !== eventId)

  // Remove from quarter statistics (using filter for better performance than splice)
  foundQuarter.statistics = foundQuarter.statistics.filter(s => s.eventId !== eventId)

  // Remove from history
  gameState.history = gameState.history.filter(h => h.event.eventId !== eventId)

  // Recursively remove assist event if it exists
  if (assistEventId) {
    revertStatEvent(assistEventId)
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

// Delete a player (minimum 5 players required)
export function deletePlayer(playerId) {
  if (gameState.players.length <= 5) {
    return { success: false, message: 'Cannot delete player. Minimum 5 players required.' }
  }

  const playerIndex = gameState.players.findIndex(p => p.playerId === playerId)
  if (playerIndex === -1) {
    return { success: false, message: 'Player not found.' }
  }

  // Remove the player
  gameState.players.splice(playerIndex, 1)

  // Remove player's statistics from all quarters
  gameState.quarters.forEach(quarter => {
    quarter.statistics = quarter.statistics.filter(stat => stat.playerId !== playerId)
  })

  // Remove player's actions from history
  gameState.history = gameState.history.filter(entry => entry.playerId !== playerId)

  saveGame()
  return { success: true, message: 'Player deleted successfully.' }
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
  const gameId = gameState.gameId.substring(0, 8)
  a.download = `b-strack-${gameId}-${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
}

// Export game data as CSV
export function exportCSV() {
  // Box score format header
  let csv = '#,Name,PTS,FGM,FGA,FG%,3PM,3PA,3P%,FTM,FTA,FT%,OREB,DREB,REB,AST,STL,BLK,TO,PF\n'

  // Add player data using shared stats calculator
  gameState.players.forEach(player => {
    const playerStats = calculatePlayerStats(player)
    csv += `${playerStats.jerseyNumber},${playerStats.name},${playerStats.PTS},${playerStats.FGM},${playerStats.FGA},${playerStats.FGP},${playerStats.TPM},${playerStats.TPA},${playerStats.TPP},${playerStats.FTM},${playerStats.FTA},${playerStats.FTP},${playerStats.OREB},${playerStats.DREB},${playerStats.REB},${playerStats.AST},${playerStats.STL},${playerStats.BLK},${playerStats.TO},${playerStats.PF}\n`
  })

  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `basketball-game-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

// Validate imported game data for security
function validateImportedGame(importedGame) {
  // Check required top-level fields
  if (!importedGame.gameId || typeof importedGame.gameId !== 'string') {
    return { valid: false, error: 'Missing or invalid gameId' }
  }
  if (!importedGame.homeTeam || typeof importedGame.homeTeam !== 'string') {
    return { valid: false, error: 'Missing or invalid homeTeam' }
  }
  if (!importedGame.oppositionTeam || typeof importedGame.oppositionTeam !== 'string') {
    return { valid: false, error: 'Missing or invalid oppositionTeam' }
  }
  if (typeof importedGame.oppositionScore !== 'number' || importedGame.oppositionScore < 0) {
    return { valid: false, error: 'Invalid oppositionScore' }
  }

  // Validate players array
  if (!Array.isArray(importedGame.players)) {
    return { valid: false, error: 'Players must be an array' }
  }
  if (importedGame.players.length === 0 || importedGame.players.length > 12) {
    return { valid: false, error: 'Player count must be between 1 and 12' }
  }

  // Validate each player
  const playerIds = new Set()
  const jerseyNumbers = new Set()
  for (const player of importedGame.players) {
    if (!player.playerId || typeof player.playerId !== 'string') {
      return { valid: false, error: 'Invalid player ID' }
    }
    if (playerIds.has(player.playerId)) {
      return { valid: false, error: 'Duplicate player ID found' }
    }
    playerIds.add(player.playerId)

    if (typeof player.jerseyNumber !== 'number' || player.jerseyNumber < 0 || player.jerseyNumber > 99) {
      return { valid: false, error: 'Jersey number must be between 0 and 99' }
    }
    if (jerseyNumbers.has(player.jerseyNumber)) {
      return { valid: false, error: 'Duplicate jersey number found' }
    }
    jerseyNumbers.add(player.jerseyNumber)

    if (!player.name || typeof player.name !== 'string' || player.name.trim().length === 0) {
      return { valid: false, error: 'Invalid player name' }
    }
    if (typeof player.totalPoints !== 'number' || player.totalPoints < 0) {
      return { valid: false, error: 'Invalid totalPoints' }
    }
    if (typeof player.totalFouls !== 'number' || player.totalFouls < 0) {
      return { valid: false, error: 'Invalid totalFouls' }
    }
    if (!Array.isArray(player.statistics)) {
      return { valid: false, error: 'Player statistics must be an array' }
    }
    if (player.statistics.length > 1000) {
      return { valid: false, error: 'Too many player statistics (max 1000 per player)' }
    }
  }

  // Validate quarters array
  if (!Array.isArray(importedGame.quarters)) {
    return { valid: false, error: 'Quarters must be an array' }
  }
  if (importedGame.quarters.length === 0) {
    return { valid: false, error: 'At least one quarter required' }
  }

  // Validate each quarter
  const quarterIds = new Set()
  const validStatTypes = Object.values(StatType)

  for (const quarter of importedGame.quarters) {
    if (!quarter.quarterId || typeof quarter.quarterId !== 'string') {
      return { valid: false, error: 'Invalid quarter ID' }
    }
    if (quarterIds.has(quarter.quarterId)) {
      return { valid: false, error: 'Duplicate quarter ID found' }
    }
    quarterIds.add(quarter.quarterId)

    if (!quarter.quarterName || typeof quarter.quarterName !== 'string') {
      return { valid: false, error: 'Invalid quarter name' }
    }
    if (!Array.isArray(quarter.statistics)) {
      return { valid: false, error: 'Quarter statistics must be an array' }
    }
    if (quarter.statistics.length > 1000) {
      return { valid: false, error: 'Too many quarter statistics (max 1000 per quarter)' }
    }

    // Validate statistics events
    for (const stat of quarter.statistics) {
      if (!stat.eventId || typeof stat.eventId !== 'string') {
        return { valid: false, error: 'Invalid event ID in statistics' }
      }
      if (!playerIds.has(stat.playerId)) {
        return { valid: false, error: 'Statistics reference non-existent player' }
      }
      if (!quarterIds.has(stat.quarterId)) {
        return { valid: false, error: 'Statistics reference invalid quarter' }
      }
      if (!validStatTypes.includes(stat.statType)) {
        return { valid: false, error: `Invalid stat type: ${stat.statType}` }
      }
      if (typeof stat.value !== 'number') {
        return { valid: false, error: 'Stat value must be a number' }
      }
    }
  }

  // Validate current quarter
  const validQuarterNames = importedGame.quarters.map(q => q.quarterName)
  if (!validQuarterNames.includes(importedGame.currentQuarter)) {
    return { valid: false, error: 'Current quarter does not exist in quarters list' }
  }

  // Validate history array
  if (!Array.isArray(importedGame.history)) {
    return { valid: false, error: 'History must be an array' }
  }
  if (importedGame.history.length > 2000) {
    return { valid: false, error: 'Too many history entries (max 2000)' }
  }

  return { valid: true }
}

// Import game from JSON
export function importGame(jsonData) {
  try {
    const importedGame = JSON.parse(jsonData)

    // Comprehensive validation
    const validation = validateImportedGame(importedGame)
    if (!validation.valid) {
      return { success: false, message: `Validation failed: ${validation.error}` }
    }

    // Only assign validated properties (whitelist approach)
    const validatedGame = {
      gameId: importedGame.gameId,
      homeTeam: importedGame.homeTeam,
      oppositionTeam: importedGame.oppositionTeam,
      date: importedGame.date,
      oppositionScore: importedGame.oppositionScore,
      quarters: importedGame.quarters,
      players: importedGame.players,
      currentQuarter: importedGame.currentQuarter,
      overtimeCount: importedGame.overtimeCount || 0,
      history: importedGame.history
    }

    Object.assign(gameState, validatedGame)
    saveGame()

    return { success: true, message: 'Game imported successfully' }
  } catch (error) {
    console.error('Error importing game:', error)
    return { success: false, message: 'Failed to parse game file. Please ensure it is valid JSON.' }
  }
}

// Reset game
export function resetGame(keepPlayers = false) {
  let players

  if (keepPlayers) {
    // Keep player names and numbers but reset their stats
    players = gameState.players.map(player => ({
      playerId: player.playerId,
      jerseyNumber: player.jerseyNumber,
      name: player.name,
      totalPoints: 0,
      totalFouls: 0,
      statistics: []
    }))
  } else {
    players = createDefaultPlayers()
  }

  const newGame = {
    gameId: generateUUID(),
    homeTeam: 'Home Team',
    oppositionTeam: 'Opposition',
    date: new Date().toISOString(),
    oppositionScore: 0,
    quarters: createDefaultQuarters(),
    players: players,
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
