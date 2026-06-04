<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigValue;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AdministrationConfigStateService
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @param array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}> $values
     */
    public function replaceToolValues(string $applicationCode, string $toolCode, array $values): void
    {
        $manager = $this->entityManager();
        $manager->createQueryBuilder()
            ->delete(AdministrationConfigValue::class, 'value')
            ->andWhere('value.applicationCode = :applicationCode')
            ->andWhere('value.toolCode = :toolCode')
            ->setParameter('applicationCode', $applicationCode)
            ->setParameter('toolCode', $toolCode)
            ->getQuery()
            ->execute();

        foreach ($values as $fieldKey => $value) {
            $record = new AdministrationConfigValue(
                $applicationCode,
                $toolCode,
                $fieldKey,
                $value['fieldType'],
                $value['secret'],
            );
            $record->markCurrent($value['current'], $value['pending'], $value['masked'], $value['status']);
            $manager->persist($record);
        }

        $manager->flush();
    }

    /** @return list<AdministrationConfigValue> */
    public function valuesForTool(string $applicationCode, string $toolCode): array
    {
        $manager = $this->entityManager();

        return $manager->getRepository(AdministrationConfigValue::class)->findBy([
            'applicationCode' => $applicationCode,
            'toolCode' => $toolCode,
        ], ['fieldKey' => 'ASC']);
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

    private function entityManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationConfigValue::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine entity manager is configured for Administering config state records.');
        }

        return $manager;
    }

    private function camelize(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace_callback('/_([a-z])/', static fn (array $match): string => strtoupper($match[1]), $value) ?? $value;

        return lcfirst($value);
    }
}
