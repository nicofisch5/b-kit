<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\OrganizationRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function __construct(private OrganizationRepository $orgRepo) {}

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) return;

        $orgSlug = null;
        if ($user->getOrganizationId()) {
            $org = $this->orgRepo->find($user->getOrganizationId());
            $orgSlug = $org?->getSlug();
        }

        $payload = $event->getData();
        $payload['userId']           = $user->getId();
        $payload['role']             = $user->getRole();
        $payload['organizationId']   = $user->getOrganizationId();
        $payload['organizationSlug'] = $orgSlug;
        $event->setData($payload);
    }
}
