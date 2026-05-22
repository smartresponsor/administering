<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationFieldAccessMutationApplyServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Applies a persisted Managing field-access review through the Rolling-owned ACL
 * execution flow after field-specific guard checks pass.
 */
final class AdministrationManagingFieldAccessMutationApplyController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldAccessMutationApplyServiceInterface $applyService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/managing/field-access-mutations/apply', name: 'administration_managing_field_access_mutation_apply', methods: ['GET', 'POST'])]
    public function apply(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply.view', 'administering:managing-field-access');

        if ($request->isMethod('GET')) {
            return new Response(sprintf(<<<'HTML'
<h1>Apply Managing Field Access Review</h1>
<p>This surface applies only persisted Managing field-access review records. Broader Rolling ACL reviews are rejected here.</p>
<form method="post" action="%s">
  <input type="hidden" name="_token" value="%s">
  <label>Review request key <input name="requestKey" value="%s" placeholder="acl-review-..." required></label><br>
  <button type="submit">Apply Managing field access review</button>
</form>
HTML,
                $this->escape($this->generateUrl('administration_managing_field_access_mutation_apply')),
                $this->escape($this->csrfTokenManager->getToken($this->csrfTokenId())->getValue()),
                $this->escape(trim((string) $request->query->get('requestKey', ''))),
            ));
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId(), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid Managing field access apply token.');
        }

        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:managing-field-access');

        $currentUser = $this->currentUserContextProvider->current();
        $result = $this->applyService->applyReviewedFieldAccessMutation(
            trim((string) $request->request->get('requestKey', '')),
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
        );

        return new Response(sprintf(
            '<h1>Managing Field Access Apply Result</h1><pre>%s</pre><p><a href="%s">Back</a></p>',
            $this->escape(json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)),
            $this->escape($this->generateUrl('administration_managing_field_access_mutation_apply')),
        ));
    }

    private function csrfTokenId(): string
    {
        return 'administering.managing.field_access_mutation.apply';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
