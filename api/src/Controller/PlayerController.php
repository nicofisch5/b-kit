<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/players')]
class PlayerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PlayerRepository $playerRepo,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $teamId = $request->query->get('teamId');
        $category = $request->query->get('category');
        $search = $request->query->get('search');

        $orgId = $this->sec->getOrgFilter();
        $players = $this->playerRepo->findFiltered($orgId, $teamId, $category, $search);

        return $this->json(['data' => array_map(fn(Player $p) => $this->serialize($p), $players)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $errors = $this->validateBody($body);
        if (!empty($errors)) {
            return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $player = new Player();
        $player->setFirstname(trim($body['firstname']));
        $player->setLastname(trim($body['lastname']));
        $player->setOrganizationId($this->sec->getOrgFilter());
        if (!empty($body['dob'])) {
            $player->setDob(new \DateTime($body['dob']));
        }
        if (isset($body['jerseyNumber'])) {
            $player->setJerseyNumber((int) $body['jerseyNumber']);
        }
        $this->em->persist($player);
        $this->em->flush();

        return $this->json(['data' => $this->serialize($player)], Response::HTTP_CREATED);
    }

    #[Route('/{playerId}', methods: ['GET'])]
    public function show(string $playerId): JsonResponse
    {
        $player = $this->playerRepo->find($playerId);
        if (!$player) {
            return $this->json(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }
        if ($player->getOrganizationId() !== null) {
            $this->sec->assertSameOrg($player->getOrganizationId());
        }

        return $this->json(['data' => $this->serializeDetail($player)]);
    }

    #[Route('/{playerId}', methods: ['PUT'])]
    public function update(string $playerId, Request $request): JsonResponse
    {
        $player = $this->playerRepo->find($playerId);
        if (!$player) {
            return $this->json(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }
        if ($player->getOrganizationId() !== null) {
            $this->sec->assertSameOrg($player->getOrganizationId());
        }

        $body = json_decode($request->getContent(), true) ?? [];

        if (isset($body['firstname'])) $player->setFirstname(trim($body['firstname']));
        if (isset($body['lastname'])) $player->setLastname(trim($body['lastname']));
        if (array_key_exists('dob', $body)) {
            $player->setDob($body['dob'] ? new \DateTime($body['dob']) : null);
        }
        if (array_key_exists('jerseyNumber', $body)) {
            $player->setJerseyNumber($body['jerseyNumber'] !== null ? (int) $body['jerseyNumber'] : null);
        }

        $this->em->flush();

        return $this->json(['data' => $this->serializeDetail($player)]);
    }

    #[Route('/{playerId}', methods: ['DELETE'])]
    public function delete(string $playerId): JsonResponse
    {
        $player = $this->playerRepo->find($playerId);
        if (!$player) {
            return $this->json(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }
        if ($player->getOrganizationId() !== null) {
            $this->sec->assertSameOrg($player->getOrganizationId());
        }

        $this->em->remove($player);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    private function serialize(Player $p): array
    {
        $teams = [];
        foreach ($p->getTeamPlayers() as $tp) {
            $t = $tp->getTeam();
            $teams[] = ['id' => $t->getId(), 'name' => $t->getName(), 'color' => $t->getColor(), 'category' => $t->getCategory()];
        }

        return [
            'id' => $p->getId(),
            'firstname' => $p->getFirstname(),
            'lastname' => $p->getLastname(),
            'name' => $p->getName(),
            'dob' => $p->getDob()?->format('Y-m-d'),
            'jerseyNumber' => $p->getJerseyNumber(),
            'teams' => $teams,
        ];
    }

    private function serializeDetail(Player $p): array
    {
        return $this->serialize($p);
    }

    private function validateBody(array $body): array
    {
        $errors = [];
        if (empty($body['firstname'])) $errors[] = 'firstname is required';
        if (empty($body['lastname'])) $errors[] = 'lastname is required';
        return $errors;
    }
}
