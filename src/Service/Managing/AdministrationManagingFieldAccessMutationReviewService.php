<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationReviewServiceInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\ValidatorInterface\Rolling\AdministrationFieldAccessPolicyDescriptorValidatorInterface;
use App\Administering\Value\Rolling\AdministrationFieldAccessMutationReviewInput;
use App\Administering\Value\Rolling\AdministrationFieldAccessMutationReviewResult;
use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationReviewBuilderInterface;
use App\Rolling\Value\Administration\RollingAclMutationRequest;
use App\Rolling\Value\Administration\RollingFieldAccessDecisionRequest;
use App\Rolling\Value\Administration\RollingFieldAccessScopeSet;

/**
 * Builds review-only Rolling ACL mutation requests for Managing field access policies.
 *
 * Administering owns the control-plane review surface; Rolling still owns effective ACL mutation semantics.
 */
final readonly class AdministrationManagingFieldAccessMutationReviewService implements AdministrationFieldAccessMutationReviewServiceInterface
{
    public function __construct(
        private RollingAclMutationReviewBuilderInterface $reviewBuilder,
        private AdministrationAclMutationReviewRecorderInterface $reviewRecorder,
        private AdministrationFieldAccessPolicyDescriptorValidatorInterface $descriptorValidator,
    ) {
    }

    public function review(AdministrationFieldAccessMutationReviewInput $input): AdministrationFieldAccessMutationReviewResult
    {
        $this->descriptorValidator->assertValid($input->descriptor);
        $mutationRequest = $this->toRollingMutationRequest($input);
        $review = $this->reviewBuilder->review($mutationRequest);
        $record = $this->reviewRecorder->record($mutationRequest, $review);

        return new AdministrationFieldAccessMutationReviewResult($input->descriptor, $review, $record);
    }

    private function toRollingMutationRequest(AdministrationFieldAccessMutationReviewInput $input): RollingAclMutationRequest
    {
        $descriptor = $input->descriptor;
        $scope = RollingFieldAccessScopeSet::fromRequest(new RollingFieldAccessDecisionRequest(
            permissionKey: $descriptor->permissionKey,
            componentKey: $descriptor->target->componentKey,
            resourceClass: $descriptor->target->resourceClass,
            fieldName: $descriptor->target->fieldName,
            pageName: $descriptor->target->pageName,
            operation: $descriptor->target->operation,
            subjectIdentifier: $this->subjectIdentifier($descriptor),
            attributes: $descriptor->target->attributes,
        ))->mostSpecificScope();

        return new RollingAclMutationRequest(
            $this->mutationType($descriptor),
            $this->subjectIdentifier($descriptor),
            $descriptor->permissionKey,
            $scope,
            $input->requestedBySubject,
            $input->toSafeContext(),
        );
    }

    private function mutationType(AdministrationFieldAccessPolicyDescriptor $descriptor): string
    {
        if (AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE === $descriptor->subjectType) {
            return $descriptor->allows() ? 'permission.grant' : 'permission.revoke';
        }

        return $descriptor->allows() ? 'acl.allow' : 'acl.deny';
    }

    private function subjectIdentifier(AdministrationFieldAccessPolicyDescriptor $descriptor): string
    {
        $identifier = trim($descriptor->subjectIdentifier);

        if (AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE === $descriptor->subjectType) {
            return $identifier;
        }

        if (str_contains($identifier, ':')) {
            return $identifier;
        }

        return sprintf('%s:%s', $descriptor->subjectType, $identifier);
    }
}
