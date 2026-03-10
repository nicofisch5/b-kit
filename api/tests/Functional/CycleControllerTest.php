<?php

namespace App\Tests\Functional;

use App\Entity\Cycle;
use App\Entity\TrainingSession;
use App\Tests\AbstractApiTestCase;

class CycleControllerTest extends AbstractApiTestCase
{
    // ── Helpers ──────────────────────────────────────────────────────

    private function createCycle(\App\Entity\Organization $org, string $userId, string $name = 'Pre-season', ?string $start = '2026-01-01', ?string $end = '2026-03-31'): Cycle
    {
        $cycle = (new Cycle())
            ->setName($name)
            ->setDescription('A test cycle')
            ->setStartDate($start ? new \DateTime($start) : null)
            ->setEndDate($end ? new \DateTime($end) : null)
            ->setCreatedBy($userId)
            ->setOrganizationId($org->getId());
        self::$em->persist($cycle);
        self::$em->flush();
        return $cycle;
    }

    private function createSession(\App\Entity\Organization $org, string $userId, string $date = '2026-02-01', ?Cycle $cycle = null): TrainingSession
    {
        $session = (new TrainingSession())
            ->setDate(new \DateTime($date))
            ->setCreatedBy($userId)
            ->setOrganizationId($org->getId());
        if ($cycle) $session->setCycle($cycle);
        self::$em->persist($session);
        self::$em->flush();
        return $session;
    }

    // ── List ─────────────────────────────────────────────────────────

    public function testListReturnsOnlyOwnCycles(): void
    {
        $org    = $this->createOrg('OrgCycleList');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);

        $this->createCycle($org, $coach1->getId(), 'Coach1 Cycle');
        $this->createCycle($org, $coach2->getId(), 'Coach2 Cycle');

        $response = $this->get('/api/v1/cycles', $coach1);
        $this->assertStatus(200, $response);

        $names = array_column($this->jsonBody($response)['data'], 'name');
        $this->assertContains('Coach1 Cycle', $names);
        $this->assertNotContains('Coach2 Cycle', $names);
    }

    // ── Create ───────────────────────────────────────────────────────

    public function testCreateCycleSetsCreatedBy(): void
    {
        $org   = $this->createOrg('OrgCycleCreate');
        $coach = $this->createCoach($org);

        $response = $this->post('/api/v1/cycles', [
            'name'        => 'Spring Block',
            'startDate'   => '2026-03-01',
            'endDate'     => '2026-05-31',
            'description' => 'Spring training',
        ], $coach);

        $this->assertStatus(201, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertSame('Spring Block', $data['name']);
        $this->assertSame($coach->getId(), $data['createdBy']);
        $this->assertSame('2026-03-01', $data['startDate']);
        $this->assertSame('2026-05-31', $data['endDate']);
    }

    public function testCreateCycleRequiresName(): void
    {
        $org   = $this->createOrg('OrgCycleNoName');
        $coach = $this->createCoach($org);

        $response = $this->post('/api/v1/cycles', ['description' => 'No name'], $coach);
        $this->assertStatus(400, $response);
    }

    // ── Show ─────────────────────────────────────────────────────────

    public function testShowIncludesSessions(): void
    {
        $org   = $this->createOrg('OrgCycleShow');
        $coach = $this->createCoach($org);
        $cycle = $this->createCycle($org, $coach->getId());
        $this->createSession($org, $coach->getId(), '2026-02-15', $cycle);

        $response = $this->get('/api/v1/cycles/'.$cycle->getId(), $coach);
        $this->assertStatus(200, $response);

        $data = $this->jsonBody($response)['data'];
        $this->assertArrayHasKey('sessions', $data);
        $this->assertCount(1, $data['sessions']);
        $this->assertSame('2026-02-15', $data['sessions'][0]['date']);
    }

    public function testShowDeniesAccessToOtherUserCycle(): void
    {
        $org    = $this->createOrg('OrgCycleShowDeny');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);
        $cycle  = $this->createCycle($org, $coach1->getId());

        $response = $this->get('/api/v1/cycles/'.$cycle->getId(), $coach2);
        $this->assertStatus(403, $response);
    }

    // ── Update ───────────────────────────────────────────────────────

    public function testUpdateByOwnerSucceeds(): void
    {
        $org   = $this->createOrg('OrgCycleUpdate');
        $coach = $this->createCoach($org);
        $cycle = $this->createCycle($org, $coach->getId());

        $response = $this->put('/api/v1/cycles/'.$cycle->getId(), [
            'name'    => 'Renamed Cycle',
            'outcome' => 'Great progress',
        ], $coach);

        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertSame('Renamed Cycle', $data['name']);
        $this->assertSame('Great progress', $data['outcome']);
    }

    public function testUpdateByNonOwnerFails(): void
    {
        $org    = $this->createOrg('OrgCycleUpdateDeny');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);
        $cycle  = $this->createCycle($org, $coach1->getId());

        $response = $this->put('/api/v1/cycles/'.$cycle->getId(), ['name' => 'Hack'], $coach2);
        $this->assertStatus(403, $response);
    }

    // ── Delete ───────────────────────────────────────────────────────

    public function testDeleteByOwnerSucceeds(): void
    {
        $org   = $this->createOrg('OrgCycleDelete');
        $coach = $this->createCoach($org);
        $cycle = $this->createCycle($org, $coach->getId());

        $response = $this->delete('/api/v1/cycles/'.$cycle->getId(), $coach);
        $this->assertStatus(200, $response);
    }

    public function testDeleteUnlinksSessionsCycle(): void
    {
        $org     = $this->createOrg('OrgCycleDeleteUnlink');
        $coach   = $this->createCoach($org);
        $cycle   = $this->createCycle($org, $coach->getId());
        $session = $this->createSession($org, $coach->getId(), '2026-02-01', $cycle);

        // Verify session has a cycle
        self::$em->refresh($session);
        $this->assertNotNull($session->getCycle());

        // Delete the cycle
        $this->delete('/api/v1/cycles/'.$cycle->getId(), $coach);

        // Session's cycle should be NULL (ON DELETE SET NULL)
        self::$em->refresh($session);
        $this->assertNull($session->getCycle());
    }
}
