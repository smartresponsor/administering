<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Native surface for promoting a previously reviewed Rolling ACL mutation to a
 * controlled Rolling-owned apply attempt.
 */
final class AdministrationRollingAclMutationApplyController extends AbstractController
{
    public function __construct(
        private readonly AdministrationAclMutationApplyServiceInterface $applyService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/rolling/acl-mutations/apply', name: 'administration_rolling_acl_mutation_apply', methods: ['GET', 'POST'])]
    public function apply(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        if ($request->isMethod('GET')) {
            return new Response(sprintf(<<<'HTML'
<h1>Apply Reviewed Rolling ACL Mutation</h1>
<p>This surface applies only an existing persisted dry-run review. Rolling remains the ACL execution owner.</p>
<form method="post" action="%s">
  <input type="hidden" name="_token" value="%s">
  <label>Review request key <input name="requestKey" placeholder="acl-review-..." required></label><br>
  <button type="submit">Apply through Rolling</button>
</form>
HTML,
                htmlspecialchars($this->generateUrl('administration_rolling_acl_mutation_apply'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($this->csrfTokenManager->getToken($this->csrfTokenId())->getValue(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ));
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId(), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid ACL mutation apply token.');
        }

        $currentUser = $this->currentUserContextProvider->current();
        $result = $this->applyService->applyReviewedMutation(
            trim((string) $request->request->get('requestKey', '')),
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
        );

        return new Response(sprintf(
            '<h1>Rolling ACL Apply Result</h1><pre>%s</pre><p><a href="%s">Back</a></p>',
            htmlspecialchars(json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($this->generateUrl('administration_rolling_acl_mutation_apply'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ));
    }

    private function csrfTokenId(): string
    {
        return 'administering.rolling.acl_mutation.apply';
    }
}
