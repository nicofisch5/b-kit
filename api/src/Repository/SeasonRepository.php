<?php

namespace App\Repository;

use App\Entity\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Season>
 */
class SeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    /** @return Season[] */
    public function findAllOrdered(?string $organizationId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.championshipSeasons', 'cs')->addSelect('cs')
            ->orderBy('s.name', 'DESC');

        if ($organizationId !== null) {
            $qb->andWhere('s.organizationId = :orgId')->setParameter('orgId', $organizationId);
        }

        return $qb->getQuery()->getResult();
    }
}
