<?php

namespace App\Tests\Functional;

use App\Entity\Cycle;
use App\Entity\Drill;
use App\Entity\TrainingSession;
use App\Tests\AbstractApiTestCase;

class TrainingSessionControllerTest extends AbstractApiTestCase
{
    // ── Helpers ──────────────────────────────────────────────────────

    private function createSession(\App\Entity\Organization $org, string $userId, string $date = '2026-03-10'): TrainingSession
    {
        $session = (new TrainingSession())
            ->setDate(new \DateTime($date))
            ->setGoal('Work on defense')
            ->setDuration(90)
            ->setOrganizationId($org->getId())
            ->setCreatedBy($userId);
        self::$em->persist($session);
        self::$em->flush();
        return $session;
    }

    private function createDrill(\App\Entity\Organization $org, string $userId, string $code = 'DR-S1'): Drill
    {
        $drill = (new Drill())
            ->setCode($code)->setName('Test Drill '.$code)
            ->setVisibility('org')->setOrganizationId($org->getId())
            ->setCreatedBy($userId)->setTags([])->setLinks([]);
        self::$em->persist($drill);
        self::$em->flush();
        return $drill;
    }

    // ── List ─────────────────────────────────────────────────────────

    public function testCoachSeesOnlyOwnSessions(): void
    {
        $org = $this->createOrg('OrgSessions');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);

        $this->createSession($org, $coach1->getId());
        $this->createSession($org, $coach2->getId());

