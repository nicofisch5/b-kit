<?php

namespace App\Controller;

use App\Entity\Drill;
use App\Repository\DrillRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/drills')]
class DrillController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private DrillRepository $drillRepo,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->sec->getCurrentUser();
        $orgId = $this->sec->getOrgFilter();
        $search = $request->query->get('search');
        $tag = $request->query->get('tag');

        $drills = $this->drillRepo->findVisible($orgId, $user->getId(), $search, $tag);

        return $this->json(['data' => array_map(fn(Drill $d) => $this->serialize($d), $drills)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (empty($body['code'])) {
            return $this->json(['error' => 'code is required'], Response::HTTP_BAD_REQUEST);
        }
        if (empty($body['name'])) {
            return $this->json(['error' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->sec->getCurrentUser();
        $orgId = $this->sec->getOrgFilter();

        $drill = new Drill();
        $drill->setCode(trim($body['code']));
        $drill->setName(trim($body['name']));
        $drill->setVisibility(in_array($body['visibility'] ?? '', ['org', 'personal']) ? $body['visibility'] : 'org');
        $drill->setSetup($body['setup'] ?? null);
        $drill->setExecution($body['execution'] ?? null);
        $drill->setRotation($body['rotation'] ?? null);
        $drill->setEvolution($body['evolution'] ?? null);
        $drill->setDuration(isset($body['duration']) && $body['duration'] !== '' ? (int)$body['duration'] : null);
        $drill->setEquipment($body['equipment'] ?? null);
        $drill->setMinimumPlayers(isset($body['minimumPlayers']) && $body['minimumPlayers'] !== '' ? (int)$body['minimumPlayers'] : null);
        $drill->setTags(is_array($body['tags'] ?? null) ? $body['tags'] : []);
        $drill->setLinks(is_array($body['links'] ?? null) ? $body['links'] : []);
        $drill->setCreatedBy($user->getId());
        $drill->setOrganizationId($orgId);

        $this->em->persist($drill);
        $this->em->flush();

        return $this->json(['data' => $this->serialize($drill)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $drill = $this->drillRepo->find($id);
        if (!$drill) {
            return $this->json(['error' => 'Drill not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanRead($drill);

        return $this->json(['data' => $this->serialize($drill)]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $drill = $this->drillRepo->find($id);
        if (!$drill) {
            return $this->json(['error' => 'Drill not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanEdit($drill);

        $body = json_decode($request->getContent(), true) ?? [];

        if (isset($body['code'])) $drill->setCode(trim($body['code']));
        if (isset($body['name'])) $drill->setName(trim($body['name']));
        if (array_key_exists('visibility', $body) && in_array($body['visibility'], ['org', 'personal'])) {
            $drill->setVisibility($body['visibility']);
        }
        if (array_key_exists('setup', $body)) $drill->setSetup($body['setup']);
        if (array_key_exists('execution', $body)) $drill->setExecution($body['execution']);
        if (array_key_exists('rotation', $body)) $drill->setRotation($body['rotation']);
        if (array_key_exists('evolution', $body)) $drill->setEvolution($body['evolution']);
        if (array_key_exists('duration', $body)) $drill->setDuration($body['duration'] !== '' && $body['duration'] !== null ? (int)$body['duration'] : null);
        if (array_key_exists('equipment', $body)) $drill->setEquipment($body['equipment']);
        if (array_key_exists('minimumPlayers', $body)) $drill->setMinimumPlayers($body['minimumPlayers'] !== '' && $body['minimumPlayers'] !== null ? (int)$body['minimumPlayers'] : null);
        if (array_key_exists('tags', $body) && is_array($body['tags'])) $drill->setTags($body['tags']);
        if (array_key_exists('links', $body) && is_array($body['links'])) $drill->setLinks($body['links']);

        $this->em->flush();

        return $this->json(['data' => $this->serialize($drill)]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $drill = $this->drillRepo->find($id);
        if (!$drill) {
            return $this->json(['error' => 'Drill not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanEdit($drill);

        $this->em->remove($drill);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    private function assertCanRead(Drill $drill): void
    {
        if ($this->sec->isSuperAdmin()) return;
        $user = $this->sec->getCurrentUser();
        if ($drill->getVisibility() === 'personal') {
            if ($drill->getCreatedBy() !== $user->getId()) {
                throw new AccessDeniedHttpException('Access denied.');
            }
            return;
        }
        // org drill
        $this->sec->assertSameOrg($drill->getOrganizationId() ?? '');
    }

    private function assertCanEdit(Drill $drill): void
    {
        if ($this->sec->isSuperAdmin()) return;
        $user = $this->sec->getCurrentUser();
        // Creator can always edit
        if ($drill->getCreatedBy() === $user->getId()) return;
        // Admin can edit org drills in same org
        if ($this->sec->isAdmin() && $drill->getOrganizationId() === $user->getOrganizationId()) return;
        throw new AccessDeniedHttpException('Access denied.');
    }

    private function serialize(Drill $drill): array
    {
        return [
            'id'             => $drill->getId(),
            'code'           => $drill->getCode(),
            'name'           => $drill->getName(),
            'setup'          => $drill->getSetup(),
            'execution'      => $drill->getExecution(),
            'rotation'       => $drill->getRotation(),
            'evolution'      => $drill->getEvolution(),
            'duration'       => $drill->getDuration(),
            'equipment'      => $drill->getEquipment(),
            'minimumPlayers' => $drill->getMinimumPlayers(),
            'tags'           => $drill->getTags(),
            'links'          => $drill->getLinks(),
            'visibility'     => $drill->getVisibility(),
            'organizationId' => $drill->getOrganizationId(),
            'createdBy'      => $drill->getCreatedBy(),
            'createdAt'      => $drill->getCreatedAt()->format('Y-m-d\TH:i:s'),
            'updatedAt'      => $drill->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }
}
