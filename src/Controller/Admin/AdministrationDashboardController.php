<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin;

use App\Accessing\Entity\AccessAccountEntity;
use App\Administering\BuilderInterface\Admin\AdministrationMainMenuBuilderInterface;
use App\Administering\Controller\Admin\Crud\AdministrationConfigToolCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationServiceToolRecordCrudController;
use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\Form\Admin\AdministrationAdminServiceToolRuntimeControlsFormType;
use App\Administering\ProviderInterface\Admin\AdministrationServiceSectionToolDashboardProviderInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolOpenGuardInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolOperationPlanFactoryInterface;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationSubmitterInterface;
use App\Administering\Value\Form\Admin\AdministrationAdminServiceToolRuntimeControlsData;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'administration_admin_index')]
final class AdministrationDashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdministrationMainMenuBuilderInterface $mainMenuBuilder,
        private readonly AdministrationServiceSectionToolDashboardProviderInterface $toolDashboardProvider,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ManagerRegistry $managerRegistry,
        private readonly AdministrationServiceToolOperationPlanFactoryInterface $toolOperationPlanFactory,
        private readonly AdministrationServiceToolOpenGuardInterface $toolOpenGuard,
        private readonly AdministrationOperationSubmitterInterface $operationSubmitter,
        private readonly AdministrationAuditRecorderInterface $auditRecorder,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            \Symfony\Component\HttpFoundation\RequestStack::class => '?'.\Symfony\Component\HttpFoundation\RequestStack::class,
            \EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory::class => '?'.\EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory::class,
            \EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory::class => '?'.\EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory::class,
            \Twig\Environment::class => '?'.\Twig\Environment::class,
        ]);
    }

    public function index(): Response
    {
        $requestStack = $this->container?->get(\Symfony\Component\HttpFoundation\RequestStack::class);
        if (!$requestStack instanceof \Symfony\Component\HttpFoundation\RequestStack) {
            throw new \LogicException('The RequestStack service is not available while rendering the Administering dashboard.');
        }

        $request = $requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new \LogicException('The current request is not available while rendering the Administering dashboard.');
        }

        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->disableCaching($this->redirectToRoute('interfacing_welcome_sign_in'));
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $adminContextFactory = $this->container?->get(\EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory::class);
        if (!$adminContextFactory instanceof \EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory) {
            throw new \LogicException('EasyAdmin AdminContextFactory is not available while rendering the Administering dashboard.');
        }

        $controllerFactory = $this->container?->get(\EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory::class);
        if (!$controllerFactory instanceof \EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory) {
            throw new \LogicException('EasyAdmin ControllerFactory is not available while rendering the Administering dashboard.');
        }

        $twig = $this->container?->get(\Twig\Environment::class);
        if (!$twig instanceof \Twig\Environment) {
            throw new \LogicException('Twig environment is not available while rendering the Administering dashboard.');
        }

        $crudController = $controllerFactory->getCrudControllerInstance(AdministrationConfigToolCrudController::class, Crud::PAGE_INDEX, $request);
        if (null === $crudController) {
            throw new \LogicException('Unable to instantiate the AdministrationConfigTool CRUD controller for the main dashboard.');
        }

        $request->attributes->set(EA::CRUD_CONTROLLER_FQCN, AdministrationConfigToolCrudController::class);
        $request->attributes->set(EA::CRUD_ACTION, Crud::PAGE_INDEX);

        $adminContext = $adminContextFactory->create($request, $this, $crudController, Crud::PAGE_INDEX);
        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $adminContext);

        $responseParameters = $crudController->index($adminContext);
        if ($responseParameters instanceof Response) {
            return $this->disableCaching($responseParameters);
        }

        if (!$responseParameters instanceof KeyValueStore) {
            throw new \LogicException('Unexpected controller result returned by the AdministrationConfigTool CRUD index.');
        }

        $templateParameters = $responseParameters->all();
        $templatePath = \array_key_exists('templatePath', $templateParameters)
            ? (string) $templateParameters['templatePath']
            : $adminContext->getTemplatePath((string) $templateParameters['templateName']);

        $formErrorCount = 0;
        foreach ($templateParameters as $paramName => $paramValue) {
            if ($paramValue instanceof FormInterface) {
                $templateParameters[$paramName] = $paramValue->createView();
                $formErrorCount = max($formErrorCount, count($paramValue->getErrors(true)));
            }
        }

        $httpCode = $formErrorCount > 0 ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return $this->disableCaching(new Response($twig->render($templatePath, $templateParameters), $httpCode));
    }

    #[Route('/admin/service-section/{sectionKey}/tools', name: 'administration_service_section_tools', methods: ['GET'], defaults: [
        EA::ROUTE_CREATED_BY_EASYADMIN => true,
        EA::DASHBOARD_CONTROLLER_FQCN => self::class,
        EA::CRUD_CONTROLLER_FQCN => null,
        EA::CRUD_ACTION => null,
    ])]
    public function serviceSectionTools(string $sectionKey): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->disableCaching($this->redirectToRoute('interfacing_welcome_sign_in'));
        }

        $dashboard = $this->toolDashboardProvider->dashboardForSection($sectionKey);
        if (null === $dashboard) {
            throw $this->createNotFoundException(sprintf('Unknown Administering service section "%s".', $sectionKey));
        }

        $this->denyAccessUnlessGranted($dashboard->section->permission, 'administering:service-section:'.$sectionKey);

        return $this->disableCaching($this->redirect($this->serviceToolIndexUrl($sectionKey)));
    }

    #[Route('/admin/service-section/{sectionKey}/tools/{toolShortName}', name: 'administration_service_section_tool_detail', methods: ['GET'], defaults: [
        EA::ROUTE_CREATED_BY_EASYADMIN => true,
        EA::DASHBOARD_CONTROLLER_FQCN => self::class,
        EA::CRUD_CONTROLLER_FQCN => null,
        EA::CRUD_ACTION => null,
    ])]
    public function serviceSectionToolDetail(string $sectionKey, string $toolShortName): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->disableCaching($this->redirectToRoute('interfacing_welcome_sign_in'));
        }

        $detail = $this->toolDashboardProvider->detailForTool($sectionKey, $toolShortName);
        if (null === $detail) {
            throw $this->createNotFoundException(sprintf('Unknown Administering service tool "%s/%s".', $sectionKey, $toolShortName));
        }

        $this->denyAccessUnlessGranted($detail->section->permission, 'administering:service-section-tool:'.$sectionKey.':'.$toolShortName);

        return $this->disableCaching($this->render('@Administering/easy_admin/service_section_tool_detail.html.twig', [
            'detail' => $detail,
        ]));
    }

    #[Route('/admin/service-tool/{toolKey}/open', name: 'administration_service_tool_open', methods: ['GET', 'POST'], defaults: [
        EA::ROUTE_CREATED_BY_EASYADMIN => true,
        EA::DASHBOARD_CONTROLLER_FQCN => self::class,
        EA::CRUD_CONTROLLER_FQCN => null,
        EA::CRUD_ACTION => null,
    ])]
    public function openServiceTool(string $toolKey, Request $request): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->disableCaching($this->redirectToRoute('interfacing_welcome_sign_in'));
        }

        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering service tool records.');
        }

        $record = $manager->getRepository(AdministrationServiceToolRecord::class)->findOneBy(['toolKey' => $toolKey]);

        if (!$record instanceof AdministrationServiceToolRecord) {
            throw $this->createNotFoundException(sprintf('Unknown Administering service tool "%s".', $toolKey));
        }

        $this->denyAccessUnlessGranted('administration.dashboard.view', 'administering:service-tool:'.$toolKey);

        $this->toolOpenGuard->assertRecordCanOpen($record);

        $form = null;
        $submitted = false;
        $operationRun = null;
        $formTypeClass = $record->getFormTypeClass();
        if (!is_string($formTypeClass)) {
            throw new \LogicException(sprintf('Mapped FormType for Administering service tool "%s" is not available after open guard validation.', $toolKey));
        }

        $form = $this->createForm($formTypeClass);
        $form->handleRequest($request);
        $submitted = $form->isSubmitted() && $form->isValid();
        if ($submitted) {
            $operationRun = $this->operationSubmitter->submitForCurrentUser(
                $this->toolOperationPlanFactory->createForSubmittedTool($record, $form->getData()),
            );

            return $this->disableCaching($this->redirectToRoute('administering_operation_run_detail', [
                'operationKey' => $operationRun->operationKey(),
            ]));
        }

        return $this->disableCaching($this->render('@Administering/easy_admin/service_tool_open.html.twig', [
            'record' => $record,
            'form' => $form?->createView(),
            'submitted' => $submitted,
            'operationRun' => $operationRun,
            'indexUrl' => $this->serviceToolIndexUrl($record->getSectionKey()),
        ]));
    }

    #[Route('/admin/service-tool/{toolKey}/runtime-controls', name: 'administration_service_tool_runtime_controls', methods: ['GET', 'POST'], defaults: [
        EA::ROUTE_CREATED_BY_EASYADMIN => true,
        EA::DASHBOARD_CONTROLLER_FQCN => self::class,
        EA::CRUD_CONTROLLER_FQCN => null,
        EA::CRUD_ACTION => null,
    ])]
    public function serviceToolRuntimeControls(string $toolKey, Request $request): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->disableCaching($this->redirectToRoute('interfacing_welcome_sign_in'));
        }

        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering service tool records.');
        }

        $record = $manager->getRepository(AdministrationServiceToolRecord::class)->findOneBy(['toolKey' => $toolKey]);
        if (!$record instanceof AdministrationServiceToolRecord) {
            throw $this->createNotFoundException(sprintf('Unknown Administering service tool "%s".', $toolKey));
        }

        $this->denyAccessUnlessGranted('administration.dashboard.view', 'administering:service-tool-runtime-controls:'.$toolKey);

        $data = AdministrationAdminServiceToolRuntimeControlsData::fromRecord($record);
        $form = $this->createForm(AdministrationAdminServiceToolRuntimeControlsFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $before = [
                'enabled' => $record->isEnabled(),
                'visible' => $record->isVisible(),
                'position' => $record->getPosition(),
                'labelOverride' => $record->getLabelOverride(),
                'displayLabel' => $record->getDisplayLabel(),
            ];

            $record->configureRuntimeControls($data->enabled, $data->visible, $data->position, $data->labelOverride, $data->clearLabelOverride);
            $manager->flush();

            $this->auditRecorder->record('administration.service_tool.runtime_controls.updated', $record->getToolKey(), [
                'source' => 'easyadmin',
                'sectionKey' => $record->getSectionKey(),
                'toolSlug' => $record->getToolSlug(),
                'before' => $before,
                'after' => [
                    'enabled' => $record->isEnabled(),
                    'visible' => $record->isVisible(),
                    'position' => $record->getPosition(),
                    'labelOverride' => $record->getLabelOverride(),
                    'displayLabel' => $record->getDisplayLabel(),
                ],
            ]);

            return $this->disableCaching($this->redirect($this->serviceToolIndexUrl($record->getSectionKey())));
        }

        return $this->disableCaching($this->render('@Administering/easy_admin/service_tool_runtime_controls.html.twig', [
            'record' => $record,
            'form' => $form->createView(),
            'indexUrl' => $this->serviceToolIndexUrl($record->getSectionKey()),
            'openUrl' => $record->isOpenable() ? $this->generateUrl('administration_service_tool_open', ['toolKey' => $record->getToolKey()]) : null,
        ]));
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Administering');
    }

    public function configureMenuItems(): iterable
    {
        yield from $this->mainMenuBuilder->build();
    }

    private function serviceToolIndexUrl(string $sectionKey): string
    {
        return $this->adminUrlGenerator
            ->setDashboard(self::class)
            ->setController(AdministrationServiceToolRecordCrudController::class)
            ->setAction(Crud::PAGE_INDEX)
            ->set('filters[sectionKey][comparison]', '=')
            ->set('filters[sectionKey][value]', $sectionKey)
            ->generateUrl();
    }

    private function disableCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private', true);
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
