<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\CheckerInterface\Security\AdministrationPermissionCheckerInterface;
use App\Administering\Entity\AdministrationAccountActionRequestRecord;
use App\Administering\Entity\AdministrationAclMutationApplyRecord;
use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\Entity\AdministrationAuditEvent;
use App\Administering\Entity\AdministrationConfigSnapshot;
use App\Administering\Entity\AdministrationCredentialState;
use App\Administering\Entity\AdministrationOperationArtifact;
use App\Administering\Entity\AdministrationOperationEvent;
use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\MessageHandler\AdministrationOperationRunMessageHandler;
use App\Administering\ProviderInterface\Security\AdministrationExternalPermissionDecisionProviderInterface;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationQueueInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationReportProviderInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunnerInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationStatusRecorderInterface;
use App\Administering\Value\Operation\AdministrationOperationType;
use App\Rolling\Entity\Acl\RollingAclMutationExecutionEventEntity;
use App\Rolling\Entity\Acl\RollingAclRule;
use App\Rolling\Entity\Acl\RollingPermission;
use App\Rolling\Entity\Acl\RollingRole;
use App\Rolling\Entity\Acl\RollingRoleHierarchy;
use App\Rolling\Entity\Acl\RollingRolePermission;
use App\Rolling\Entity\Acl\RollingSubjectRoleAssignment;
use App\Rolling\ServiceInterface\Administration\RollingAdministrationPermissionCatalogInterface;
use App\Rolling\ServiceInterface\Administration\RollingAdministrationPermissionDecisionServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'administering:runtime:readiness',
    description: 'Checks the Administering/Rolling runtime wiring needed before RC promotion.',
)]
final class AdministrationRuntimeReadinessCommand extends Command
{
    /** @var list<class-string> */
    private const ENTITY_CLASSES = [
        AdministrationOperationRun::class,
        AdministrationOperationEvent::class,
        AdministrationOperationArtifact::class,
        AdministrationAuditEvent::class,
        AdministrationConfigSnapshot::class,
        AdministrationCredentialState::class,
        AdministrationAccountActionRequestRecord::class,
        AdministrationAclMutationReviewRecord::class,
        AdministrationAclMutationApplyRecord::class,
        RollingPermission::class,
        RollingRole::class,
        RollingRolePermission::class,
        RollingRoleHierarchy::class,
        RollingSubjectRoleAssignment::class,
        RollingAclRule::class,
        RollingAclMutationExecutionEventEntity::class,
    ];

    /**
     * Permissions used by Administering native controllers, EasyAdmin menu items,
     * read-only CRUD entity permissions, and launchable operation gates.
     *
     * Keep this list explicit so the readiness command fails closed when a new
     * controller gate is added without a matching Rolling catalog descriptor.
     *
     * @var list<string>
     */
    private const REQUIRED_PERMISSION_GATES = [
        'administration.dashboard.view',
        'administration.config.view',
        'administration.operation.view',
        'administration.accessing.account.view',
        'administration.accessing.account_action.view',
        'administration.accessing.account_action.execute',
        'administration.accessing.account_action.audit.view',
        'administration.rolling.permission_catalog.view',
        'administration.rolling.subject_access_report.view',
        'administration.rolling.acl_mutation.review.view',
        'administration.rolling.acl_mutation.review',
        'administration.rolling.acl_mutation.apply',
        'administration.rolling.acl_mutation.apply.view',
        'administration.rolling.acl.execution_report.view',
        'administration.connected_component.overview.view',
        'administration.connected_component.readiness.view',
        'administration.connected_component.remediation.view',
        'administration.connected_component.work_plan.view',
        'administration.connected_component.execution_plan.view',
        'administration.connected_component.capability_matrix.view',
        'administration.connected_component.contract_matrix.view',
        'administration.connected_component.health.view',
        'administration.connected_component.diagnostics.view',
    ];

