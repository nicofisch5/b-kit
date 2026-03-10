<?php

namespace App\Tests\Functional;

use App\Entity\Drill;
use App\Tests\AbstractApiTestCase;

class DrillControllerTest extends AbstractApiTestCase
{
    // ── Helpers ──────────────────────────────────────────────────────

    private function createDrill(\App\Entity\Organization $org, string $userId, string $visibility = 'org', string $code = 'DR-001', string $name = 'Box Out Drill'): Drill
    {
        $drill = (new Drill())
            ->setCode($code)
            ->setName($name)
            ->setVisibility($visibility)
            ->setOrganizationId($visibility === 'org' ? $org->getId() : null)
            ->setCreatedBy($userId)
            ->setTags(['defense', 'rebounding'])
            ->setLinks([['title' => 'Video', 'url' => 'https://example.com']]);
        self::$em->persist($drill);
        self::$em->flush();
        return $drill;
    }

    // ── List ─────────────────────────────────────────────────────────

    public function testListReturnsOrgDrillsForCoach(): void
    {
        $org = $this->createOrg('OrgDrills');
        $coach = $this->createCoach($org);
        $this->createDrill($org, $coach->getId(), 'org', 'DR-001', 'Drill One');
        $this->createDrill($org, $coach->getId(), 'org', 'DR-002', 'Drill Two');

        $response = $this->get('/api/v1/drills', $coach);

        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertCount(2, $data);
    }

    public function testListDoesNotReturnDrillsFromOtherOrg(): void
    {
        $orgA = $this->createOrg('OrgDrillsA');
        $orgB = $this->createOrg('OrgDrillsB');
        $coachA = $this->createCoach($orgA);
        $coachB = $this->createCoach($orgB);

        $this->createDrill($orgA, $coachA->getId(), 'org', 'DR-A1', 'Drill OrgA');
        $this->createDrill($orgB, $coachB->getId(), 'org', 'DR-B1', 'Drill OrgB');

        $response = $this->get('/api/v1/drills', $coachA);

        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $codes = array_column($data, 'code');
        $this->assertContains('DR-A1', $codes);
        $this->assertNotContains('DR-B1', $codes);
    }

    public function testListReturnsPersonalDrillsOfCreatorOnly(): void
    {
        $org = $this->createOrg('OrgPersonal');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);

        // Personal drill owned by coach1
        $drill = new Drill();
        $drill->setCode('DR-P1')->setName('Personal Drill')->setVisibility('personal')
              ->setOrganizationId(null)->setCreatedBy($coach1->getId())
              ->setTags([])->setLinks([]);
        self::$em->persist($drill);
        self::$em->flush();

        // Coach2 should NOT see coach1's personal drill
        $response = $this->get('/api/v1/drills', $coach2);
        $this->assertStatus(200, $response);
        $codes = array_column($this->jsonBody($response)['data'], 'code');
        $this->assertNotContains('DR-P1', $codes);

