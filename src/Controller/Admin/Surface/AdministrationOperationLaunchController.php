<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\ServiceInterface\Operation\AdministrationOperationSubmitterInterface;
use App\Administering\Value\Operation\AdministrationOperationPlan;
use App\Administering\Value\Operation\AdministrationOperationType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Native Symfony surface for launching safe metadata-only Administering operations.
 */
final class AdministrationOperationLaunchController extends AbstractController
{
    public function __construct(
        private readonly AdministrationOperationSubmitterInterface $operationSubmitter,
        private readonly ManagerRegistry $managerRegistry,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/operations', name: 'administration_operations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.operation.view', 'administering:operation');

        $rows = array_map(
            fn (string $operationType): string => sprintf(
                '<tr><td>%s</td><td><form method="post" action="%s"><input type="hidden" name="_token" value="%s"><button type="submit">Queue</button></form></td></tr>',
                htmlspecialchars($operationType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($this->generateUrl('administration_operation_start', ['operationType' => $operationType]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($this->csrfTokenManager->getToken($this->csrfTokenId($operationType))->getValue(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            AdministrationOperationType::launchable(),
        );

        return new Response(sprintf(
            '<h1>Administering Operations</h1><p>Launches metadata-only operations. Secrets and raw configuration payloads are forbidden in operation messages.</p><h2>Launchable operations</h2><table><thead><tr><th>Operation</th><th>Action</th></tr></thead><tbody>%s</tbody></table><h2>Recent runs</h2>%s',
            implode('', $rows),
            $this->renderRecentRunsTable(),
        ));
    }

    #[Route('/admin/operations/{operationType}/start', name: 'administration_operation_start', methods: ['POST'])]
    public function start(string $operationType, Request $request): RedirectResponse
    {
        if (!AdministrationOperationType::isLaunchable($operationType)) {
            throw $this->createNotFoundException(sprintf('Operation "%s" is not launchable from the Administering UI.', $operationType));
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId($operationType), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid operation launch token.');
        }

        $this->denyAccessUnlessGranted($operationType, 'administering:operation');

        $operationRun = $this->operationSubmitter->submitForCurrentUser(new AdministrationOperationPlan(
            $operationType,
            'administering:operation',
            [
                'source' => 'admin_ui',
                'client' => 'native_symfony',
                'requested_from' => $request->getPathInfo(),
            ],
        ));

        $this->addFlash('success', sprintf('Queued operation %s.', $operationRun->operationKey()));

        return $this->redirectToRoute('administering_operation_run_detail', ['operationKey' => $operationRun->operationKey()]);
    }

    private function csrfTokenId(string $operationType): string
    {
        return 'administering.operation.start.'.$operationType;
    }

    private function renderRecentRunsTable(): string
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);
        if (null === $manager) {
            return '<p>Operation storage is not configured for App\\Administering entities.</p>';
        }

        $runs = $manager->getRepository(AdministrationOperationRun::class)->findBy([], ['id' => 'DESC'], 20);
        if ([] === $runs) {
            return '<p>No operation runs recorded yet.</p>';
        }

        $rows = array_map(function (AdministrationOperationRun $run): string {
            return sprintf(
                '<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($this->generateUrl('administering_operation_run_detail', ['operationKey' => $run->operationKey()]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($run->operationKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($run->operationType(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($run->status(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($run->createdAt()->format(\DateTimeInterface::ATOM), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }, $runs);

        return sprintf(
            '<table><thead><tr><th>Run</th><th>Type</th><th>Status</th><th>Created</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $rows),
        );
    }
}
