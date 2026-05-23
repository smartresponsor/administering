<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Rolling\AdministrationRollingSubjectAccessReportLookupFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\Form\Rolling\AdministrationRollingSubjectAccessReportLookupData;
use App\Rolling\ServiceInterface\Administration\RollingAdministrationSubjectAccessReportProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only operator surface for diagnosing the current Rolling access model used by Administering.
 */
final class AdministrationRollingSubjectAccessReportController extends AbstractController
{
    public function __construct(
        private readonly RollingAdministrationSubjectAccessReportProviderInterface $reportProvider,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/admin/rolling/subject-access', name: 'administration_rolling_subject_access_report', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.subject_access_report.view', 'administering:rolling');

        $data = new AdministrationRollingSubjectAccessReportLookupData();
        $data->subjectIdentifier = trim((string) $request->query->get('subject', ''));
        $data->scope = trim((string) $request->query->get('scope', 'administering:global')) ?: 'administering:global';
        $data->format = strtolower(trim((string) $request->query->get('format', 'html'))) ?: 'html';

        $form = $this->createForm(AdministrationRollingSubjectAccessReportLookupFormType::class, $data, [
            'action' => $this->generateUrl('administration_rolling_subject_access_report'),
        ]);
        $form->handleRequest($request);

        $lookup = $form->isSubmitted() ? $form->getData() : $data;

        $currentContext = $this->currentUserContextProvider->current();
        $subjectIdentifier = trim($lookup->subjectIdentifier);
        if ('' === $subjectIdentifier && null !== $currentContext) {
            $subjectIdentifier = $currentContext->subjectIdentifier();
        }

        $scope = trim($lookup->scope);
        if ('' === $scope) {
            $scope = 'administering:global';
        }

        if ('' === $subjectIdentifier) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Rolling Subject Access Report',
                'lead' => 'Read-only inspection of the active Rolling access model used by Administering.',
                'form' => $form->createView(),
                'result_title' => null,
                'result_json' => null,
                'result_html' => null,
                'error_message' => 'No authenticated subject was detected. Provide a subject to inspect or log in first.',
                'action_links' => [],
                'back_url' => null,
            ]);
        }

        $report = $this->reportProvider->reportFor($subjectIdentifier, $scope);
        if ('json' === $lookup->format) {
            return new JsonResponse($report->toArray());
        }

        $currentSubjectHint = null !== $currentContext ? $currentContext->subjectIdentifier() : 'none';

        return $this->render('@Administering/administering/rolling_subject_access_report.html.twig', [
            'form' => $form->createView(),
            'report' => $report,
            'current_subject_hint' => $currentSubjectHint,
            'json_url' => $this->generateUrl('administration_rolling_subject_access_report', [
                'subject' => $report->subjectIdentifier(),
                'scope' => $report->scope(),
                'format' => 'json',
            ]),
            'table_sections' => [
                [
                    'title' => 'Assigned roles',
                    'headers' => $this->tableHeaders($report->assignedRoles()),
                    'rows' => $this->normalizeRows($report->assignedRoles()),
                ],
                [
                    'title' => 'Direct ACL rules',
                    'headers' => $this->tableHeaders($report->directRules()),
                    'rows' => $this->normalizeRows($report->directRules()),
                ],
                [
                    'title' => 'Role permissions',
                    'headers' => $this->tableHeaders($report->rolePermissions()),
                    'rows' => $this->normalizeRows($report->rolePermissions()),
                ],
            ],
        ]);
    }

    /** @param list<array<string, mixed>> $rows */
    private function tableHeaders(array $rows): array
    {
        if ([] === $rows) {
            return [];
        }

        $keys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }

    /** @param list<array<string, mixed>> $rows */
    private function normalizeRows(array $rows): array
    {
        return array_map(function (array $row): array {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[$key] = $this->stringify($value);
            }

            return $normalized;
        }, $rows);
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (null === $value) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