    /** @var list<string> */
    private const ROUTE_NAMES = [
        'administration_admin_index',
        'administration_operations',
        'administration_operation_start',
        'administering_operation_run_detail',
        'administering_operation_report_json',
        'administration_rolling_permission_catalog',
        'administration_rolling_subject_access_report',
        'administration_rolling_acl_mutations',
        'administration_rolling_acl_mutation_review',
        'administration_rolling_acl_mutation_apply',
        'administration_rolling_acl_mutation_apply_report',
        'administration_rolling_acl_mutation_execution_report',
        'administration_accessing_accounts',
        'administration_accessing_account_actions',
        'administration_accessing_account_action_execute',
        'administration_accessing_account_action_audit',
        'administration_connected_component_overview',
        'administration_connected_component_readiness',
        'administration_connected_component_remediation',
        'administration_connected_component_work_plan',
        'administration_connected_component_execution_plan',
        'administration_connected_component_capability_matrix',
        'administration_connected_component_contract_matrix',
        'administration_connected_component_health',
        'administration_connected_component_diagnostics',
    ];

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly RouterInterface $router,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly AdministrationPermissionCheckerInterface $permissionChecker,
        private readonly AdministrationExternalPermissionDecisionProviderInterface $externalPermissionDecisionProvider,
        private readonly AdministrationOperationQueueInterface $operationQueue,
        private readonly AdministrationOperationRunnerInterface $operationRunner,
        private readonly AdministrationOperationStatusRecorderInterface $operationStatusRecorder,
        private readonly AdministrationOperationReportProviderInterface $operationReportProvider,
        private readonly AdministrationOperationRunMessageHandler $operationRunMessageHandler,
        private readonly RollingAdministrationPermissionCatalogInterface $rollingPermissionCatalog,
        private readonly RollingAdministrationPermissionDecisionServiceInterface $rollingPermissionDecisionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'json',
            null,
            InputOption::VALUE_NONE,
            'Print a machine-readable readiness report.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $managerRows = $this->managerRows();
        $routeRows = $this->routeRows();
        $serviceRows = $this->serviceRows();
        $permissionRows = $this->permissionRows();
        $operationRows = $this->operationRows();
        $catalogIntegrityRows = $this->catalogIntegrityRows();

        $missingManagers = array_values(array_filter($managerRows, static fn (array $row): bool => false === $row['configured']));
        $missingRoutes = array_values(array_filter($routeRows, static fn (array $row): bool => false === $row['configured']));
        $missingPermissions = array_values(array_filter($permissionRows, static fn (array $row): bool => false === $row['catalogued']));
        $unsupportedLaunchableOperations = array_values(array_filter($operationRows, static fn (array $row): bool => true === $row['launchable'] && false === $row['supported_by_runner']));
        $catalogIntegrityFailures = array_values(array_filter($catalogIntegrityRows, static fn (array $row): bool => false === $row['valid']));
        $ready = [] === $missingManagers && [] === $missingRoutes && [] === $missingPermissions && [] === $unsupportedLaunchableOperations && [] === $catalogIntegrityFailures;

        $report = [
            'status' => $ready ? 'ready' : 'not_ready',
            'ready' => $ready,
            'summary' => [
                'entity_manager_mappings' => count($managerRows),
                'missing_entity_manager_mappings' => count($missingManagers),
                'routes' => count($routeRows),
                'missing_routes' => count($missingRoutes),
                'wired_services' => count($serviceRows),
                'permission_gates' => count($permissionRows),
                'missing_permission_gates' => count($missingPermissions),
                'unsupported_launchable_operations' => count($unsupportedLaunchableOperations),
                'catalog_integrity_failures' => count($catalogIntegrityFailures),
            ],
            'entity_managers' => $managerRows,
            'routes' => $routeRows,
            'services' => $serviceRows,
            'permission_gates' => $permissionRows,
            'operations' => $operationRows,
            'catalog_integrity' => $catalogIntegrityRows,
        ];

        if (true === $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering runtime readiness');
        $io->definitionList(
            ['status' => $report['status']],
            ['missing entity manager mappings' => (string) count($missingManagers)],
            ['missing routes' => (string) count($missingRoutes)],
            ['missing permission gates' => (string) count($missingPermissions)],
            ['unsupported launchable operations' => (string) count($unsupportedLaunchableOperations)],
            ['catalog integrity failures' => (string) count($catalogIntegrityFailures)],
            ['wired services' => (string) count($serviceRows)],
        );

        $io->section('Doctrine manager mappings');
        $io->table(['entity', 'configured', 'manager'], array_map(
            static fn (array $row): array => [$row['entity'], $row['configured'] ? 'yes' : 'no', $row['manager'] ?? 'missing'],
            $managerRows,
        ));

        $io->section('Route surface');
        $io->table(['route', 'configured'], array_map(
            static fn (array $row): array => [$row['route'], $row['configured'] ? 'yes' : 'no'],
            $routeRows,
        ));

        $io->section('Permission gate coverage');
        $io->table(['permission', 'catalogued'], array_map(
            static fn (array $row): array => [$row['permission'], $row['catalogued'] ? 'yes' : 'no'],
            $permissionRows,
        ));

        $io->section('Operation runner coverage');
        $io->table(['operation', 'launchable', 'supported by runner'], array_map(
            static fn (array $row): array => [$row['operation'], $row['launchable'] ? 'yes' : 'no', $row['supported_by_runner'] ? 'yes' : 'no'],
            $operationRows,
        ));

        $io->section('Rolling catalog integrity');
        $io->table(['check', 'valid', 'details'], array_map(
            static fn (array $row): array => [$row['check'], $row['valid'] ? 'yes' : 'no', $row['details']],
            $catalogIntegrityRows,
        ));

        $io->section('Critical services');
        $io->table(['contract', 'implementation'], array_map(
            static fn (array $row): array => [$row['contract'], $row['implementation']],
            $serviceRows,
        ));

        if (!$ready) {
            $io->warning('Runtime readiness failed. Fix missing Doctrine mappings/routes/catalog gates/operation runner coverage before promoting this slice to 3RC.');
        } else {
            $io->success('Administering/Rolling runtime wiring is ready for the next RC proof step.');
        }

        return $ready ? Command::SUCCESS : Command::FAILURE;
    }

