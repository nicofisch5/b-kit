<?php

namespace App\Enum;

enum StatType: string
{
    case TWO_PT_MADE = 'TWO_PT_MADE';
    case TWO_PT_MISS = 'TWO_PT_MISS';
    case THREE_PT_MADE = 'THREE_PT_MADE';
    case THREE_PT_MISS = 'THREE_PT_MISS';
    case FT_MADE = 'FT_MADE';
    case FT_MISS = 'FT_MISS';
    case OFF_REB = 'OFF_REB';
    case DEF_REB = 'DEF_REB';
    case ASSIST = 'ASSIST';
    case STEAL = 'STEAL';
    case BLOCK = 'BLOCK';
    case FOUL = 'FOUL';
    case TURNOVER = 'TURNOVER';

    public function points(): int
    {
        return match ($this) {
            self::TWO_PT_MADE => 2,
            self::THREE_PT_MADE => 3,
            self::FT_MADE => 1,
            default => 0,
        };
    }
}
