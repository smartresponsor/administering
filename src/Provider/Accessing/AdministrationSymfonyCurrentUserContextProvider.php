<?php

declare(strict_types=1);

namespace App\Administering\Provider\Accessing;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\AdministrationCurrentUserContext;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final class AdministrationSymfonyCurrentUserContextProvider implements AdministrationCurrentUserContextProviderInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function current(): ?AdministrationCurrentUserContext
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return null;
        }

        $subjectIdentifier = 'symfony:user:'.$user->getUserIdentifier();

        return new AdministrationCurrentUserContext(
            $subjectIdentifier,
            $user->getUserIdentifier(),
            array_values(array_unique($user->getRoles())),
        );
    }
}
