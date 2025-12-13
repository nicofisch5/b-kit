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
