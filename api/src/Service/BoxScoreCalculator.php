<?php

namespace App\Service;

use App\DTO\Response\BoxScoreResponse;
use App\DTO\Response\PlayerBoxScore;
use App\Repository\StatEventRepository;

class BoxScoreCalculator
{
    public function __construct(
        private StatEventRepository $statEventRepo,
    ) {}

    public function calculate(string $gameId, ?string $quarterName = null): BoxScoreResponse
    {
        $rows = $this->statEventRepo->getBoxScoreData($gameId, $quarterName);

        $response = new BoxScoreResponse();

        foreach ($rows as $row) {
            $ps = new PlayerBoxScore();
            $ps->playerId = $row['player_id'];
            $ps->playerName = $row['player_name'];
            $ps->jerseyNumber = (int) $row['jersey_number'];
            $ps->twoPtMade = (int) $row['two_pt_made'];
            $ps->twoPtMiss = (int) $row['two_pt_miss'];
            $ps->threePtMade = (int) $row['three_pt_made'];
            $ps->threePtMiss = (int) $row['three_pt_miss'];
            $ps->ftMade = (int) $row['ft_made'];
            $ps->ftMiss = (int) $row['ft_miss'];
            $ps->offReb = (int) $row['off_reb'];
            $ps->defReb = (int) $row['def_reb'];
            $ps->totalReb = $ps->offReb + $ps->defReb;
            $ps->assists = (int) $row['assists'];
            $ps->steals = (int) $row['steals'];
            $ps->blocks = (int) $row['blocks'];
            $ps->fouls = (int) $row['fouls'];
            $ps->turnovers = (int) $row['turnovers'];

            $ps->points = ($ps->twoPtMade * 2) + ($ps->threePtMade * 3) + $ps->ftMade;
            $ps->fgMade = $ps->twoPtMade + $ps->threePtMade;
            $ps->fgAttempts = $ps->fgMade + $ps->twoPtMiss + $ps->threePtMiss;
            $ps->fgPercentage = $ps->fgAttempts > 0 ? round(($ps->fgMade / $ps->fgAttempts) * 100, 1) : null;

            $ftAttempts = $ps->ftMade + $ps->ftMiss;
            $ps->ftPercentage = $ftAttempts > 0 ? round(($ps->ftMade / $ftAttempts) * 100, 1) : null;

            $response->players[] = $ps;

            // Accumulate team totals
            $response->teamTotals->twoPtMade += $ps->twoPtMade;
            $response->teamTotals->twoPtMiss += $ps->twoPtMiss;
            $response->teamTotals->threePtMade += $ps->threePtMade;
            $response->teamTotals->threePtMiss += $ps->threePtMiss;
            $response->teamTotals->ftMade += $ps->ftMade;
            $response->teamTotals->ftMiss += $ps->ftMiss;
            $response->teamTotals->offReb += $ps->offReb;
            $response->teamTotals->defReb += $ps->defReb;
            $response->teamTotals->assists += $ps->assists;
            $response->teamTotals->steals += $ps->steals;
            $response->teamTotals->blocks += $ps->blocks;
            $response->teamTotals->fouls += $ps->fouls;
            $response->teamTotals->turnovers += $ps->turnovers;
        }

        // Compute derived totals
        $t = $response->teamTotals;
        $t->totalReb = $t->offReb + $t->defReb;
        $t->points = ($t->twoPtMade * 2) + ($t->threePtMade * 3) + $t->ftMade;
        $t->fgMade = $t->twoPtMade + $t->threePtMade;
        $t->fgAttempts = $t->fgMade + $t->twoPtMiss + $t->threePtMiss;
        $t->fgPercentage = $t->fgAttempts > 0 ? round(($t->fgMade / $t->fgAttempts) * 100, 1) : null;
        $ftAttempts = $t->ftMade + $t->ftMiss;
        $t->ftPercentage = $ftAttempts > 0 ? round(($t->ftMade / $ftAttempts) * 100, 1) : null;

        return $response;
    }
}
