<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native Symfony surface for Accessing-owned account projections.
 */
final class AdministrationAccessingAccountController extends AbstractController
{
    public function __construct(private readonly AdministrationAccountProjectionProviderInterface $accountProjectionProvider)
    {
    }

    #[Route('/ea/accessing/accounts', name: 'administration_accessing_accounts')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.accessing.account.view', 'administering:accessing');

        return $this->render('@Administering/administering/accessing_accounts.html.twig', [
            'projections' => $this->accountProjectionProvider->recent(25),
        ]);
    }
}
