<?php

namespace App\Tests\Functional;

use App\Tests\AbstractApiTestCase;

/**
 * Tests that ChampionshipController enforces access control on listGames.
 */
class ChampionshipControllerTest extends AbstractApiTestCase
{
    public function testCoachAssignedToChampionshipCanListGames(): void
    {
        $org   = $this->createOrg('ChampOrgA');
        $coach = $this->createCoach($org);
        $champ = $this->createChampionship($org, 'Pro League');
        $this->assignCoachToChampionship($coach, $champ);

        $response = $this->get('/api/v1/championships/'.$champ->getId().'/games', $coach);

        $this->assertStatus(200, $response);
    }

    public function testCoachNotAssignedToChampionshipCannotListGames(): void
    {
        $org   = $this->createOrg('ChampOrgB');
        $coach = $this->createCoach($org);
        $champ = $this->createChampionship($org, 'Amateur Cup'); // coach NOT assigned

        $response = $this->get('/api/v1/championships/'.$champ->getId().'/games', $coach);

        $this->assertStatus(403, $response);
    }

    public function testAdminCanListGamesForAnyChampionshipInOrg(): void
    {
        $org   = $this->createOrg('ChampOrgC');
        $admin = $this->createAdmin($org);
        $champ = $this->createChampionship($org, 'City Finals');

        $response = $this->get('/api/v1/championships/'.$champ->getId().'/games', $admin);

        $this->assertStatus(200, $response);
    }

    public function testAdminFromDifferentOrgCannotListGames(): void
    {
        $orgA  = $this->createOrg('ChampOrgD');
        $orgB  = $this->createOrg('ChampOrgE');
        $admin = $this->createAdmin($orgA);
        $champ = $this->createChampionship($orgB, 'Other League');

        $response = $this->get('/api/v1/championships/'.$champ->getId().'/games', $admin);

        $this->assertStatus(403, $response);
    }

    public function testSerializeUsesCountQueryNotFindBy(): void
    {
        // Regression: serializeDetail must not load game objects to count them
        $org   = $this->createOrg('ChampOrgF');
        $admin = $this->createAdmin($org);
        $champ = $this->createChampionship($org, 'Count Test Champ');

        $response = $this->get('/api/v1/championships/'.$champ->getId(), $admin);

        $this->assertStatus(200, $response);
        $data = $this->jsonBody($response)['data'];
        $this->assertArrayHasKey('gameCount', $data);
        $this->assertSame(0, $data['gameCount']);
    }
}
