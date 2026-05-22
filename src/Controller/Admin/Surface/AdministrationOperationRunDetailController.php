<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Entity\AdministrationOperationRun;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Small native status page for queued/running operation runs.
 */
final class AdministrationOperationRunDetailController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Route('/admin/operations/run/{operationKey}', name: 'administering_operation_run_detail', methods: ['GET'])]
    public function __invoke(string $operationKey): Response
    {
        $this->denyAccessUnlessGranted('administration.operation.view', 'administering:operation');

        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);
        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering operation runs.');
        }

        $operationRun = $manager->getRepository(AdministrationOperationRun::class)->findOneBy(['operationKey' => $operationKey]);
        if (!$operationRun instanceof AdministrationOperationRun) {
            throw $this->createNotFoundException(sprintf('Operation run "%s" was not found.', $operationKey));
        }

        return new Response(sprintf(
            '<h1>Operation Run</h1><dl><dt>Key</dt><dd>%s</dd><dt>Type</dt><dd>%s</dd><dt>Status</dt><dd>%s</dd><dt>Subject</dt><dd>%s</dd><dt>Target</dt><dd>%s</dd></dl><p><a href="%s">JSON report</a> · <a href="%s">Back to operations</a></p>',
            htmlspecialchars($operationRun->operationKey(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($operationRun->operationType(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($operationRun->status(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($operationRun->subjectIdentifier(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($operationRun->targetReference() ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($this->generateUrl('administering_operation_report_json', ['operationKey' => $operationRun->operationKey()]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($this->generateUrl('administration_operations'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ));
    }
}
