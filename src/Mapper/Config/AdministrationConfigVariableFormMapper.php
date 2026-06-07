<?php

declare(strict_types=1);

namespace App\Administering\Mapper\Config;

use App\Administering\Value\Config\ConfigVariable;

final readonly class AdministrationConfigVariableFormMapper
{
    public static function fieldName(ConfigVariable $variable): string
    {
        $suffix = strtolower((string) preg_replace('/[^A-Za-z0-9_]+/', '_', $variable->key));
        $suffix = trim($suffix, '_');

        return 'v_'.substr(hash('sha256', $variable->storage.'|'.$variable->key), 0, 12).('' !== $suffix ? '_'.$suffix : '');
    }

    /**
     * @param iterable<ConfigVariable> $variables
     * @param array<string, mixed>     $variableData keyed by ConfigVariable::key
     *
     * @return array<string, mixed> keyed by generated form field names
     */
    public function toFormData(iterable $variables, array $variableData): array
    {
        $formData = [];
        foreach ($variables as $variable) {
            $formData[self::fieldName($variable)] = $variableData[$variable->key] ?? null;
        }

        return $formData;
    }

    /**
     * @param iterable<ConfigVariable> $variables
     * @param array<string, mixed>     $formData  keyed by generated form field names
     *
     * @return array<string, mixed> keyed by ConfigVariable::key
     */
    public function toVariableData(iterable $variables, array $formData): array
    {
        $variableData = [];
        foreach ($variables as $variable) {
            $fieldName = self::fieldName($variable);
            if (array_key_exists($fieldName, $formData)) {
                $variableData[$variable->key] = $formData[$fieldName];
            }
        }

        return $variableData;
    }
}
