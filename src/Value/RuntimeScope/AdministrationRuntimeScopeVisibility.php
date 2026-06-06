<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeVisibility
{
    /**
     * @param list<string> $componentKeys
     */
    public function __construct(
        public string $raw,
        public bool $allComponentsVisible,
        public array $componentKeys,
    ) {
    }

    public static function fromRaw(?string $raw): self
    {
        $raw = null === $raw ? '' : trim($raw);
        if ('' === $raw) {
            return new self('', false, ['administering']);
        }

        if ('*' === $raw) {
            return new self('*', true, []);
        }

        $tokens = preg_split('/[|,\s]+/', $raw) ?: [];
        $componentKeys = [];
        foreach ($tokens as $token) {
            $componentKey = self::normalizeComponent($token);
            if ('' !== $componentKey) {
                $componentKeys[] = $componentKey;
            }
        }

        $componentKeys = array_values(array_unique($componentKeys));
        if ([] === $componentKeys) {
            $componentKeys = ['administering'];
        }

        return new self($raw, false, $componentKeys);
    }

    public function includes(string $componentKey): bool
    {
        if ($this->allComponentsVisible) {
            return true;
        }

        return in_array(self::normalizeComponent($componentKey), $this->componentKeys, true);
    }

    public function label(): string
    {
        if ($this->allComponentsVisible) {
            return '*';
        }

        return implode(',', $this->componentKeys);
    }

    public static function normalizeComponent(string $component): string
    {
        $component = trim($component);
        $component = preg_replace('/Bundle$/', '', $component) ?? $component;
        $component = preg_replace('/[^A-Za-z0-9]+/', '-', $component) ?? $component;
        $component = strtolower(trim((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $component), '-'));

        return str_replace('--', '-', $component);
    }
}
