<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationBridgeInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationActionDescriptor;
use App\Accessing\Value\Admin\AccessingAccountAdministrationRequest;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionRequestRecorderInterface;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Native Symfony surface for controlled Accessing-owned account actions.
 *
 * The surface creates safe request DTOs only. Accessing owns execution,
 * validation, audit, and all account/session/security internals.
 */
final class AdministrationAccessingAccountActionController extends AbstractController
{
    public function __construct(
        private readonly AccessingAccountAdministrationActionCatalogInterface $actionCatalog,
        private readonly AccessingAccountAdministrationBridgeInterface $accountAdministrationBridge,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly AdministrationAccountActionRequestRecorderInterface $requestRecorder,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/accessing/account-actions', name: 'administration_accessing_account_actions', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.accessing.account_action.view', 'administering:accessing');

        $rows = array_map(
            static fn (AccessingAccountAdministrationActionDescriptor $descriptor): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><form method="post" action="%s"><input type="hidden" name="_token" value="%s"><input type="hidden" name="action" value="%s"><input name="accountReference" placeholder="accessing:account:123" required><input name="reason" placeholder="safe reason" required><button type="submit">Submit controlled request</button></form></td></tr>',
                htmlspecialchars($descriptor->key(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($descriptor->label(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($descriptor->riskLevel(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $descriptor->requiresReason() ? 'yes' : 'no',
                htmlspecialchars($this->generateUrl('administration_accessing_account_action_execute'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($this->csrfTokenManager->getToken($this->csrfTokenId($descriptor->key()))->getValue(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($descriptor->key(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            $this->actionCatalog->descriptors(),
        );

        return new Response(sprintf(
            '<h1>Accessing Account Actions</h1><p>Controlled action requests only. Accessing remains the owner of login, session, password, 2FA, and security internals.</p><table><thead><tr><th>Action</th><th>Label</th><th>Risk</th><th>Reason required</th><th>Submit</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $rows),
        ));
    }

    #[Route('/admin/accessing/account-actions/execute', name: 'administration_accessing_account_action_execute', methods: ['POST'])]
    public function execute(Request $request): RedirectResponse
    {
        $action = trim((string) $request->request->get('action', ''));
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId($action), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid account action token.');
        }

        $this->denyAccessUnlessGranted('administration.accessing.account_action.execute', 'administering:accessing');

        $currentUser = $this->currentUserContextProvider->current();
        $accountReference = trim((string) $request->request->get('accountReference', ''));
        $reason = trim((string) $request->request->get('reason', ''));

        $actionRequest = new AccessingAccountAdministrationRequest(
            $action,
            $accountReference,
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            $reason,
            [
                'source' => 'administering_ui',
                'surface' => 'accessing_account_actions',
            ],
        );

        $result = $this->accountAdministrationBridge->executeRequest($actionRequest);
        $record = $this->requestRecorder->record($actionRequest, $result);

        $this->addFlash(
            $result->succeeded() ? 'success' : 'warning',
            sprintf('%s Request record: %s.', $result->safeMessage(), $record->requestKey()),
        );

        return $this->redirectToRoute('administration_accessing_account_actions');
    }

    private function csrfTokenId(string $action): string
    {
        return 'administering.accessing.account_action.'.$action;
    }
}
