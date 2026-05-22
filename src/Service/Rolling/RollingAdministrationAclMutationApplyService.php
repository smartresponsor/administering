<?php

declare(strict_types=1);

namespace App\Administering\Service\Rolling;

use App\Administering\Entity\AdministrationAclMutationApplyRecord;
use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyServiceInterface;
use App\Administering\Value\Rolling\AdministrationAclMutationApplyResult;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationApplyRequestBuilderInterface;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationExecutionGatewayInterface;
use App\Rolling\Value\Administration\RollingAclMutationReview;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Builds a controlled apply request from an existing Administering review record
 * and delegates execution to Rolling-owned ACL administration services.
 */
final readonly class RollingAdministrationAclMutationApplyService implements AdministrationAclMutationApplyServiceInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AdministrationAuditRecorderInterface $auditRecorder,
        private RollingAclMutationApplyRequestBuilderInterface $applyRequestBuilder,
        private RollingAclMutationExecutionGatewayInterface $executionGateway,
    ) {
    }

    public function applyReviewedMutation(string $requestKey, string $requestedBySubject): AdministrationAclMutationApplyResult
    {
        $manager = $this->manager();

        $record = $manager
            ->getRepository(AdministrationAclMutationReviewRecord::class)
            ->findOneBy(['requestKey' => $requestKey]);

        if (!$record instanceof AdministrationAclMutationReviewRecord) {
            return AdministrationAclMutationApplyResult::skipped(
                $requestKey,
                'ACL mutation review record was not found.',
                ['reason' => 'missing_review_record'],
            );
        }

        if (!$record->valid()) {
            $result = AdministrationAclMutationApplyResult::rejected(
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

        $applyRequest = $this->applyRequestBuilder->fromReview($record->requestKey(), $review, $requestedBySubject);
        $rollingResult = $this->executionGateway->execute($applyRequest);

        $result = AdministrationAclMutationApplyResult::fromRollingResult(
            $record->requestKey(),
            $rollingResult->succeeded(),
            $rollingResult->status(),
            $rollingResult->safeMessage(),
            $rollingResult->safeContext() + [
                'apply_request_key' => $applyRequest->requestKey(),
                'review_valid' => $applyRequest->reviewValid(),
            ],
        );

        $this->recordApplyAttempt($record, $requestedBySubject, $result);

        return $result;
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
        AdministrationAclMutationApplyResult $result,
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
