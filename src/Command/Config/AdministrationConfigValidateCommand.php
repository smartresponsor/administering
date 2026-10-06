<?php

declare(strict_types=1);

namespace App\Administering\Command\Config;

use App\Administering\Service\Config\AdministrationConfigToolRegistryService;
use App\Administering\ServiceInterface\Config\AdministrationConfigToolServiceInterface;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Form\AbstractType;

#[AsCommand(
    name: 'administering:config:validate',
    description: 'Validates discovered configuration tool descriptors, form classes, and secret/file whitelists.',
)]
/**
 * Validates discovered configuration descriptors against approved form/service classes and writable-file or secret-name constraints.
 */
final class AdministrationConfigValidateCommand extends Command
{
    public function __construct(private readonly AdministrationConfigToolRegistryService $registryService)
    {
        parent::__construct();
    }

    /**
     * Validates every discovered descriptor and reports success only when all form, service, file, and secret constraints pass.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $descriptors = $this->registryService->toolDescriptors();
        $errors = [];

        foreach ($descriptors as $descriptor) {
            array_push($errors, ...$this->validateDescriptor($descriptor));
        }

        if ([] === $errors) {
            $io->success(sprintf('Validated %d configuration tool descriptors.', count($descriptors)));

            return Command::SUCCESS;
        }

        $io->error($errors);

        return Command::FAILURE;
    }

    /** @return list<string> */
    private function validateDescriptor(AdministrationConfigToolDescriptor $descriptor): array
    {
        return [
            ...$this->validateFormClass($descriptor),
            ...$this->validateServiceClass($descriptor),
            ...$this->validateWritableFiles($descriptor),
            ...$this->validateSecretNames($descriptor),
        ];
    }

    /** @return list<string> */
    private function validateFormClass(AdministrationConfigToolDescriptor $descriptor): array
    {
        $formClass = $descriptor->formClass;
        if (null !== $formClass && class_exists($formClass) && is_subclass_of($formClass, AbstractType::class)) {
            return [];
        }

        return [sprintf('%s/%s: invalid form class %s', $descriptor->applicationCode, $descriptor->toolCode, $formClass ?? '<missing>')];
    }

    /** @return list<string> */
    private function validateServiceClass(AdministrationConfigToolDescriptor $descriptor): array
    {
        $serviceClass = $descriptor->serviceClass;
        if (null !== $serviceClass && class_exists($serviceClass) && is_subclass_of($serviceClass, AdministrationConfigToolServiceInterface::class)) {
            return [];
        }

        return [sprintf('%s/%s: missing service class %s', $descriptor->applicationCode, $descriptor->toolCode, $serviceClass ?? '<missing>')];
    }

    /** @return list<string> */
    private function validateWritableFiles(AdministrationConfigToolDescriptor $descriptor): array
    {
        $errors = [];
        foreach ($descriptor->writableFiles as $file) {
            if (str_starts_with($file, '/')
                || str_contains($file, '..')
                || (!str_starts_with($file, 'config/') && !str_starts_with($file, '.env'))) {
                $errors[] = sprintf('%s/%s: disallowed writable file %s', $descriptor->applicationCode, $descriptor->toolCode, $file);
            }
        }

        return $errors;
    }

    /** @return list<string> */
    private function validateSecretNames(AdministrationConfigToolDescriptor $descriptor): array
    {
        $errors = [];
        foreach ($descriptor->secretNames as $fieldKey => $secretName) {
            if (!preg_match('/^[A-Z0-9_]+$/', $secretName)) {
                $errors[] = sprintf('%s/%s: invalid secret mapping %s => %s', $descriptor->applicationCode, $descriptor->toolCode, $fieldKey, $secretName);
            }
        }

        return $errors;
    }
}
