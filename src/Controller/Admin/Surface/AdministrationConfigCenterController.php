<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Entity\Config\AdministrationConfigTool;
use App\Administering\Locator\Config\AdministrationConfigToolServiceLocator;
use App\Administering\Service\Config\AdministrationConfigFormResolverService;
use App\Administering\Service\Config\AdministrationConfigStateService;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdministrationConfigCenterController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly AdministrationConfigToolServiceLocator $toolServiceLocator,
        private readonly AdministrationConfigFormResolverService $formResolverService,
        private readonly AdministrationConfigStateService $stateService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/ea/config', name: 'administration_config_center_index', methods: ['GET'], defaults: [
        EA::ROUTE_CREATED_BY_EASYADMIN => true,
        EA::CRUD_CONTROLLER_FQCN => null,
        EA::CRUD_ACTION => null,
    ])]
    public function index(): Response
    {
        if (null === $this->getUser()) {
            return $this->disableCaching($this->redirectToRoute('interfacing_welcome_sign_in'));
        }

        $this->denyAccessUnlessGranted('administration.config.view');

        return $this->disableCaching($this->render('@Administering/administering/config_center_index.html.twig', [
            'applications' => $this->fetch('App\Administering\Entity\Config\AdministrationConfigApplication'),
            'tools' => $this->fetch(AdministrationConfigTool::class),
            'applicationCount' => $this->count('App\Administering\Entity\Config\AdministrationConfigApplication'),
            'toolCount' => $this->count(AdministrationConfigTool::class),
            'secretToolCount' => $this->secretToolCount(),
            'applicationIndexUrl' => $this->generateUrl('administration_admin_index_administration_config_application_index'),
            'toolIndexUrl' => $this->generateUrl('administration_admin_index_administration_config_tool_index'),
            'valueIndexUrl' => $this->generateUrl('administration_admin_index_administration_config_value_index'),
            'applyLogIndexUrl' => $this->generateUrl('administration_admin_index_administration_config_apply_log_index'),
        ]));
    }

    #[Route('/ea/config/{applicationCode}/{toolCode}/edit', name: 'administration_config_tool_edit', methods: ['GET', 'POST'], defaults: [
        EA::ROUTE_CREATED_BY_EASYADMIN => true,
        EA::CRUD_CONTROLLER_FQCN => null,
        EA::CRUD_ACTION => null,
    ])]
    public function edit(string $applicationCode, string $toolCode, Request $request): Response
    {
        if (null === $this->getUser()) {
            return $this->disableCaching($this->redirectToRoute('interfacing_welcome_sign_in'));
        }

        $tool = $this->tool($applicationCode, $toolCode);
        if (!$tool instanceof AdministrationConfigTool) {
            throw $this->createNotFoundException(sprintf('Unknown configuration tool "%s/%s".', $applicationCode, $toolCode));
        }

        $this->denyAccessUnlessGranted($tool->getRequiredPermission(), 'administering:config-tool:'.$applicationCode.':'.$toolCode);

        $formClass = $this->formResolverService->formClassForTool($applicationCode, $toolCode);
        if (null === $formClass) {
            throw new \LogicException(sprintf('No approved Symfony Form is registered for configuration tool "%s/%s".', $applicationCode, $toolCode));
        }

        $toolService = $this->toolServiceLocator->forTool($applicationCode, $toolCode);
        if (null === $toolService) {
            throw new \LogicException(sprintf('No config service is registered for "%s/%s".', $applicationCode, $toolCode));
        }

        $data = $this->stateService->hydratePendingValues($applicationCode, $toolCode, $toolService->loadData());
        $form = $this->createForm($formClass, $data);
        $form->handleRequest($request);

        $result = null;
        $resultTitle = '';
        if ($form->isSubmitted() && $form->isValid()) {
            $actor = $this->currentUserContextProvider->current()?->subjectIdentifier() ?? 'system';
            $payload = $form->getData();
            if (!is_object($payload)) {
                throw new \LogicException('Configuration tool form data must be an object before save/apply.');
            }

            $applyButton = $form->get('apply');
            if ($applyButton instanceof ClickableInterface && $applyButton->isClicked()) {
                $result = $toolService->apply($payload, ['actor' => $actor]);
                $resultTitle = 'Apply result';
            } else {
                $result = $toolService->save($payload, ['actor' => $actor]);
                $resultTitle = 'Save result';
            }
        }

        return $this->disableCaching($this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => $tool->getLabel(),
            'lead' => $tool->getDescription(),
            'tool' => $tool,
            'summary_items' => [
                ['label' => 'Application', 'value' => $tool->getApplicationCode()],
                ['label' => 'Tool', 'value' => $tool->getToolCode()],
                ['label' => 'Status', 'value' => $tool->getStatus()],
                ['label' => 'Apply strategy', 'value' => $tool->getApplyStrategy()],
                ['label' => 'Permission', 'value' => $tool->getRequiredPermission()],
                ['label' => 'Read files', 'value' => implode(', ', $tool->getReadableFiles()) ?: 'n/a'],
                ['label' => 'Write files', 'value' => implode(', ', $tool->getWritableFiles()) ?: 'n/a'],
            ],
            'form' => $form->createView(),
            'result_title' => $resultTitle,
            'result_html' => null !== $result ? $this->renderResultHtml($result) : '',
            'result_json' => null !== $result ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
            'action_links' => [
                ['label' => 'Back to configuration center', 'url' => $this->generateUrl('administration_config_center_index')],
                ['label' => 'Tool index', 'url' => $this->generateUrl('administration_admin_index_administration_config_tool_index')],
            ],
            'back_url' => $this->generateUrl('administration_config_center_index'),
        ]));
    }

    /** @param array<string, mixed> $result */
    private function renderResultHtml(array $result): string
    {
        $messages = array_map(
            static fn (string $message): string => '<li>'.htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>',
            array_values(array_filter($result['messages'] ?? [], static fn ($message): bool => is_string($message) && '' !== trim($message))),
        );

        $summary = sprintf(
            '<div class="alert alert-info"><strong>Status:</strong> %s</div>',
            htmlspecialchars((string) ($result['status'] ?? 'unknown'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );

        return $summary.'<ul>'.implode('', $messages).'</ul>';
    }

    /**
     * @param class-string<object> $entityClass
     *
     * @return list<object>
     */
    private function fetch(string $entityClass): array
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        if (null === $manager) {
            return [];
        }

        return $manager->getRepository($entityClass)->findBy([], ['id' => 'ASC']);
    }

    private function count(string $entityClass): int
    {
        return count($this->fetch($entityClass));
    }

    private function secretToolCount(): int
    {
        return count(array_filter(
            $this->fetch(AdministrationConfigTool::class),
            static fn (AdministrationConfigTool $tool): bool => [] !== $tool->getSecretNames(),
        ));
    }

    private function tool(string $applicationCode, string $toolCode): ?AdministrationConfigTool
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationConfigTool::class);
        if (null === $manager) {
            return null;
        }

        $tool = $manager->getRepository(AdministrationConfigTool::class)->findOneBy([
            'applicationCode' => $applicationCode,
            'toolCode' => $toolCode,
        ]);

        return $tool instanceof AdministrationConfigTool ? $tool : null;
    }

    private function disableCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private', true);
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
