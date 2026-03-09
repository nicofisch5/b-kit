<?php

namespace App\Controller;

use App\DTO\Request\AddPlayerRequest;
use App\Entity\GamePlayer;
use App\Entity\Player;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/games/{gameId}/players')]
class GamePlayerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameRepository $gameRepo,
        private PlayerRepository $playerRepo,
        private GamePlayerRepository $gamePlayerRepo,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $players = [];
        foreach ($game->getGamePlayers() as $gp) {
            $players[] = [
                'id' => $gp->getPlayer()->getId(),
                'name' => $gp->getPlayer()->getName(),
                'jerseyNumber' => $gp->getJerseyNumber(),
                'sortOrder' => $gp->getSortOrder(),
            ];
        }

        return $this->json(['data' => $players]);
    }

    #[Route('', methods: ['POST'])]
    public function add(string $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new AddPlayerRequest();
        $dto->name = $body['name'] ?? '';
        $dto->jerseyNumber = $body['jerseyNumber'] ?? 0;

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Check jersey number uniqueness within this game
        foreach ($game->getGamePlayers() as $existing) {
            if ($existing->getJerseyNumber() === $dto->jerseyNumber) {
                return $this->json(['error' => 'Jersey number already in use for this game'], Response::HTTP_CONFLICT);
            }
        }

        $player = new Player();
        $player->setName($dto->name);
        $this->em->persist($player);

        $sortOrder = $game->getGamePlayers()->count();

        $gp = new GamePlayer();
        $gp->setGame($game);
        $gp->setPlayer($player);
        $gp->setJerseyNumber($dto->jerseyNumber);
        $gp->setSortOrder($sortOrder);
        $this->em->persist($gp);

        $this->em->flush();

        return $this->json(['data' => [
            'id' => $player->getId(),
            'name' => $player->getName(),
            'jerseyNumber' => $gp->getJerseyNumber(),
            'sortOrder' => $gp->getSortOrder(),
        ]], Response::HTTP_CREATED);
    }

    #[Route('/{playerId}', methods: ['PUT'])]
    public function update(string $gameId, string $playerId, Request $request): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $gamePlayer = null;
        foreach ($game->getGamePlayers() as $gp) {
            if ($gp->getPlayer()->getId() === $playerId) {
                $gamePlayer = $gp;
                break;
            }
        }

        if (!$gamePlayer) {
            return $this->json(['error' => 'Player not found in this game'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        if (isset($body['name'])) {
            $gamePlayer->getPlayer()->setName($body['name']);
        }

        if (isset($body['jerseyNumber'])) {
            $newJersey = (int) $body['jerseyNumber'];
            // Check uniqueness
            foreach ($game->getGamePlayers() as $existing) {
                if ($existing->getPlayer()->getId() !== $playerId && $existing->getJerseyNumber() === $newJersey) {
                    return $this->json(['error' => 'Jersey number already in use for this game'], Response::HTTP_CONFLICT);
                }
            }
            $gamePlayer->setJerseyNumber($newJersey);
        }

        $this->em->flush();

        return $this->json(['data' => [
            'id' => $gamePlayer->getPlayer()->getId(),
            'name' => $gamePlayer->getPlayer()->getName(),
            'jerseyNumber' => $gamePlayer->getJerseyNumber(),
            'sortOrder' => $gamePlayer->getSortOrder(),
        ]]);
    }

    #[Route('/{playerId}', methods: ['DELETE'])]
    public function remove(string $gameId, string $playerId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $count = $this->gamePlayerRepo->countByGame($gameId);
        if ($count <= 5) {
            return $this->json(['error' => 'Cannot remove player: minimum 5 players required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $gamePlayer = null;
        foreach ($game->getGamePlayers() as $gp) {
            if ($gp->getPlayer()->getId() === $playerId) {
                $gamePlayer = $gp;
                break;
            }
        }

        if (!$gamePlayer) {
            return $this->json(['error' => 'Player not found in this game'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($gamePlayer);
        $this->em->flush();

        return $this->json(['data' => ['removed' => true]]);
    }
}
