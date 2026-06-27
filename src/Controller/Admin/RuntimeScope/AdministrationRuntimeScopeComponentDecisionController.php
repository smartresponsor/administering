<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\RuntimeScope;

use App\Administering\Entity\AdministrationConnectedComponentRecord;
use App\Administering\Form\RuntimeScope\AdministrationRuntimeScopeComponentDecisionType;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeComponentDecisionApplyService;
use App\Administering\Value\Form\RuntimeScope\AdministrationRuntimeScopeComponentDecisionData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdministrationRuntimeScopeComponentDecisionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdministrationRuntimeScopeComponentDecisionApplyService $applyService,
    ) {
    }

    #[Route('/ea/administration/component/{componentKey}/decision', name: 'administration_runtime_scope_component_decision', methods: ['GET', 'POST'])]
    public function __invoke(string $componentKey, Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.connected_component.overview.view', 'administering:enabled-components');

        $record = $this->record($componentKey);
        $environment = $this->requestedEnvironment($request);
        $data = null !== $record
            ? AdministrationRuntimeScopeComponentDecisionData::fromRecord($record, $environment)
            : new AdministrationRuntimeScopeComponentDecisionData(componentKey: $componentKey, environment: $environment);

        $form = $this->createForm(AdministrationRuntimeScopeComponentDecisionType::class, $data, [
            'action' => $this->generateUrl('administration_runtime_scope_component_decision', ['componentKey' => $componentKey]),
        ]);
        $form->handleRequest($request);

        $result = null;
        $errorMessage = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var AdministrationRuntimeScopeComponentDecisionData $data */
                $data = $form->getData();
                $result = $this->applyService->apply($data);
                $this->addFlash('success', sprintf('Runtime-scope decision saved for %s (%s).', $data->componentKey, $data->environment));
            } catch (\Throwable $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Enabled Component Decision',
            'lead' => 'Enable or disable one component for the selected APP_RUNTIME_SCOPE lock. The decision is written to the runtime lock, synchronized into SQLite, and recorded in audit.',
            'form' => $form->createView(),
            'result_title' => null !== $result ? 'Saved decision' : null,
            'result_json' => null !== $result ? json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) : null,
            'error_message' => $errorMessage,
            'action_links' => [
                [
                    'label' => 'Back to Enabled Components',
                    'url' => '/ea/administration',
                ],
            ],
            'back_url' => '/ea/administration',
        ]);
    }

    private function record(string $componentKey): ?AdministrationConnectedComponentRecord
    {
        return $this->entityManager
            ->getRepository(AdministrationConnectedComponentRecord::class)
            ->findOneBy(['componentName' => strtolower(trim($componentKey))]);
    }

    private function requestedEnvironment(Request $request): string
    {
        $environment = strtolower((string) $request->query->get('environment', 'dev'));

        return in_array($environment, ['dev', 'prod'], true) ? $environment : 'dev';
    }
}
