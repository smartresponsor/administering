<?php

declare(strict_types=1);

namespace App\Administering\Support\Form;

final readonly class AdministrationFormInputParser
{
    /** @return list<string> */
    public static function parseDelimitedList(string $value): array
    {
        $items = preg_split('/[\s,;]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if (false === $items) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            $item = trim($item);
            if ('' !== $item) {
                $normalized[$item] = $item;
            }
        }

        return array_values($normalized);
    }

    /** @return array<string, mixed> */
    public static function parseJsonObject(string $json, string $fieldName): array
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
}
