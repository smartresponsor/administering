<?php

declare(strict_types=1);

namespace App\Administering\Service\Rolling;

use App\Administering\Entity\AdministrationAclMutationApplyRecord;
use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyServiceInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;
use App\Managing\Value\Administration\ManagingAclMutationApplyResult;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationApplyServiceInterface as RollingAclMutationApplyServiceContract;
use App\Rolling\Value\Administration\RollingAclMutationReview;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Builds a controlled apply request from an existing Administering review record
 * and delegates execution to Rolling-owned ACL administration services.
 */
final readonly class AdministrationRollingAclMutationApplyService implements AdministrationAclMutationApplyServiceInterface, AdministrationServiceToolHandlerInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AdministrationAuditRecorderInterface $auditRecorder,
        private AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private RollingAclMutationApplyServiceContract $rollingAclMutationApplyService,
    ) {
    }

    public function handleAdministrationServiceTool(AdministrationServiceToolInvocation $invocation): AdministrationOperationExecutionResult
    {
        $requestKey = $invocation->stringFormValue('requestKey');
        if ('' === $requestKey) {
            return AdministrationOperationExecutionResult::failed('Review request key is required to apply a Rolling ACL mutation.', [
                'tool_key' => $invocation->toolKey,
                'reason' => 'missing_request_key',
            ]);
        }

        $result = $this->applyReviewedMutation($requestKey, $this->requestedBySubject());

        return $result->succeeded()
            ? AdministrationOperationExecutionResult::succeeded($result->safeMessage(), $this->executionSafeContext($invocation, $result))
            : AdministrationOperationExecutionResult::failed($result->safeMessage(), $this->executionSafeContext($invocation, $result));
    }

    public function applyReviewedMutation(string $requestKey, string $requestedBySubject): ManagingAclMutationApplyResult
    {
        $manager = $this->manager();

        $record = $manager
            ->getRepository(AdministrationAclMutationReviewRecord::class)
            ->findOneBy(['requestKey' => $requestKey]);

        if (!$record instanceof AdministrationAclMutationReviewRecord) {
            return ManagingAclMutationApplyResult::skipped(
                $requestKey,
                'ACL mutation review record was not found.',
                ['reason' => 'missing_review_record'],
            );
        }

        if (!$record->valid()) {
            $result = ManagingAclMutationApplyResult::rejected(
                $requestKey,
                'ACL mutation review is invalid and cannot be applied.',
                ['reason' => 'invalid_review_record'],
            );
            $this->recordApplyAttempt($record, $requestedBySubject, $result);

            return $result;
        }

        $review = new RollingAclMutationReview(
            $record->mutationType(),
            $record->subjectIdentifier(),
            $record->permissionOrRoleKey(),
            $record->scopeKey(),
            $record->valid(),
            $this->stringList($record->safeReviewPayload()['steps'] ?? []),
            $this->stringList($record->safeReviewPayload()['warnings'] ?? []),
            $this->stringList($record->safeReviewPayload()['violations'] ?? []),
            $this->safeContext($record->safeReviewPayload()['safe_context'] ?? []),
        );

        $rollingResult = $this->rollingAclMutationApplyService->applyReviewedMutation(
            $record->requestKey(),
            $review,
            $requestedBySubject,
        );

        $result = ManagingAclMutationApplyResult::fromRollingResult(
            $record->requestKey(),
            $rollingResult->succeeded(),
            $rollingResult->status(),
            $rollingResult->safeMessage(),
            $rollingResult->safeContext() + [
                'review_valid' => $review->valid(),
            ],
        );

        $this->recordApplyAttempt($record, $requestedBySubject, $result);

        return $result;
    }

    private function requestedBySubject(): string
    {
        return $this->currentUserContextProvider->current()?->subjectIdentifier() ?? 'administering:service-tool';
    }

    /** @return array<string, mixed> */
    private function executionSafeContext(AdministrationServiceToolInvocation $invocation, ManagingAclMutationApplyResult $result): array
    {
        return [
            'tool_key' => $invocation->toolKey,
            'section_key' => $invocation->sectionKey,
            'tool_slug' => $invocation->toolSlug,
            'request_key' => $result->requestKey(),
            'result_status' => $result->status(),
            'result_succeeded' => $result->succeeded(),
            'result_context' => $result->safeContext(),
        ];
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (mixed $item): string => (string) $item, $value)));
    }

    /** @return array<string, mixed> */
    private function safeContext(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function recordApplyAttempt(
        AdministrationAclMutationReviewRecord $record,
        string $requestedBySubject,
        ManagingAclMutationApplyResult $result,
    ): void {
        $applyRecord = new AdministrationAclMutationApplyRecord(
            $record->requestKey(),
            $record->mutationType(),
            $record->subjectIdentifier(),
            $record->permissionOrRoleKey(),
            $record->scopeKey(),
            $requestedBySubject,
            $result->status(),
            $result->succeeded(),
            $result->safeMessage(),
            $result->toSafeArray(),
        );

        $manager = $this->manager();
        $manager->persist($applyRecord);
        $manager->flush();

        $this->auditRecorder->record('administration.rolling.acl_mutation.applied', $requestedBySubject, [
            'request_key' => $record->requestKey(),
            'mutation_type' => $record->mutationType(),
            'subject_identifier' => $record->subjectIdentifier(),
            'permission_or_role_key' => $record->permissionOrRoleKey(),
            'scope_key' => $record->scopeKey(),
            'status' => $result->status(),
            'succeeded' => $result->succeeded(),
        ]);
    }

    private function manager(): \Doctrine\Persistence\ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationAclMutationReviewRecord::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering ACL mutation records. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        return $manager;
    }
}
