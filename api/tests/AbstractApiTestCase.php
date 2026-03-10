<?php

namespace App\Tests;

use App\Entity\Championship;
use App\Entity\CoachChampionship;
use App\Entity\CoachTeam;
use App\Entity\Organization;
use App\Entity\Player;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiTestCase extends WebTestCase
{
    protected static KernelBrowser $client;
    protected static EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::$client = static::createClient();
        self::$em = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    // ── Factory helpers ──────────────────────────────────────────────

    protected function createOrg(string $name = 'Org'): Organization
    {
        $org = (new Organization())->setName($name)->setSlug(strtolower($name).'-'.uniqid());
        self::$em->persist($org);
        self::$em->flush();
        return $org;
    }

    protected function createAdmin(Organization $org): User
    {
        return $this->createUser($org, User::ROLE_ADMIN);
    }

    protected function createCoach(Organization $org): User
    {
        return $this->createUser($org, User::ROLE_COACH);
    }

    private function createUser(Organization $org, string $role): User
    {
        $user = (new User())
            ->setEmail(uniqid('user').'@test.com')
            ->setRole($role)
            ->setOrganizationId($org->getId())
            ->setPassword('not-used-in-jwt-tests');
        self::$em->persist($user);
        self::$em->flush();
        return $user;
    }

    protected function createSeason(Organization $org, string $name = 'Season'): Season
    {
        $season = (new Season())->setName($name)->setOrganizationId($org->getId());
        self::$em->persist($season);
        self::$em->flush();
        return $season;
    }

    protected function createTeam(Organization $org, string $name = 'Team'): Team
    {
        $team = (new Team())
            ->setName($name)->setShortName('T')
            ->setColor('#FF5500')->setCategory('U18')
            ->setOrganizationId($org->getId());
        self::$em->persist($team);
        self::$em->flush();
        return $team;
    }

    protected function createPlayer(Organization $org, string $firstname = 'John', string $lastname = 'Doe'): Player
    {
        $player = (new Player())
            ->setFirstname($firstname)->setLastname($lastname)
            ->setOrganizationId($org->getId());
        self::$em->persist($player);
        self::$em->flush();
        return $player;
    }

    protected function createChampionship(Organization $org, string $name = 'Champ'): Championship
    {
        $champ = (new Championship())->setName($name)->setOrganizationId($org->getId());
        self::$em->persist($champ);
        self::$em->flush();
        return $champ;
    }

    protected function assignCoachToTeam(User $coach, Team $team): void
    {
        $ct = (new CoachTeam())->setUser($coach)->setTeam($team);
        self::$em->persist($ct);
        self::$em->flush();
    }

    protected function assignCoachToChampionship(User $coach, Championship $champ): void
    {
        $cc = (new CoachChampionship())->setUser($coach)->setChampionship($champ);
        self::$em->persist($cc);
        self::$em->flush();
    }

    // ── JWT helper ───────────────────────────────────────────────────

    protected function jwtHeader(User $user): array
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($user);
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    // ── Request helpers ──────────────────────────────────────────────

    protected function get(string $uri, User $user): Response
    {
        self::$client->request('GET', $uri, [], [], $this->jwtHeader($user));
        return self::$client->getResponse();
    }

    protected function post(string $uri, array $body, User $user): Response
    {
        self::$client->request('POST', $uri, [], [], $this->jwtHeader($user) + ['CONTENT_TYPE' => 'application/json'], json_encode($body));
        return self::$client->getResponse();
    }

    protected function put(string $uri, array $body, User $user): Response
    {
        self::$client->request('PUT', $uri, [], [], $this->jwtHeader($user) + ['CONTENT_TYPE' => 'application/json'], json_encode($body));
        return self::$client->getResponse();
    }

    protected function delete(string $uri, User $user): Response
    {
        self::$client->request('DELETE', $uri, [], [], $this->jwtHeader($user));
        return self::$client->getResponse();
    }

    protected function assertStatus(int $expected, Response $response): void
    {
        $this->assertSame($expected, $response->getStatusCode(), 'Response body: '.$response->getContent());
    }

    protected function jsonBody(Response $response): array
    {
        return json_decode($response->getContent(), true);
    }
}
