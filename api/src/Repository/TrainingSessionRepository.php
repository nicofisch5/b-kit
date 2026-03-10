<?php

namespace App\Repository;

use App\Entity\TrainingSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingSession>
 */
class TrainingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingSession::class);
    }

    /**
     * Find training sessions visible to the given user.
     *
     * - isAdmin (and orgId not null): all sessions in same org
     * - Coach: only sessions created by this user
     * - SuperAdmin (orgId null): only sessions created by this user
     */
    public function findForUser(?string $orgId, string $userId, bool $isAdmin): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.sessionDrills', 'sd')
            ->addSelect('sd')
            ->leftJoin('sd.drill', 'dr')
            ->addSelect('dr')
            ->leftJoin('s.cycle', 'cy')
            ->addSelect('cy');

        if ($orgId !== null && $isAdmin) {
            $qb->where('s.organizationId = :orgId')
               ->setParameter('orgId', $orgId);
        } else {
            $qb->where('s.createdBy = :userId')
               ->setParameter('userId', $userId);
        }

        $qb->orderBy('s.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /** Returns sessions belonging to a cycle, scoped to a specific creator. */
    public function findByCycle(string $cycleId, string $userId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.cycle', 'cy')
            ->where('cy.id = :cycleId')
            ->andWhere('s.createdBy = :userId')
            ->setParameter('cycleId', $cycleId)
            ->setParameter('userId', $userId)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
