<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Operation;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\Form\Operation\AdministrationOperationLaunchFormType;
use App\Administering\ServiceInterface\Operation\AdministrationOperationSubmitterInterface;
use App\Administering\Value\Form\Operation\AdministrationOperationLaunchData;
use App\Administering\Value\Operation\AdministrationOperationPlan;
use App\Administering\Value\Operation\AdministrationOperationType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * EasyRuntime Scope source for launching safe metadata-only Administering operations.
 */
final class AdministrationOperationLaunchController extends AbstractController
{
    public function __construct(
        private readonly AdministrationOperationSubmitterInterface $operationSubmitter,
        private readonly ManagerRegistry $managerRegistry,
        private readonly \Symfony\Component\Form\FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/ea/administration/operation', name: 'administration_operations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.operation.view', 'administering:operation');

        return $this->render('@Administering/easy_admin/operation_launch.html.twig', [
            'operations' => $this->launchableOperationRows(),
            'recentRuns' => $this->recentRuns(),
            'operationStorageConfigured' => null !== $this->operationRunManager(),
        ]);
    }

    #[Route('/ea/administration/operation/{operationType}/start', name: 'administration_operation_start', methods: ['POST'])]
    public function start(string $operationType, Request $request): Response
    {
        if (!AdministrationOperationType::isLaunchable($operationType)) {
            throw $this->createNotFoundException(sprintf('Operation "%s" is not launchable from the Administering UI.', $operationType));
        }

        $this->denyAccessUnlessGranted($operationType, 'administering:operation');

        $form = $this->createLaunchForm($operationType);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/easy_admin/operation_launch.html.twig', [
                'operations' => $this->launchableOperationRows($operationType, $form),
                'recentRuns' => $this->recentRuns(),
                'operationStorageConfigured' => null !== $this->operationRunManager(),
            ]);
        }

        $operationRun = $this->operationSubmitter->submitForCurrentUser(new AdministrationOperationPlan(
            $operationType,
            'administering:operation',
            ['source' => 'admin_ui', 'client' => 'easy_admin', 'requested_from' => $request->getPathInfo()],
        ));

        $this->addFlash('success', sprintf('Queued operation %s.', $operationRun->operationKey()));

        return $this->redirectToRoute('administering_operation_run_detail', ['operationKey' => $operationRun->operationKey()]);
    }

    /** @return list<array{type: string, form: \Symfony\Component\Form\FormView}> */
    private function launchableOperationRows(?string $selectedOperationType = null, ?FormInterface $selectedForm = null): array
    {
        $rows = [];

        foreach (AdministrationOperationType::launchable() as $operationType) {
            if (null !== $selectedOperationType && $operationType === $selectedOperationType && null !== $selectedForm) {
                $formView = $selectedForm->createView();
            } else {
                $formView = $this->createLaunchForm($operationType)->createView();
            }

            $rows[] = [
                'type' => $operationType,
                'form' => $formView,
            ];
        }

        return $rows;
    }

    /** @return list<AdministrationOperationRun> */
    private function recentRuns(): array
    {
        $manager = $this->operationRunManager();
        if (null === $manager) {
            return [];
        }

        return $manager->getRepository(AdministrationOperationRun::class)->findBy([], ['id' => 'DESC'], 20);
    }

    private function operationRunManager(): ?ObjectManager
    {
        return $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);
    }

    private function createLaunchForm(string $operationType): FormInterface
    {
        $data = new AdministrationOperationLaunchData();
        $data->operationType = $operationType;

        return $this->formFactory->createNamed(
            $this->formNameForOperation($operationType),
            AdministrationOperationLaunchFormType::class,
            $data,
            [
                'action' => $this->generateUrl('administration_operation_start', ['operationType' => $operationType]),
                'csrf_token_id' => $this->csrfTokenId($operationType),
            ],
        );
    }

    private function formNameForOperation(string $operationType): string
    {
        return 'administering_operation_start_'.strtr($operationType, [
            '.' => '_',
            ':' => '_',
            '-' => '_',
        ]);
    }

    private function csrfTokenId(string $operationType): string
    {
        return 'administering.operation.start.'.$operationType;
    }
}
