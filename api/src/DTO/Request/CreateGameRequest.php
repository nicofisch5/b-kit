<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateGameRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $homeTeam = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $oppositionTeam = '';

    #[Assert\NotBlank]
    public string $date = '';

    #[Assert\GreaterThanOrEqual(0)]
    public int $oppositionScore = 0;

    /** @var array<int, array{name: string, jerseyNumber: int}> */
    #[Assert\Valid]
    public array $players = [];
}
