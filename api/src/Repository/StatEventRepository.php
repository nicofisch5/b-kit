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
