<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\RuntimeScope;

use App\Administering\Form\RuntimeScope\AdministrationRuntimeScopeComponentDecisionType;
use App\Administering\Value\Form\RuntimeScope\AdministrationRuntimeScopeComponentDecisionData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;

final class AdministrationRuntimeScopeComponentDecisionTypeTest extends TestCase
{
    public function testUncheckedEnabledCheckboxMapsToFalse(): void
    {
        $formFactory = self::createFormFactory();
        $data = new AdministrationRuntimeScopeComponentDecisionData(
            componentKey: 'accessing',
            environment: 'dev',
            enabled: true,
        );

        $form = $formFactory->create(AdministrationRuntimeScopeComponentDecisionType::class, $data, [
            'csrf_protection' => false,
        ]);
        $form->submit([
            'componentKey' => 'accessing',
            'environment' => 'prod',
            'reason' => 'Disable for production runtime scope.',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame('accessing', $data->componentKey);
        self::assertSame('prod', $data->environment);
        self::assertFalse($data->enabled);
        self::assertSame('Disable for production runtime scope.', $data->reason);
    }

    public function testCheckedEnabledCheckboxMapsToTrue(): void
    {
        $formFactory = self::createFormFactory();
        $data = new AdministrationRuntimeScopeComponentDecisionData(
            componentKey: 'paying',
            environment: 'prod',
            enabled: false,
        );

        $form = $formFactory->create(AdministrationRuntimeScopeComponentDecisionType::class, $data, [
            'csrf_protection' => false,
        ]);
        $form->submit([
            'componentKey' => 'paying',
            'environment' => 'dev',
            'enabled' => '1',
            'reason' => '',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame('paying', $data->componentKey);
        self::assertSame('dev', $data->environment);
        self::assertTrue($data->enabled);
        self::assertNull($data->reason);
    }

    private static function createFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addExtension(new CsrfExtension(new CsrfTokenManager()))
            ->getFormFactory();
    }
}
