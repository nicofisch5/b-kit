<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Player
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $teamId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $organizationId = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 50)]
    private string $lastname;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dob = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $jerseyNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    /** @var Collection<int, GamePlayer> */
    #[ORM\OneToMany(targetEntity: GamePlayer::class, mappedBy: 'player', cascade: ['remove'], orphanRemoval: true)]
    private Collection $gamePlayers;

    /** @var Collection<int, TeamPlayer> */
    #[ORM\OneToMany(targetEntity: TeamPlayer::class, mappedBy: 'player', cascade: ['remove'], orphanRemoval: true)]
    private Collection $teamPlayers;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->gamePlayers = new ArrayCollection();
        $this->teamPlayers = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }

    public function getTeamId(): ?string { return $this->teamId; }
    public function setTeamId(?string $teamId): self { $this->teamId = $teamId; return $this; }

    public function getOrganizationId(): ?string { return $this->organizationId; }
    public function setOrganizationId(?string $organizationId): self { $this->organizationId = $organizationId; return $this; }

    public function getFirstname(): string { return $this->firstname; }
    public function setFirstname(string $firstname): self { $this->firstname = $firstname; return $this; }

    public function getLastname(): string { return $this->lastname; }
    public function setLastname(string $lastname): self { $this->lastname = $lastname; return $this; }

    public function getDob(): ?\DateTimeInterface { return $this->dob; }
    public function setDob(?\DateTimeInterface $dob): self { $this->dob = $dob; return $this; }

    public function getJerseyNumber(): ?int { return $this->jerseyNumber; }
    public function setJerseyNumber(?int $jerseyNumber): self { $this->jerseyNumber = $jerseyNumber; return $this; }

    /**
     * Returns "Firstname Lastname" — used by all existing game tracker code.
     */
    public function getName(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    /**
     * Convenience setter: splits on first space into firstname + lastname.
     * "Player A" → firstname="Player", lastname="A"
     */
    public function setName(string $name): self
    {
        $parts = explode(' ', trim($name), 2);
        $this->firstname = $parts[0] ?? $name;
        $this->lastname = $parts[1] ?? '';
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @return Collection<int, GamePlayer> */
    public function getGamePlayers(): Collection { return $this->gamePlayers; }

    /** @return Collection<int, TeamPlayer> */
    public function getTeamPlayers(): Collection { return $this->teamPlayers; }
}
