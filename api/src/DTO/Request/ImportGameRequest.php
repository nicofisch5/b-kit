<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ImportGameRequest
{
    #[Assert\NotNull]
    public array $game = [];
}
