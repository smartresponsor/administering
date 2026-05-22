<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Documents the config/storage shape that Managing executes for personal view profiles.
 */
final readonly class AdministrationFieldViewProfileRuleShape
{
    /** @param list<string> $ruleKeys */
    public function __construct(
        public string $section,
        public string $scopePath,
        public array $ruleKeys,
        public string $effect,
        public string $notes,
    ) {
    }
}
