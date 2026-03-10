<?php

namespace App\Repository;

use App\Entity\Drill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Drill>
 */
class DrillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Drill::class);
    }

    /**
     * Find drills visible to the given user.
     *
     * - Org drills: visibility='org' AND org_id=orgId
     * - Personal drills: visibility='personal' AND created_by=userId
     * - If orgId is null (SuperAdmin): only personal drills created by this user
     */
    public function findVisible(?string $orgId, string $userId, ?string $search = null, ?string $tag = null): array
    {
        $qb = $this->createQueryBuilder('d');

        if ($orgId !== null) {
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('d.visibility', ':visOrg'),
                        $qb->expr()->eq('d.organizationId', ':orgId')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('d.visibility', ':visPersonal'),
                        $qb->expr()->eq('d.createdBy', ':userId')
                    )
                )
            )
            ->setParameter('visOrg', 'org')
            ->setParameter('orgId', $orgId)
            ->setParameter('visPersonal', 'personal')
            ->setParameter('userId', $userId);
        } else {
            // SuperAdmin: only personal drills they created
            $qb->where('d.visibility = :visPersonal AND d.createdBy = :userId')
               ->setParameter('visPersonal', 'personal')
               ->setParameter('userId', $userId);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('d.name LIKE :search OR d.code LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($tag !== null && $tag !== '') {
            $qb->andWhere('d.tags LIKE :tagLike')
               ->setParameter('tagLike', '%"' . addslashes($tag) . '"%');
        }

        $qb->orderBy('d.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
