<?php

declare(strict_types=1);

namespace App\Administering\Voter;

use App\Administering\CheckerInterface\Security\AdministrationPermissionCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Symfony-native gate for Administering actions.
 *
 * The voter keeps EasyAdmin/controllers independent from external authorization internals. The host can be
 * wired behind AdministrationPermissionCheckerInterface by the host application.
 */
/** @extends Voter<string, mixed> */
final class AdministrationAccessVoter extends Voter
{
    public function __construct(private readonly AdministrationPermissionCheckerInterface $permissionChecker)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'administration.');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $scope = is_string($subject) && '' !== $subject ? $subject : 'administering:global';

        return $this->permissionChecker->isGranted($attribute, $scope, [
            'subject' => is_object($subject) ? $subject::class : $subject,
        ]);
    }
}
