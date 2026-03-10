<?php

namespace App\Repository;

use App\Entity\StatEvent;
use App\Enum\StatType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatEvent>
 */
class StatEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatEvent::class);
    }

    /**
     * @return StatEvent[]
     */
    public function findByGameFiltered(string $gameId, ?string $playerId = null, ?string $quarterId = null, ?StatType $statType = null): array
    {
        $qb = $this->createQueryBuilder('se')
            ->andWhere('se.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('se.timestamp', 'ASC');

        if ($playerId !== null) {
            $qb->andWhere('se.player = :playerId')->setParameter('playerId', $playerId);
        }
        if ($quarterId !== null) {
            $qb->andWhere('se.quarter = :quarterId')->setParameter('quarterId', $quarterId);
        }
        if ($statType !== null) {
            $qb->andWhere('se.statType = :statType')->setParameter('statType', $statType->value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Aggregate player stats across multiple games, scoped by org/team/championship.
     * @return array<int, array<string, mixed>>
     */
    public function getAggregateStats(?string $orgId, ?string $teamId, ?string $champId, ?array $coachTeamIds, ?array $coachChampIds): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $where = [];
        $params = [];

        if ($orgId !== null) {
            $where[] = 'g.organization_id = :orgId';
            $params['orgId'] = $orgId;
        }
        if ($teamId !== null) {
            $where[] = 'g.team_id = :teamId';
            $params['teamId'] = $teamId;
        }
        if ($champId !== null) {
            $where[] = 'g.championship_id = :champId';
            $params['champId'] = $champId;
        }

        // Coach scope: game's team OR championship must be assigned
        if ($coachTeamIds !== null || $coachChampIds !== null) {
            $scopeParts = [];
            if ($coachTeamIds !== null && count($coachTeamIds) > 0) {
                $inList = implode(',', array_map(fn($id) => $conn->quote($id), $coachTeamIds));
                $scopeParts[] = "g.team_id IN ($inList)";
            }
            if ($coachChampIds !== null && count($coachChampIds) > 0) {
                $inList = implode(',', array_map(fn($id) => $conn->quote($id), $coachChampIds));
                $scopeParts[] = "g.championship_id IN ($inList)";
            }
            // If coach has no assignments at all, return nothing
            if (empty($scopeParts)) {
                return [];
            }
            $where[] = '(' . implode(' OR ', $scopeParts) . ')';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT
                p.id AS player_id,
                p.name AS player_name,
                COUNT(DISTINCT gp.game_id) AS games_played,
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MADE'   THEN 1 ELSE 0 END) AS two_pt_made,
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MISS'   THEN 1 ELSE 0 END) AS two_pt_miss,
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MADE' THEN 1 ELSE 0 END) AS three_pt_made,
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MISS' THEN 1 ELSE 0 END) AS three_pt_miss,
                SUM(CASE WHEN se.stat_type = 'FT_MADE'       THEN 1 ELSE 0 END) AS ft_made,
                SUM(CASE WHEN se.stat_type = 'FT_MISS'       THEN 1 ELSE 0 END) AS ft_miss,
                SUM(CASE WHEN se.stat_type = 'OFF_REB'       THEN 1 ELSE 0 END) AS off_reb,
                SUM(CASE WHEN se.stat_type = 'DEF_REB'       THEN 1 ELSE 0 END) AS def_reb,
                SUM(CASE WHEN se.stat_type = 'ASSIST'        THEN 1 ELSE 0 END) AS assists,
                SUM(CASE WHEN se.stat_type = 'STEAL'         THEN 1 ELSE 0 END) AS steals,
                SUM(CASE WHEN se.stat_type = 'BLOCK'         THEN 1 ELSE 0 END) AS blocks,
                SUM(CASE WHEN se.stat_type = 'FOUL'          THEN 1 ELSE 0 END) AS fouls,
                SUM(CASE WHEN se.stat_type = 'TURNOVER'      THEN 1 ELSE 0 END) AS turnovers
            FROM game_player gp
            INNER JOIN player p ON p.id = gp.player_id
            INNER JOIN game   g ON g.id = gp.game_id
            LEFT JOIN  stat_event se ON se.game_id = gp.game_id AND se.player_id = gp.player_id
            $whereClause
            GROUP BY p.id, p.name
            ORDER BY (
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MADE'   THEN 2 ELSE 0 END) +
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MADE' THEN 3 ELSE 0 END) +
                SUM(CASE WHEN se.stat_type = 'FT_MADE'       THEN 1 ELSE 0 END)
            ) DESC, p.name ASC
        ";

        return $conn->fetchAllAssociative($sql, $params);
    }

    /**
     * Aggregate team stats across multiple games, scoped by org/championship.
     * Only includes games that have a team linked (game.team_id IS NOT NULL).
     * @return array<int, array<string, mixed>>
     */
    public function getTeamAggregateStats(?string $orgId, ?string $champId, ?array $coachTeamIds, ?array $coachChampIds): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $where = ['g.team_id IS NOT NULL'];
        $params = [];

        if ($orgId !== null) {
            $where[] = 'g.organization_id = :orgId';
            $params['orgId'] = $orgId;
        }
        if ($champId !== null) {
            $where[] = 'g.championship_id = :champId';
            $params['champId'] = $champId;
        }

        // Coach scope: game's team OR championship must be assigned
        if ($coachTeamIds !== null || $coachChampIds !== null) {
            $scopeParts = [];
            if ($coachTeamIds !== null && count($coachTeamIds) > 0) {
                $inList = implode(',', array_map(fn($id) => $conn->quote($id), $coachTeamIds));
                $scopeParts[] = "g.team_id IN ($inList)";
            }
            if ($coachChampIds !== null && count($coachChampIds) > 0) {
                $inList = implode(',', array_map(fn($id) => $conn->quote($id), $coachChampIds));
                $scopeParts[] = "g.championship_id IN ($inList)";
            }
            if (empty($scopeParts)) {
                return [];
            }
            $where[] = '(' . implode(' OR ', $scopeParts) . ')';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT
                t.id    AS team_id,
                t.name  AS team_name,
                t.color AS team_color,
                COUNT(DISTINCT g.id) AS games_played,
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MADE'   THEN 1 ELSE 0 END) AS two_pt_made,
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MISS'   THEN 1 ELSE 0 END) AS two_pt_miss,
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MADE' THEN 1 ELSE 0 END) AS three_pt_made,
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MISS' THEN 1 ELSE 0 END) AS three_pt_miss,
                SUM(CASE WHEN se.stat_type = 'FT_MADE'       THEN 1 ELSE 0 END) AS ft_made,
                SUM(CASE WHEN se.stat_type = 'FT_MISS'       THEN 1 ELSE 0 END) AS ft_miss,
                SUM(CASE WHEN se.stat_type = 'OFF_REB'       THEN 1 ELSE 0 END) AS off_reb,
                SUM(CASE WHEN se.stat_type = 'DEF_REB'       THEN 1 ELSE 0 END) AS def_reb,
                SUM(CASE WHEN se.stat_type = 'ASSIST'        THEN 1 ELSE 0 END) AS assists,
                SUM(CASE WHEN se.stat_type = 'STEAL'         THEN 1 ELSE 0 END) AS steals,
                SUM(CASE WHEN se.stat_type = 'BLOCK'         THEN 1 ELSE 0 END) AS blocks,
                SUM(CASE WHEN se.stat_type = 'FOUL'          THEN 1 ELSE 0 END) AS fouls,
                SUM(CASE WHEN se.stat_type = 'TURNOVER'      THEN 1 ELSE 0 END) AS turnovers
            FROM game g
            INNER JOIN team t ON t.id = g.team_id
            LEFT JOIN game_player gp ON gp.game_id = g.id
            LEFT JOIN stat_event se ON se.game_id = g.id AND se.player_id = gp.player_id
            $whereClause
            GROUP BY t.id, t.name, t.color
            ORDER BY (
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MADE'   THEN 2 ELSE 0 END) +
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MADE' THEN 3 ELSE 0 END) +
                SUM(CASE WHEN se.stat_type = 'FT_MADE'       THEN 1 ELSE 0 END)
            ) DESC, t.name ASC
        ";

        return $conn->fetchAllAssociative($sql, $params);
    }

    /**
     * Get box score data via aggregate query.
     * @return array<int, array<string, mixed>>
     */
    public function getBoxScoreData(string $gameId, ?string $quarterName = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                p.id AS player_id,
                p.name AS player_name,
                gp.jersey_number,
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MADE' THEN 1 ELSE 0 END) AS two_pt_made,
                SUM(CASE WHEN se.stat_type = 'TWO_PT_MISS' THEN 1 ELSE 0 END) AS two_pt_miss,
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MADE' THEN 1 ELSE 0 END) AS three_pt_made,
                SUM(CASE WHEN se.stat_type = 'THREE_PT_MISS' THEN 1 ELSE 0 END) AS three_pt_miss,
                SUM(CASE WHEN se.stat_type = 'FT_MADE' THEN 1 ELSE 0 END) AS ft_made,
                SUM(CASE WHEN se.stat_type = 'FT_MISS' THEN 1 ELSE 0 END) AS ft_miss,
                SUM(CASE WHEN se.stat_type = 'OFF_REB' THEN 1 ELSE 0 END) AS off_reb,
                SUM(CASE WHEN se.stat_type = 'DEF_REB' THEN 1 ELSE 0 END) AS def_reb,
                SUM(CASE WHEN se.stat_type = 'ASSIST' THEN 1 ELSE 0 END) AS assists,
                SUM(CASE WHEN se.stat_type = 'STEAL' THEN 1 ELSE 0 END) AS steals,
                SUM(CASE WHEN se.stat_type = 'BLOCK' THEN 1 ELSE 0 END) AS blocks,
                SUM(CASE WHEN se.stat_type = 'FOUL' THEN 1 ELSE 0 END) AS fouls,
                SUM(CASE WHEN se.stat_type = 'TURNOVER' THEN 1 ELSE 0 END) AS turnovers
            FROM game_player gp
            INNER JOIN player p ON p.id = gp.player_id
            LEFT JOIN stat_event se ON se.game_id = gp.game_id AND se.player_id = gp.player_id
        ";

        $params = ['gameId' => $gameId];

        if ($quarterName !== null) {
            $sql .= " INNER JOIN quarter q ON q.id = se.quarter_id AND q.quarter_name = :quarterName";
            $params['quarterName'] = $quarterName;
        }

        $sql .= "
            WHERE gp.game_id = :gameId
            GROUP BY p.id, p.name, gp.jersey_number
            ORDER BY gp.sort_order ASC
        ";

        return $conn->fetchAllAssociative($sql, $params);
    }
}
