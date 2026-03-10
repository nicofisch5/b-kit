<?php

namespace App\Entity;

use App\Repository\SessionDrillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SessionDrillRepository::class)]
class SessionDrill
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: TrainingSession::class, inversedBy: 'sessionDrills')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TrainingSession $session;

    #[ORM\ManyToOne(targetEntity: Drill::class)]
    #[ORM\JoinColumn(name: 'drill_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Drill $drill;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string { return $this->id; }

    public function getSession(): TrainingSession { return $this->session; }
    public function setSession(TrainingSession $session): self { $this->session = $session; return $this; }

    public function getDrill(): Drill { return $this->drill; }
    public function setDrill(Drill $drill): self { $this->drill = $drill; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }
}
