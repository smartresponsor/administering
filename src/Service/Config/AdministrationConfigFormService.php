<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigToolEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use Symfony\Component\Form\AbstractType;

final readonly class AdministrationConfigFormService
{
    public function __construct(private AdministrationPersistenceRepository $persistenceRepository)
    {
    }

    public function formClassForTool(string $applicationCode, string $toolCode): ?string
    {
        $tool = $this->tool($applicationCode, $toolCode);
        if (!$tool instanceof AdministrationConfigToolEntity) {
            return null;
        }

        $formClass = $tool->getFormClass();
        if (!class_exists($formClass) || !is_subclass_of($formClass, AbstractType::class)) {
            return null;
        }

        return $formClass;
    }

    private function tool(string $applicationCode, string $toolCode): ?AdministrationConfigToolEntity
    {
        $tool = $this->persistenceRepository->findOneByIfManaged(AdministrationConfigToolEntity::class, [
            'applicationCode' => $applicationCode,
            'toolCode' => $toolCode,
        ]);

        return $tool instanceof AdministrationConfigToolEntity ? $tool : null;
    }
}
