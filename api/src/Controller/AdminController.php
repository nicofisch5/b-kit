<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrganizationRepository $orgRepo,
        private UserRepository $userRepo,
        private UserPasswordHasherInterface $hasher,
    ) {}

    // ── Organizations ──────────────────────────────────────

    #[Route('/organizations', methods: ['GET'])]
    public function listOrgs(): JsonResponse
    {
        $orgs = $this->orgRepo->findAllOrdered();
        return $this->json(['data' => array_map(fn($o) => $this->serializeOrg($o), $orgs)]);
    }

    #[Route('/organizations', methods: ['POST'])]
    public function createOrg(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        if (empty($body['name']) || empty($body['slug'])) {
            return $this->json(['error' => 'name and slug are required'], Response::HTTP_BAD_REQUEST);
        }
        $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $body['slug']));
        if ($this->orgRepo->findBySlug($slug)) {
            return $this->json(['error' => 'Slug already taken'], Response::HTTP_CONFLICT);
        }

        $org = new Organization();
        $org->setName(trim($body['name']));
        $org->setSlug($slug);
        $this->em->persist($org);
        $this->em->flush();

        return $this->json(['data' => $this->serializeOrg($org)], Response::HTTP_CREATED);
    }

    #[Route('/organizations/{orgId}', methods: ['PUT'])]
    public function updateOrg(string $orgId, Request $request): JsonResponse
    {
        $org = $this->orgRepo->find($orgId);
        if (!$org) return $this->json(['error' => 'Organization not found'], Response::HTTP_NOT_FOUND);

        $body = json_decode($request->getContent(), true) ?? [];
        if (!empty($body['name'])) $org->setName(trim($body['name']));
        if (!empty($body['slug'])) {
            $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $body['slug']));
            $existing = $this->orgRepo->findBySlug($slug);
            if ($existing && $existing->getId() !== $orgId) {
                return $this->json(['error' => 'Slug already taken'], Response::HTTP_CONFLICT);
            }
            $org->setSlug($slug);
        }
        $this->em->flush();
        return $this->json(['data' => $this->serializeOrg($org)]);
    }

    #[Route('/organizations/{orgId}', methods: ['DELETE'])]
    public function deleteOrg(string $orgId): JsonResponse
    {
        $org = $this->orgRepo->find($orgId);
        if (!$org) return $this->json(['error' => 'Organization not found'], Response::HTTP_NOT_FOUND);
        $this->em->remove($org);
        $this->em->flush();
        return $this->json(['data' => ['deleted' => true]]);
    }

    // ── Users ───────────────────────────────────────────────

    #[Route('/organizations/{orgId}/users', methods: ['GET'])]
    public function listUsers(string $orgId): JsonResponse
    {
        $org = $this->orgRepo->find($orgId);
        if (!$org) return $this->json(['error' => 'Organization not found'], Response::HTTP_NOT_FOUND);
        $users = $this->userRepo->findByOrganization($orgId);
        return $this->json(['data' => array_map(fn($u) => $this->serializeUser($u), $users)]);
    }

    #[Route('/organizations/{orgId}/users', methods: ['POST'])]
    public function createUser(string $orgId, Request $request): JsonResponse
    {
        $org = $this->orgRepo->find($orgId);
        if (!$org) return $this->json(['error' => 'Organization not found'], Response::HTTP_NOT_FOUND);

        $body = json_decode($request->getContent(), true) ?? [];
        if (empty($body['email']) || empty($body['password'])) {
            return $this->json(['error' => 'email and password are required'], Response::HTTP_BAD_REQUEST);
        }
        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }
        if (strlen($body['password']) < 8) {
            return $this->json(['error' => 'Password must be at least 8 characters'], Response::HTTP_BAD_REQUEST);
        }
        $role = $body['role'] ?? User::ROLE_COACH;
        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_COACH])) {
            return $this->json(['error' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail(trim($body['email']));
        $user->setPassword($this->hasher->hashPassword($user, $body['password']));
        $user->setRole($role);
        $user->setOrganizationId($orgId);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json(['data' => $this->serializeUser($user)], Response::HTTP_CREATED);
    }

    #[Route('/users/{userId}', methods: ['PUT'])]
    public function updateUser(string $userId, Request $request): JsonResponse
    {
        $user = $this->userRepo->find($userId);
        if (!$user) return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);

        $body = json_decode($request->getContent(), true) ?? [];
        if (!empty($body['email'])) {
            if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail(trim($body['email']));
        }
        if (!empty($body['password'])) {
            if (strlen($body['password']) < 8) {
                return $this->json(['error' => 'Password must be at least 8 characters'], Response::HTTP_BAD_REQUEST);
            }
            $user->setPassword($this->hasher->hashPassword($user, $body['password']));
        }
        if (!empty($body['role']) && in_array($body['role'], [User::ROLE_ADMIN, User::ROLE_COACH])) {
            $user->setRole($body['role']);
        }
        $this->em->flush();
        return $this->json(['data' => $this->serializeUser($user)]);
    }

    #[Route('/users/{userId}', methods: ['DELETE'])]
    public function deleteUser(string $userId): JsonResponse
    {
        $user = $this->userRepo->find($userId);
        if (!$user) return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        $this->em->remove($user);
        $this->em->flush();
        return $this->json(['data' => ['deleted' => true]]);
    }

    private function serializeOrg(Organization $o): array
    {
        return ['id' => $o->getId(), 'name' => $o->getName(), 'slug' => $o->getSlug(), 'createdAt' => $o->getCreatedAt()->format('Y-m-d\TH:i:s')];
    }

    private function serializeUser(User $u): array
    {
        return ['id' => $u->getId(), 'email' => $u->getEmail(), 'role' => $u->getRole(), 'organizationId' => $u->getOrganizationId(), 'createdAt' => $u->getCreatedAt()->format('Y-m-d\TH:i:s')];
    }
}
