<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationFieldVisibilityInspectionPrepareServiceInterface;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityInspectionPrepareRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Prepares read-only Managing field visibility inspection requests from Administering.
 */
final class AdministrationManagingFieldVisibilityInspectionPrepareController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldVisibilityInspectionPrepareServiceInterface $prepareService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/managing/field-visibility-inspection', name: 'administration_managing_field_visibility_inspection', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-visibility-inspection');

        return new Response(sprintf(<<<'HTML'
<h1>Managing Field Visibility Inspection</h1>
<p>Prepares a read-only Managing diagnostic request. Administering does not execute Managing runtime here.</p>
<form method="post" action="%s">
  <input type="hidden" name="_token" value="%s">
  <label>Resource class <input name="resourceClass" placeholder="App\Cataloging\Entity\Catalog\CatalogCategoryEntity" required size="90"></label><br>
  <label>Field name <input name="fieldName" placeholder="title" required></label><br>
  <label>Page <select name="pageName"><option>index</option><option>detail</option><option>new</option><option>edit</option></select></label><br>
  <label>Subject identifier <input name="subjectIdentifier" placeholder="user:42 or role:admin"></label><br>
  <label>Status field candidates <input name="statusCandidates" placeholder="status,state"></label><br>
  <label>Publication flag candidates <input name="publicationFlagCandidates" placeholder="published,enabled"></label><br>
  <label>Publication date candidates <input name="publicationDateCandidates" placeholder="publishedAt"></label><br>
  <label>Reason <input name="reason" placeholder="Optional diagnostic reason"></label><br>
  <button type="submit">Prepare inspection request</button>
</form>
HTML,
            $this->escape($this->generateUrl('administration_managing_field_visibility_inspection_prepare')),
            $this->escape($this->csrfTokenManager->getToken($this->csrfTokenId())->getValue()),
        ));
    }

    #[Route('/admin/managing/field-visibility-inspection/prepare', name: 'administration_managing_field_visibility_inspection_prepare', methods: ['POST'])]
    public function prepare(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId(), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid Managing field visibility inspection token.');
        }

        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-visibility-inspection');

        $currentUser = $this->currentUserContextProvider->current();
        $result = $this->prepareService->prepare(new AdministrationFieldVisibilityInspectionPrepareRequest(
            resourceClass: trim((string) $request->request->get('resourceClass', '')),
            fieldName: trim((string) $request->request->get('fieldName', '')),
            pageName: trim((string) $request->request->get('pageName', 'index')),
            subjectIdentifier: trim((string) $request->request->get('subjectIdentifier', '')) ?: null,
            statusCandidates: $this->fieldsFromRequestValue((string) $request->request->get('statusCandidates', '')),
            publicationFlagCandidates: $this->fieldsFromRequestValue((string) $request->request->get('publicationFlagCandidates', '')),
            publicationDateCandidates: $this->fieldsFromRequestValue((string) $request->request->get('publicationDateCandidates', '')),
            requestedBySubject: $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            reason: trim((string) $request->request->get('reason', '')) ?: null,
        ));

        return new Response(sprintf(
            '<h1>Prepared Managing Field Visibility Inspection</h1><pre>%s</pre><p><a href="%s">Back</a></p>',
            $this->escape(json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)),
            $this->escape($this->generateUrl('administration_managing_field_visibility_inspection')),
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
        return 'administering.managing.field_visibility_inspection.prepare';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
