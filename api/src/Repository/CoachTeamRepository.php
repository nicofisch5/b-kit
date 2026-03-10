<?php

namespace App\Repository;

use App\Entity\CoachTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CoachTeam> */
class CoachTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoachTeam::class);
    }

    public function findByPair(string $userId, string $teamId): ?CoachTeam
    {
        return $this->findOneBy(['user' => $userId, 'team' => $teamId]);
    }

    /** @return array<array{id: string, name: string, category: string}> */
    public function getTeamsForUser(string $userId): array
    {
        return $this->createQueryBuilder('ct')
            ->select('t.id', 't.name', 't.category')
            ->join('ct.team', 't')
            ->andWhere('ct.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();
    }

    /** @return string[] — team IDs assigned to this coach */
    public function getTeamIds(string $userId): array
    {
        $rows = $this->createQueryBuilder('ct')
            ->select('IDENTITY(ct.team) AS teamId')
            ->andWhere('ct.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getScalarResult();
        return array_column($rows, 'teamId');
    }
}
