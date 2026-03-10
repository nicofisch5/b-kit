<?php

namespace App\Controller;

use App\Entity\CoachChampionship;
use App\Entity\CoachTeam;
use App\Entity\User;
use App\Repository\ChampionshipRepository;
use App\Repository\CoachChampionshipRepository;
use App\Repository\CoachTeamRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/org')]
class OrgController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SecurityService $sec,
        private UserRepository $userRepo,
        private TeamRepository $teamRepo,
        private ChampionshipRepository $champRepo,
        private CoachTeamRepository $coachTeamRepo,
        private CoachChampionshipRepository $coachChampRepo,
        private UserPasswordHasherInterface $hasher,
    ) {}

    // ── Users in own org ────────────────────────────────────

    #[Route('/users', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        $org = $this->sec->requireOrg();
        $users = $this->userRepo->findByOrganization($org->getId());
        return $this->json(['data' => array_map(fn($u) => $this->serializeUser($u), $users)]);
    }

    #[Route('/users', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $org = $this->sec->requireOrg();
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

        $user = new User();
        $user->setEmail(trim($body['email']));
        $user->setPassword($this->hasher->hashPassword($user, $body['password']));
        $user->setRole(User::ROLE_COACH); // Admins can only create coaches
        $user->setOrganizationId($org->getId());
        $this->em->persist($user);
        $this->em->flush();

        return $this->json(['data' => $this->serializeUser($user)], Response::HTTP_CREATED);
    }

    #[Route('/users/{userId}', methods: ['PUT'])]
    public function updateUser(string $userId, Request $request): JsonResponse
    {
        $org  = $this->sec->requireOrg();
        $user = $this->userRepo->find($userId);
        if (!$user || $user->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

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
        $this->em->flush();
        return $this->json(['data' => $this->serializeUser($user)]);
    }

    #[Route('/users/{userId}', methods: ['DELETE'])]
    public function deleteUser(string $userId): JsonResponse
    {
        $org  = $this->sec->requireOrg();
        $user = $this->userRepo->find($userId);
        if (!$user || $user->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $this->em->remove($user);
        $this->em->flush();
        return $this->json(['data' => ['deleted' => true]]);
    }

    // ── Coach → Team assignments ────────────────────────────

    #[Route('/users/{userId}/teams', methods: ['GET'])]
    public function listCoachTeams(string $userId): JsonResponse
    {
        $org  = $this->sec->requireOrg();
        $user = $this->userRepo->find($userId);
        if (!$user || $user->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->coachTeamRepo->getTeamsForUser($userId);
        return $this->json(['data' => $data]);
    }

    #[Route('/users/{userId}/teams', methods: ['POST'])]
    public function assignTeam(string $userId, Request $request): JsonResponse
    {
        $org  = $this->sec->requireOrg();
        $user = $this->userRepo->find($userId);
        if (!$user || $user->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $body   = json_decode($request->getContent(), true) ?? [];
        $teamId = $body['teamId'] ?? null;
        $team   = $teamId ? $this->teamRepo->find($teamId) : null;
        if (!$team || $team->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }
        if ($this->coachTeamRepo->findByPair($userId, $teamId)) {
            return $this->json(['error' => 'Already assigned'], Response::HTTP_CONFLICT);
        }
        $ct = new CoachTeam();
        $ct->setUser($user);
        $ct->setTeam($team);
        $this->em->persist($ct);
        $this->em->flush();
        return $this->json(['data' => ['teamId' => $teamId]], Response::HTTP_CREATED);
    }

    #[Route('/users/{userId}/teams/{teamId}', methods: ['DELETE'])]
    public function unassignTeam(string $userId, string $teamId): JsonResponse
    {
        $ct = $this->coachTeamRepo->findByPair($userId, $teamId);
        if (!$ct) return $this->json(['error' => 'Assignment not found'], Response::HTTP_NOT_FOUND);
        $this->em->remove($ct);
        $this->em->flush();
        return $this->json(['data' => ['removed' => true]]);
    }

    // ── Coach → Championship assignments ───────────────────

    #[Route('/users/{userId}/championships', methods: ['GET'])]
    public function listCoachChampionships(string $userId): JsonResponse
    {
        $org  = $this->sec->requireOrg();
        $user = $this->userRepo->find($userId);
        if (!$user || $user->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->coachChampRepo->getChampionshipsForUser($userId);
        return $this->json(['data' => $data]);
    }

    #[Route('/users/{userId}/championships', methods: ['POST'])]
    public function assignChampionship(string $userId, Request $request): JsonResponse
    {
        $org  = $this->sec->requireOrg();
        $user = $this->userRepo->find($userId);
        if (!$user || $user->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $body   = json_decode($request->getContent(), true) ?? [];
        $champId = $body['championshipId'] ?? null;
        $champ   = $champId ? $this->champRepo->find($champId) : null;
        if (!$champ || $champ->getOrganizationId() !== $org->getId()) {
            return $this->json(['error' => 'Championship not found'], Response::HTTP_NOT_FOUND);
        }
        if ($this->coachChampRepo->findByPair($userId, $champId)) {
            return $this->json(['error' => 'Already assigned'], Response::HTTP_CONFLICT);
        }
        $cc = new CoachChampionship();
        $cc->setUser($user);
        $cc->setChampionship($champ);
        $this->em->persist($cc);
        $this->em->flush();
        return $this->json(['data' => ['championshipId' => $champId]], Response::HTTP_CREATED);
    }

    #[Route('/users/{userId}/championships/{champId}', methods: ['DELETE'])]
    public function unassignChampionship(string $userId, string $champId): JsonResponse
    {
        $cc = $this->coachChampRepo->findByPair($userId, $champId);
        if (!$cc) return $this->json(['error' => 'Assignment not found'], Response::HTTP_NOT_FOUND);
        $this->em->remove($cc);
        $this->em->flush();
        return $this->json(['data' => ['removed' => true]]);
    }

    private function serializeUser(User $u): array
    {
        return ['id' => $u->getId(), 'email' => $u->getEmail(), 'role' => $u->getRole(), 'createdAt' => $u->getCreatedAt()->format('Y-m-d\TH:i:s')];
    }
}
