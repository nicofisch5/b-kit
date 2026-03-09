<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class UndoService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameHistoryRepository $historyRepo,
    ) {}

    /**
     * Undoes the last action for a game by popping the highest sequence from history.
     *
     * @return array{undone: bool, message: string}
     */
    public function undoLast(Game $game): array
    {
        return $this->em->wrapInTransaction(function () use ($game) {
            $latest = $this->historyRepo->findLatestByGame($game->getId());

            if ($latest === null) {
                return ['undone' => false, 'message' => 'Nothing to undo'];
            }

            // Remove the assist event if it exists
            $assistEvent = $latest->getAssistEvent();
            if ($assistEvent !== null) {
                $this->em->remove($assistEvent);
            }

            // Remove the main stat event
            $this->em->remove($latest->getEvent());

            // Remove the history entry
            $this->em->remove($latest);

            $this->em->flush();

            return ['undone' => true, 'message' => 'Last action undone (sequence ' . $latest->getSequence() . ')'];
        });
    }
}
