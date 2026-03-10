<?php

namespace App\Controller;

use App\Entity\ChampionshipSeason;
use App\Entity\Season;
use App\Repository\ChampionshipRepository;
use App\Repository\ChampionshipSeasonRepository;
use App\Repository\SeasonRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/seasons')]
class SeasonController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SeasonRepository $seasonRepo,
        private ChampionshipRepository $champRepo,
        private ChampionshipSeasonRepository $champSeasonRepo,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $orgId = $this->sec->getOrgFilter();
        $seasons = $this->seasonRepo->findAllOrdered($orgId);
        return $this->json(['data' => array_map(fn($s) => $this->serialize($s), $seasons)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        if (empty($body['name'])) {
            return $this->json(['error' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $season = new Season();
        $season->setName(trim($body['name']));
        $season->setOrganizationId($this->sec->requireOrg()->getId());
        $this->em->persist($season);
        $this->em->flush();

        return $this->json(['data' => $this->serialize($season)], Response::HTTP_CREATED);
    }

    #[Route('/{seasonId}', methods: ['GET'])]
    public function show(string $seasonId): JsonResponse
    {
        $season = $this->seasonRepo->find($seasonId);
        if (!$season) {
            return $this->json(['error' => 'Season not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertSameOrg($season->getOrganizationId() ?? '');
        return $this->json(['data' => $this->serializeDetail($season)]);
    }

    #[Route('/{seasonId}', methods: ['PUT'])]
    public function update(string $seasonId, Request $request): JsonResponse
    {
        $season = $this->seasonRepo->find($seasonId);
        if (!$season) {
            return $this->json(['error' => 'Season not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertSameOrg($season->getOrganizationId() ?? '');

        $body = json_decode($request->getContent(), true) ?? [];
        if (!empty($body['name'])) {
            $season->setName(trim($body['name']));
        }
        $this->em->flush();

        return $this->json(['data' => $this->serialize($season)]);
    }

    #[Route('/{seasonId}', methods: ['DELETE'])]
    public function delete(string $seasonId): JsonResponse
    {
        $season = $this->seasonRepo->find($seasonId);
        if (!$season) {
            return $this->json(['error' => 'Season not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertSameOrg($season->getOrganizationId() ?? '');

        $this->em->remove($season);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    #[Route('/{seasonId}/championships', methods: ['GET'])]
    public function listChampionships(string $seasonId): JsonResponse
    {
        $season = $this->seasonRepo->find($seasonId);
        if (!$season) {
            return $this->json(['error' => 'Season not found'], Response::HTTP_NOT_FOUND);
        }

        $championships = [];
        foreach ($season->getChampionshipSeasons() as $cs) {
            $c = $cs->getChampionship();
            $championships[] = [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'teamCount' => $c->getChampionshipTeams()->count(),
            ];
        }

        return $this->json(['data' => $championships]);
    }

    #[Route('/{seasonId}/championships', methods: ['POST'])]
    public function addChampionship(string $seasonId, Request $request): JsonResponse
    {
        $season = $this->seasonRepo->find($seasonId);
        if (!$season) {
            return $this->json(['error' => 'Season not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $champId = $body['championshipId'] ?? null;
        if (!$champId) {
            return $this->json(['error' => 'championshipId is required'], Response::HTTP_BAD_REQUEST);
        }

        $championship = $this->champRepo->find($champId);
        if (!$championship) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }

        if ($this->champSeasonRepo->findByPair($champId, $seasonId)) {
            return $this->json(['error' => 'Championship already linked to this season'], Response::HTTP_CONFLICT);
        }

        $cs = new ChampionshipSeason();
        $cs->setChampionship($championship);
        $cs->setSeason($season);
        $this->em->persist($cs);
        $this->em->flush();

        return $this->json(['data' => [
            'id' => $championship->getId(),
            'name' => $championship->getName(),
        ]], Response::HTTP_CREATED);
    }

    #[Route('/{seasonId}/championships/{champId}', methods: ['DELETE'])]
    public function removeChampionship(string $seasonId, string $champId): JsonResponse
    {
        $cs = $this->champSeasonRepo->findByPair($champId, $seasonId);
        if (!$cs) {
            return $this->json(['error' => 'Championship not linked to this season'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($cs);
        $this->em->flush();

        return $this->json(['data' => ['removed' => true]]);
    }

    private function serialize(Season $s): array
    {
        return [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'championshipCount' => $s->getChampionshipSeasons()->count(),
            'createdAt' => $s->getCreatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }

    private function serializeDetail(Season $s): array
    {
        $championships = [];
        foreach ($s->getChampionshipSeasons() as $cs) {
            $c = $cs->getChampionship();
            $championships[] = [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'teamCount' => $c->getChampionshipTeams()->count(),
            ];
        }

        return [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'championships' => $championships,
            'createdAt' => $s->getCreatedAt()->format('Y-m-d\TH:i:s'),
            'updatedAt' => $s->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }
}
