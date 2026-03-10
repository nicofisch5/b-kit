<?php

namespace App\Entity;

use App\Enum\GameStatus;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Game
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $organizationId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $championshipId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $teamId = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $homeTeam;

    #[ORM\Column(type: 'string', length: 100)]
    private string $oppositionTeam;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
    private int $oppositionScore = 0;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'Q1'])]
    private string $currentQuarter = 'Q1';

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
    private int $overtimeCount = 0;

    #[ORM\Column(type: 'string', length: 20, enumType: GameStatus::class, options: ['default' => 'in_progress'])]
    private GameStatus $status = GameStatus::IN_PROGRESS;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    /** @var Collection<int, GamePlayer> */
    #[ORM\OneToMany(targetEntity: GamePlayer::class, mappedBy: 'game', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $gamePlayers;

    /** @var Collection<int, Quarter> */
    #[ORM\OneToMany(targetEntity: Quarter::class, mappedBy: 'game', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $quarters;

    /** @var Collection<int, StatEvent> */
    #[ORM\OneToMany(targetEntity: StatEvent::class, mappedBy: 'game', cascade: ['remove'], orphanRemoval: true)]
    private Collection $statEvents;

    /** @var Collection<int, GameHistory> */
    #[ORM\OneToMany(targetEntity: GameHistory::class, mappedBy: 'game', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sequence' => 'DESC'])]
    private Collection $history;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->gamePlayers = new ArrayCollection();
        $this->quarters = new ArrayCollection();
        $this->statEvents = new ArrayCollection();
        $this->history = new ArrayCollection();
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

    public function getOrganizationId(): ?string { return $this->organizationId; }
    public function setOrganizationId(?string $organizationId): self { $this->organizationId = $organizationId; return $this; }

    public function getTeamId(): ?string { return $this->teamId; }
    public function setTeamId(?string $teamId): self { $this->teamId = $teamId; return $this; }

    public function getChampionshipId(): ?string { return $this->championshipId; }
    public function setChampionshipId(?string $championshipId): self { $this->championshipId = $championshipId; return $this; }

    public function getHomeTeam(): string { return $this->homeTeam; }
    public function setHomeTeam(string $homeTeam): self { $this->homeTeam = $homeTeam; return $this; }

    public function getOppositionTeam(): string { return $this->oppositionTeam; }
    public function setOppositionTeam(string $oppositionTeam): self { $this->oppositionTeam = $oppositionTeam; return $this; }

    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }

    public function getOppositionScore(): int { return $this->oppositionScore; }
    public function setOppositionScore(int $oppositionScore): self { $this->oppositionScore = $oppositionScore; return $this; }

    public function getCurrentQuarter(): string { return $this->currentQuarter; }
    public function setCurrentQuarter(string $currentQuarter): self { $this->currentQuarter = $currentQuarter; return $this; }

    public function getOvertimeCount(): int { return $this->overtimeCount; }
    public function setOvertimeCount(int $overtimeCount): self { $this->overtimeCount = $overtimeCount; return $this; }

    public function getStatus(): GameStatus { return $this->status; }
    public function setStatus(GameStatus $status): self { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @return Collection<int, GamePlayer> */
    public function getGamePlayers(): Collection { return $this->gamePlayers; }

    public function addGamePlayer(GamePlayer $gamePlayer): self
    {
        if (!$this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers->add($gamePlayer);
            $gamePlayer->setGame($this);
        }
        return $this;
    }

    /** @return Collection<int, Quarter> */
    public function getQuarters(): Collection { return $this->quarters; }

    public function addQuarter(Quarter $quarter): self
    {
        if (!$this->quarters->contains($quarter)) {
            $this->quarters->add($quarter);
            $quarter->setGame($this);
        }
        return $this;
    }

    /** @return Collection<int, StatEvent> */
    public function getStatEvents(): Collection { return $this->statEvents; }

    /** @return Collection<int, GameHistory> */
    public function getHistory(): Collection { return $this->history; }
}
