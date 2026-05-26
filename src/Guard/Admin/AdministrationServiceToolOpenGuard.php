<?php

declare(strict_types=1);

namespace App\Administering\Guard\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolOpenGuardInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validates that an indexed service-tool record can cross the UI/execution boundary.
 *
 * The guard keeps Administering thin: owner-provided tools may be rendered and
 * dispatched through the Administering shell, but only when the owner provenance,
 * form class, data class, and service class metadata are internally consistent.
 */
final class AdministrationServiceToolOpenGuard implements AdministrationServiceToolOpenGuardInterface
{
    private const SOURCE_INTERNAL = 'administering_internal';
    private const SOURCE_OWNER = 'owner_component';

    public function assertRecordCanOpen(AdministrationServiceToolRecord $record): void
    {
        $toolKey = $record->getToolKey();

        if (!$record->isEnabled()) {
            throw new AccessDeniedHttpException(sprintf('Administering service tool "%s" is disabled.', $toolKey));
        }

        if (!$record->isVisible()) {
            throw new NotFoundHttpException(sprintf('Administering service tool "%s" is not visible in the administration surface.', $toolKey));
        }

        if (!$record->hasFormType()) {
            throw new NotFoundHttpException(sprintf('Administering service tool "%s" has no mapped FormType and cannot be opened as an operation form.', $toolKey));
        }

        $formTypeClass = $record->getFormTypeClass();
        if (!is_string($formTypeClass) || !class_exists($formTypeClass)) {
            throw new \LogicException(sprintf('Mapped FormType for Administering service tool "%s" does not exist.', $toolKey));
        }

        $formDataClass = $record->getFormDataClass();
        if (is_string($formDataClass) && '' !== trim($formDataClass) && !class_exists($formDataClass)) {
            throw new \LogicException(sprintf('Mapped form data class for Administering service tool "%s" does not exist.', $toolKey));
        }

        $this->assertRecordSourceConsistency($record);
    }

    public function assertInvocationCanExecute(AdministrationServiceToolInvocation $invocation): void
    {
        if (!$invocation->executable) {
            throw new \LogicException(sprintf('Service tool "%s" was submitted but is not marked executable in the materialized tool index.', $invocation->toolKey));
        }

        if (null === $invocation->formTypeClass || '' === trim($invocation->formTypeClass)) {
            throw new \LogicException(sprintf('Service tool "%s" invocation has no mapped FormType.', $invocation->toolKey));
        }

        $this->assertKnownSourceOwnership($invocation->toolKey, $invocation->sourceOwnership);

        if (self::SOURCE_OWNER === $invocation->sourceOwnership) {
            $this->assertNonEmpty($invocation->toolKey, 'ownerComponentKey', $invocation->ownerComponentKey);
            $this->assertNonEmpty($invocation->toolKey, 'ownerComponentToken', $invocation->ownerComponentToken);
            $this->assertNonEmpty($invocation->toolKey, 'ownerProviderClass', $invocation->ownerProviderClass);
            $this->assertNonEmpty($invocation->toolKey, 'ownerServiceClass', $invocation->ownerServiceClass);

            if ($invocation->ownerServiceClass !== $invocation->serviceClass) {
                throw new \LogicException(sprintf('Owner service class mismatch for tool "%s". Materialized serviceClass and ownerServiceClass must match.', $invocation->toolKey));
            }
        }
    }

    private function assertRecordSourceConsistency(AdministrationServiceToolRecord $record): void
    {
        $toolKey = $record->getToolKey();
        $sourceOwnership = $record->getSourceOwnership();
        $this->assertKnownSourceOwnership($toolKey, $sourceOwnership);

        if (self::SOURCE_INTERNAL === $sourceOwnership) {
            return;
        }

        $this->assertNonEmpty($toolKey, 'ownerComponentKey', $record->getOwnerComponentKey());
        $this->assertNonEmpty($toolKey, 'ownerComponentToken', $record->getOwnerComponentToken());
        $this->assertNonEmpty($toolKey, 'ownerProviderClass', $record->getOwnerProviderClass());
        $this->assertNonEmpty($toolKey, 'ownerServiceClass', $record->getOwnerServiceClass());

        if ($record->getOwnerServiceClass() !== $record->getServiceClass()) {
            throw new \LogicException(sprintf('Owner service class mismatch for tool "%s". Materialized serviceClass and ownerServiceClass must match.', $toolKey));
        }

        $formTypeClass = $record->getFormTypeClass();
        if (is_string($formTypeClass) && str_starts_with($formTypeClass, 'App\\Administering\\Form\\')) {
            throw new \LogicException(sprintf('Owner-provided service tool "%s" must use an owner-side FormType, not an Administering-owned FormType.', $toolKey));
        }
    }

    private function assertKnownSourceOwnership(string $toolKey, string $sourceOwnership): void
    {
        if (!in_array($sourceOwnership, [self::SOURCE_INTERNAL, self::SOURCE_OWNER], true)) {
            throw new \LogicException(sprintf('Service tool "%s" has unsupported source ownership "%s".', $toolKey, $sourceOwnership));
        }
    }

    private function assertNonEmpty(string $toolKey, string $field, ?string $value): void
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new \LogicException(sprintf('Owner-provided service tool "%s" is missing required source metadata "%s".', $toolKey, $field));
        }
    }
}
