<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountProjection;
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

    #[Route('/admin/accessing/accounts', name: 'administration_accessing_accounts')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.accessing.account.view', 'administering:accessing');

        $rows = array_map(
            static fn (AdministrationAccountProjection $projection): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($projection->subjectId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($projection->identifier(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $projection->active() ? 'active' : 'inactive',
                $projection->verified() ? 'verified' : 'unverified',
                htmlspecialchars(implode(', ', $projection->bootstrapRoles()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            $this->accountProjectionProvider->recent(25),
        );

        return new Response(sprintf(
            '<h1>Accessing Accounts</h1><p>Safe account projections. Security internals remain owned by Accessing.</p><table><thead><tr><th>Subject</th><th>Identifier</th><th>Status</th><th>Verification</th><th>Bootstrap roles</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $rows),
        ));
    }
}
