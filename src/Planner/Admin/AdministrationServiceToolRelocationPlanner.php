<?php

declare(strict_types=1);

namespace App\Administering\Planner\Admin;

use App\Administering\PlannerInterface\Admin\AdministrationServiceToolRelocationPlannerInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolConventionAuditorInterface;
use App\Administering\Value\Admin\AdministrationServiceToolConventionViolation;
use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanEntry;

/**
 * Builds a non-destructive relocation plan for helper classes found in the tool surface.
 *
 * src/Service/<Direction> is reserved for openable Administering tools. This planner
 * turns audit violations into owner-reviewable move recommendations without touching
 * the filesystem or rewriting namespaces.
 */
final readonly class AdministrationServiceToolRelocationPlanner implements AdministrationServiceToolRelocationPlannerInterface
{
    public function __construct(private AdministrationServiceToolConventionAuditorInterface $auditor)
    {
    }

    public function plan(?string $section = null): array
    {
        return array_map(
            fn (AdministrationServiceToolConventionViolation $violation): AdministrationServiceToolRelocationPlanEntry => $this->entry($violation),
            $this->auditor->violations($section),
        );
    }

    private function entry(AdministrationServiceToolConventionViolation $violation): AdministrationServiceToolRelocationPlanEntry
    {
        $targetNamespace = null === $violation->suggestedPath ? null : $this->namespaceForPath($violation->suggestedPath);
        $automaticMoveAllowed = false;

        return new AdministrationServiceToolRelocationPlanEntry(
            section: $violation->section,
            sourcePath: $violation->serviceFile,
            shortName: $violation->shortName,
            reason: $violation->reason,
            suggestedAction: $violation->suggestedAction,
            targetPath: $violation->suggestedPath,
            targetNamespace: $targetNamespace,
            automaticMoveAllowed: $automaticMoveAllowed,
            reviewNote: $this->reviewNote($violation, $targetNamespace),
        );
    }

    private function namespaceForPath(string $path): ?string
    {
        if (!str_starts_with($path, 'src/') || !str_ends_with($path, '.php')) {
            return null;
        }

        $withoutPrefix = substr($path, strlen('src/'));
        $parts = explode('/', $withoutPrefix);
        array_pop($parts);

        if ([] === $parts) {
            return 'App\\Administering';
        }

        return 'App\\Administering\\'.implode('\\', $parts);
    }

    private function reviewNote(AdministrationServiceToolConventionViolation $violation, ?string $targetNamespace): string
    {
        if (null === $violation->suggestedPath) {
            return 'Review manually: the auditor cannot infer a safe target layer for this class.';
        }

        if ('fix_namespace_or_move_file' === $violation->suggestedAction) {
            return 'Review namespace/path mismatch before moving; updating the namespace may be sufficient.';
        }

        if (str_contains($violation->suggestedAction, 'move')) {
            return sprintf(
                'Candidate move only: update namespace to %s and review service aliases/imports before applying.',
                $targetNamespace ?? 'the inferred target namespace',
            );
        }

        return 'Review whether this class is a true tool. Rename it only if it should be openable from EasyAdmin.';
    }
}
