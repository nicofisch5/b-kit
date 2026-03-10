<?php

namespace App\Controller;

use App\Entity\SessionDrill;
use App\Entity\TrainingSession;
use App\Repository\CycleRepository;
use App\Repository\DrillRepository;
use App\Repository\SessionDrillRepository;
use App\Repository\TrainingSessionRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/training-sessions')]
class TrainingSessionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TrainingSessionRepository $sessionRepo,
        private SessionDrillRepository $sessionDrillRepo,
        private DrillRepository $drillRepo,
        private CycleRepository $cycleRepo,
        private SecurityService $sec,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->sec->getCurrentUser();
        $orgId = $this->sec->getOrgFilter();
        $isAdmin = $this->sec->isAdmin();

        $sessions = $this->sessionRepo->findForUser($orgId, $user->getId(), $isAdmin);

        return $this->json(['data' => array_map(fn(TrainingSession $s) => $this->serializeList($s), $sessions)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (empty($body['date'])) {
            return $this->json(['error' => 'date is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->sec->getCurrentUser();
        $orgId = $this->sec->getOrgFilter();

        $session = new TrainingSession();
        $session->setDate(new \DateTime($body['date']));
        $session->setGoal($body['goal'] ?? null);
        $session->setDuration(isset($body['duration']) && $body['duration'] !== '' ? (int)$body['duration'] : null);
        $session->setComments($body['comments'] ?? null);
        $session->setCreatedBy($user->getId());
        $session->setOrganizationId($orgId);

        $warnings = $this->applyCycle($session, $body['cycleId'] ?? null, $user->getId());

        $this->em->persist($session);
        $this->em->flush();

        return $this->json(['data' => $this->serializeList($session), 'warnings' => $warnings], Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) {
            return $this->json(['error' => 'Training session not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanEdit($session);

        return $this->json(['data' => $this->serializeDetail($session)]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) {
            return $this->json(['error' => 'Training session not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanEdit($session);

        $body = json_decode($request->getContent(), true) ?? [];

        if (isset($body['date'])) $session->setDate(new \DateTime($body['date']));
        if (array_key_exists('goal', $body)) $session->setGoal($body['goal']);
        if (array_key_exists('duration', $body)) $session->setDuration($body['duration'] !== '' && $body['duration'] !== null ? (int)$body['duration'] : null);
        if (array_key_exists('comments', $body)) $session->setComments($body['comments']);

        $warnings = [];
        if (array_key_exists('cycleId', $body)) {
            $warnings = $this->applyCycle($session, $body['cycleId'], $this->sec->getCurrentUser()->getId());
        } else {
            $warnings = $this->dateWarnings($session);
        }

        $this->em->flush();

        return $this->json(['data' => $this->serializeDetail($session), 'warnings' => $warnings]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) {
            return $this->json(['error' => 'Training session not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanEdit($session);

        $this->em->remove($session);
        $this->em->flush();

        return $this->json(['data' => ['deleted' => true]]);
    }

    #[Route('/{id}/drills', methods: ['PUT'])]
    public function updateDrills(string $id, Request $request): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) {
            return $this->json(['error' => 'Training session not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanEdit($session);

        $body = json_decode($request->getContent(), true) ?? [];
        $drillsInput = $body['drills'] ?? [];

        if (!is_array($drillsInput)) {
            return $this->json(['error' => 'drills must be an array'], Response::HTTP_BAD_REQUEST);
        }

        // Remove all existing session drills
        foreach ($session->getSessionDrills() as $sd) {
            $this->em->remove($sd);
        }
        $this->em->flush();

        // Recreate in order
        foreach ($drillsInput as $index => $item) {
            $drillId = $item['drillId'] ?? null;
            if (!$drillId) continue;

            $drill = $this->drillRepo->find($drillId);
            if (!$drill) continue;

            $sd = new SessionDrill();
            $sd->setSession($session);
            $sd->setDrill($drill);
            $sd->setSortOrder($index);
            $sd->setNote($item['note'] ?? null);
            $this->em->persist($sd);
        }

        $this->em->flush();

        // Reload the session to get fresh collection
        $this->em->refresh($session);

        return $this->json(['data' => $this->serializeDetail($session)]);
    }

    #[Route('/{id}/pdf', methods: ['GET'])]
    public function exportPdf(string $id): Response
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) {
            return $this->json(['error' => 'Training session not found'], Response::HTTP_NOT_FOUND);
        }

        $this->assertCanEdit($session);

        $html = $this->buildPdfHtml($session);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'training-session-'.$session->getDate()->format('Y-m-d').'.pdf';

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    private function buildPdfHtml(TrainingSession $session): string
    {
        $date     = $session->getDate()->format('d/m/Y');
        $goal     = $this->esc($session->getGoal() ?? '—');
        $duration = $session->getDuration() ? $session->getDuration().' min' : '—';
        $comments = $session->getComments();
        $drills   = $session->getSessionDrills()->toArray();
        $count    = count($drills);

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
            .header { background: #ff6b35; color: white; padding: 18px 24px; margin-bottom: 20px; }
            .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
            .app-name { font-size: 10px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; opacity: 0.85; }
            .session-date { font-size: 13px; font-weight: bold; }
            .header-title { font-size: 20px; font-weight: bold; margin-top: 4px; }
            .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            .meta-table td { padding: 5px 12px; vertical-align: top; }
            .meta-table td:first-child { font-weight: bold; color: #666; width: 90px; font-size: 10px; text-transform: uppercase; }
            .meta-box { background: #f8f8f8; border: 1px solid #e5e5e5; border-radius: 4px; padding: 12px 16px; margin: 0 24px 20px; }
            .divider { border: none; border-top: 2px solid #ff6b35; margin: 0 24px 20px; }
            .drill-section { margin: 0 24px 20px; page-break-inside: avoid; }
            .drill-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #e5e5e5; }
            .drill-num { background: #ff6b35; color: white; border-radius: 50%; width: 22px; height: 22px; display: inline-block; text-align: center; line-height: 22px; font-weight: bold; font-size: 11px; flex-shrink: 0; }
            .drill-code { background: #ff6b35; color: white; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: bold; font-family: monospace; }
            .drill-name { font-size: 13px; font-weight: bold; flex: 1; }
            .drill-meta { font-size: 10px; color: #888; white-space: nowrap; }
            .drill-body { display: flex; flex-direction: column; gap: 7px; padding-left: 32px; }
            .field-block { }
            .field-label { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #ff6b35; margin-bottom: 2px; }
            .field-value { font-size: 11px; color: #333; white-space: pre-wrap; line-height: 1.5; }
            .note-box { background: #fff8f5; border-left: 3px solid #ff6b35; padding: 6px 10px; border-radius: 0 3px 3px 0; }
            .note-label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #ff6b35; margin-bottom: 2px; }
            .tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px; }
            .tag { background: #f0f0f0; padding: 1px 6px; border-radius: 10px; font-size: 9px; color: #555; }
            .link-line { font-size: 10px; color: #1a56db; }
            .footer { margin-top: 30px; padding: 10px 24px; border-top: 1px solid #e5e5e5; font-size: 9px; color: #aaa; display: flex; justify-content: space-between; }
        </style></head><body>';

        // ── Header ──────────────────────────────────────────────────
        $html .= '<div class="header">';
        $html .= '<div class="header-top"><span class="app-name">B-Strack</span><span class="session-date">'.$date.'</span></div>';
        $html .= '<div class="header-title">Training Session Plan</div>';
        $html .= '</div>';

        // ── Session meta ────────────────────────────────────────────
        $html .= '<div class="meta-box">';
        $html .= '<table class="meta-table">';
        $html .= '<tr><td>Goal</td><td>'.$goal.'</td></tr>';
        $html .= '<tr><td>Duration</td><td>'.$this->esc($duration).'</td></tr>';
        $html .= '<tr><td>Drills</td><td>'.$count.'</td></tr>';
        if ($comments) {
            $html .= '<tr><td>Comments</td><td>'.$this->esc($comments).'</td></tr>';
        }
        $html .= '</table></div>';

        if ($count === 0) {
            $html .= '<div style="margin:0 24px;color:#888;font-style:italic;">No drills in this session.</div>';
        }

        // ── Drills ──────────────────────────────────────────────────
        foreach ($drills as $i => $sd) {
            /** @var \App\Entity\SessionDrill $sd */
            $drill = $sd->getDrill();
            $num   = $i + 1;

            $metaParts = [];
            if ($drill->getDuration()) $metaParts[] = $drill->getDuration().' min';
            if ($drill->getMinimumPlayers()) $metaParts[] = 'Min. '.$drill->getMinimumPlayers().' players';
            $drillMeta = implode(' · ', $metaParts);

            $html .= '<hr class="divider"><div class="drill-section">';
            $html .= '<div class="drill-header">';
            $html .= '<span class="drill-num">'.$num.'</span>';
            $html .= '<span class="drill-code">'.$this->esc($drill->getCode()).'</span>';
            $html .= '<span class="drill-name">'.$this->esc($drill->getName()).'</span>';
            if ($drillMeta) {
                $html .= '<span class="drill-meta">'.$this->esc($drillMeta).'</span>';
            }
            $html .= '</div>';

            $html .= '<div class="drill-body">';

            foreach ([
                'Equipment'  => $drill->getEquipment(),
                'Setup'      => $drill->getSetup(),
                'Execution'  => $drill->getExecution(),
                'Rotation'   => $drill->getRotation(),
                'Evolution'  => $drill->getEvolution(),
            ] as $label => $value) {
                if ($value) {
                    $html .= '<div class="field-block">';
                    $html .= '<div class="field-label">'.$label.'</div>';
                    $html .= '<div class="field-value">'.$this->esc($value).'</div>';
                    $html .= '</div>';
                }
            }

            // Coach note
            if ($sd->getNote()) {
                $html .= '<div class="note-box">';
                $html .= '<div class="note-label">Coach note</div>';
                $html .= '<div class="field-value">'.$this->esc($sd->getNote()).'</div>';
                $html .= '</div>';
            }

            // Tags
            $tags = $drill->getTags();
            if (!empty($tags)) {
                $html .= '<div class="tags">';
                foreach ($tags as $tag) {
                    $html .= '<span class="tag">'.$this->esc($tag).'</span>';
                }
                $html .= '</div>';
            }

            // Links
            $links = $drill->getLinks();
            if (!empty($links)) {
                foreach ($links as $link) {
                    $title = $this->esc($link['title'] ?? '');
                    $url   = $this->esc($link['url'] ?? '');
                    if ($url) {
                        $html .= '<div class="link-line">&#128279; '.$title.' — '.$url.'</div>';
                    }
                }
            }

            $html .= '</div></div>'; // .drill-body .drill-section
        }

        // ── Footer ──────────────────────────────────────────────────
        $html .= '<div class="footer">';
        $html .= '<span>Generated by B-Strack</span>';
        $html .= '<span>'.date('d/m/Y H:i').'</span>';
        $html .= '</div>';

        $html .= '</body></html>';

        return $html;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function assertCanEdit(TrainingSession $session): void
    {
        if ($this->sec->isSuperAdmin()) return;
        $user = $this->sec->getCurrentUser();
        if ($this->sec->isAdmin()) {
            if ($session->getOrganizationId() !== $user->getOrganizationId()) {
                throw new AccessDeniedHttpException('Access denied.');
            }
            return;
        }
        if ($session->getCreatedBy() !== $user->getId()) {
            throw new AccessDeniedHttpException('Access denied.');
        }
    }

    private function serializeList(TrainingSession $session): array
    {
        $cycle = $session->getCycle();
        return [
            'id'             => $session->getId(),
            'date'           => $session->getDate()->format('Y-m-d'),
            'goal'           => $session->getGoal(),
            'duration'       => $session->getDuration(),
            'comments'       => $session->getComments(),
            'organizationId' => $session->getOrganizationId(),
            'createdBy'      => $session->getCreatedBy(),
            'drillCount'     => $session->getSessionDrills()->count(),
            'cycleId'        => $cycle?->getId(),
            'cycleName'      => $cycle?->getName(),
            'createdAt'      => $session->getCreatedAt()->format('Y-m-d\TH:i:s'),
            'updatedAt'      => $session->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }

    private function serializeDetail(TrainingSession $session): array
    {
        $drills = [];
        foreach ($session->getSessionDrills() as $sd) {
            $drill = $sd->getDrill();
            $drills[] = [
                'id'        => $sd->getId(),
                'sortOrder' => $sd->getSortOrder(),
                'note'      => $sd->getNote(),
                'drill'     => [
                    'id'             => $drill->getId(),
                    'code'           => $drill->getCode(),
                    'name'           => $drill->getName(),
                    'setup'          => $drill->getSetup(),
                    'execution'      => $drill->getExecution(),
                    'rotation'       => $drill->getRotation(),
                    'evolution'      => $drill->getEvolution(),
                    'duration'       => $drill->getDuration(),
                    'equipment'      => $drill->getEquipment(),
                    'minimumPlayers' => $drill->getMinimumPlayers(),
                    'tags'           => $drill->getTags(),
                    'links'          => $drill->getLinks(),
                    'visibility'     => $drill->getVisibility(),
                    'organizationId' => $drill->getOrganizationId(),
                    'createdBy'      => $drill->getCreatedBy(),
                    'createdAt'      => $drill->getCreatedAt()->format('Y-m-d\TH:i:s'),
                    'updatedAt'      => $drill->getUpdatedAt()->format('Y-m-d\TH:i:s'),
                ],
            ];
        }

        $cycle = $session->getCycle();
        return [
            'id'             => $session->getId(),
            'date'           => $session->getDate()->format('Y-m-d'),
            'goal'           => $session->getGoal(),
            'duration'       => $session->getDuration(),
            'comments'       => $session->getComments(),
            'organizationId' => $session->getOrganizationId(),
            'createdBy'      => $session->getCreatedBy(),
            'cycleId'        => $cycle?->getId(),
            'cycleName'      => $cycle?->getName(),
            'cycleStartDate' => $cycle?->getStartDate()?->format('Y-m-d'),
            'cycleEndDate'   => $cycle?->getEndDate()?->format('Y-m-d'),
            'createdAt'      => $session->getCreatedAt()->format('Y-m-d\TH:i:s'),
            'updatedAt'      => $session->getUpdatedAt()->format('Y-m-d\TH:i:s'),
            'drills'         => $drills,
        ];
    }

    /**
     * Links or unlinks a cycle on a session.
     * Validates that the cycle belongs to the current user.
     * Returns an array of date-range warning strings.
     */
    private function applyCycle(TrainingSession $session, ?string $cycleId, string $userId): array
    {
        if (!$cycleId) {
            $session->setCycle(null);
            return [];
        }

        $cycle = $this->cycleRepo->find($cycleId);
        if (!$cycle || $cycle->getCreatedBy() !== $userId) {
            // Ignore unknown or foreign cycles silently — do not expose existence
            $session->setCycle(null);
            return [];
        }

        $session->setCycle($cycle);
        return $this->dateWarnings($session);
    }

    /** Returns warning messages when the session date falls outside the linked cycle's date range. */
    private function dateWarnings(TrainingSession $session): array
    {
        $cycle = $session->getCycle();
        if (!$cycle) return [];

        $warnings = [];
        $date = $session->getDate();

        if ($cycle->getStartDate() && $date < $cycle->getStartDate()) {
            $warnings[] = 'Session date ('.$date->format('Y-m-d').') is before the cycle start date ('.$cycle->getStartDate()->format('Y-m-d').').';
        }
        if ($cycle->getEndDate() && $date > $cycle->getEndDate()) {
            $warnings[] = 'Session date ('.$date->format('Y-m-d').') is after the cycle end date ('.$cycle->getEndDate()->format('Y-m-d').').';
        }

        return $warnings;
    }
}
