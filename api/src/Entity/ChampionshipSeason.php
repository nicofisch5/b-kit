<?php

namespace App\Entity;

use App\Repository\ChampionshipSeasonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ChampionshipSeasonRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_championship_season', columns: ['championship_id', 'season_id'])]
class ChampionshipSeason
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Championship::class, inversedBy: 'championshipSeasons')]
    #[ORM\JoinColumn(name: 'championship_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Championship $championship;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'championshipSeasons')]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Season $season;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string { return $this->id; }

    public function getChampionship(): Championship { return $this->championship; }
    public function setChampionship(Championship $championship): self { $this->championship = $championship; return $this; }

    public function getSeason(): Season { return $this->season; }
    public function setSeason(Season $season): self { $this->season = $season; return $this; }
}
