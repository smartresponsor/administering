<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationApplyServiceInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyServiceInterface;
use App\Administering\Value\Rolling\AdministrationAclMutationApplyResult;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Applies only previously reviewed Managing field-access policy records.
 *
 * This service is a narrow Administering safeguard over the generic Rolling ACL
 * apply flow. It refuses unrelated Rolling ACL reviews so the Managing field
 * access surface cannot accidentally apply broader role or ACL mutations.
 */
final readonly class AdministrationManagingFieldAccessMutationApplyService implements AdministrationFieldAccessMutationApplyServiceInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AdministrationAclMutationApplyServiceInterface $aclMutationApplyService,
    ) {
    }

    public function applyReviewedFieldAccessMutation(string $requestKey, string $requestedBySubject): AdministrationAclMutationApplyResult
    {
        $requestKey = trim($requestKey);

        if ('' === $requestKey) {
            return AdministrationAclMutationApplyResult::skipped('', 'Managing field access review key is required.', [
                'reason' => 'missing_request_key',
                'surface' => 'managing_field_access_mutation_apply',
            ]);
        }

        $record = $this->reviewRecord($requestKey);

        if (!$record instanceof AdministrationAclMutationReviewRecord) {
            return AdministrationAclMutationApplyResult::skipped($requestKey, 'Managing field access review record was not found.', [
                'reason' => 'missing_review_record',
                'surface' => 'managing_field_access_mutation_apply',
            ]);
        }

        if (!$this->isManagingFieldAccessReview($record)) {
            return AdministrationAclMutationApplyResult::rejected($requestKey, 'Review record is not a Managing field access mutation review.', [
                'reason' => 'non_managing_field_access_review',
                'surface' => 'managing_field_access_mutation_apply',
                'permission_or_role_key' => $record->permissionOrRoleKey(),
                'scope_key' => $record->scopeKey(),
                'mutation_type' => $record->mutationType(),
            ]);
        }

        if (!$record->valid()) {
            return AdministrationAclMutationApplyResult::rejected($requestKey, 'Managing field access review is invalid and cannot be applied.', [
                'reason' => 'invalid_review_record',
                'surface' => 'managing_field_access_mutation_apply',
                'permission_or_role_key' => $record->permissionOrRoleKey(),
                'scope_key' => $record->scopeKey(),
            ]);
        }

        return $this->aclMutationApplyService->applyReviewedMutation($requestKey, $requestedBySubject);
    }

    private function reviewRecord(string $requestKey): ?AdministrationAclMutationReviewRecord
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationAclMutationReviewRecord::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering ACL mutation review records. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        $record = $manager
            ->getRepository(AdministrationAclMutationReviewRecord::class)
            ->findOneBy(['requestKey' => $requestKey]);

        return $record instanceof AdministrationAclMutationReviewRecord ? $record : null;
    }

    private function isManagingFieldAccessReview(AdministrationAclMutationReviewRecord $record): bool
    {
        if (!str_starts_with($record->permissionOrRoleKey(), 'managing.field.')) {
            return false;
        }

        if (!str_starts_with($record->scopeKey(), 'component:managing')) {
            return false;
        }

        if (!in_array($record->mutationType(), ['permission.grant', 'permission.revoke', 'acl.allow', 'acl.deny'], true)) {
            return false;
        }

        $safeContext = $record->safeReviewPayload()['safe_context'] ?? [];

        if (!is_array($safeContext)) {
            return true;
        }

        $target = $safeContext['target'] ?? null;

        if (is_array($target) && isset($target['component'])) {
            return 'Managing' === $target['component'] || 'managing' === strtolower((string) $target['component']);
        }

        $surface = $safeContext['surface'] ?? null;

        if (is_string($surface) && 'managing_field_access_mutation_review' === $surface) {
            return true;
        }

        return in_array($record->permissionOrRoleKey(), AdministrationManagingFieldPermissionVocabulary::policyKeys(), true);
    }
}
