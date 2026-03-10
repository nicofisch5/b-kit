<?php

namespace App\Entity;

use App\Repository\ChampionshipTeamRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ChampionshipTeamRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_championship_team', columns: ['championship_id', 'team_id'])]
class ChampionshipTeam
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Championship::class, inversedBy: 'championshipTeams')]
    #[ORM\JoinColumn(name: 'championship_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Championship $championship;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Team $team;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $groupName = null;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string { return $this->id; }

    public function getChampionship(): Championship { return $this->championship; }
    public function setChampionship(Championship $championship): self { $this->championship = $championship; return $this; }

    public function getTeam(): Team { return $this->team; }
    public function setTeam(Team $team): self { $this->team = $team; return $this; }

    public function getGroupName(): ?string { return $this->groupName; }
    public function setGroupName(?string $groupName): self { $this->groupName = $groupName; return $this; }
}
