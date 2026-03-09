<?php

namespace App\Entity;

use App\Repository\GamePlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GamePlayerRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_game_player', columns: ['game_id', 'player_id'])]
#[ORM\UniqueConstraint(name: 'uniq_game_jersey', columns: ['game_id', 'jersey_number'])]
class GamePlayer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    private int $jerseyNumber;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
    private int $sortOrder = 0;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }

    public function getGame(): Game { return $this->game; }
    public function setGame(Game $game): self { $this->game = $game; return $this; }

    public function getPlayer(): Player { return $this->player; }
    public function setPlayer(Player $player): self { $this->player = $player; return $this; }

    public function getJerseyNumber(): int { return $this->jerseyNumber; }
    public function setJerseyNumber(int $jerseyNumber): self { $this->jerseyNumber = $jerseyNumber; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; return $this; }
}
