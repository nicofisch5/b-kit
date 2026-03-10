<?php

namespace App\Entity;

use App\Repository\CoachChampionshipRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CoachChampionshipRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'championship_id'])]
class CoachChampionship
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Championship::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Championship $championship;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getChampionship(): Championship { return $this->championship; }
    public function setChampionship(Championship $championship): self { $this->championship = $championship; return $this; }
}
