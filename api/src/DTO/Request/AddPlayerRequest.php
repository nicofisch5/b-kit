<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class AddPlayerRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name = '';

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public int $jerseyNumber = 0;
}
