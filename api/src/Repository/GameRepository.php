<?php

namespace App\Repository;

use App\Entity\Game;
use App\Enum\GameStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * @return Game[]
     */
    public function findFiltered(?GameStatus $status, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('g')
            ->orderBy('g.date', 'DESC');

        if ($status !== null) {
            $qb->andWhere('g.status = :status')->setParameter('status', $status->value);
        }
        if ($dateFrom !== null) {
            $qb->andWhere('g.date >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }
        if ($dateTo !== null) {
            $qb->andWhere('g.date <= :dateTo')->setParameter('dateTo', $dateTo);
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(?GameStatus $status, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)');

        if ($status !== null) {
            $qb->andWhere('g.status = :status')->setParameter('status', $status->value);
        }
        if ($dateFrom !== null) {
            $qb->andWhere('g.date >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }
        if ($dateTo !== null) {
            $qb->andWhere('g.date <= :dateTo')->setParameter('dateTo', $dateTo);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
