<?php

namespace App\Entity;

use App\Repository\TeamPlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TeamPlayerRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_team_player', columns: ['team_id', 'player_id'])]
class TeamPlayer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'teamPlayers')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Team $team;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'teamPlayers')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string { return $this->id; }

    public function getTeam(): Team { return $this->team; }
    public function setTeam(Team $team): self { $this->team = $team; return $this; }

    public function getPlayer(): Player { return $this->player; }
    public function setPlayer(Player $player): self { $this->player = $player; return $this; }
}
