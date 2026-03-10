<?php

namespace App\Entity;

use App\Repository\CoachTeamRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CoachTeamRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'team_id'])]
class CoachTeam
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Team $team;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getTeam(): Team { return $this->team; }
    public function setTeam(Team $team): self { $this->team = $team; return $this; }
}
