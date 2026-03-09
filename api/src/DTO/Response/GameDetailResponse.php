<?php

namespace App\DTO\Response;

use App\Entity\Game;

class GameDetailResponse
{
    public string $id;
    public string $homeTeam;
    public string $oppositionTeam;
    public string $date;
    public int $oppositionScore;
    public string $currentQuarter;
    public int $overtimeCount;
    public string $status;
    public array $players = [];
    public array $quarters = [];
    public string $createdAt;
    public string $updatedAt;

    public static function fromEntity(Game $game): self
    {
        $dto = new self();
        $dto->id = $game->getId();
        $dto->homeTeam = $game->getHomeTeam();
        $dto->oppositionTeam = $game->getOppositionTeam();
        $dto->date = $game->getDate()->format('Y-m-d\TH:i:s');
        $dto->oppositionScore = $game->getOppositionScore();
        $dto->currentQuarter = $game->getCurrentQuarter();
        $dto->overtimeCount = $game->getOvertimeCount();
        $dto->status = $game->getStatus()->value;
        $dto->createdAt = $game->getCreatedAt()->format('Y-m-d\TH:i:s');
        $dto->updatedAt = $game->getUpdatedAt()->format('Y-m-d\TH:i:s');

        foreach ($game->getGamePlayers() as $gp) {
            $dto->players[] = [
                'id' => $gp->getPlayer()->getId(),
                'name' => $gp->getPlayer()->getName(),
                'jerseyNumber' => $gp->getJerseyNumber(),
                'sortOrder' => $gp->getSortOrder(),
            ];
        }

        foreach ($game->getQuarters() as $q) {
            $dto->quarters[] = [
                'id' => $q->getId(),
                'quarterName' => $q->getQuarterName(),
                'sortOrder' => $q->getSortOrder(),
            ];
        }

        return $dto;
    }
}
