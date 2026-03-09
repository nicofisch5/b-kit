<?php

namespace App\Controller;

use App\Entity\Quarter;
use App\Repository\GameRepository;
use App\Repository\QuarterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/games/{gameId}/quarters')]
class QuarterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameRepository $gameRepo,
        private QuarterRepository $quarterRepo,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $quarters = [];
        foreach ($game->getQuarters() as $q) {
            $quarters[] = [
                'id' => $q->getId(),
                'quarterName' => $q->getQuarterName(),
                'sortOrder' => $q->getSortOrder(),
            ];
        }

        return $this->json(['data' => $quarters]);
    }

    #[Route('', methods: ['POST'])]
    public function addOvertime(string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $otCount = $game->getOvertimeCount() + 1;
        $quarterName = $otCount === 1 ? 'OT' : 'OT' . $otCount;

        // Check for duplicate
        $existing = $this->quarterRepo->findByGameAndName($gameId, $quarterName);
        if ($existing) {
            return $this->json(['error' => 'Quarter already exists'], Response::HTTP_CONFLICT);
        }

        $sortOrder = $game->getQuarters()->count();

        $quarter = new Quarter();
        $quarter->setGame($game);
        $quarter->setQuarterName($quarterName);
        $quarter->setSortOrder($sortOrder);
        $this->em->persist($quarter);

        $game->setOvertimeCount($otCount);
        $game->setCurrentQuarter($quarterName);

        $this->em->flush();

        return $this->json(['data' => [
            'id' => $quarter->getId(),
            'quarterName' => $quarter->getQuarterName(),
            'sortOrder' => $quarter->getSortOrder(),
        ]], Response::HTTP_CREATED);
    }

    #[Route('/{quarterId}', methods: ['DELETE'])]
    public function remove(string $gameId, string $quarterId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $quarter = $this->quarterRepo->find($quarterId);
        if (!$quarter || $quarter->getGame()->getId() !== $gameId) {
            return $this->json(['error' => 'Quarter not found'], Response::HTTP_NOT_FOUND);
        }

        // Only allow deleting OT quarters
        if (in_array($quarter->getQuarterName(), ['Q1', 'Q2', 'Q3', 'Q4'], true)) {
            return $this->json(['error' => 'Cannot delete standard quarters'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Only allow if empty (no stat events)
        if ($quarter->getStatEvents()->count() > 0) {
            return $this->json(['error' => 'Cannot delete quarter with recorded stats'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->remove($quarter);

        // Decrement overtime count
        $game->setOvertimeCount(max(0, $game->getOvertimeCount() - 1));

        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }
}
