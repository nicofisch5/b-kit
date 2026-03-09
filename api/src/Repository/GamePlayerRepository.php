<?php

namespace App\Repository;

use App\Entity\GamePlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GamePlayer>
 */
class GamePlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GamePlayer::class);
    }

    public function countByGame(string $gameId): int
    {
        return (int) $this->createQueryBuilder('gp')
            ->select('COUNT(gp.id)')
            ->andWhere('gp.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
