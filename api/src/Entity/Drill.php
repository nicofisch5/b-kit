<?php

namespace App\Entity;

use App\Repository\DrillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DrillRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Drill
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $code;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $setup = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $execution = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rotation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $evolution = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $duration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $equipment = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $minimumPlayers = null;

    #[ORM\Column(type: Types::JSON)]
    private array $tags = [];

    #[ORM\Column(type: Types::JSON)]
    private array $links = [];

    #[ORM\Column(type: 'string', length: 10)]
    private string $visibility = 'org';

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $organizationId = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $createdBy;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
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

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = $code; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getSetup(): ?string { return $this->setup; }
    public function setSetup(?string $setup): self { $this->setup = $setup; return $this; }

    public function getExecution(): ?string { return $this->execution; }
    public function setExecution(?string $execution): self { $this->execution = $execution; return $this; }

    public function getRotation(): ?string { return $this->rotation; }
    public function setRotation(?string $rotation): self { $this->rotation = $rotation; return $this; }

    public function getEvolution(): ?string { return $this->evolution; }
    public function setEvolution(?string $evolution): self { $this->evolution = $evolution; return $this; }

    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(?int $duration): self { $this->duration = $duration; return $this; }

    public function getEquipment(): ?string { return $this->equipment; }
    public function setEquipment(?string $equipment): self { $this->equipment = $equipment; return $this; }

    public function getMinimumPlayers(): ?int { return $this->minimumPlayers; }
    public function setMinimumPlayers(?int $minimumPlayers): self { $this->minimumPlayers = $minimumPlayers; return $this; }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): self { $this->tags = $tags; return $this; }

    public function getLinks(): array { return $this->links; }
    public function setLinks(array $links): self { $this->links = $links; return $this; }

    public function getVisibility(): string { return $this->visibility; }
    public function setVisibility(string $visibility): self { $this->visibility = $visibility; return $this; }

    public function getOrganizationId(): ?string { return $this->organizationId; }
    public function setOrganizationId(?string $organizationId): self { $this->organizationId = $organizationId; return $this; }

    public function getCreatedBy(): string { return $this->createdBy; }
    public function setCreatedBy(string $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}
