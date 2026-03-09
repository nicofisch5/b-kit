/**
 * apiSync.js - Fire-and-forget sync layer to MariaDB via Symfony API.
 *
 * localStorage remains the source of truth and primary storage.
 * All functions here are best-effort: errors are logged but never thrown.
 * The backend is seeded with local UUIDs so all IDs stay consistent.
 */

const BASE_URL = '/api/v1'

async function call(method, path, body = null) {
  try {
    const opts = { method, headers: { 'Content-Type': 'application/json' } }
    if (body) opts.body = JSON.stringify(body)
    const res = await fetch(BASE_URL + path, opts)
    if (!res.ok) {
      const err = await res.json().catch(() => ({}))
      console.warn('[API Sync] Error', method, path, res.status, err)
    }
    return res.ok
  } catch (e) {
    console.warn('[API Sync] Network error', method, path, e.message)
    return false
  }
}

/**
 * Build an import payload that preserves all local UUIDs.
 * This lets us reuse playerId, quarterId, eventId across localStorage ↔ backend.
 */
function buildImportPayload(gs) {
  const players = gs.players.map((p, i) => ({
    id: p.playerId,
    name: p.name,
    jerseyNumber: p.jerseyNumber,
    sortOrder: i,
  }))

  const quarters = gs.quarters.map((q, i) => ({
    id: q.quarterId,
    quarterName: q.quarterName,
    sortOrder: i,
  }))

  // Collect all unique events across all quarters
  const seenIds = new Set()
  const events = []
  gs.quarters.forEach(q => {
    q.statistics.forEach(s => {
      if (!seenIds.has(s.eventId)) {
        seenIds.add(s.eventId)
        events.push({
          id: s.eventId,
          playerId: s.playerId,
          quarterId: s.quarterId,
          statType: s.statType,
          timestamp: s.timestamp,
        })
      }
    })
  })

  const history = gs.history.map((h, i) => ({
    eventId: h.event.eventId,
    playerId: h.playerId,
    assistEventId: h.assistEvent?.eventId || null,
    assistPlayerId: h.assistPlayerId || null,
    sequence: i + 1,
  }))

  return {
    game: {
      id: gs.gameId,
      homeTeam: gs.homeTeam,
      oppositionTeam: gs.oppositionTeam,
      date: gs.date,
      oppositionScore: gs.oppositionScore,
      currentQuarter: gs.currentQuarter,
      overtimeCount: gs.overtimeCount,
      status: 'in_progress',
    },
    players,
    quarters,
    events,
    history,
  }
}

/**
 * On game load: check if the game exists in the backend.
 * If not, do a full import (preserving all local UUIDs).
 */
export async function ensureGame(gameState) {
  const exists = await call('GET', `/games/${gameState.gameId}`)
  if (exists) return
  await call('POST', '/games/import', buildImportPayload(gameState))
}

/**
 * Record a stat event. assistPlayerId is optional.
 * The backend will auto-create the ASSIST event if assistPlayerId is given.
 * NOTE: Do not call this for the internal recursive assist — pass assistPlayerId instead.
 */
export function recordStat(gameId, playerId, quarterId, statType, assistPlayerId = null) {
  call('POST', `/games/${gameId}/events`, {
    playerId,
    quarterId,
    statType,
    timestamp: new Date().toISOString(),
    assistPlayerId: assistPlayerId || null,
  })
}

/**
 * Undo the last action (pops highest sequence from game_history).
 */
export function undoLast(gameId) {
  call('POST', `/games/${gameId}/undo`)
}

/**
 * Revert a specific stat event by its ID.
 */
export function revertEvent(gameId, eventId) {
  call('DELETE', `/games/${gameId}/events/${eventId}`)
}

/**
 * Update opposition score.
 */
export function updateScore(gameId, score) {
  call('PUT', `/games/${gameId}`, { oppositionScore: score })
}

/**
 * Update player name and/or jersey number.
 */
export function updatePlayer(gameId, playerId, updates) {
  call('PUT', `/games/${gameId}/players/${playerId}`, updates)
}

/**
 * Remove a player from a game.
 */
export function deletePlayer(gameId, playerId) {
  call('DELETE', `/games/${gameId}/players/${playerId}`)
}

/**
 * Add an overtime quarter (backend auto-names it OT, OT2, ...).
 */
export function addOvertimeQuarter(gameId) {
  call('POST', `/games/${gameId}/quarters`)
}

/**
 * Update current quarter on the game record.
 */
export function updateCurrentQuarter(gameId, quarterName) {
  call('PUT', `/games/${gameId}`, { currentQuarter: quarterName })
}

/**
 * Full import of a game (used after resetGame or importGame).
 */
export function syncFullGame(gameState) {
  call('POST', '/games/import', buildImportPayload(gameState))
}
