<?php

declare(strict_types=1);

namespace App\Administering\Service\Rolling;

use App\Administering\Entity\AdministrationAclMutationApplyRecordEntity;
use App\Administering\Entity\AdministrationAclMutationReviewRecordEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyServiceInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Managing\AdministrationManagingAclMutationApplyResult;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationReview;

/**
 * Builds a controlled apply request from an existing Administering review record
 * and delegates execution to Rolling-owned ACL administration services.
 */
final readonly class AdministrationRollingAclMutationApplyService implements AdministrationAclMutationApplyServiceInterface, AdministrationServiceToolHandlerInterface
{
    public function __construct(
        private AdministrationPersistenceRepository $persistenceRepository,
        private AdministrationAuditRecorderInterface $auditRecorder,
        private AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
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

    public function applyReviewedMutation(string $requestKey, string $requestedBySubject): AdministrationManagingAclMutationApplyResult
    {
        $record = $this->persistenceRepository->findOneBy(AdministrationAclMutationReviewRecordEntity::class, ['requestKey' => $requestKey]);

        if (!$record instanceof AdministrationAclMutationReviewRecordEntity) {
            return AdministrationManagingAclMutationApplyResult::skipped(
                $requestKey,
                'ACL mutation review record was not found.',
                ['reason' => 'missing_review_record'],
            );
        }

        if (!$record->valid()) {
            $result = AdministrationManagingAclMutationApplyResult::rejected(
                $requestKey,
                'ACL mutation review is invalid and cannot be applied.',
                ['reason' => 'invalid_review_record'],
            );
            $this->recordApplyAttempt($record, $requestedBySubject, $result);

            return $result;
        }

        $review = new AdministrationRollingAclMutationReview(
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

        $result = AdministrationManagingAclMutationApplyResult::skipped(
            $record->requestKey(),
            'Rolling ACL mutation apply is dry-run only inside Administering standalone runtime.',
            [
                'review_valid' => $review->valid(),
                'requested_by_subject' => $requestedBySubject,
                'reason' => 'owner_rolling_runtime_not_connected',
                'mode' => 'administering_self_contained_dry_runtime',
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
    private function executionSafeContext(AdministrationServiceToolInvocation $invocation, AdministrationManagingAclMutationApplyResult $result): array
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
        AdministrationAclMutationReviewRecordEntity $record,
        string $requestedBySubject,
        AdministrationManagingAclMutationApplyResult $result,
    ): void {
        $applyRecord = new AdministrationAclMutationApplyRecordEntity(
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

        $this->persistenceRepository->persist($applyRecord);

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
}
