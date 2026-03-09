<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Service\GameExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepo,
        private GameExportService $exportService,
    ) {}

    #[Route('/api/v1/games/{gameId}/export', methods: ['GET'])]
    public function export(string $gameId, Request $request): JsonResponse|Response
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $format = $request->query->get('format', 'json');

        if ($format === 'csv') {
            $csv = $this->exportService->exportCsv($game);
            return new Response($csv, Response::HTTP_OK, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="game-' . $gameId . '-boxscore.csv"',
            ]);
        }

        $data = $this->exportService->exportJson($game);
        return $this->json(['data' => $data]);
    }
}
