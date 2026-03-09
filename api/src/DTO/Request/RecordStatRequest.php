<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RecordStatRequest
{
    #[Assert\NotBlank]
    public string $playerId = '';

    #[Assert\NotBlank]
    public string $quarterId = '';

    #[Assert\NotBlank]
    public string $statType = '';

    public ?string $timestamp = null;

    public ?string $assistPlayerId = null;
}
