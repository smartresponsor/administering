<?php

declare(strict_types=1);

namespace App\Administering\Auditor\Admin;

use App\Administering\ServiceInterface\Admin\AdministrationServiceToolConventionAuditorInterface;
use App\Administering\Value\Admin\AdministrationServiceToolConventionViolation;

/**
 * Audits src/Service/<Direction> for files that do not satisfy the tool convention.
 *
 * The scanner used by EasyAdmin skips invalid files. This auditor makes those
 * skipped files visible so helper/provider/definition classes can be relocated
 * out of the tool surface without guessing.
 */
final readonly class AdministrationServiceToolConventionAuditor implements AdministrationServiceToolConventionAuditorInterface
{
    /** @return list<AdministrationServiceToolConventionViolation> */
    public function violations(?string $section = null): array
    {
        $sections = null === $section ? $this->sections() : [$section];
        $violations = [];

        foreach ($sections as $currentSection) {
            $directory = $this->serviceRoot().'/'.$currentSection;
            if (!is_dir($directory)) {
                $violations[] = new AdministrationServiceToolConventionViolation(
                    section: $currentSection,
                    serviceFile: 'src/Service/'.$currentSection,
                    shortName: '',
                    declaredNamespace: null,
                    expectedNamespace: 'App\\Administering\\Service\\'.$currentSection,
                    reason: 'section_directory_missing',
                    suggestedAction: 'create_or_remove_section_reference',
                    suggestedPath: null,
                );

                continue;
            }

            foreach ($this->phpFiles($directory) as $file) {
                $path = $directory.'/'.$file;
                $shortName = basename($file, '.php');
                $expectedNamespace = 'App\\Administering\\Service\\'.$currentSection;
                $declaredNamespace = $this->declaredNamespace($path);
                $reason = $this->violationReason($currentSection, $shortName, $declaredNamespace, $expectedNamespace);

                if (null === $reason) {
                    continue;
                }

                $violations[] = new AdministrationServiceToolConventionViolation(
                    section: $currentSection,
                    serviceFile: 'src/Service/'.$currentSection.'/'.$file,
                    shortName: $shortName,
                    declaredNamespace: $declaredNamespace,
                    expectedNamespace: $expectedNamespace,
                    reason: $reason,
                    suggestedAction: $this->suggestedAction($reason, $shortName),
                    suggestedPath: $this->suggestedPath($currentSection, $shortName, $reason),
                );
            }
        }

        return $violations;
    }

    /** @return list<string> */
    private function sections(): array
    {
        $root = $this->serviceRoot();
        if (!is_dir($root)) {
            return [];
        }

        $sections = array_values(array_filter(
            scandir($root) ?: [],
            static fn (string $entry): bool => !str_starts_with($entry, '.') && is_dir($root.'/'.$entry),
        ));
        sort($sections);

        return $sections;
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = array_values(array_filter(
            scandir($directory) ?: [],
            static fn (string $file): bool => str_ends_with($file, '.php'),
        ));
        sort($files);

        return $files;
    }

    private function violationReason(string $section, string $shortName, ?string $declaredNamespace, string $expectedNamespace): ?string
    {
        if ($declaredNamespace !== $expectedNamespace) {
            return 'namespace_mismatch';
        }

        $prefix = 'Administration'.$section;
        if (!str_starts_with($shortName, 'Administration')) {
            return 'missing_administration_prefix';
        }

        if (!str_starts_with($shortName, $prefix)) {
            return 'second_token_must_match_section';
        }

        if (!str_ends_with($shortName, 'Service')) {
            return 'missing_service_suffix';
        }

        $toolSlug = substr($shortName, strlen($prefix), -strlen('Service'));
        if ('' === $toolSlug) {
            return 'empty_tool_slug';
        }

        if (!ctype_upper($toolSlug[0])) {
            return 'tool_slug_must_start_with_uppercase_token';
        }

        return null;
    }

    private function suggestedAction(string $reason, string $shortName): string
    {
        if ('missing_service_suffix' === $reason || 'empty_tool_slug' === $reason || 'second_token_must_match_section' === $reason || 'missing_administration_prefix' === $reason) {
            if ($this->looksLikeSupportClass($shortName)) {
                return 'move_support_class_out_of_service_surface';
            }

            return 'rename_or_confirm_as_non_tool';
        }

        if ('namespace_mismatch' === $reason) {
            return 'fix_namespace_or_move_file';
        }

        if ('tool_slug_must_start_with_uppercase_token' === $reason) {
            return 'rename_tool_slug_to_uppercase_token';
        }

        return 'review_manually';
    }

    private function suggestedPath(string $section, string $shortName, string $reason): ?string
    {
        if ('namespace_mismatch' === $reason) {
            return 'src/Service/'.$section.'/'.$shortName.'.php';
        }

        if (!$this->looksLikeSupportClass($shortName)) {
            return null;
        }

        $layer = $this->suggestedLayer($shortName);

        return 'src/'.$layer.'/'.$section.'/'.$shortName.'.php';
    }

    private function looksLikeSupportClass(string $shortName): bool
    {
        foreach (['Provider', 'Catalog', 'Recorder', 'Writer', 'Queue', 'Scanner', 'Runner', 'Factory', 'Auditor', 'Operator'] as $suffix) {
            if (str_ends_with($shortName, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function suggestedLayer(string $shortName): string
    {
        foreach ([
            'Provider' => 'Provider',
            'Catalog' => 'Catalog',
            'Recorder' => 'Recorder',
            'Writer' => 'Writer',
            'Queue' => 'Queue',
            'Scanner' => 'Scanner',
            'Runner' => 'Runner',
            'Factory' => 'Factory',
            'Auditor' => 'Auditor',
            'Operator' => 'Operator',
        ] as $suffix => $layer) {
            if (str_ends_with($shortName, $suffix)) {
                return $layer;
            }
        }

        return 'Support';
    }

    private function declaredNamespace(string $path): ?string
    {
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            return null;
        }

        if (1 !== preg_match('/^namespace\s+([^;]+);/m', $contents, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function serviceRoot(): string
    {
        return dirname(__DIR__, 2).'/Service';
    }
}
