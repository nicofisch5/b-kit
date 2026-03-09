<?php

namespace App\Entity;

use App\Enum\StatType;
use App\Repository\StatEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StatEventRepository::class)]
#[ORM\Index(name: 'idx_stat_game_player', columns: ['game_id', 'player_id'])]
#[ORM\Index(name: 'idx_stat_game_quarter', columns: ['game_id', 'quarter_id'])]
#[ORM\HasLifecycleCallbacks]
class StatEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'statEvents')]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Quarter::class, inversedBy: 'statEvents')]
    #[ORM\JoinColumn(name: 'quarter_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Quarter $quarter;

    #[ORM\Column(type: 'string', length: 20, enumType: StatType::class)]
    private StatType $statType;

    #[ORM\Column(type: 'datetime', options: ['precision' => 3])]
    private \DateTimeInterface $timestamp;

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

    public function getPlayer(): Player { return $this->player; }
    public function setPlayer(Player $player): self { $this->player = $player; return $this; }

    public function getQuarter(): Quarter { return $this->quarter; }
    public function setQuarter(Quarter $quarter): self { $this->quarter = $quarter; return $this; }

    public function getStatType(): StatType { return $this->statType; }
    public function setStatType(StatType $statType): self { $this->statType = $statType; return $this; }

    public function getTimestamp(): \DateTimeInterface { return $this->timestamp; }
    public function setTimestamp(\DateTimeInterface $timestamp): self { $this->timestamp = $timestamp; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
