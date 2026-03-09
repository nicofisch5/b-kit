<?php

namespace App\Entity;

use App\Repository\GameHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameHistoryRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_game_sequence', columns: ['game_id', 'sequence'])]
#[ORM\Index(name: 'idx_history_game', columns: ['game_id'])]
#[ORM\HasLifecycleCallbacks]
class GameHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: StatEvent::class)]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StatEvent $event;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: StatEvent::class)]
    #[ORM\JoinColumn(name: 'assist_event_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StatEvent $assistEvent = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $assistPlayerId = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $sequence;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }

    public function getGame(): Game { return $this->game; }
    public function setGame(Game $game): self { $this->game = $game; return $this; }

    public function getEvent(): StatEvent { return $this->event; }
    public function setEvent(StatEvent $event): self { $this->event = $event; return $this; }

    public function getPlayer(): Player { return $this->player; }
    public function setPlayer(Player $player): self { $this->player = $player; return $this; }

    public function getAssistEvent(): ?StatEvent { return $this->assistEvent; }
    public function setAssistEvent(?StatEvent $assistEvent): self { $this->assistEvent = $assistEvent; return $this; }

    public function getAssistPlayerId(): ?string { return $this->assistPlayerId; }
    public function setAssistPlayerId(?string $assistPlayerId): self { $this->assistPlayerId = $assistPlayerId; return $this; }

    public function getSequence(): int { return $this->sequence; }
    public function setSequence(int $sequence): self { $this->sequence = $sequence; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