        // Coach1 SHOULD see their own personal drill
        $response = $this->get('/api/v1/drills', $coach1);
        $this->assertStatus(200, $response);
        $codes = array_column($this->jsonBody($response)['data'], 'code');
        $this->assertContains('DR-P1', $codes);
    }

    // ── Create ───────────────────────────────────────────────────────

    public function testCreateDrillSetsCreatedByAndOrgId(): void
    {
        $org = $this->createOrg('OrgCreate');
        $coach = $this->createCoach($org);

        $response = $this->post('/api/v1/drills', [
            'code'       => 'DR-NEW',
            'name'       => 'New Drill',
            'visibility' => 'org',
            'tags'       => ['offense'],
            'links'      => [['title' => 'Ref', 'url' => 'https://ref.com']],
        ], $coach);

        $this->assertStatus(201, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertSame('DR-NEW', $data['code']);
        $this->assertSame($coach->getId(), $data['createdBy']);
        $this->assertSame($org->getId(), $data['organizationId']);
        $this->assertSame(['offense'], $data['tags']);
        $this->assertCount(1, $data['links']);
    }

    public function testCreateDrillRequiresCode(): void
    {
        $org = $this->createOrg('OrgReqCode');
        $coach = $this->createCoach($org);

        $response = $this->post('/api/v1/drills', ['name' => 'Missing Code'], $coach);
        $this->assertStatus(400, $response);
    }

    // ── Show ─────────────────────────────────────────────────────────

    public function testShowOrgDrillDeniesAccessFromDifferentOrg(): void
    {
        $orgA = $this->createOrg('OrgShowA');
        $orgB = $this->createOrg('OrgShowB');
        $coachA = $this->createCoach($orgA);
        $coachB = $this->createCoach($orgB);

        $drill = $this->createDrill($orgA, $coachA->getId(), 'org');

        $response = $this->get('/api/v1/drills/'.$drill->getId(), $coachB);
        $this->assertStatus(403, $response);
    }

    public function testShowPersonalDrillDeniesAccessToNonCreator(): void
    {
        $org = $this->createOrg('OrgShowPersonal');
        $coach1 = $this->createCoach($org);
        $coach2 = $this->createCoach($org);

        $drill = new Drill();
        $drill->setCode('DR-PP')->setName('Personal')->setVisibility('personal')
              ->setOrganizationId(null)->setCreatedBy($coach1->getId())
              ->setTags([])->setLinks([]);
        self::$em->persist($drill);
        self::$em->flush();

        $response = $this->get('/api/v1/drills/'.$drill->getId(), $coach2);
        $this->assertStatus(403, $response);
    }

    // ── Update ───────────────────────────────────────────────────────

    public function testUpdateByCreatorSucceeds(): void
    {
        $org = $this->createOrg('OrgUpdate');
        $coach = $this->createCoach($org);
        $drill = $this->createDrill($org, $coach->getId());

        $response = $this->put('/api/v1/drills/'.$drill->getId(), ['name' => 'Updated Name'], $coach);
        $this->assertStatus(200, $response);
        $this->assertSame('Updated Name', $this->jsonBody($response)['data']['name']);
    }

    public function testUpdateByNonCreatorCoachFails(): void
    {
        $org = $this->createOrg('OrgUpdateDeny');
        $coachA = $this->createCoach($org);
        $coachB = $this->createCoach($org);
        $drill = $this->createDrill($org, $coachA->getId());

        $response = $this->put('/api/v1/drills/'.$drill->getId(), ['name' => 'Hack'], $coachB);
        $this->assertStatus(403, $response);
    }

    public function testAdminCanUpdateDrillInSameOrg(): void
    {
        $org = $this->createOrg('OrgAdminEdit');
        $coach = $this->createCoach($org);
        $admin = $this->createAdmin($org);
        $drill = $this->createDrill($org, $coach->getId());

        $response = $this->put('/api/v1/drills/'.$drill->getId(), ['name' => 'Admin Edit'], $admin);
        $this->assertStatus(200, $response);
    }

    // ── Delete ───────────────────────────────────────────────────────

    public function testDeleteByNonCreatorFails(): void
    {
        $org = $this->createOrg('OrgDeleteDeny');
        $coachA = $this->createCoach($org);
        $coachB = $this->createCoach($org);
        $drill = $this->createDrill($org, $coachA->getId());

        $response = $this->delete('/api/v1/drills/'.$drill->getId(), $coachB);
        $this->assertStatus(403, $response);
    }

    public function testDeleteByCreatorSucceeds(): void
    {
        $org = $this->createOrg('OrgDeleteOk');
        $coach = $this->createCoach($org);
        $drill = $this->createDrill($org, $coach->getId());

        $response = $this->delete('/api/v1/drills/'.$drill->getId(), $coach);
        $this->assertStatus(200, $response);
    }
}
