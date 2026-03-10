<?php

namespace App\Repository;

use App\Entity\CoachChampionship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CoachChampionship> */
class CoachChampionshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoachChampionship::class);
    }

    public function findByPair(string $userId, string $champId): ?CoachChampionship
    {
        return $this->findOneBy(['user' => $userId, 'championship' => $champId]);
    }

    /** @return array<array{id: string, name: string}> */
    public function getChampionshipsForUser(string $userId): array
    {
        return $this->createQueryBuilder('cc')
            ->select('c.id', 'c.name')
            ->join('cc.championship', 'c')
            ->andWhere('cc.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();
    }

    /** @return string[] — championship IDs assigned to this coach */
    public function getChampionshipIds(string $userId): array
    {
        $rows = $this->createQueryBuilder('cc')
            ->select('IDENTITY(cc.championship) AS champId')
            ->andWhere('cc.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getScalarResult();
        return array_column($rows, 'champId');
    }
}
