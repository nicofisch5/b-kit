<?php

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * @return Team[]
     */
    public function findAllOrdered(?string $organizationId = null, ?array $teamIds = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.teamPlayers', 'tp')->addSelect('tp')
            ->orderBy('t.category', 'ASC')
            ->addOrderBy('t.name', 'ASC');

        if ($organizationId !== null) {
            $qb->andWhere('t.organizationId = :orgId')->setParameter('orgId', $organizationId);
        }
        if ($teamIds !== null) {
            $qb->andWhere('t.id IN (:teamIds)')->setParameter('teamIds', $teamIds ?: ['__none__']);
        }

        return $qb->getQuery()->getResult();
    }
}
