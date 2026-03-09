<?php

namespace App\Controller;

use App\Repository\GameHistoryRepository;
use App\Repository\GameRepository;
use App\Service\UndoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/games/{gameId}')]
class UndoController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepo,
        private GameHistoryRepository $historyRepo,
        private UndoService $undoService,
    ) {}

    #[Route('/undo', methods: ['POST'])]
    public function undo(string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->undoService->undoLast($game);

        if (!$result['undone']) {
            return $this->json(['error' => $result['message']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['data' => $result]);
    }

    #[Route('/history', methods: ['GET'])]
    public function history(string $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $entries = $this->historyRepo->findByGamePaginated($gameId, $page, $limit);

        $data = array_map(fn($h) => [
            'id' => $h->getId(),
            'sequence' => $h->getSequence(),
            'eventId' => $h->getEvent()->getId(),
            'playerId' => $h->getPlayer()->getId(),
            'playerName' => $h->getPlayer()->getName(),
            'statType' => $h->getEvent()->getStatType()->value,
            'assistEventId' => $h->getAssistEvent()?->getId(),
            'assistPlayerId' => $h->getAssistPlayerId(),
            'createdAt' => $h->getCreatedAt()->format('Y-m-d\TH:i:s'),
        ], $entries);

        return $this->json(['data' => $data, 'meta' => ['page' => $page, 'limit' => $limit]]);
    }
}
