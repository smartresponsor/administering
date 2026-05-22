<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationFieldViewProfileApplyServiceInterface;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileApplyRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Apply-preparation surface for reviewed Managing field view profile payloads.
 */
final class AdministrationManagingFieldViewProfileApplyController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldViewProfileApplyServiceInterface $applyService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/managing/field-view-profiles/apply', name: 'administration_managing_field_view_profile_apply', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile-apply');

        return new Response(sprintf(<<<'HTML'
<h1>Apply Managing Field View Profile Review</h1>
<p>This surface validates a reviewed profile payload and prepares a Managing apply-handler payload. It does not write Managing storage directly.</p>
<form method="post" action="%s">
  <input type="hidden" name="_token" value="%s">
  <label>Normalized profile payload JSON<br><textarea name="normalizedProfilePayload" rows="12" cols="110" required>{
  "subjects": {
    "user:42": {
      "defaults": {
        "index": {
          "hidden": ["createdAt"]
        }
      }
    }
  }
}</textarea></label><br>
  <label>Review context JSON<br><textarea name="reviewContext" rows="10" cols="110" required>{
  "surface": "managing_field_view_profile_review",
  "subject_key": "user:42",
  "profile_permission": "managing.field.profile.user_update",
  "mode": "replace",
  "page_name": "index"
}</textarea></label><br>
  <label>Reason <input name="reason" placeholder="Optional safe reason"></label><br>
  <button type="submit">Prepare Managing apply payload</button>
</form>
HTML,
            $this->escape($this->generateUrl('administration_managing_field_view_profile_apply_prepare')),
            $this->escape($this->csrfTokenManager->getToken($this->csrfTokenId())->getValue()),
        ));
    }

    #[Route('/admin/managing/field-view-profiles/apply/prepare', name: 'administration_managing_field_view_profile_apply_prepare', methods: ['POST'])]
    public function prepare(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenId(), (string) $request->request->get('_token', '')))) {
            throw $this->createAccessDeniedException('Invalid Managing field view profile apply token.');
        }

        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile-apply');

        $currentUser = $this->currentUserContextProvider->current();
        $result = $this->applyService->prepare(new AdministrationFieldViewProfileApplyRequest(
            normalizedProfilePayload: $this->jsonObjectFromRequest((string) $request->request->get('normalizedProfilePayload', '{}'), 'normalizedProfilePayload'),
            reviewContext: $this->jsonObjectFromRequest((string) $request->request->get('reviewContext', '{}'), 'reviewContext'),
            requestedBySubject: $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            reason: trim((string) $request->request->get('reason', '')) ?: null,
        ));

        return new Response(sprintf(
            '<h1>Managing Field View Profile Apply Preparation</h1><pre>%s</pre><p><a href="%s">Back</a></p>',
            $this->escape(json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)),
            $this->escape($this->generateUrl('administration_managing_field_view_profile_apply')),
        ));
    }

    /** @return array<string, mixed> */
    private function jsonObjectFromRequest(string $json, string $fieldName): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(sprintf('Invalid JSON in %s: %s', $fieldName, $exception->getMessage()), previous: $exception);
        }

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException(sprintf('%s must decode to a JSON object.', $fieldName));
        }

        return $decoded;
    }

    private function csrfTokenId(): string
    {
        return 'administering.managing.field_view_profile.apply';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
