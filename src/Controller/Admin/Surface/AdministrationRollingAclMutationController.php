<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationReviewBuilderInterface;
use App\Rolling\Value\Administration\RollingAclMutationRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Native Symfony surface for safe Rolling ACL mutation dry-run reviews.
 *
 * This controller does not execute ACL mutations. It only builds review metadata
 * so an operator can see whether a request is valid before a future apply step.
 */
final class AdministrationRollingAclMutationController extends AbstractController
{
    public function __construct(
        private readonly RollingAclMutationReviewBuilderInterface $reviewBuilder,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly AdministrationAclMutationReviewRecorderInterface $reviewRecorder,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/rolling/acl-mutations', name: 'administration_rolling_acl_mutations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review.view', 'administering:rolling');

        return new Response(sprintf(<<<'HTML'
<h1>Rolling ACL Mutation Review</h1>
<p>Dry-run review only. Rolling owns authorization and ACL persistence.</p>
<form method="post" action="%s">
  <input type="hidden" name="_token" value="%s">
  <label>Mutation type <input name="mutationType" value="role.assign" required></label><br>
  <label>Subject <input name="subjectIdentifier" placeholder="accessing:account:123" required></label><br>
  <label>Permission/role key <input name="permissionOrRoleKey" placeholder="administration.config.view" required></label><br>
  <label>Scope <input name="scopeKey" value="global" required></label><br>
  <button type="submit">Review dry-run plan</button>
</form>
HTML,
            htmlspecialchars($this->generateUrl('administration_rolling_acl_mutation_review'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($this->csrfTokenManager->getToken($this->csrfTokenId())->getValue(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ));
    }

    #[Route('/admin/rolling/acl-mutations/review', name: 'administration_rolling_acl_mutation_review', methods: ['POST'])]
    public function review(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId(), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid ACL mutation review token.');
        }

        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review', 'administering:rolling');

        $currentUser = $this->currentUserContextProvider->current();
        $mutationRequest = new RollingAclMutationRequest(
            trim((string) $request->request->get('mutationType', '')),
            trim((string) $request->request->get('subjectIdentifier', '')),
            trim((string) $request->request->get('permissionOrRoleKey', '')),
            trim((string) $request->request->get('scopeKey', 'global')),
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            [
                'source' => 'administering_ui',
                'surface' => 'rolling_acl_mutation_review',
            ],
        );

        $review = $this->reviewBuilder->review($mutationRequest);
        $record = $this->reviewRecorder->record($mutationRequest, $review);

        return new Response(sprintf(
            '<h1>Rolling ACL Mutation Review</h1><p>Persisted review record: %s</p><pre>%s</pre><p><a href="%s">Back</a></p>',
            htmlspecialchars($record->requestKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars(json_encode($review->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($this->generateUrl('administration_rolling_acl_mutations'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ));
    }

    private function csrfTokenId(): string
    {
        return 'administering.rolling.acl_mutation.review';
    }
}
