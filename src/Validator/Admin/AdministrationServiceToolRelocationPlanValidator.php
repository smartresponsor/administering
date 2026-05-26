<?php

declare(strict_types=1);

namespace App\Administering\Validator\Admin;

use App\Administering\ValidatorInterface\Admin\AdministrationServiceToolRelocationPlanValidatorInterface;
use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanEntry;
use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanIssue;

/**
 * Validates a relocation plan before an owner decides whether to create a real patch.
 *
 * The validator is intentionally conservative: it only reports conflicts and missing
 * metadata. It never moves files, deletes files, rewrites namespaces, or rewires services.
 */
final readonly class AdministrationServiceToolRelocationPlanValidator implements AdministrationServiceToolRelocationPlanValidatorInterface
{
    public function __construct(private string $projectDir)
    {
    }

    public function validate(array $entries): array
    {
        $issues = [];
        $targetPathCounts = [];

        foreach ($entries as $entry) {
            if (null !== $entry->targetPath) {
                $targetPathCounts[$entry->targetPath] = ($targetPathCounts[$entry->targetPath] ?? 0) + 1;
            }
        }

        foreach ($entries as $entry) {
            array_push($issues, ...$this->entryIssues($entry, $targetPathCounts));
        }

        return $issues;
    }

    /**
     * @param array<string, int> $targetPathCounts
     *
     * @return list<AdministrationServiceToolRelocationPlanIssue>
     */
    private function entryIssues(AdministrationServiceToolRelocationPlanEntry $entry, array $targetPathCounts): array
    {
        $issues = [];

        if (!$this->repositoryFileExists($entry->sourcePath)) {
            $issues[] = $this->issue(
                severity: 'error',
                code: 'source_path_missing',
                message: 'Source file does not exist in the repository snapshot.',
                entry: $entry,
            );
        }

        if (null === $entry->targetPath) {
            $issues[] = $this->issue(
                severity: 'warning',
                code: 'target_path_missing',
                message: 'Relocation planner could not infer a target path; manual review is required.',
                entry: $entry,
            );

            return $issues;
        }

        if ($entry->sourcePath === $entry->targetPath) {
            $issues[] = $this->issue(
                severity: 'error',
                code: 'target_same_as_source',
                message: 'Suggested target path is identical to the source path.',
                entry: $entry,
            );
        }

        if (($targetPathCounts[$entry->targetPath] ?? 0) > 1) {
            $issues[] = $this->issue(
                severity: 'error',
                code: 'duplicate_target_path',
                message: 'Multiple relocation entries point to the same target path.',
                entry: $entry,
            );
        }

        if ($this->repositoryFileExists($entry->targetPath)) {
            $issues[] = $this->issue(
                severity: 'error',
                code: 'target_path_already_exists',
                message: 'Suggested target file already exists; applying this move would overwrite code.',
                entry: $entry,
            );
        }

        if (null === $entry->targetNamespace) {
            $issues[] = $this->issue(
                severity: 'warning',
                code: 'target_namespace_missing',
                message: 'Target namespace could not be inferred from the target path.',
                entry: $entry,
            );
        }

        if ($entry->automaticMoveAllowed) {
            $issues[] = $this->issue(
                severity: 'error',
                code: 'automatic_move_not_allowed',
                message: 'Relocation entries for this tool surface must remain manual-review only.',
                entry: $entry,
            );
        }

        return $issues;
    }

    private function repositoryFileExists(string $repositoryRelativePath): bool
    {
        return is_file($this->projectDir.'/'.ltrim($repositoryRelativePath, '/'));
    }

    private function issue(
        string $severity,
        string $code,
        string $message,
        AdministrationServiceToolRelocationPlanEntry $entry,
    ): AdministrationServiceToolRelocationPlanIssue {
        return new AdministrationServiceToolRelocationPlanIssue(
            severity: $severity,
            code: $code,
            message: $message,
            sourcePath: $entry->sourcePath,
            targetPath: $entry->targetPath,
            section: $entry->section,
        );
    }
}
