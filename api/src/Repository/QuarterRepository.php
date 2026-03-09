<?php

namespace App\Repository;

use App\Entity\Quarter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quarter>
 */
class QuarterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quarter::class);
    }

    public function findByGameAndName(string $gameId, string $quarterName): ?Quarter
    {
        return $this->findOneBy(['game' => $gameId, 'quarterName' => $quarterName]);
    }
}
