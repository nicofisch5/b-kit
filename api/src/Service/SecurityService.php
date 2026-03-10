<?php

namespace App\Service;

use App\Entity\Championship;
use App\Entity\Game;
use App\Entity\Organization;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\CoachChampionshipRepository;
use App\Repository\CoachTeamRepository;
use App\Repository\OrganizationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SecurityService
{
    private ?Organization $cachedOrg = null;
    private bool $orgLoaded = false;
    private ?array $cachedCoachTeamIds = null;
    private bool $coachTeamIdsLoaded = false;
    private ?array $cachedCoachChampIds = null;
    private bool $coachChampIdsLoaded = false;

    public function __construct(
        private Security $security,
        private OrganizationRepository $orgRepo,
        private CoachTeamRepository $coachTeamRepo,
        private CoachChampionshipRepository $coachChampRepo,
    ) {}

    public function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated.');
        }
        return $user;
    }

    public function getCurrentOrg(): ?Organization
    {
        if ($this->orgLoaded) {
            return $this->cachedOrg;
        }
        $this->orgLoaded = true;
        $user = $this->getCurrentUser();
        if ($user->getOrganizationId() === null) {
            return null; // SuperAdmin
        }
        $this->cachedOrg = $this->orgRepo->find($user->getOrganizationId());
        return $this->cachedOrg;
    }

    public function requireOrg(): Organization
    {
        $org = $this->getCurrentOrg();
        if ($org === null) {
            throw new AccessDeniedHttpException('This endpoint requires an organization context.');
        }
        return $org;
    }

    public function isSuperAdmin(): bool
    {
        return $this->security->isGranted('ROLE_SUPER_ADMIN');
    }

    public function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    public function assertSameOrg(string $organizationId): void
    {
        if ($this->isSuperAdmin()) return;
        $user = $this->getCurrentUser();
        if ($user->getOrganizationId() !== $organizationId) {
            throw new AccessDeniedHttpException('Access denied.');
        }
    }

    public function assertCanAccessTeam(Team $team): void
    {
        if ($this->isSuperAdmin()) return;
        $user = $this->getCurrentUser();
        // Admin: same org is enough
        if ($this->isAdmin()) {
            $this->assertSameOrg($team->getOrganizationId() ?? '');
            return;
        }
        // Coach: must be explicitly assigned
        if (!$this->coachTeamRepo->findByPair($user->getId(), $team->getId())) {
            throw new AccessDeniedHttpException('You are not assigned to this team.');
        }
    }

    public function assertCanAccessChampionship(Championship $championship): void
    {
        if ($this->isSuperAdmin()) return;
        $user = $this->getCurrentUser();
        if ($this->isAdmin()) {
            $this->assertSameOrg($championship->getOrganizationId() ?? '');
            return;
        }
        if (!$this->coachChampRepo->findByPair($user->getId(), $championship->getId())) {
            throw new AccessDeniedHttpException('You are not assigned to this championship.');
        }
    }

    public function assertCanAccessGame(Game $game): void
    {
        if ($this->isSuperAdmin()) return;
        $user = $this->getCurrentUser();
        if ($this->isAdmin()) {
            $this->assertSameOrg($game->getOrganizationId() ?? '');
            return;
        }
        // Coach: game's team OR championship must be assigned.
        // Exception: if the game has no team and no championship, any coach in the same org can access it.
        if (!$game->getTeamId() && !$game->getChampionshipId()) {
            $this->assertSameOrg($game->getOrganizationId() ?? '');
            return;
        }
        $teamOk = $game->getTeamId() && $this->coachTeamRepo->findByPair($user->getId(), $game->getTeamId());
        $champOk = $game->getChampionshipId() && $this->coachChampRepo->findByPair($user->getId(), $game->getChampionshipId());
        if (!$teamOk && !$champOk) {
            throw new AccessDeniedHttpException('You do not have access to this game.');
        }
    }

    /** Returns org filter for list queries: null means no filter (SuperAdmin sees all) */
    public function getOrgFilter(): ?string
    {
        if ($this->isSuperAdmin()) return null;
        return $this->getCurrentUser()->getOrganizationId();
    }

    /** Returns team IDs a coach can access, or null if admin/superadmin (no restriction). Result is cached per request. */
    public function getCoachTeamIds(): ?array
    {
        if ($this->isAdmin()) return null;
        if (!$this->coachTeamIdsLoaded) {
            $this->coachTeamIdsLoaded = true;
            $this->cachedCoachTeamIds = $this->coachTeamRepo->getTeamIds($this->getCurrentUser()->getId());
        }
        return $this->cachedCoachTeamIds;
    }

    /** Returns championship IDs a coach can access, or null if admin/superadmin. Result is cached per request. */
    public function getCoachChampionshipIds(): ?array
    {
        if ($this->isAdmin()) return null;
        if (!$this->coachChampIdsLoaded) {
            $this->coachChampIdsLoaded = true;
            $this->cachedCoachChampIds = $this->coachChampRepo->getChampionshipIds($this->getCurrentUser()->getId());
        }
        return $this->cachedCoachChampIds;
    }
}
