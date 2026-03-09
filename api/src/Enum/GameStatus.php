<?php

namespace App\Enum;

enum GameStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
