<?php

declare(strict_types=1);

namespace App\Administering\Subscriber\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Prevent stale HTML caches on admin screens.
 *
 * The admin shell and configuration center are highly dynamic and should not
 * be cached by the browser or intermediary proxies because stale markup can
 * keep old admin links alive after a deployment or cache clear.
 */
final readonly class AdminNoStoreSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        $routeName = (string) $request->attributes->get('_route', '');

        if (!str_starts_with($pathInfo, '/ea') && !str_starts_with($routeName, 'administration_')) {
            return;
        }

        $response = $event->getResponse();
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private', true);
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        if (Response::HTTP_OK === $response->getStatusCode()) {
            $response->headers->set('X-Admin-No-Store', '1');
        }
    }
}
