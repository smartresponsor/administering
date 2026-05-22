<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationFieldAccessMutationReviewServiceInterface;
use App\Administering\Value\Rolling\AdministrationFieldAccessMutationReviewInput;
use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;
use App\Administering\Value\Rolling\AdministrationFieldAccessTarget;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Review-only surface for Managing field access policy changes.
 */
final class AdministrationManagingFieldAccessMutationReviewController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldAccessMutationReviewServiceInterface $reviewService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/managing/field-access-mutations', name: 'administration_managing_field_access_mutations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review.view', 'administering:managing-field-access');

        return new Response(sprintf(<<<'HTML'
<h1>Managing Field Access Mutation Review</h1>
<p>Review-only surface. No Rolling ACL changes are applied here.</p>
<form method="post" action="%s">
  <input type="hidden" name="_token" value="%s">
  <label>Subject type <select name="subjectType"><option>role</option><option>user</option><option>group</option></select></label><br>
  <label>Subject identifier <input name="subjectIdentifier" placeholder="security.admin" required></label><br>
  <label>Effect <select name="effect"><option>allow</option><option>deny</option></select></label><br>
  <label>Permission <input name="permissionKey" value="managing.field.view" required></label><br>
  <label>Resource class <input name="resourceClass" placeholder="App\Cataloging\Entity\Catalog\CatalogCategoryEntity" required></label><br>
  <label>Field <input name="fieldName" placeholder="internalCost" required></label><br>
  <label>Page <input name="pageName" value="detail" required></label><br>
  <label>Operation <input name="operation" value="view" required></label><br>
  <label>Reason <input name="reason" placeholder="Optional safe reason"></label><br>
  <button type="submit">Review field access mutation</button>
</form>
HTML,
            $this->escape($this->generateUrl('administration_managing_field_access_mutation_review')),
            $this->escape($this->csrfTokenManager->getToken($this->csrfTokenId())->getValue()),
        ));
    }

    #[Route('/admin/managing/field-access-mutations/review', name: 'administration_managing_field_access_mutation_review', methods: ['POST'])]
    public function review(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId(), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid Managing field access mutation review token.');
        }

        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review', 'administering:managing-field-access');

        $currentUser = $this->currentUserContextProvider->current();
        $result = $this->reviewService->review(new AdministrationFieldAccessMutationReviewInput(
            $this->descriptorFromRequest($request),
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
        ));

        return new Response(sprintf(
            '<h1>Managing Field Access Mutation Review</h1><p>Persisted review record: %s</p><pre>%s</pre><p><a href="%s">Apply reviewed record</a></p><p><a href="%s">Back</a></p>',
            $this->escape($result->record->requestKey()),
            $this->escape(json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)),
            $this->escape($this->generateUrl('administration_managing_field_access_mutation_apply', ['requestKey' => $result->record->requestKey()])),
            $this->escape($this->generateUrl('administration_managing_field_access_mutations')),
        ));
    }

    private function descriptorFromRequest(Request $request): AdministrationFieldAccessPolicyDescriptor
    {
        return new AdministrationFieldAccessPolicyDescriptor(
            new AdministrationFieldAccessTarget(
                'Managing',
                trim((string) $request->request->get('resourceClass', '')),
                trim((string) $request->request->get('fieldName', '')),
                trim((string) $request->request->get('pageName', 'detail')),
                trim((string) $request->request->get('operation', 'view')),
            ),
            trim((string) $request->request->get('permissionKey', AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW)),
            trim((string) $request->request->get('subjectType', AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE)),
            trim((string) $request->request->get('subjectIdentifier', '')),
            trim((string) $request->request->get('effect', AdministrationFieldAccessPolicyDescriptor::EFFECT_ALLOW)),
            trim((string) $request->request->get('reason', '')) ?: null,
        );
    }

    private function csrfTokenId(): string
    {
        return 'administering.managing.field_access_mutation.review';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