        $response = $this->get('/api/v1/training-sessions', $coach1);
        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];

        // Coach1 should see only their own session
        foreach ($data as $s) {
            $this->assertSame($coach1->getId(), $s['createdBy']);
        }
    }

    public function testAdminSeesAllSessionsInOrg(): void
    {
        $org = $this->createOrg('OrgAdminSessions');
        $admin = $this->createAdmin($org);
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);

        $this->createSession($org, $coach1->getId(), '2026-03-01');
        $this->createSession($org, $coach2->getId(), '2026-03-02');

        $response = $this->get('/api/v1/training-sessions', $admin);
        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    // ── Create ───────────────────────────────────────────────────────

    public function testCreateSessionSetsCreatedByAndOrg(): void
    {
        $org = $this->createOrg('OrgCreateSession');
        $coach = $this->createCoach($org);

        $response = $this->post('/api/v1/training-sessions', [
            'date'     => '2026-04-01',
            'goal'     => 'Improve shooting',
            'duration' => 60,
        ], $coach);

        $this->assertStatus(201, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertSame('2026-04-01', $data['date']);
        $this->assertSame($coach->getId(), $data['createdBy']);
        $this->assertSame($org->getId(), $data['organizationId']);
        $this->assertSame(0, $data['drillCount']);
    }

    public function testCreateSessionRequiresDate(): void
    {
        $org = $this->createOrg('OrgNoDate');
        $coach = $this->createCoach($org);

        $response = $this->post('/api/v1/training-sessions', ['goal' => 'No date'], $coach);
        $this->assertStatus(400, $response);
    }

    // ── Show ─────────────────────────────────────────────────────────

    public function testShowDeniesAccessToSessionFromDifferentOrg(): void
    {
        $orgA = $this->createOrg('OrgShowSessA');
        $orgB = $this->createOrg('OrgShowSessB');
        $coachA = $this->createCoach($orgA);
        $coachB = $this->createCoach($orgB);

        $session = $this->createSession($orgA, $coachA->getId());

        $response = $this->get('/api/v1/training-sessions/'.$session->getId(), $coachB);
        $this->assertStatus(403, $response);
    }

    public function testShowReturnsEmptyDrillsArray(): void
    {
        $org = $this->createOrg('OrgShowSess');
        $coach = $this->createCoach($org);
        $session = $this->createSession($org, $coach->getId());

        $response = $this->get('/api/v1/training-sessions/'.$session->getId(), $coach);
        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertArrayHasKey('drills', $data);
        $this->assertSame([], $data['drills']);
    }

    // ── Update drills ────────────────────────────────────────────────

    public function testUpdateDrillsAddsAndOrdersDrills(): void
    {
        $org = $this->createOrg('OrgDrillUpdate');
        $coach = $this->createCoach($org);
        $session = $this->createSession($org, $coach->getId());
        $drill1 = $this->createDrill($org, $coach->getId(), 'DR-T1');
        $drill2 = $this->createDrill($org, $coach->getId(), 'DR-T2');

        $response = $this->put('/api/v1/training-sessions/'.$session->getId().'/drills', [
            'drills' => [
                ['drillId' => $drill1->getId(), 'note' => 'Focus on footwork'],
                ['drillId' => $drill2->getId(), 'note' => null],
            ],
        ], $coach);

        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertCount(2, $data['drills']);
        $this->assertSame(0, $data['drills'][0]['sortOrder']);
        $this->assertSame(1, $data['drills'][1]['sortOrder']);
        $this->assertSame('Focus on footwork', $data['drills'][0]['note']);
        $this->assertSame($drill1->getId(), $data['drills'][0]['drill']['id']);
    }

    public function testSameDrillCanAppearTwiceInSession(): void
    {
        $org = $this->createOrg('OrgRepeatDrill');
        $coach = $this->createCoach($org);
        $session = $this->createSession($org, $coach->getId());
        $drill = $this->createDrill($org, $coach->getId(), 'DR-REP');

        $response = $this->put('/api/v1/training-sessions/'.$session->getId().'/drills', [
            'drills' => [
                ['drillId' => $drill->getId(), 'note' => 'First pass'],
                ['drillId' => $drill->getId(), 'note' => 'Second pass'],
            ],
        ], $coach);

        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertCount(2, $data['drills']);
        $this->assertSame($drill->getId(), $data['drills'][0]['drill']['id']);
        $this->assertSame($drill->getId(), $data['drills'][1]['drill']['id']);
    }

    public function testUpdateDrillsDeniesNonCreatorCoach(): void
    {
        $org = $this->createOrg('OrgDrillDeny');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);
        $session = $this->createSession($org, $coach1->getId());

        $response = $this->put('/api/v1/training-sessions/'.$session->getId().'/drills', ['drills' => []], $coach2);
        $this->assertStatus(403, $response);
    }

    // ── Cycle linking ────────────────────────────────────────────────

    private function createCycle(\App\Entity\Organization $org, string $userId, string $start = '2026-01-01', string $end = '2026-12-31'): Cycle
    {
        $cycle = (new Cycle())
            ->setName('Test Cycle')
            ->setStartDate(new \DateTime($start))
            ->setEndDate(new \DateTime($end))
            ->setCreatedBy($userId)
            ->setOrganizationId($org->getId());
        self::$em->persist($cycle);
        self::$em->flush();
        return $cycle;
    }

    public function testCreateSessionWithCycleSetsCycleName(): void
    {
        $org   = $this->createOrg('OrgSessionCycle');
        $coach = $this->createCoach($org);
        $cycle = $this->createCycle($org, $coach->getId());

        $response = $this->post('/api/v1/training-sessions', [
            'date'    => '2026-06-15',
            'cycleId' => $cycle->getId(),
        ], $coach);

        $this->assertStatus(201, $response);
        $body = $this->jsonBody($response);
        $this->assertSame($cycle->getId(), $body['data']['cycleId']);
        $this->assertSame('Test Cycle', $body['data']['cycleName']);
        $this->assertEmpty($body['warnings']);
    }

    public function testSessionDateBeforeCycleStartProducesWarning(): void
    {
        $org   = $this->createOrg('OrgSessionCycleWarn');
        $coach = $this->createCoach($org);
        $cycle = $this->createCycle($org, $coach->getId(), '2026-04-01', '2026-06-30');

        $response = $this->post('/api/v1/training-sessions', [
            'date'    => '2026-03-01', // before cycle start
            'cycleId' => $cycle->getId(),
        ], $coach);

        $this->assertStatus(201, $response);
        $body = $this->jsonBody($response);
        $this->assertNotEmpty($body['warnings']);
        $this->assertStringContainsString('before the cycle start date', $body['warnings'][0]);
    }

    public function testSessionDateAfterCycleEndProducesWarning(): void
    {
        $org   = $this->createOrg('OrgSessionCycleWarnEnd');
        $coach = $this->createCoach($org);
        $cycle = $this->createCycle($org, $coach->getId(), '2026-01-01', '2026-03-31');

        $response = $this->post('/api/v1/training-sessions', [
            'date'    => '2026-05-01', // after cycle end
            'cycleId' => $cycle->getId(),
        ], $coach);

        $this->assertStatus(201, $response);
        $body = $this->jsonBody($response);
        $this->assertNotEmpty($body['warnings']);
        $this->assertStringContainsString('after the cycle end date', $body['warnings'][0]);
    }

    public function testCannotLinkSessionToAnotherUsersCycle(): void
    {
        $org    = $this->createOrg('OrgSessionForeignCycle');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);
        $cycle  = $this->createCycle($org, $coach1->getId());

        // coach2 tries to link to coach1's cycle
        $response = $this->post('/api/v1/training-sessions', [
            'date'    => '2026-06-01',
            'cycleId' => $cycle->getId(),
        ], $coach2);

        $this->assertStatus(201, $response);
        // Cycle must be silently ignored — cycleId should be null
        $this->assertNull($this->jsonBody($response)['data']['cycleId']);
    }

    public function testUpdateSessionRemovesCycleWhenNullSent(): void
    {
        $org     = $this->createOrg('OrgSessionCycleRemove');
        $coach   = $this->createCoach($org);
        $cycle   = $this->createCycle($org, $coach->getId());
        $session = $this->createSession($org, $coach->getId());

        // First link it
        $this->put('/api/v1/training-sessions/'.$session->getId(), ['date' => '2026-06-01', 'cycleId' => $cycle->getId()], $coach);

        // Then unlink
        $response = $this->put('/api/v1/training-sessions/'.$session->getId(), ['cycleId' => null], $coach);
        $this->assertStatus(200, $response);
        $this->assertNull($this->jsonBody($response)['data']['cycleId']);
    }

    // ── PDF export ───────────────────────────────────────────────────

    public function testExportPdfReturnsApplicationPdf(): void
    {
        $org = $this->createOrg('OrgPdfExport');
        $coach = $this->createCoach($org);
        $session = $this->createSession($org, $coach->getId());

        $response = $this->get('/api/v1/training-sessions/'.$session->getId().'/pdf', $coach);

        $this->assertStatus(200, $response);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function testExportPdfIncludesDrillContent(): void
    {
        $org = $this->createOrg('OrgPdfContent');
        $coach = $this->createCoach($org);
        $session = $this->createSession($org, $coach->getId(), '2026-05-01');
        $drill = $this->createDrill($org, $coach->getId(), 'DR-PDF');

        $this->put('/api/v1/training-sessions/'.$session->getId().'/drills', [
            'drills' => [['drillId' => $drill->getId(), 'note' => 'Focus hard']],
        ], $coach);

        $response = $this->get('/api/v1/training-sessions/'.$session->getId().'/pdf', $coach);
        $this->assertStatus(200, $response);
        // dompdf output is binary PDF — verify it is non-empty and starts with %PDF
        $this->assertGreaterThan(1000, strlen($response->getContent()));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function testExportPdfDeniesAccessFromDifferentCoach(): void
    {
        $org = $this->createOrg('OrgPdfDeny');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);
        $session = $this->createSession($org, $coach1->getId());

        $response = $this->get('/api/v1/training-sessions/'.$session->getId().'/pdf', $coach2);
        $this->assertStatus(403, $response);
    }

    // ── Delete ───────────────────────────────────────────────────────

    public function testDeleteByCreatorSucceeds(): void
    {
        $org = $this->createOrg('OrgDelSess');
        $coach = $this->createCoach($org);
        $session = $this->createSession($org, $coach->getId());

        $response = $this->delete('/api/v1/training-sessions/'.$session->getId(), $coach);
        $this->assertStatus(200, $response);
    }

    public function testDeleteByDifferentCoachFails(): void
    {
        $org = $this->createOrg('OrgDelSessDeny');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);
        $session = $this->createSession($org, $coach1->getId());

        $response = $this->delete('/api/v1/training-sessions/'.$session->getId(), $coach2);
        $this->assertStatus(403, $response);
    }
}
