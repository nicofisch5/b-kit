<?php

namespace App\Controller;

use App\DTO\Request\CreateGameRequest;
use App\DTO\Response\GameDetailResponse;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Player;
use App\Entity\Quarter;
use App\Enum\GameStatus;
use App\Repository\GameRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/games')]
class GameController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameRepository $gameRepo,
        private ValidatorInterface $validator,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $teamId = $request->query->get('teamId') ?: null;
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $statusEnum = $status ? GameStatus::tryFrom($status) : null;
        $dateFromDt = $dateFrom ? new \DateTime($dateFrom) : null;
        $dateToDt = $dateTo ? new \DateTime($dateTo) : null;

        $orgId = $this->sec->getOrgFilter();
        $coachTeamIds = $this->sec->getCoachTeamIds();
        $coachChampIds = $this->sec->getCoachChampionshipIds();
        $games = $this->gameRepo->findFiltered($statusEnum, $dateFromDt, $dateToDt, $page, $limit, $teamId, $orgId, $coachTeamIds, $coachChampIds);
        $total = $this->gameRepo->countFiltered($statusEnum, $dateFromDt, $dateToDt, $teamId, $orgId, $coachTeamIds, $coachChampIds);

        $data = array_map(fn(Game $g) => [
            'id' => $g->getId(),
            'teamId' => $g->getTeamId(),
            'championshipId' => $g->getChampionshipId(),
            'homeTeam' => $g->getHomeTeam(),
            'oppositionTeam' => $g->getOppositionTeam(),
            'date' => $g->getDate()->format('Y-m-d\TH:i:s'),
            'oppositionScore' => $g->getOppositionScore(),
            'currentQuarter' => $g->getCurrentQuarter(),
            'status' => $g->getStatus()->value,
            'createdAt' => $g->getCreatedAt()->format('Y-m-d\TH:i:s'),
        ], $games);

        return $this->json([
            'data' => $data,
            'meta' => ['total' => $total, 'page' => $page, 'limit' => $limit],
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new CreateGameRequest();
        $dto->homeTeam = $body['homeTeam'] ?? '';
        $dto->oppositionTeam = $body['oppositionTeam'] ?? '';
        $dto->date = $body['date'] ?? '';
        $dto->oppositionScore = $body['oppositionScore'] ?? 0;
        $dto->players = $body['players'] ?? [];

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $game = new Game();
        if (!empty($body['teamId'])) {
            $game->setTeamId($body['teamId']);
        }
        $game->setOrganizationId($this->sec->requireOrg()->getId());
        $game->setHomeTeam($dto->homeTeam);
        $game->setOppositionTeam($dto->oppositionTeam);
        try {
            $game->setDate(new \DateTime($dto->date));
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }
        $game->setOppositionScore($dto->oppositionScore);
        $this->em->persist($game);

        // Auto-create 4 quarters
        $quarterNames = ['Q1', 'Q2', 'Q3', 'Q4'];
        foreach ($quarterNames as $i => $name) {
            $quarter = new Quarter();
            $quarter->setGame($game);
            $quarter->setQuarterName($name);
            $quarter->setSortOrder($i);
            $this->em->persist($quarter);
        }

        // Create optional players
        foreach ($dto->players as $i => $pd) {
            $player = new Player();
            $player->setName($pd['name'] ?? '');
            $this->em->persist($player);

            $gp = new GamePlayer();
            $gp->setGame($game);
            $gp->setPlayer($player);
            $gp->setJerseyNumber($pd['jerseyNumber'] ?? 0);
            $gp->setSortOrder($i);
            $this->em->persist($gp);
        }

        $this->em->flush();

        return $this->json(
            ['data' => GameDetailResponse::fromEntity($game)],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/{gameId}', methods: ['GET'])]
    public function show(string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessGame($game);

        return $this->json(['data' => GameDetailResponse::fromEntity($game)]);
    }

    #[Route('/{gameId}', methods: ['PUT'])]
    public function update(string $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessGame($game);

        $body = json_decode($request->getContent(), true) ?? [];

        if (isset($body['homeTeam'])) {
            $game->setHomeTeam($body['homeTeam']);
        }
        if (isset($body['oppositionTeam'])) {
            $game->setOppositionTeam($body['oppositionTeam']);
        }
        if (isset($body['date'])) {
            try {
                $game->setDate(new \DateTime($body['date']));
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (isset($body['oppositionScore'])) {
            $game->setOppositionScore((int) $body['oppositionScore']);
        }
        if (isset($body['currentQuarter'])) {
            $game->setCurrentQuarter($body['currentQuarter']);
        }

        $this->em->flush();

        return $this->json(['data' => GameDetailResponse::fromEntity($game)]);
    }

    #[Route('/{gameId}', methods: ['DELETE'])]
    public function delete(string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessGame($game);

        $this->em->remove($game);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    #[Route('/{gameId}/complete', methods: ['POST'])]
    public function complete(string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessGame($game);

        $game->setStatus(GameStatus::COMPLETED);
        $this->em->flush();

        return $this->json(['data' => GameDetailResponse::fromEntity($game)]);
    }
}
