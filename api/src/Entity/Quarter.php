<?php

namespace App\Entity;

use App\Repository\QuarterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuarterRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_game_quarter', columns: ['game_id', 'quarter_name'])]
class Quarter
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'quarters')]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\Column(type: 'string', length: 10)]
    private string $quarterName;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
    private int $sortOrder = 0;

    /** @var Collection<int, StatEvent> */
    #[ORM\OneToMany(targetEntity: StatEvent::class, mappedBy: 'quarter')]
    private Collection $statEvents;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->statEvents = new ArrayCollection();
    }

    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }

    public function getGame(): Game { return $this->game; }
    public function setGame(Game $game): self { $this->game = $game; return $this; }

    public function getQuarterName(): string { return $this->quarterName; }
    public function setQuarterName(string $quarterName): self { $this->quarterName = $quarterName; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; return $this; }

    /** @return Collection<int, StatEvent> */
    public function getStatEvents(): Collection { return $this->statEvents; }
}
