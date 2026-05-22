<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
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

        $currentContext = $this->currentUserContextProvider->current();
        $subjectIdentifier = trim((string) $request->query->get('subject', ''));
        if ('' === $subjectIdentifier && null !== $currentContext) {
            $subjectIdentifier = $currentContext->subjectIdentifier();
        }

        $scope = trim((string) $request->query->get('scope', 'administering:global'));
        if ('' === $scope) {
            $scope = 'administering:global';
        }

        if ('' === $subjectIdentifier) {
            return new Response(
                '<h1>Rolling Subject Access Report</h1><p>No authenticated subject was detected. Pass <code>?subject=...</code> to inspect an explicit subject.</p>',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $report = $this->reportProvider->reportFor($subjectIdentifier, $scope);
        if ('json' === strtolower((string) $request->query->get('format', ''))) {
            return new JsonResponse($report->toArray());
        }

        $currentSubjectHint = null !== $currentContext ? $currentContext->subjectIdentifier() : 'none';

        return new Response(sprintf(
            '<h1>Rolling Subject Access Report</h1>%s%s%s%s%s%s%s%s',
            $this->renderLookupForm($subjectIdentifier, $scope),
            sprintf(
                '<p><strong>Current Administering subject:</strong> <code>%s</code></p>',
                $this->e($currentSubjectHint),
            ),
            sprintf(
                '<p><strong>Report subject:</strong> <code>%s</code><br><strong>Scope:</strong> <code>%s</code><br><a href="%s">JSON report</a></p>',
                $this->e($report->subjectIdentifier()),
                $this->e($report->scope()),
                $this->e($this->generateUrl('administration_rolling_subject_access_report', [
                    'subject' => $report->subjectIdentifier(),
                    'scope' => $report->scope(),
                    'format' => 'json',
                ])),
            ),
            $this->renderList('Effective roles', $report->effectiveRoles()),
            $this->renderRows('Assigned roles', $report->assignedRoles()),
            $this->renderRows('Direct ACL rules', $report->directRules()),
            $this->renderRows('Role permissions', $report->rolePermissions()),
            $this->renderList('Granted catalog permissions', $report->grantedPermissions()),
            $this->renderList('Denied catalog permissions', $report->deniedPermissions()),
            $this->renderList('Full catalogued permissions', $report->cataloguedPermissions()),
        ));
    }

    private function renderLookupForm(string $subjectIdentifier, string $scope): string
    {
        return sprintf(
            '<form method="get" action="%s"><label>Subject <input name="subject" value="%s" size="48"></label> <label>Scope <input name="scope" value="%s" size="24"></label> <button type="submit">Inspect</button></form>',
            $this->e($this->generateUrl('administration_rolling_subject_access_report')),
            $this->e($subjectIdentifier),
            $this->e($scope),
        );
    }

    /** @param list<string> $items */
    private function renderList(string $title, array $items): string
    {
        if ([] === $items) {
            return sprintf('<h2>%s</h2><p>None.</p>', $this->e($title));
        }

        return sprintf(
            '<h2>%s</h2><ul>%s</ul>',
            $this->e($title),
            implode('', array_map(fn (string $item): string => sprintf('<li><code>%s</code></li>', $this->e($item)), $items)),
        );
    }

    /** @param list<array<string, mixed>> $rows */
    private function renderRows(string $title, array $rows): string
    {
        if ([] === $rows) {
            return sprintf('<h2>%s</h2><p>None.</p>', $this->e($title));
        }

        $keys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $keys[$key] = true;
            }
        }
        $headers = array_keys($keys);

        $head = implode('', array_map(fn (string $key): string => sprintf('<th>%s</th>', $this->e($key)), $headers));
        $body = '';
        foreach ($rows as $row) {
            $body .= '<tr>'.implode('', array_map(
                fn (string $key): string => sprintf('<td>%s</td>', $this->e($this->stringify($row[$key] ?? ''))),
                $headers,
            )).'</tr>';
        }

        return sprintf('<h2>%s</h2><table><thead><tr>%s</tr></thead><tbody>%s</tbody></table>', $this->e($title), $head, $body);
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

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
