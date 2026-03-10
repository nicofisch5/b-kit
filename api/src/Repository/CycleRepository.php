<?php

namespace App\Repository;

use App\Entity\Cycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cycle>
 */
class CycleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cycle::class);
    }

    /** Returns all cycles created by this user, newest start date first. */
    public function findForUser(string $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.createdBy = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.startDate', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
