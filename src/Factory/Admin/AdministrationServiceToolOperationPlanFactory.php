<?php

declare(strict_types=1);

namespace App\Administering\Factory\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolOperationPlanFactoryInterface;
use App\Administering\Value\Operation\AdministrationOperationPlan;

/**
 * Creates metadata-only operation plans for tool form submissions.
 *
 * The tool service file remains the source of identity. The submitted form data
 * is copied only as scalar/array safe context and is redacted again by the
 * operation plan before persistence.
 */
final class AdministrationServiceToolOperationPlanFactory implements AdministrationServiceToolOperationPlanFactoryInterface
{
    public function createForSubmittedTool(AdministrationServiceToolRecord $record, mixed $formData): AdministrationOperationPlan
    {
        return new AdministrationOperationPlan(
            $record->getOperationType(),
            'service_tool:'.$record->getToolKey(),
            [
                'toolKey' => $record->getToolKey(),
                'sectionKey' => $record->getSectionKey(),
                'toolSlug' => $record->getToolSlug(),
                'serviceClass' => $record->getServiceClass(),
                'serviceFile' => $record->getServiceFile(),
                'formTypeClass' => $record->getFormTypeClass(),
                'formDataClass' => $record->getFormDataClass(),
                'executable' => $record->isExecutable(),
                'sourceOwnership' => $record->getSourceOwnership(),
                'ownerComponentKey' => $record->getOwnerComponentKey(),
                'ownerComponentToken' => $record->getOwnerComponentToken(),
                'ownerProviderClass' => $record->getOwnerProviderClass(),
                'ownerServiceClass' => $record->getOwnerServiceClass(),
                'ownerSourceLabel' => $record->getOwnerSourceLabel(),
                'formData' => $this->safeFormData($formData),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function safeFormData(mixed $formData): array
    {
        if (null === $formData) {
            return [];
        }

        if (is_array($formData)) {
            return $this->safeArray($formData);
        }

        if (!is_object($formData)) {
            return ['value' => $this->safeValue($formData)];
        }

        $reflection = new \ReflectionObject($formData);
        $safe = [
            '_data_class' => $formData::class,
        ];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $safe[$property->getName()] = $property->isInitialized($formData)
                ? $this->safeValue($property->getValue($formData))
                : null;
        }

        return $safe;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function safeArray(array $data): array
    {
        $safe = [];
        foreach ($data as $key => $value) {
            $safe[(string) $key] = $this->safeValue($value);
        }

        return $safe;
    }

    private function safeValue(mixed $value): mixed
    {
        if (null === $value || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_array($value)) {
            return $this->safeArray($value);
        }

        if (is_object($value)) {
            return ['_object' => $value::class];
        }

        return sprintf('[%s]', get_debug_type($value));
    }
}
