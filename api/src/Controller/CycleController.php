<?php

namespace App\Controller;

use App\Entity\Cycle;
use App\Repository\CycleRepository;
use App\Repository\TrainingSessionRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/cycles')]
class CycleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CycleRepository $cycleRepo,
        private TrainingSessionRepository $sessionRepo,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user   = $this->sec->getCurrentUser();
        $cycles = $this->cycleRepo->findForUser($user->getId());

        return $this->json(['data' => array_map(fn(Cycle $c) => $this->serialize($c), $cycles)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (empty($body['name'])) {
            return $this->json(['error' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->sec->getCurrentUser();

        $cycle = new Cycle();
        $cycle->setName(trim($body['name']));
        $cycle->setDescription($body['description'] ?? null);
        $cycle->setStartDate(isset($body['startDate']) && $body['startDate'] ? new \DateTime($body['startDate']) : null);
        $cycle->setEndDate(isset($body['endDate']) && $body['endDate'] ? new \DateTime($body['endDate']) : null);
        $cycle->setOutcome($body['outcome'] ?? null);
        $cycle->setCreatedBy($user->getId());
        $cycle->setOrganizationId($this->sec->getOrgFilter());

        $this->em->persist($cycle);
        $this->em->flush();

        return $this->json(['data' => $this->serialize($cycle)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $cycle = $this->cycleRepo->find($id);
        if (!$cycle) {
            return $this->json(['error' => 'Cycle not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertOwner($cycle);

        $user     = $this->sec->getCurrentUser();
        $sessions = $this->sessionRepo->findByCycle($cycle->getId(), $user->getId());

        return $this->json(['data' => $this->serializeDetail($cycle, $sessions)]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $cycle = $this->cycleRepo->find($id);
        if (!$cycle) {
            return $this->json(['error' => 'Cycle not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertOwner($cycle);

        $body = json_decode($request->getContent(), true) ?? [];

        if (isset($body['name'])) $cycle->setName(trim($body['name']));
        if (array_key_exists('description', $body)) $cycle->setDescription($body['description']);
        if (array_key_exists('startDate', $body)) {
            $cycle->setStartDate($body['startDate'] ? new \DateTime($body['startDate']) : null);
        }
        if (array_key_exists('endDate', $body)) {
            $cycle->setEndDate($body['endDate'] ? new \DateTime($body['endDate']) : null);
        }
        if (array_key_exists('outcome', $body)) $cycle->setOutcome($body['outcome']);

        $this->em->flush();

        return $this->json(['data' => $this->serialize($cycle)]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $cycle = $this->cycleRepo->find($id);
        if (!$cycle) {
            return $this->json(['error' => 'Cycle not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertOwner($cycle);

        $this->em->remove($cycle);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function assertOwner(Cycle $cycle): void
    {
        if ($this->sec->isSuperAdmin()) return;
        $user = $this->sec->getCurrentUser();
        if ($cycle->getCreatedBy() !== $user->getId()) {
            throw new AccessDeniedHttpException('Access denied.');
        }
    }

    private function serialize(Cycle $cycle): array
    {
        return [
            'id'          => $cycle->getId(),
            'name'        => $cycle->getName(),
            'description' => $cycle->getDescription(),
            'startDate'   => $cycle->getStartDate()?->format('Y-m-d'),
            'endDate'     => $cycle->getEndDate()?->format('Y-m-d'),
            'outcome'     => $cycle->getOutcome(),
            'createdBy'   => $cycle->getCreatedBy(),
            'createdAt'   => $cycle->getCreatedAt()->format('Y-m-d\TH:i:s'),
            'updatedAt'   => $cycle->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }

    private function serializeDetail(Cycle $cycle, array $sessions): array
    {
        $data = $this->serialize($cycle);
        $data['sessions'] = array_map(fn($s) => [
            'id'         => $s->getId(),
            'date'       => $s->getDate()->format('Y-m-d'),
            'goal'       => $s->getGoal(),
            'duration'   => $s->getDuration(),
            'drillCount' => $s->getSessionDrills()->count(),
        ], $sessions);

        return $data;
    }
}
