<?php

namespace App\Controller;

use App\DTO\Response\GameDetailResponse;
use App\Service\GameImportService;
use App\Service\SecurityService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportController extends AbstractController
{
    public function __construct(
        private GameImportService $importService,
        private SecurityService $sec,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/v1/games/import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->importService->validate($data);
        if (!empty($errors)) {
            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $orgId = $this->sec->getOrgFilter(); // null for SuperAdmin (keeps existing org from payload)
        try {
            $game = $this->importService->import($data, $orgId);
        } catch (\Exception $e) {
            $this->logger->error('Game import failed', ['exception' => $e]);
            return $this->json(['error' => 'Import failed. Please check the file and try again.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(
            ['data' => GameDetailResponse::fromEntity($game)],
            Response::HTTP_CREATED,
        );
    }
}
