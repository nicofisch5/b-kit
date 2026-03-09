<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameHistory;
use App\Entity\Player;
use App\Entity\Quarter;
use App\Entity\StatEvent;
use App\Enum\StatType;
use App\Repository\GameHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatRecorderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameHistoryRepository $historyRepo,
    ) {}

    /**
     * Records a stat event and optional assist, wrapped in a transaction.
     *
     * @return array{event: StatEvent, assistEvent: StatEvent|null, history: GameHistory}
     */
    public function record(
        Game $game,
        Player $player,
        Quarter $quarter,
        StatType $statType,
        ?\DateTimeInterface $timestamp = null,
        ?Player $assistPlayer = null,
    ): array {
        return $this->em->wrapInTransaction(function () use ($game, $player, $quarter, $statType, $timestamp, $assistPlayer) {
            $ts = $timestamp ?? new \DateTime();

            $event = new StatEvent();
            $event->setGame($game);
            $event->setPlayer($player);
            $event->setQuarter($quarter);
            $event->setStatType($statType);
            $event->setTimestamp($ts);
            $this->em->persist($event);

            $assistEvent = null;
            if ($assistPlayer !== null) {
                $assistEvent = new StatEvent();
                $assistEvent->setGame($game);
                $assistEvent->setPlayer($assistPlayer);
                $assistEvent->setQuarter($quarter);
                $assistEvent->setStatType(StatType::ASSIST);
                $assistEvent->setTimestamp($ts);
                $this->em->persist($assistEvent);
            }

            $sequence = $this->historyRepo->getNextSequence($game->getId());

            $history = new GameHistory();
            $history->setGame($game);
            $history->setEvent($event);
            $history->setPlayer($player);
            $history->setAssistEvent($assistEvent);
            $history->setAssistPlayerId($assistPlayer?->getId());
            $history->setSequence($sequence);
            $this->em->persist($history);

            $this->em->flush();

            return ['event' => $event, 'assistEvent' => $assistEvent, 'history' => $history];
        });
    }
}
