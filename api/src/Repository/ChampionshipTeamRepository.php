<?php

namespace App\Repository;

use App\Entity\ChampionshipTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChampionshipTeam>
 */
class ChampionshipTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChampionshipTeam::class);
    }

    public function findByPair(string $championshipId, string $teamId): ?ChampionshipTeam
    {
        return $this->findOneBy(['championship' => $championshipId, 'team' => $teamId]);
    }
}
