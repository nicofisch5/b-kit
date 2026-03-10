<?php

namespace App\Tests\Functional;

use App\Tests\AbstractApiTestCase;

/**
 * Tests that TeamController enforces access control on player management endpoints.
 */
class TeamControllerTest extends AbstractApiTestCase
{
    public function testCoachAssignedToTeamCanListPlayers(): void
    {
        $org   = $this->createOrg('TeamOrgA');
        $coach = $this->createCoach($org);
        $team  = $this->createTeam($org, 'Bulls');
        $this->assignCoachToTeam($coach, $team);

        $response = $this->get('/api/v1/teams/'.$team->getId().'/players', $coach);

        $this->assertStatus(200, $response);
    }

    public function testCoachNotAssignedToTeamCannotListPlayers(): void
    {
        $org   = $this->createOrg('TeamOrgB');
        $coach = $this->createCoach($org);
        $team  = $this->createTeam($org, 'Lakers'); // coach NOT assigned

        $response = $this->get('/api/v1/teams/'.$team->getId().'/players', $coach);

        $this->assertStatus(403, $response);
    }

    public function testCoachNotAssignedToTeamCannotAddPlayer(): void
    {
        $org    = $this->createOrg('TeamOrgC');
        $coach  = $this->createCoach($org);
        $team   = $this->createTeam($org, 'Celtics');
        $player = $this->createPlayer($org, 'Ivan', 'Ivanov');

        $response = $this->post(
            '/api/v1/teams/'.$team->getId().'/players',
            ['playerId' => $player->getId()],
            $coach
        );

        $this->assertStatus(403, $response);
    }

    public function testAdminCanAddPlayerToAnyTeamInOrg(): void
    {
        $org    = $this->createOrg('TeamOrgD');
        $admin  = $this->createAdmin($org);
        $team   = $this->createTeam($org, 'Heat');
        $player = $this->createPlayer($org, 'Jake', 'Johnson');

        $response = $this->post(
            '/api/v1/teams/'.$team->getId().'/players',
            ['playerId' => $player->getId()],
            $admin
        );

        $this->assertStatus(201, $response);
    }

    public function testCoachNotAssignedToTeamCannotRemovePlayer(): void
    {
        $org    = $this->createOrg('TeamOrgE');
        $coach  = $this->createCoach($org);
        $admin  = $this->createAdmin($org);
        $team   = $this->createTeam($org, 'Nets');
        $player = $this->createPlayer($org, 'Karl', 'King');

        // Admin adds the player first
        $this->post('/api/v1/teams/'.$team->getId().'/players', ['playerId' => $player->getId()], $admin);

        // Coach (not assigned) tries to remove
        $response = $this->delete('/api/v1/teams/'.$team->getId().'/players/'.$player->getId(), $coach);

        $this->assertStatus(403, $response);
    }
}
