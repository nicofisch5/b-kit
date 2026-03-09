<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameHistory;
use App\Entity\GamePlayer;
use App\Entity\Player;
use App\Entity\Quarter;
use App\Entity\StatEvent;
use App\Enum\GameStatus;
use App\Enum\StatType;
use Doctrine\ORM\EntityManagerInterface;

class GameImportService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function import(array $data): Game
    {
        return $this->em->wrapInTransaction(function () use ($data) {
            $gameData = $data['game'];

            $game = new Game();
            if (isset($gameData['id'])) {
                $game->setId($gameData['id']);
            }
            $game->setHomeTeam($gameData['homeTeam']);
            $game->setOppositionTeam($gameData['oppositionTeam']);
            $game->setDate(new \DateTime($gameData['date']));
            $game->setOppositionScore($gameData['oppositionScore'] ?? 0);
            $game->setCurrentQuarter($gameData['currentQuarter'] ?? 'Q1');
            $game->setOvertimeCount($gameData['overtimeCount'] ?? 0);
            $game->setStatus(GameStatus::from($gameData['status'] ?? 'in_progress'));
            $this->em->persist($game);

            // Build lookup maps
            $playerMap = [];
            foreach ($data['players'] ?? [] as $pd) {
                $player = new Player();
                if (isset($pd['id'])) {
                    $player->setId($pd['id']);
                }
                $player->setName($pd['name']);
                $this->em->persist($player);
                $playerMap[$player->getId()] = $player;

                $gp = new GamePlayer();
                $gp->setGame($game);
                $gp->setPlayer($player);
                $gp->setJerseyNumber($pd['jerseyNumber']);
                $gp->setSortOrder($pd['sortOrder'] ?? 0);
                $this->em->persist($gp);
            }

            $quarterMap = [];
            foreach ($data['quarters'] ?? [] as $qd) {
                $quarter = new Quarter();
                if (isset($qd['id'])) {
                    $quarter->setId($qd['id']);
                }
                $quarter->setGame($game);
                $quarter->setQuarterName($qd['quarterName']);
                $quarter->setSortOrder($qd['sortOrder'] ?? 0);
                $this->em->persist($quarter);
                $quarterMap[$quarter->getId()] = $quarter;
            }

            $eventMap = [];
            foreach ($data['events'] ?? [] as $ed) {
                $event = new StatEvent();
                if (isset($ed['id'])) {
                    $event->setId($ed['id']);
                }
                $event->setGame($game);
                $event->setPlayer($playerMap[$ed['playerId']]);
                $event->setQuarter($quarterMap[$ed['quarterId']]);
                $event->setStatType(StatType::from($ed['statType']));
                $event->setTimestamp(new \DateTime($ed['timestamp']));
                $this->em->persist($event);
                $eventMap[$event->getId()] = $event;
            }

            foreach ($data['history'] ?? [] as $hd) {
                $history = new GameHistory();
                if (isset($hd['id'])) {
                    $history->setId($hd['id']);
                }
                $history->setGame($game);
                $history->setEvent($eventMap[$hd['eventId']]);
                $history->setPlayer($playerMap[$hd['playerId']]);
                if (isset($hd['assistEventId']) && isset($eventMap[$hd['assistEventId']])) {
                    $history->setAssistEvent($eventMap[$hd['assistEventId']]);
                }
                $history->setAssistPlayerId($hd['assistPlayerId'] ?? null);
                $history->setSequence($hd['sequence']);
                $this->em->persist($history);
            }

            $this->em->flush();

            return $game;
        });
    }

    public function validate(array $data): array
    {
        $errors = [];

        if (!isset($data['game'])) {
            $errors[] = 'Missing "game" key';
        } else {
            if (empty($data['game']['homeTeam'])) {
                $errors[] = 'game.homeTeam is required';
            }
            if (empty($data['game']['oppositionTeam'])) {
                $errors[] = 'game.oppositionTeam is required';
            }
            if (empty($data['game']['date'])) {
                $errors[] = 'game.date is required';
            }
        }

        if (!isset($data['players']) || !is_array($data['players'])) {
            $errors[] = 'Missing "players" array';
        }

        if (!isset($data['quarters']) || !is_array($data['quarters'])) {
            $errors[] = 'Missing "quarters" array';
        }

        return $errors;
    }
}
