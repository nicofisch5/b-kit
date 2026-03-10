<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * @return Player[]
     */
    public function findFiltered(?string $orgId = null, ?string $teamId = null, ?string $category = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.teamPlayers', 'tp')->addSelect('tp')
            ->leftJoin('tp.team', 't')->addSelect('t')
            ->orderBy('p.lastname', 'ASC')
            ->addOrderBy('p.firstname', 'ASC');

        if ($orgId !== null) {
            $qb->andWhere('p.organizationId = :orgId')->setParameter('orgId', $orgId);
        }

        if ($teamId !== null) {
            $qb->andWhere('tp.team = :teamId')->setParameter('teamId', $teamId);
        }

        if ($category !== null) {
            $qb->andWhere('t.category = :category')->setParameter('category', $category);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.firstname)', ':search'),
                    $qb->expr()->like('LOWER(p.lastname)', ':search'),
                )
            )->setParameter('search', '%' . strtolower(trim($search)) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
