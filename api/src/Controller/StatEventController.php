<?php

namespace App\Controller;

use App\DTO\Request\RecordStatRequest;
use App\Enum\StatType;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\QuarterRepository;
use App\Repository\StatEventRepository;
use App\Service\SecurityService;
use App\Service\StatRecorderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/games/{gameId}/events')]
class StatEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameRepository $gameRepo,
        private PlayerRepository $playerRepo,
        private QuarterRepository $quarterRepo,
        private StatEventRepository $statEventRepo,
        private StatRecorderService $statRecorder,
        private ValidatorInterface $validator,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(string $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessGame($game);

        $playerId = $request->query->get('playerId');
        $quarterId = $request->query->get('quarterId');
        $statTypeStr = $request->query->get('statType');
        $statType = $statTypeStr ? StatType::tryFrom($statTypeStr) : null;

        $events = $this->statEventRepo->findByGameFiltered($gameId, $playerId, $quarterId, $statType);

        $data = array_map(fn($se) => [
            'id' => $se->getId(),
            'playerId' => $se->getPlayer()->getId(),
            'playerName' => $se->getPlayer()->getName(),
            'quarterId' => $se->getQuarter()->getId(),
            'quarterName' => $se->getQuarter()->getQuarterName(),
            'statType' => $se->getStatType()->value,
            'timestamp' => $se->getTimestamp()->format('Y-m-d\TH:i:s.v'),
        ], $events);

        return $this->json(['data' => $data]);
    }

    #[Route('', methods: ['POST'])]
    public function record(string $gameId, Request $request): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessGame($game);

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new RecordStatRequest();
        $dto->playerId = $body['playerId'] ?? '';
        $dto->quarterId = $body['quarterId'] ?? '';
        $dto->statType = $body['statType'] ?? '';
        $dto->timestamp = $body['timestamp'] ?? null;
        $dto->assistPlayerId = $body['assistPlayerId'] ?? null;

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $player = $this->playerRepo->find($dto->playerId);
        if (!$player) {
            return $this->json(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $quarter = $this->quarterRepo->find($dto->quarterId);
        if (!$quarter || $quarter->getGame()->getId() !== $gameId) {
            return $this->json(['error' => 'Quarter not found'], Response::HTTP_NOT_FOUND);
        }

        $statType = StatType::tryFrom($dto->statType);
        if (!$statType) {
            return $this->json(['error' => 'Invalid stat type'], Response::HTTP_BAD_REQUEST);
        }

        $timestamp = $dto->timestamp ? new \DateTime($dto->timestamp) : null;

        $assistPlayer = null;
        if ($dto->assistPlayerId) {
            $assistPlayer = $this->playerRepo->find($dto->assistPlayerId);
            if (!$assistPlayer) {
                return $this->json(['error' => 'Assist player not found'], Response::HTTP_NOT_FOUND);
            }
        }

        $result = $this->statRecorder->record($game, $player, $quarter, $statType, $timestamp, $assistPlayer);

        $responseData = [
            'event' => [
                'id' => $result['event']->getId(),
                'statType' => $result['event']->getStatType()->value,
                'playerId' => $player->getId(),
            ],
            'historySequence' => $result['history']->getSequence(),
        ];

        if ($result['assistEvent']) {
            $responseData['assistEvent'] = [
                'id' => $result['assistEvent']->getId(),
                'statType' => 'ASSIST',
                'playerId' => $assistPlayer->getId(),
            ];
        }

        return $this->json(['data' => $responseData], Response::HTTP_CREATED);
    }

    #[Route('/{eventId}', methods: ['DELETE'])]
    public function revert(string $gameId, string $eventId): JsonResponse
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        $this->sec->assertCanAccessGame($game);

        $event = $this->statEventRepo->find($eventId);
        if (!$event || $event->getGame()->getId() !== $gameId) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        // Find and remove any linked history entries
        $historyEntries = $this->em->getRepository(\App\Entity\GameHistory::class)->findBy(['event' => $eventId]);
        foreach ($historyEntries as $h) {
            if ($h->getAssistEvent()) {
                $this->em->remove($h->getAssistEvent());
            }
            $this->em->remove($h);
        }

        // Also check if this event is an assist event referenced in history
        $assistHistoryEntries = $this->em->getRepository(\App\Entity\GameHistory::class)->findBy(['assistEvent' => $eventId]);
        foreach ($assistHistoryEntries as $h) {
            $h->setAssistEvent(null);
        }

        $this->em->remove($event);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }
}
