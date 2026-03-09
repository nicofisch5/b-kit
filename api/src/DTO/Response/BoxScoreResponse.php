<?php

namespace App\DTO\Response;

class BoxScoreResponse
{
    /** @var array<int, PlayerBoxScore> */
    public array $players = [];

    public TeamTotals $teamTotals;

    public function __construct()
    {
        $this->teamTotals = new TeamTotals();
    }
}

class PlayerBoxScore
{
    public string $playerId;
    public string $playerName;
    public int $jerseyNumber;
    public int $twoPtMade = 0;
    public int $twoPtMiss = 0;
    public int $threePtMade = 0;
    public int $threePtMiss = 0;
    public int $ftMade = 0;
    public int $ftMiss = 0;
    public int $offReb = 0;
    public int $defReb = 0;
    public int $totalReb = 0;
    public int $assists = 0;
    public int $steals = 0;
    public int $blocks = 0;
    public int $fouls = 0;
    public int $turnovers = 0;
    public int $points = 0;
    public int $fgAttempts = 0;
    public int $fgMade = 0;
    public ?float $fgPercentage = null;
    public ?float $ftPercentage = null;
}

class TeamTotals extends PlayerBoxScore
{
    public function __construct()
    {
        $this->playerId = '';
        $this->playerName = 'TOTALS';
        $this->jerseyNumber = 0;
    }
}
