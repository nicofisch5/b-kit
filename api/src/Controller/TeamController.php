<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamPlayer;
use App\Repository\PlayerRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/teams')]
class TeamController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TeamRepository $teamRepo,
        private PlayerRepository $playerRepo,
        private TeamPlayerRepository $teamPlayerRepo,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $orgId = $this->sec->getOrgFilter();
        $teamIds = $this->sec->getCoachTeamIds();
        $teams = $this->teamRepo->findAllOrdered($orgId, $teamIds);

        $data = array_map(fn(Team $t) => $this->serialize($t), $teams);

        return $this->json(['data' => $data]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $errors = $this->validateTeamBody($body);
        if (!empty($errors)) {
            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $team = new Team();
        $team->setName($body['name']);
        $team->setShortName($body['shortName']);
        $team->setColor($body['color']);
        $team->setCategory($body['category']);
        $team->setOrganizationId($this->sec->requireOrg()->getId());
        $this->em->persist($team);
        $this->em->flush();

        return $this->json(['data' => $this->serialize($team)], Response::HTTP_CREATED);
    }

    #[Route('/{teamId}', methods: ['GET'])]
    public function show(string $teamId): JsonResponse
    {
        $team = $this->teamRepo->find($teamId);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessTeam($team);

        return $this->json(['data' => $this->serialize($team)]);
    }

    #[Route('/{teamId}', methods: ['PUT'])]
    public function update(string $teamId, Request $request): JsonResponse
    {
        $team = $this->teamRepo->find($teamId);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessTeam($team);

        $body = json_decode($request->getContent(), true) ?? [];

        if (isset($body['name'])) $team->setName($body['name']);
        if (isset($body['shortName'])) $team->setShortName($body['shortName']);
        if (isset($body['color'])) $team->setColor($body['color']);
        if (isset($body['category'])) $team->setCategory($body['category']);

        $this->em->flush();

        return $this->json(['data' => $this->serialize($team)]);
    }

    #[Route('/{teamId}', methods: ['DELETE'])]
    public function delete(string $teamId): JsonResponse
    {
        $team = $this->teamRepo->find($teamId);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessTeam($team);

        $this->em->remove($team);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    #[Route('/{teamId}/players', methods: ['GET'])]
    public function listPlayers(string $teamId): JsonResponse
    {
        $team = $this->teamRepo->find($teamId);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessTeam($team);

        $players = [];
        foreach ($team->getTeamPlayers() as $tp) {
            $p = $tp->getPlayer();
            $players[] = [
                'id' => $p->getId(),
                'firstname' => $p->getFirstname(),
                'lastname' => $p->getLastname(),
                'name' => $p->getName(),
                'dob' => $p->getDob()?->format('Y-m-d'),
                'jerseyNumber' => $p->getJerseyNumber(),
            ];
        }

        return $this->json(['data' => $players]);
    }

    #[Route('/{teamId}/players', methods: ['POST'])]
    public function addPlayer(string $teamId, Request $request): JsonResponse
    {
        $team = $this->teamRepo->find($teamId);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessTeam($team);

        $body = json_decode($request->getContent(), true) ?? [];
        $playerId = $body['playerId'] ?? null;

        if (!$playerId) {
            return $this->json(['error' => 'playerId is required'], Response::HTTP_BAD_REQUEST);
        }

        $player = $this->playerRepo->find($playerId);
        if (!$player) {
            return $this->json(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        // Check already in team
        if ($this->teamPlayerRepo->findByTeamAndPlayer($teamId, $playerId)) {
            return $this->json(['error' => 'Player already in this team'], Response::HTTP_CONFLICT);
        }

        $tp = new TeamPlayer();
        $tp->setTeam($team);
        $tp->setPlayer($player);
        $this->em->persist($tp);
        $this->em->flush();

        return $this->json(['data' => [
            'id' => $player->getId(),
            'firstname' => $player->getFirstname(),
            'lastname' => $player->getLastname(),
            'name' => $player->getName(),
            'jerseyNumber' => $player->getJerseyNumber(),
        ]], Response::HTTP_CREATED);
    }

    #[Route('/{teamId}/players/{playerId}', methods: ['DELETE'])]
    public function removePlayer(string $teamId, string $playerId): JsonResponse
    {
        $team = $this->teamRepo->find($teamId);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessTeam($team);

        $tp = $this->teamPlayerRepo->findByTeamAndPlayer($teamId, $playerId);
        if (!$tp) {
            return $this->json(['error' => 'Player not found in this team'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($tp);
        $this->em->flush();

        return $this->json(['data' => ['removed' => true]]);
    }

    private function serialize(Team $team): array
    {
        return [
            'id' => $team->getId(),
            'name' => $team->getName(),
            'shortName' => $team->getShortName(),
            'color' => $team->getColor(),
            'category' => $team->getCategory(),
            'playerCount' => $team->getTeamPlayers()->count(),
            'createdAt' => $team->getCreatedAt()->format('Y-m-d\TH:i:s'),
            'updatedAt' => $team->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }

    private function validateTeamBody(array $body): array
    {
        $errors = [];
        if (empty($body['name'])) $errors[] = 'name is required';
        if (empty($body['shortName'])) $errors[] = 'shortName is required';
        if (empty($body['color'])) $errors[] = 'color is required';
        if (empty($body['category'])) $errors[] = 'category is required';
        return $errors;
    }
}
