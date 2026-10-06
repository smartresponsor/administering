<?php

declare(strict_types=1);

namespace App\Administering\Responder\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * Produces an unauthenticated response without owning authentication.
 *
 * Host compositions may expose the Interfacing sign-in route; standalone
 * Administering fails closed with HTTP 401 when that owner route is absent.
 */
final readonly class AdministrationAuthenticationRequiredResponder
{
    private const string HOST_SIGN_IN_ROUTE = 'interfacing_welcome_sign_in';

    public function __construct(private RouterInterface $router)
    {
    }

    public function respond(): Response
    {
        if (null !== $this->router->getRouteCollection()->get(self::HOST_SIGN_IN_ROUTE)) {
            return new RedirectResponse($this->router->generate(self::HOST_SIGN_IN_ROUTE));
        }

        return new Response('Authentication is required.', Response::HTTP_UNAUTHORIZED);
    }
}
