<?php

namespace App\Repository;

use App\Entity\GameHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameHistory>
 */
class GameHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameHistory::class);
    }

    public function findLatestByGame(string $gameId): ?GameHistory
    {
        return $this->createQueryBuilder('gh')
            ->andWhere('gh.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('gh.sequence', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextSequence(string $gameId): int
    {
        $result = $this->createQueryBuilder('gh')
            ->select('MAX(gh.sequence)')
            ->andWhere('gh.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }

    /**
     * @return GameHistory[]
     */
    public function findByGamePaginated(string $gameId, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('gh')
            ->andWhere('gh.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('gh.sequence', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
