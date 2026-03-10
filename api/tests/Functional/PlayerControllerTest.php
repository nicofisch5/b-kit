<?php

namespace App\Tests\Functional;

use App\Tests\AbstractApiTestCase;

/**
 * Tests that PlayerController correctly scopes data by organization.
 */
class PlayerControllerTest extends AbstractApiTestCase
{
    public function testListReturnsOnlyPlayersFromSameOrg(): void
    {
        $orgA = $this->createOrg('OrgA');
        $orgB = $this->createOrg('OrgB');
        $admin = $this->createAdmin($orgA);

        $this->createPlayer($orgA, 'Alice', 'Alpha');
        $this->createPlayer($orgA, 'Bob',   'Beta');
        $this->createPlayer($orgB, 'Carol', 'Gamma'); // other org — must NOT appear

        $response = $this->get('/api/v1/players', $admin);

        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];

        $names = array_column($data, 'firstname');
        $this->assertContains('Alice', $names);
        $this->assertContains('Bob',   $names);
        $this->assertNotContains('Carol', $names, 'Players from another org must not be returned');
    }

    public function testShowDeniesAccessToPlayerFromDifferentOrg(): void
    {
        $orgA = $this->createOrg('OrgA2');
        $orgB = $this->createOrg('OrgB2');
        $admin = $this->createAdmin($orgA);
        $playerInOrgB = $this->createPlayer($orgB, 'Dave', 'Delta');

        $response = $this->get('/api/v1/players/'.$playerInOrgB->getId(), $admin);

        $this->assertStatus(403, $response);
    }

    public function testShowAllowsAccessToPlayerFromSameOrg(): void
    {
        $org   = $this->createOrg('OrgC');
        $admin = $this->createAdmin($org);
        $player = $this->createPlayer($org, 'Eve', 'Echo');

        $response = $this->get('/api/v1/players/'.$player->getId(), $admin);

        $this->assertStatus(200, $response);
        $this->assertSame('Eve', $this->jsonBody($response)['data']['firstname']);
    }

    public function testCreateSetsOrganizationId(): void
    {
        $org   = $this->createOrg('OrgD');
        $admin = $this->createAdmin($org);

        $response = $this->post('/api/v1/players', ['firstname' => 'Frank', 'lastname' => 'Fox'], $admin);

        $this->assertStatus(201, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertSame('Frank', $data['firstname']);

        // Reload from DB and check orgId was stamped
        self::$em->clear();
        $player = self::$em->getRepository(\App\Entity\Player::class)->find($data['id']);
        $this->assertSame($org->getId(), $player->getOrganizationId());
    }

    public function testDeleteDeniesAccessToPlayerFromDifferentOrg(): void
    {
        $orgA = $this->createOrg('OrgE');
        $orgB = $this->createOrg('OrgF');
        $admin = $this->createAdmin($orgA);
        $playerInOrgB = $this->createPlayer($orgB, 'Greg', 'Gold');

        $response = $this->delete('/api/v1/players/'.$playerInOrgB->getId(), $admin);

        $this->assertStatus(403, $response);
    }

    public function testUpdateDeniesAccessToPlayerFromDifferentOrg(): void
    {
        $orgA = $this->createOrg('OrgG');
        $orgB = $this->createOrg('OrgH');
        $admin = $this->createAdmin($orgA);
        $playerInOrgB = $this->createPlayer($orgB, 'Hank', 'Hill');

        $response = $this->put('/api/v1/players/'.$playerInOrgB->getId(), ['firstname' => 'Hacked'], $admin);

        $this->assertStatus(403, $response);
    }
}
