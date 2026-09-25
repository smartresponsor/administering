<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigValueEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;

final readonly class AdministrationConfigStateService
{
    public function __construct(private AdministrationPersistenceRepository $persistenceRepository)
    {
    }

    /**
     * @param array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}> $values
     */
    public function replaceToolValues(string $applicationCode, string $toolCode, array $values): void
    {
        $this->persistenceRepository->deleteBy(AdministrationConfigValueEntity::class, [
            'applicationCode' => $applicationCode,
            'toolCode' => $toolCode,
        ]);

        $records = [];

        foreach ($values as $fieldKey => $value) {
            $record = new AdministrationConfigValueEntity(
                $applicationCode,
                $toolCode,
                $fieldKey,
                $value['fieldType'],
                $value['secret'],
            );
            $record->markCurrent($value['current'], $value['pending'], $value['masked'], $value['status']);
            $records[] = $record;
        }

        $this->persistenceRepository->persistAll($records, AdministrationConfigValueEntity::class);
    }

    /** @return list<AdministrationConfigValueEntity> */
    public function valuesForTool(string $applicationCode, string $toolCode): array
    {
        /** @var list<AdministrationConfigValueEntity> $values */
        $values = $this->persistenceRepository->findBy(
            AdministrationConfigValueEntity::class,
            [
                'applicationCode' => $applicationCode,
                'toolCode' => $toolCode,
            ],
            ['fieldKey' => 'ASC'],
        );

        return $values;
    }

    public function hydratePendingValues(string $applicationCode, string $toolCode, object $data): object
    {
        foreach ($this->valuesForTool($applicationCode, $toolCode) as $value) {
            $pendingValue = $value->getPendingValue();
            if (null === $pendingValue) {
                continue;
            }

            $property = $this->camelize($value->getFieldKey());
            if (property_exists($data, $property)) {
                $data->{$property} = $pendingValue;
            }
        }

        return $data;
    }

    private function camelize(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace_callback('/_([a-z])/', static fn (array $match): string => strtoupper($match[1]), $value) ?? $value;

        return lcfirst($value);
    }
}
