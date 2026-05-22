<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationFieldViewProfileReviewServiceInterface;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileEditRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Review-only surface for Managing field view profile changes.
 */
final class AdministrationManagingFieldViewProfileReviewController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldViewProfileReviewServiceInterface $reviewService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/managing/field-view-profiles/edit', name: 'administration_managing_field_view_profile_edit', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile');

        return new Response(sprintf(<<<'HTML'
<h1>Managing Field View Profile Review</h1>
<p>Review-only surface. It builds a normalized Managing profile payload but does not persist or apply it.</p>
<form method="post" action="%s">
  <input type="hidden" name="_token" value="%s">
  <label>Subject type <select name="subjectType"><option>user</option><option>role</option><option>group</option></select></label><br>
  <label>Subject identifier <input name="subjectIdentifier" placeholder="user:42 or security.admin" required></label><br>
  <label>Mode <select name="mode"><option>replace</option><option>merge</option><option>clear</option></select></label><br>
  <label>Resource class <input name="resourceClass" placeholder="Leave empty for page defaults"></label><br>
  <label>Page <input name="pageName" value="index" required></label><br>
  <label>Visible fields <textarea name="visibleFields" placeholder="title, status" rows="3" cols="80"></textarea></label><br>
  <label>Hidden fields <textarea name="hiddenFields" placeholder="createdAt, updatedAt" rows="3" cols="80"></textarea></label><br>
  <label>Reason <input name="reason" placeholder="Optional safe reason"></label><br>
  <button type="submit">Review field view profile change</button>
</form>
HTML,
            $this->escape($this->generateUrl('administration_managing_field_view_profile_review')),
            $this->escape($this->csrfTokenManager->getToken($this->csrfTokenId())->getValue()),
        ));
    }

    #[Route('/admin/managing/field-view-profiles/review', name: 'administration_managing_field_view_profile_review', methods: ['POST'])]
    public function review(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId(), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid Managing field view profile review token.');
        }

        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile');

        $currentUser = $this->currentUserContextProvider->current();
        $result = $this->reviewService->review(new AdministrationFieldViewProfileEditRequest(
            subjectType: trim((string) $request->request->get('subjectType', 'user')),
            subjectIdentifier: trim((string) $request->request->get('subjectIdentifier', '')),
            pageName: trim((string) $request->request->get('pageName', 'index')),
            visibleFields: $this->fieldsFromRequestValue((string) $request->request->get('visibleFields', '')),
            hiddenFields: $this->fieldsFromRequestValue((string) $request->request->get('hiddenFields', '')),
            resourceClass: trim((string) $request->request->get('resourceClass', '')) ?: null,
            reason: trim((string) $request->request->get('reason', '')) ?: null,
            mode: trim((string) $request->request->get('mode', 'replace')),
            requestedBySubject: $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
        ));

        return new Response(sprintf(
            '<h1>Managing Field View Profile Review</h1><p>Change type: %s</p><p>Target: %s</p><pre>%s</pre><p><a href="%s">Back</a></p>',
            $this->escape($result->changeType),
            $this->escape($result->targetReference),
            $this->escape(json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)),
            $this->escape($this->generateUrl('administration_managing_field_view_profile_edit')),
        ));
    }

    /** @return list<string> */
    private function fieldsFromRequestValue(string $value): array
    {
        $fields = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);

        if (false === $fields) {
            return [];
        }

        return array_values(array_map(static fn (string $field): string => trim($field), $fields));
    }

    private function csrfTokenId(): string
    {
        return 'administering.managing.field_view_profile.review';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
