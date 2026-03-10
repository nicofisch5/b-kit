<?php

namespace App\Repository;

use App\Entity\Championship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Championship>
 */
class ChampionshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Championship::class);
    }

    /** @return Championship[] */
    public function findAllOrdered(?string $organizationId = null, ?array $champIds = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.championshipTeams', 'ct')->addSelect('ct')
            ->leftJoin('c.championshipSeasons', 'cs')->addSelect('cs')
            ->orderBy('c.name', 'ASC');

        if ($organizationId !== null) {
            $qb->andWhere('c.organizationId = :orgId')->setParameter('orgId', $organizationId);
        }
        if ($champIds !== null) {
            $qb->andWhere('c.id IN (:champIds)')->setParameter('champIds', $champIds ?: ['__none__']);
        }

        return $qb->getQuery()->getResult();
    }
}
