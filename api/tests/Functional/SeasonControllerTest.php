<?php

namespace App\Tests\Functional;

use App\Tests\AbstractApiTestCase;

/**
 * Tests that SeasonController enforces org isolation on show/update/delete.
 */
class SeasonControllerTest extends AbstractApiTestCase
{
    public function testListReturnsOnlySeasonsFromSameOrg(): void
    {
        $orgA  = $this->createOrg('SeasonOrgA');
        $orgB  = $this->createOrg('SeasonOrgB');
        $admin = $this->createAdmin($orgA);

        $this->createSeason($orgA, 'Season A');
        $this->createSeason($orgB, 'Season B'); // must NOT appear

        $response = $this->get('/api/v1/seasons', $admin);

        $this->assertStatus(200, $response);
        $names = array_column($this->jsonBody($response)['data'], 'name');
        $this->assertContains('Season A', $names);
        $this->assertNotContains('Season B', $names);
    }

    public function testShowDeniesAccessToSeasonFromDifferentOrg(): void
    {
        $orgA   = $this->createOrg('SeasonOrgC');
        $orgB   = $this->createOrg('SeasonOrgD');
        $admin  = $this->createAdmin($orgA);
        $season = $this->createSeason($orgB, 'Forbidden Season');

        $response = $this->get('/api/v1/seasons/'.$season->getId(), $admin);

        $this->assertStatus(403, $response);
    }

    public function testShowAllowsAccessToSeasonFromSameOrg(): void
    {
        $org    = $this->createOrg('SeasonOrgE');
        $admin  = $this->createAdmin($org);
        $season = $this->createSeason($org, 'My Season');

        $response = $this->get('/api/v1/seasons/'.$season->getId(), $admin);

        $this->assertStatus(200, $response);
        $this->assertSame('My Season', $this->jsonBody($response)['data']['name']);
    }

    public function testUpdateDeniesAccessToSeasonFromDifferentOrg(): void
    {
        $orgA   = $this->createOrg('SeasonOrgF');
        $orgB   = $this->createOrg('SeasonOrgG');
        $admin  = $this->createAdmin($orgA);
        $season = $this->createSeason($orgB, 'Other Season');

        $response = $this->put('/api/v1/seasons/'.$season->getId(), ['name' => 'Hacked'], $admin);

        $this->assertStatus(403, $response);
    }

    public function testDeleteDeniesAccessToSeasonFromDifferentOrg(): void
    {
        $orgA   = $this->createOrg('SeasonOrgH');
        $orgB   = $this->createOrg('SeasonOrgI');
        $admin  = $this->createAdmin($orgA);
        $season = $this->createSeason($orgB, 'Doomed Season');

        $response = $this->delete('/api/v1/seasons/'.$season->getId(), $admin);

        $this->assertStatus(403, $response);
    }
}
