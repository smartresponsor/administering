<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Responder\Security;

use App\Administering\Responder\Security\AdministrationAuthenticationRequiredResponder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class AdministrationAuthenticationRequiredResponderTest extends TestCase
{
    public function testRedirectsToHostSignInRouteWhenAvailable(): void
    {
        $routes = new RouteCollection();
        $routes->add('interfacing_welcome_sign_in', new Route('/sign-in'));

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())->method('getRouteCollection')->willReturn($routes);
        $router->expects(self::once())->method('generate')->with('interfacing_welcome_sign_in')->willReturn('/sign-in');

        $response = (new AdministrationAuthenticationRequiredResponder($router))->respond();

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/sign-in', $response->headers->get('Location'));
    }

    public function testReturnsUnauthorizedWhenHostSignInRouteIsUnavailable(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())->method('getRouteCollection')->willReturn(new RouteCollection());
        $router->expects(self::never())->method('generate');

        $response = (new AdministrationAuthenticationRequiredResponder($router))->respond();

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Authentication is required.', $response->getContent());
    }
}
