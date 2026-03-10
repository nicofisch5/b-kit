<?php

namespace App\Entity;

use App\Repository\TrainingSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TrainingSessionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TrainingSession
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $goal = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $duration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $organizationId = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $createdBy;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: Cycle::class)]
    #[ORM\JoinColumn(name: 'cycle_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Cycle $cycle = null;

    /** @var Collection<int, SessionDrill> */
    #[ORM\OneToMany(targetEntity: SessionDrill::class, mappedBy: 'session', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $sessionDrills;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->sessionDrills = new ArrayCollection();
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

    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }

    public function getGoal(): ?string { return $this->goal; }
    public function setGoal(?string $goal): self { $this->goal = $goal; return $this; }

    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(?int $duration): self { $this->duration = $duration; return $this; }

    public function getComments(): ?string { return $this->comments; }
    public function setComments(?string $comments): self { $this->comments = $comments; return $this; }

    public function getOrganizationId(): ?string { return $this->organizationId; }
    public function setOrganizationId(?string $organizationId): self { $this->organizationId = $organizationId; return $this; }

    public function getCreatedBy(): string { return $this->createdBy; }
    public function setCreatedBy(string $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function getCycle(): ?Cycle { return $this->cycle; }
    public function setCycle(?Cycle $cycle): self { $this->cycle = $cycle; return $this; }

    /** @return Collection<int, SessionDrill> */
    public function getSessionDrills(): Collection { return $this->sessionDrills; }
}
