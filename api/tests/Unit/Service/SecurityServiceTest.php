<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\CoachChampionshipRepository;
use App\Repository\CoachTeamRepository;
use App\Repository\OrganizationRepository;
use App\Service\SecurityService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SecurityServiceTest extends TestCase
{
    private function makeUser(string $orgId = 'org-1'): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-1');
        $user->method('getOrganizationId')->willReturn($orgId);
        return $user;
    }

    private function makeSecurity(bool $isAdmin = false, bool $isSuperAdmin = false, ?User $user = null): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturnCallback(function (string $role) use ($isAdmin, $isSuperAdmin): bool {
            return match ($role) {
                'ROLE_SUPER_ADMIN' => $isSuperAdmin,
                'ROLE_ADMIN'       => $isAdmin || $isSuperAdmin,
                default            => true,
            };
        });
        $security->method('getUser')->willReturn($user ?? $this->makeUser());
        return $security;
    }

    private function makeService(Security $security, ?CoachTeamRepository $teamRepo = null, ?CoachChampionshipRepository $champRepo = null): SecurityService
    {
        return new SecurityService(
            $security,
            $this->createMock(OrganizationRepository::class),
            $teamRepo ?? $this->createMock(CoachTeamRepository::class),
            $champRepo ?? $this->createMock(CoachChampionshipRepository::class),
        );
    }

    // ── Caching ──────────────────────────────────────────────────────

    public function testCoachTeamIdsQueriedOnlyOnce(): void
    {
        $coachTeamRepo = $this->createMock(CoachTeamRepository::class);
        $coachTeamRepo->expects($this->once())
            ->method('getTeamIds')
            ->willReturn(['team-1', 'team-2']);

        $svc = $this->makeService($this->makeSecurity(isAdmin: false), $coachTeamRepo);

        $first  = $svc->getCoachTeamIds();
        $second = $svc->getCoachTeamIds();

        $this->assertSame(['team-1', 'team-2'], $first);
        $this->assertSame($first, $second, 'Second call should return the same cached reference');
    }

    public function testCoachChampIdsQueriedOnlyOnce(): void
    {
        $champRepo = $this->createMock(CoachChampionshipRepository::class);
        $champRepo->expects($this->once())
            ->method('getChampionshipIds')
            ->willReturn(['champ-1']);

        $svc = $this->makeService($this->makeSecurity(isAdmin: false), champRepo: $champRepo);

        $first  = $svc->getCoachChampionshipIds();
        $second = $svc->getCoachChampionshipIds();

        $this->assertSame(['champ-1'], $first);
        $this->assertSame($first, $second);
    }

    public function testAdminGetCoachTeamIdsReturnsNullWithoutDbCall(): void
    {
        $coachTeamRepo = $this->createMock(CoachTeamRepository::class);
        $coachTeamRepo->expects($this->never())->method('getTeamIds');

        $svc = $this->makeService($this->makeSecurity(isAdmin: true), $coachTeamRepo);

        $this->assertNull($svc->getCoachTeamIds());
    }

    public function testAdminGetCoachChampIdsReturnsNullWithoutDbCall(): void
    {
        $champRepo = $this->createMock(CoachChampionshipRepository::class);
        $champRepo->expects($this->never())->method('getChampionshipIds');

        $svc = $this->makeService($this->makeSecurity(isAdmin: true), champRepo: $champRepo);

        $this->assertNull($svc->getCoachChampionshipIds());
    }

    // ── assertSameOrg ────────────────────────────────────────────────

    public function testAssertSameOrgPassesForMatchingOrg(): void
    {
        $user = $this->makeUser('org-abc');
        $svc = $this->makeService($this->makeSecurity(isAdmin: false, user: $user));

        $this->expectNotToPerformAssertions();
        $svc->assertSameOrg('org-abc');
    }

    public function testAssertSameOrgThrowsForDifferentOrg(): void
    {
        $user = $this->makeUser('org-abc');
        $svc = $this->makeService($this->makeSecurity(isAdmin: false, user: $user));

        $this->expectException(AccessDeniedHttpException::class);
        $svc->assertSameOrg('org-xyz');
    }

    public function testSuperAdminBypassesAssertSameOrg(): void
    {
        $svc = $this->makeService($this->makeSecurity(isSuperAdmin: true));

        $this->expectNotToPerformAssertions();
        $svc->assertSameOrg('any-org-id');
    }

    // ── getOrgFilter ─────────────────────────────────────────────────

    public function testGetOrgFilterReturnUserOrgForCoach(): void
    {
        $user = $this->makeUser('org-99');
        $svc  = $this->makeService($this->makeSecurity(isAdmin: false, user: $user));

        $this->assertSame('org-99', $svc->getOrgFilter());
    }

    public function testGetOrgFilterReturnsNullForSuperAdmin(): void
    {
        $svc = $this->makeService($this->makeSecurity(isSuperAdmin: true));

        $this->assertNull($svc->getOrgFilter());
    }
}
