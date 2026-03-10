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
    public function findFiltered(?GameStatus $status, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo, int $page = 1, int $limit = 20, ?string $teamId = null, ?string $organizationId = null, ?array $gameTeamIds = null, ?array $gameChampIds = null): array
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
        if ($teamId !== null) {
            $qb->andWhere('g.teamId = :teamId')->setParameter('teamId', $teamId);
        }
        if ($organizationId !== null) {
            $qb->andWhere('g.organizationId = :orgId')->setParameter('orgId', $organizationId);
        }
        if ($gameTeamIds !== null || $gameChampIds !== null) {
            $conditions = [];
            if ($gameTeamIds !== null) {
                $qb->setParameter('gameTeamIds', $gameTeamIds ?: ['__none__']);
                $conditions[] = 'g.teamId IN (:gameTeamIds)';
            }
            if ($gameChampIds !== null) {
                $qb->setParameter('gameChampIds', $gameChampIds ?: ['__none__']);
                $conditions[] = 'g.championshipId IN (:gameChampIds)';
            }
            $qb->andWhere(implode(' OR ', $conditions));
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(?GameStatus $status, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo, ?string $teamId = null, ?string $organizationId = null, ?array $gameTeamIds = null, ?array $gameChampIds = null): int
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
        if ($teamId !== null) {
            $qb->andWhere('g.teamId = :teamId')->setParameter('teamId', $teamId);
        }
        if ($organizationId !== null) {
            $qb->andWhere('g.organizationId = :orgId')->setParameter('orgId', $organizationId);
        }
        if ($gameTeamIds !== null || $gameChampIds !== null) {
            $conditions = [];
            if ($gameTeamIds !== null) {
                $qb->setParameter('gameTeamIds', $gameTeamIds ?: ['__none__']);
                $conditions[] = 'g.teamId IN (:gameTeamIds)';
            }
            if ($gameChampIds !== null) {
                $qb->setParameter('gameChampIds', $gameChampIds ?: ['__none__']);
                $conditions[] = 'g.championshipId IN (:gameChampIds)';
            }
            $qb->andWhere(implode(' OR ', $conditions));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
