<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Service\BoxScoreCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BoxScoreController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepo,
        private BoxScoreCalculator $boxScoreCalculator,
    ) {}

    #[Route('/api/v1/games/{gameId}/boxscore', methods: ['GET'])]
    public function boxscore(string $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $quarter = $request->query->get('quarter');

        $boxScore = $this->boxScoreCalculator->calculate($gameId, $quarter);

        return $this->json(['data' => $boxScore]);
    }
}
