<?php

declare(strict_types=1);

namespace App\Administering\Service\Admin;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeSourceIndex;

/** Builds an operator-facing index of Symfony console commands declared by this component. */
final readonly class AdministrationCommandIndexService
{
    public function __construct(private string $projectDir)
    {
    }

    public function index(): AdministrationRuntimeSourceIndex
    {
        $commandDir = rtrim($this->projectDir, '/\\').'/src/Command';
        $rows = [];
        $errors = [];

        if (!is_dir($commandDir)) {
            $errors[] = sprintf('Command directory is missing: %s', $commandDir);
        } else {
            foreach ($this->commandFiles($commandDir) as $file) {
                $rows[] = $this->rowForFile($file, $commandDir);
            }
        }

        usort($rows, static fn (array $left, array $right): int => strcmp((string) $left['name'], (string) $right['name']));

        return new AdministrationRuntimeSourceIndex(
            title: 'Commands',
            description: 'Symfony console commands discovered from src/Command/*.php.',
            summaryItems: [
                ['label' => 'Source', 'value' => 'src/Command/*.php'],
                ['label' => 'Commands', 'value' => (string) count($rows)],
            ],
            sections: [[
                'title' => 'Discovered commands',
                'kind' => 'command_index',
                'rows' => $rows,
            ]],
            errors: $errors,
        );
    }

    /** @return list<string> */
    private function commandFiles(string $commandDir): array
    {
        $files = glob(rtrim($commandDir, '/\\').'/*.php');
        if (false === $files) {
            return [];
        }

        return array_values(array_filter($files, 'is_file'));
    }

    /** @return array<string, string> */
    private function rowForFile(string $file, string $commandDir): array
    {
        $source = (string) file_get_contents($file);
        $shortName = basename($file, '.php');

        return [
            'name' => $this->extractAttributeValue($source, 'name') ?: $this->fallbackCommandName($shortName),
            'description' => $this->extractAttributeValue($source, 'description'),
            'class' => $shortName,
            'file' => 'src/Command/'.ltrim(str_replace('\\', '/', substr($file, strlen(rtrim($commandDir, '/\\')))), '/'),
        ];
    }

    private function extractAttributeValue(string $source, string $key): string
    {
        if (1 === preg_match('/'.$key.'\s*:\s*\'([^\']*)\'/s', $source, $match)) {
            return trim($match[1]);
        }

        if (1 === preg_match('/'.$key.'\s*:\s*"([^"]*)"/s', $source, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    private function fallbackCommandName(string $shortName): string
    {
        $stem = preg_replace('/Command$/', '', $shortName) ?? $shortName;
        $stem = preg_replace('/^Administration/', '', $stem) ?? $stem;
        $tokens = preg_split('/(?=[A-Z])/', $stem, -1, PREG_SPLIT_NO_EMPTY) ?: [$stem];

        return 'administering:'.strtolower(implode('-', $tokens));
    }
}
