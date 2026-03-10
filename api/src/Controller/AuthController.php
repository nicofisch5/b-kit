<?php

namespace App\Controller;

use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthController extends AbstractController
{
    public function __construct(private SecurityService $sec) {}

    /**
     * This route is handled by the Symfony json_login firewall.
     * The controller body is never reached on successful auth.
     */
    #[Route('/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(['error' => 'Authentication failed'], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->sec->getCurrentUser();
        $org  = $this->sec->getCurrentOrg();

        return $this->json(['data' => [
            'id'               => $user->getId(),
            'email'            => $user->getEmail(),
            'role'             => $user->getRole(),
            'organizationId'   => $user->getOrganizationId(),
            'organizationSlug' => $org?->getSlug(),
            'organizationName' => $org?->getName(),
        ]]);
    }
}
