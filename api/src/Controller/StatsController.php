<?php

namespace App\Controller;

use App\Repository\StatEventRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/stats')]
class StatsController extends AbstractController
{
    public function __construct(
        private StatEventRepository $statEventRepo,
        private SecurityService $sec,
    ) {}

    #[Route('/players', methods: ['GET'])]
    public function players(Request $request): JsonResponse
    {
        $teamId = $request->query->get('teamId') ?: null;
        $champId = $request->query->get('championshipId') ?: null;

        $orgId = $this->sec->getOrgFilter();
        $coachTeamIds = $this->sec->getCoachTeamIds();
        $coachChampIds = $this->sec->getCoachChampionshipIds();

        $rows = $this->statEventRepo->getAggregateStats($orgId, $teamId, $champId, $coachTeamIds, $coachChampIds);

        $data = array_map(fn($r) => [
            'playerId'     => $r['player_id'],
            'playerName'   => $r['player_name'],
            'gamesPlayed'  => (int) $r['games_played'],
            'points'       => (int) $r['two_pt_made'] * 2 + (int) $r['three_pt_made'] * 3 + (int) $r['ft_made'],
            'twoPtMade'    => (int) $r['two_pt_made'],
            'twoPtMiss'    => (int) $r['two_pt_miss'],
            'threePtMade'  => (int) $r['three_pt_made'],
            'threePtMiss'  => (int) $r['three_pt_miss'],
            'ftMade'       => (int) $r['ft_made'],
            'ftMiss'       => (int) $r['ft_miss'],
            'offReb'       => (int) $r['off_reb'],
            'defReb'       => (int) $r['def_reb'],
            'rebounds'     => (int) $r['off_reb'] + (int) $r['def_reb'],
            'assists'      => (int) $r['assists'],
            'steals'       => (int) $r['steals'],
            'blocks'       => (int) $r['blocks'],
            'fouls'        => (int) $r['fouls'],
            'turnovers'    => (int) $r['turnovers'],
        ], $rows);

        return $this->json(['data' => $data]);
    }

    #[Route('/teams', methods: ['GET'])]
    public function teams(Request $request): JsonResponse
    {
        $champId = $request->query->get('championshipId') ?: null;

        $orgId = $this->sec->getOrgFilter();
        $coachTeamIds = $this->sec->getCoachTeamIds();
        $coachChampIds = $this->sec->getCoachChampionshipIds();

        $rows = $this->statEventRepo->getTeamAggregateStats($orgId, $champId, $coachTeamIds, $coachChampIds);

        $data = array_map(fn($r) => [
            'teamId'       => $r['team_id'],
            'teamName'     => $r['team_name'],
            'teamColor'    => $r['team_color'],
            'gamesPlayed'  => (int) $r['games_played'],
            'points'       => (int) $r['two_pt_made'] * 2 + (int) $r['three_pt_made'] * 3 + (int) $r['ft_made'],
            'twoPtMade'    => (int) $r['two_pt_made'],
            'twoPtMiss'    => (int) $r['two_pt_miss'],
            'threePtMade'  => (int) $r['three_pt_made'],
            'threePtMiss'  => (int) $r['three_pt_miss'],
            'ftMade'       => (int) $r['ft_made'],
            'ftMiss'       => (int) $r['ft_miss'],
            'offReb'       => (int) $r['off_reb'],
            'defReb'       => (int) $r['def_reb'],
            'rebounds'     => (int) $r['off_reb'] + (int) $r['def_reb'],
            'assists'      => (int) $r['assists'],
            'steals'       => (int) $r['steals'],
            'blocks'       => (int) $r['blocks'],
            'fouls'        => (int) $r['fouls'],
            'turnovers'    => (int) $r['turnovers'],
        ], $rows);

        return $this->json(['data' => $data]);
    }
}
