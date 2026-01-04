import { StatType } from '../store/gameStore'

/**
 * Calculate comprehensive player statistics from raw stat events
 * @param {Object} player - Player object with statistics array
 * @returns {Object} Calculated stats including FG%, 3P%, FT%, etc.
 */
export function calculatePlayerStats(player) {
  const stats = player.statistics

  // Use a single pass to count all stat types (optimization)
  const statCounts = {
    twoMade: 0,
    twoMiss: 0,
    threeMade: 0,
    threeMiss: 0,
    ftMade: 0,
    ftMiss: 0,
    offReb: 0,
    defReb: 0,
    assists: 0,
    steals: 0,
    blocks: 0,
    turnovers: 0
  }

  // Single pass through stats array instead of 12 separate filters
  for (const stat of stats) {
    switch (stat.statType) {
      case StatType.TWO_PT_MADE:
        statCounts.twoMade++
        break
      case StatType.TWO_PT_MISS:
        statCounts.twoMiss++
        break
      case StatType.THREE_PT_MADE:
        statCounts.threeMade++
        break
      case StatType.THREE_PT_MISS:
        statCounts.threeMiss++
        break
      case StatType.FT_MADE:
        statCounts.ftMade++
        break
      case StatType.FT_MISS:
        statCounts.ftMiss++
        break
      case StatType.OFF_REB:
        statCounts.offReb++
        break
      case StatType.DEF_REB:
        statCounts.defReb++
        break
      case StatType.ASSIST:
        statCounts.assists++
        break
      case StatType.STEAL:
        statCounts.steals++
        break
      case StatType.BLOCK:
        statCounts.blocks++
        break
      case StatType.TURNOVER:
        statCounts.turnovers++
        break
    }
  }

  // Calculate derived stats
  const FGM = statCounts.twoMade + statCounts.threeMade
  const FGA = statCounts.twoMade + statCounts.twoMiss + statCounts.threeMade + statCounts.threeMiss
  const FGP = FGA > 0 ? ((FGM / FGA) * 100).toFixed(1) + '%' : '0.0%'

  const TPA = statCounts.threeMade + statCounts.threeMiss
  const TPP = TPA > 0 ? ((statCounts.threeMade / TPA) * 100).toFixed(1) + '%' : '0.0%'

  const FTA = statCounts.ftMade + statCounts.ftMiss
  const FTP = FTA > 0 ? ((statCounts.ftMade / FTA) * 100).toFixed(1) + '%' : '0.0%'

  const REB = statCounts.offReb + statCounts.defReb

  return {
    playerId: player.playerId,
    jerseyNumber: player.jerseyNumber,
    name: player.name,
    PTS: player.totalPoints,
    FGM: FGM,
    FGA: FGA,
    FGP: FGP,
    TPM: statCounts.threeMade,
    TPA: TPA,
    TPP: TPP,
    FTM: statCounts.ftMade,
    FTA: FTA,
    FTP: FTP,
    OREB: statCounts.offReb,
    DREB: statCounts.defReb,
    REB: REB,
    AST: statCounts.assists,
    STL: statCounts.steals,
    BLK: statCounts.blocks,
    TO: statCounts.turnovers,
    PF: player.totalFouls
  }
}

/**
 * Calculate team totals from all player stats
 * @param {Array} playerStats - Array of calculated player stats
 * @returns {Object} Team totals
 */
export function calculateTeamTotals(playerStats) {
  const totals = {
    jerseyNumber: '',
    name: 'TEAM TOTALS',
    PTS: 0,
    FGM: 0,
    FGA: 0,
    TPM: 0,
    TPA: 0,
    FTM: 0,
    FTA: 0,
    OREB: 0,
    DREB: 0,
    REB: 0,
    AST: 0,
    STL: 0,
    BLK: 0,
    TO: 0,
    PF: 0
  }

  for (const player of playerStats) {
    totals.PTS += player.PTS
    totals.FGM += player.FGM
    totals.FGA += player.FGA
    totals.TPM += player.TPM
    totals.TPA += player.TPA
    totals.FTM += player.FTM
    totals.FTA += player.FTA
    totals.OREB += player.OREB
    totals.DREB += player.DREB
    totals.REB += player.REB
    totals.AST += player.AST
    totals.STL += player.STL
    totals.BLK += player.BLK
    totals.TO += player.TO
    totals.PF += player.PF
  }

  // Calculate percentages
  totals.FGP = totals.FGA > 0 ? ((totals.FGM / totals.FGA) * 100).toFixed(1) + '%' : '0.0%'
  totals.TPP = totals.TPA > 0 ? ((totals.TPM / totals.TPA) * 100).toFixed(1) + '%' : '0.0%'
  totals.FTP = totals.FTA > 0 ? ((totals.FTM / totals.FTA) * 100).toFixed(1) + '%' : '0.0%'

  return totals
}