    /** @return list<array{entity: string, configured: bool, manager: string|null}> */
    private function managerRows(): array
    {
        $rows = [];
        foreach (self::ENTITY_CLASSES as $entityClass) {
            $manager = $this->managerRegistry->getManagerForClass($entityClass);
            $rows[] = [
                'entity' => $entityClass,
                'configured' => null !== $manager,
                'manager' => null !== $manager ? $manager::class : null,
            ];
        }

        return $rows;
    }

    /** @return list<array{route: string, configured: bool}> */
    private function routeRows(): array
    {
        $collection = $this->router->getRouteCollection();
        $rows = [];
        foreach (self::ROUTE_NAMES as $routeName) {
            $rows[] = [
                'route' => $routeName,
                'configured' => null !== $collection->get($routeName),
            ];
        }

        return $rows;
    }

    /** @return list<array{permission: string, catalogued: bool}> */
    private function permissionRows(): array
    {
        $cataloguedPermissions = array_flip($this->rollingPermissionCatalog->permissions());
        $requiredPermissions = array_values(array_unique(array_merge(
            self::REQUIRED_PERMISSION_GATES,
            AdministrationOperationType::all(),
        )));
        sort($requiredPermissions);

        return array_map(
            static fn (string $permission): array => [
                'permission' => $permission,
                'catalogued' => isset($cataloguedPermissions[$permission]),
            ],
            $requiredPermissions,
        );
    }

    /** @return list<array{operation: string, launchable: bool, supported_by_runner: bool}> */
    private function operationRows(): array
    {
        $launchable = array_flip(AdministrationOperationType::launchable());
        $supported = array_flip($this->operationRunner->supportedOperationTypes());
        $operations = array_values(array_unique(array_merge(
            AdministrationOperationType::all(),
            $this->operationRunner->supportedOperationTypes(),
        )));
        sort($operations);

        return array_map(
            static fn (string $operationType): array => [
                'operation' => $operationType,
                'launchable' => isset($launchable[$operationType]),
                'supported_by_runner' => isset($supported[$operationType]),
            ],
            $operations,
        );
    }

    /** @return list<array{check: string, valid: bool, details: string}> */
    private function catalogIntegrityRows(): array
    {
        $descriptorKeys = [];
        $duplicateKeys = [];
        foreach ($this->rollingPermissionCatalog->descriptors() as $descriptor) {
            $key = $descriptor->key();
            if (isset($descriptorKeys[$key])) {
                $duplicateKeys[] = $key;
            }

            $descriptorKeys[$key] = true;
        }

        $catalogPermissions = $this->rollingPermissionCatalog->permissions();
        $missingDescriptorKeys = array_values(array_diff($catalogPermissions, array_keys($descriptorKeys)));

        return [
            [
                'check' => 'unique_descriptor_keys',
                'valid' => [] === $duplicateKeys,
                'details' => [] === $duplicateKeys ? 'ok' : implode(', ', array_values(array_unique($duplicateKeys))),
            ],
            [
                'check' => 'permissions_have_descriptors',
                'valid' => [] === $missingDescriptorKeys,
                'details' => [] === $missingDescriptorKeys ? 'ok' : implode(', ', $missingDescriptorKeys),
            ],
        ];
    }

    /** @return list<array{contract: string, implementation: string}> */
    private function serviceRows(): array
    {
        return [
            ['contract' => AdministrationCurrentUserContextProviderInterface::class, 'implementation' => $this->currentUserContextProvider::class],
            ['contract' => AdministrationPermissionCheckerInterface::class, 'implementation' => $this->permissionChecker::class],
            ['contract' => AdministrationExternalPermissionDecisionProviderInterface::class, 'implementation' => $this->externalPermissionDecisionProvider::class],
            ['contract' => AdministrationOperationQueueInterface::class, 'implementation' => $this->operationQueue::class],
            ['contract' => AdministrationOperationRunnerInterface::class, 'implementation' => $this->operationRunner::class],
            ['contract' => AdministrationOperationStatusRecorderInterface::class, 'implementation' => $this->operationStatusRecorder::class],
            ['contract' => AdministrationOperationReportProviderInterface::class, 'implementation' => $this->operationReportProvider::class],
            ['contract' => AdministrationOperationRunMessageHandler::class, 'implementation' => $this->operationRunMessageHandler::class],
            ['contract' => RollingAdministrationPermissionCatalogInterface::class, 'implementation' => $this->rollingPermissionCatalog::class],
            ['contract' => RollingAdministrationPermissionDecisionServiceInterface::class, 'implementation' => $this->rollingPermissionDecisionService::class],
        ];
    }
}
