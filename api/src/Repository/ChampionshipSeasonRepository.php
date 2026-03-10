<?php

namespace App\Repository;

use App\Entity\ChampionshipSeason;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChampionshipSeason>
 */
class ChampionshipSeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChampionshipSeason::class);
    }

    public function findByPair(string $championshipId, string $seasonId): ?ChampionshipSeason
    {
        return $this->findOneBy(['championship' => $championshipId, 'season' => $seasonId]);
    }
}
