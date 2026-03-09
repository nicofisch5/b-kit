<?php

namespace App\Service;

use App\Entity\Game;

class GameExportService
{
    public function __construct(
        private BoxScoreCalculator $boxScoreCalculator,
    ) {}

    public function exportJson(Game $game): array
    {
        $players = [];
        foreach ($game->getGamePlayers() as $gp) {
            $players[] = [
                'id' => $gp->getPlayer()->getId(),
                'name' => $gp->getPlayer()->getName(),
                'jerseyNumber' => $gp->getJerseyNumber(),
                'sortOrder' => $gp->getSortOrder(),
            ];
        }

        $quarters = [];
        foreach ($game->getQuarters() as $q) {
            $quarters[] = [
                'id' => $q->getId(),
                'quarterName' => $q->getQuarterName(),
                'sortOrder' => $q->getSortOrder(),
            ];
        }

        $events = [];
        foreach ($game->getStatEvents() as $se) {
            $events[] = [
                'id' => $se->getId(),
                'playerId' => $se->getPlayer()->getId(),
                'quarterId' => $se->getQuarter()->getId(),
                'statType' => $se->getStatType()->value,
                'timestamp' => $se->getTimestamp()->format('Y-m-d\TH:i:s.v'),
            ];
        }

        $history = [];
        foreach ($game->getHistory() as $h) {
            $history[] = [
                'id' => $h->getId(),
                'eventId' => $h->getEvent()->getId(),
                'playerId' => $h->getPlayer()->getId(),
                'assistEventId' => $h->getAssistEvent()?->getId(),
                'assistPlayerId' => $h->getAssistPlayerId(),
                'sequence' => $h->getSequence(),
            ];
        }

        return [
            'game' => [
                'id' => $game->getId(),
                'homeTeam' => $game->getHomeTeam(),
                'oppositionTeam' => $game->getOppositionTeam(),
                'date' => $game->getDate()->format('Y-m-d\TH:i:s'),
                'oppositionScore' => $game->getOppositionScore(),
                'currentQuarter' => $game->getCurrentQuarter(),
                'overtimeCount' => $game->getOvertimeCount(),
                'status' => $game->getStatus()->value,
            ],
            'players' => $players,
            'quarters' => $quarters,
            'events' => $events,
            'history' => $history,
        ];
    }

    public function exportCsv(Game $game): string
    {
        $boxScore = $this->boxScoreCalculator->calculate($game->getId());

        $lines = [];
        $headers = ['Jersey', 'Player', 'PTS', '2PM', '2PA', '3PM', '3PA', 'FTM', 'FTA', 'OREB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'FOL', 'TO', 'FG%', 'FT%'];
        $lines[] = implode(',', $headers);

        foreach ($boxScore->players as $ps) {
            $lines[] = implode(',', [
                $ps->jerseyNumber,
                '"' . str_replace('"', '""', $ps->playerName) . '"',
                $ps->points,
                $ps->twoPtMade,
                $ps->twoPtMade + $ps->twoPtMiss,
                $ps->threePtMade,
                $ps->threePtMade + $ps->threePtMiss,
                $ps->ftMade,
                $ps->ftMade + $ps->ftMiss,
                $ps->offReb,
                $ps->defReb,
                $ps->totalReb,
                $ps->assists,
                $ps->steals,
                $ps->blocks,
                $ps->fouls,
                $ps->turnovers,
                $ps->fgPercentage ?? '',
                $ps->ftPercentage ?? '',
            ]);
        }

        $t = $boxScore->teamTotals;
        $lines[] = implode(',', [
            '',
            'TOTALS',
            $t->points,
            $t->twoPtMade,
            $t->twoPtMade + $t->twoPtMiss,
            $t->threePtMade,
            $t->threePtMade + $t->threePtMiss,
            $t->ftMade,
            $t->ftMade + $t->ftMiss,
            $t->offReb,
            $t->defReb,
            $t->totalReb,
            $t->assists,
            $t->steals,
            $t->blocks,
            $t->fouls,
            $t->turnovers,
            $t->fgPercentage ?? '',
            $t->ftPercentage ?? '',
        ]);

        return implode("\n", $lines);
    }
}
