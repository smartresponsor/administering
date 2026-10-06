<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Operation;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Small native status page for queued/running operation runs.
 */
final class AdministrationOperationRunDetailController extends AbstractController
{
    public function __construct(private readonly AdministrationPersistenceRepository $persistenceRepository)
    {
    }

    #[Route('/ea/administration/operation/run/{operationKey}', name: 'administering_operation_run_detail', methods: ['GET'])]
    public function __invoke(string $operationKey): Response
    {
        $this->denyAccessUnlessGranted('administration.operation.view', 'administering:operation');

        $operationRun = $this->persistenceRepository->findOneBy(AdministrationOperationRunEntity::class, ['operationKey' => $operationKey]);
        if (!$operationRun instanceof AdministrationOperationRunEntity) {
            throw $this->createNotFoundException(sprintf('Operation run "%s" was not found.', $operationKey));
        }

        return $this->render('@Administering/administering/operation_run_detail.html.twig', [
            'operationRun' => $operationRun,
            'reportUrl' => $this->generateUrl('administering_operation_report_json', ['operationKey' => $operationRun->operationKey()]),
            'backUrl' => $this->generateUrl('administration_operations'),
        ]);
    }
}
