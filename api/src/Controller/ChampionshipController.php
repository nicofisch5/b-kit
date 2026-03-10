<?php

namespace App\Controller;

use App\Entity\Championship;
use App\Entity\ChampionshipTeam;
use App\Repository\ChampionshipRepository;
use App\Repository\ChampionshipTeamRepository;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/championships')]
class ChampionshipController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ChampionshipRepository $champRepo,
        private ChampionshipTeamRepository $champTeamRepo,
        private TeamRepository $teamRepo,
        private GameRepository $gameRepo,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $orgId = $this->sec->getOrgFilter();
        $champIds = $this->sec->getCoachChampionshipIds();
        $championships = $this->champRepo->findAllOrdered($orgId, $champIds);
        return $this->json(['data' => array_map(fn($c) => $this->serialize($c), $championships)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        if (empty($body['name'])) {
            return $this->json(['error' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $championship = new Championship();
        $championship->setName(trim($body['name']));
        $championship->setOrganizationId($this->sec->requireOrg()->getId());
        $this->em->persist($championship);
        $this->em->flush();

        return $this->json(['data' => $this->serialize($championship)], Response::HTTP_CREATED);
    }

    #[Route('/{champId}', methods: ['GET'])]
    public function show(string $champId): JsonResponse
    {
        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessChampionship($championship);
        return $this->json(['data' => $this->serializeDetail($championship)]);
    }

    #[Route('/{champId}', methods: ['PUT'])]
    public function update(string $champId, Request $request): JsonResponse
    {
        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessChampionship($championship);

        $body = json_decode($request->getContent(), true) ?? [];
        if (!empty($body['name'])) {
            $championship->setName(trim($body['name']));
        }
        $this->em->flush();

        return $this->json(['data' => $this->serialize($championship)]);
    }

    #[Route('/{champId}', methods: ['DELETE'])]
    public function delete(string $champId): JsonResponse
    {
        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessChampionship($championship);

        // Unlink games from this championship before deleting
        $games = $this->gameRepo->findBy(['championshipId' => $champId]);
        foreach ($games as $game) {
            $game->setChampionshipId(null);
        }

        $this->em->remove($championship);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    // ── Teams ──────────────────────────────────────────────────────────

    #[Route('/{champId}/teams', methods: ['GET'])]
    public function listTeams(string $champId): JsonResponse
    {
        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }

        $teams = [];
        foreach ($championship->getChampionshipTeams() as $ct) {
            $t = $ct->getTeam();
            $teams[] = [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'shortName' => $t->getShortName(),
                'color' => $t->getColor(),
                'category' => $t->getCategory(),
                'groupName' => $ct->getGroupName(),
            ];
        }

        return $this->json(['data' => $teams]);
    }

    #[Route('/{champId}/teams', methods: ['POST'])]
    public function addTeam(string $champId, Request $request): JsonResponse
    {
        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $teamId = $body['teamId'] ?? null;
        if (!$teamId) {
            return $this->json(['error' => 'teamId is required'], Response::HTTP_BAD_REQUEST);
        }

        $team = $this->teamRepo->find($teamId);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }

        if ($this->champTeamRepo->findByPair($champId, $teamId)) {
            return $this->json(['error' => 'Team already in this championship'], Response::HTTP_CONFLICT);
        }

        $ct = new ChampionshipTeam();
        $ct->setChampionship($championship);
        $ct->setTeam($team);
        $ct->setGroupName(!empty($body['groupName']) ? trim($body['groupName']) : null);
        $this->em->persist($ct);
        $this->em->flush();

        return $this->json(['data' => [
            'id' => $team->getId(),
            'name' => $team->getName(),
            'shortName' => $team->getShortName(),
            'color' => $team->getColor(),
            'category' => $team->getCategory(),
            'groupName' => $ct->getGroupName(),
        ]], Response::HTTP_CREATED);
    }

    #[Route('/{champId}/teams/{teamId}', methods: ['PUT'])]
    public function updateTeam(string $champId, string $teamId, Request $request): JsonResponse
    {
        $ct = $this->champTeamRepo->findByPair($champId, $teamId);
        if (!$ct) {
            return $this->json(['error' => 'Team not in this championship'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $ct->setGroupName(array_key_exists('groupName', $body) ? ($body['groupName'] ? trim($body['groupName']) : null) : $ct->getGroupName());
        $this->em->flush();

        return $this->json(['data' => ['groupName' => $ct->getGroupName()]]);
    }

    #[Route('/{champId}/teams/{teamId}', methods: ['DELETE'])]
    public function removeTeam(string $champId, string $teamId): JsonResponse
    {
        $ct = $this->champTeamRepo->findByPair($champId, $teamId);
        if (!$ct) {
            return $this->json(['error' => 'Team not in this championship'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($ct);
        $this->em->flush();

        return $this->json(['data' => ['removed' => true]]);
    }

    // ── Games ──────────────────────────────────────────────────────────

    #[Route('/{champId}/games', methods: ['GET'])]
    public function listGames(string $champId): JsonResponse
    {
        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessChampionship($championship);

        $games = $this->gameRepo->findBy(['championshipId' => $champId], ['date' => 'DESC']);

        $data = array_map(fn($g) => [
            'id' => $g->getId(),
            'homeTeam' => $g->getHomeTeam(),
            'oppositionTeam' => $g->getOppositionTeam(),
            'date' => $g->getDate()->format('Y-m-d\TH:i:s'),
            'status' => $g->getStatus()->value,
            'teamId' => $g->getTeamId(),
        ], $games);

        return $this->json(['data' => $data]);
    }

    #[Route('/{champId}/games', methods: ['POST'])]
    public function linkGame(string $champId, Request $request): JsonResponse
    {
        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $gameId = $body['gameId'] ?? null;
        if (!$gameId) {
            return $this->json(['error' => 'gameId is required'], Response::HTTP_BAD_REQUEST);
        }

        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $game->setChampionshipId($champId);
        $this->em->flush();

        return $this->json(['data' => [
            'id' => $game->getId(),
            'homeTeam' => $game->getHomeTeam(),
            'oppositionTeam' => $game->getOppositionTeam(),
            'date' => $game->getDate()->format('Y-m-d\TH:i:s'),
        ]]);
    }

    #[Route('/{champId}/games/{gameId}', methods: ['DELETE'])]
    public function unlinkGame(string $champId, string $gameId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game || $game->getChampionshipId() !== $champId) {
            return $this->json(['error' => 'Game not found in this championship'], Response::HTTP_NOT_FOUND);
        }

        $game->setChampionshipId(null);
        $this->em->flush();

        return $this->json(['data' => ['removed' => true]]);
    }

    private function serialize(Championship $c): array
    {
        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'teamCount' => $c->getChampionshipTeams()->count(),
            'seasonCount' => $c->getChampionshipSeasons()->count(),
            'createdAt' => $c->getCreatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }

    private function serializeDetail(Championship $c): array
    {
        $teams = [];
        foreach ($c->getChampionshipTeams() as $ct) {
            $t = $ct->getTeam();
            $teams[] = [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'shortName' => $t->getShortName(),
                'color' => $t->getColor(),
                'category' => $t->getCategory(),
                'groupName' => $ct->getGroupName(),
            ];
        }

        $seasons = [];
        foreach ($c->getChampionshipSeasons() as $cs) {
            $s = $cs->getSeason();
            $seasons[] = ['id' => $s->getId(), 'name' => $s->getName()];
        }

        $gameCount = $this->gameRepo->count(['championshipId' => $c->getId()]);

        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'teams' => $teams,
            'seasons' => $seasons,
            'gameCount' => $gameCount,
            'createdAt' => $c->getCreatedAt()->format('Y-m-d\TH:i:s'),
            'updatedAt' => $c->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }
}
