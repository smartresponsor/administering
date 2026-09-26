<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationReviewServiceInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\Value\Managing\AdministrationManagingFieldAccessMutationReviewInput;
use App\Administering\Value\Managing\AdministrationManagingFieldAccessMutationReviewResult;
use App\Administering\Value\Managing\AdministrationManagingFieldAccessPolicyDescriptor;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationRequest;
use App\Administering\Value\Rolling\AdministrationRollingFieldAccessDecisionRequest;
use App\Administering\Value\Rolling\AdministrationRollingFieldAccessScopeSet;

/**
 * Thin Administering adapter for the owner-side Managing field access review service.
 */
final readonly class AdministrationManagingFieldAccessMutationReviewService implements AdministrationFieldAccessMutationReviewServiceInterface
{
    public function __construct(
        private AdministrationAclMutationReviewRecorderInterface $reviewRecorder,
    ) {
    }

    public function review(AdministrationManagingFieldAccessMutationReviewInput $input): AdministrationManagingFieldAccessMutationReviewResult
    {
        $request = $this->toRollingMutationRequest($input);
        $review = $this->buildReview($input, $request);
        $record = $this->reviewRecorder->record($request, $review);

        return new AdministrationManagingFieldAccessMutationReviewResult(
            $input->descriptor,
            $review,
            $record->requestKey(),
        );
    }

    private function buildReview(
        AdministrationManagingFieldAccessMutationReviewInput $input,
        AdministrationRollingAclMutationRequest $request,
    ): \App\Administering\Value\Rolling\AdministrationRollingAclMutationReview {
        $descriptor = $input->descriptor;
        $violations = [];
        foreach ([
            'permission key' => $descriptor->permissionKey,
            'subject type' => $descriptor->subjectType,
            'subject identifier' => $descriptor->subjectIdentifier,
            'resource class' => $descriptor->target->resourceClass,
            'field nameEntity' => $descriptor->target->fieldName,
            'page nameEntity' => $descriptor->target->pageName,
            'operation' => $descriptor->target->operation,
        ] as $label => $value) {
            if ('' === trim((string) $value)) {
                $violations[] = sprintf('Missing %s.', $label);
            }
        }

        return new \App\Administering\Value\Rolling\AdministrationRollingAclMutationReview(
            $request->mutationType(),
            $request->subjectIdentifier(),
            $request->permissionOrRoleKey(),
            $request->scopeKey(),
            [] === $violations,
            [
                'Collected Managing field-access mutation request.',
                'Computed Administering-owned Rolling-compatible scope key.',
                'Persisted safe review metadata without calling Managing or Rolling services.',
            ],
            [],
            $violations,
            $request->safeContext(),
        );
    }

    private function toRollingMutationRequest(AdministrationManagingFieldAccessMutationReviewInput $input): AdministrationRollingAclMutationRequest
    {
        $descriptor = $input->descriptor;
        $scope = AdministrationRollingFieldAccessScopeSet::fromRequest(new AdministrationRollingFieldAccessDecisionRequest(
            permissionKey: $descriptor->permissionKey,
            componentKey: $descriptor->target->componentKey,
            resourceClass: $descriptor->target->resourceClass,
            fieldName: $descriptor->target->fieldName,
            pageName: $descriptor->target->pageName,
            operation: $descriptor->target->operation,
            subjectIdentifier: $this->subjectIdentifier($descriptor),
            attributes: $descriptor->target->attributes,
        ))->mostSpecificScope();

        return new AdministrationRollingAclMutationRequest(
            $this->mutationType($descriptor),
            $this->subjectIdentifier($descriptor),
            $descriptor->permissionKey,
            $scope,
            $input->requestedBySubject,
            $input->toSafeContext(),
        );
    }

    private function mutationType(AdministrationManagingFieldAccessPolicyDescriptor $descriptor): string
    {
        if (AdministrationManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE === $descriptor->subjectType) {
            return $descriptor->allows() ? 'permission.grant' : 'permission.revoke';
        }

        return $descriptor->allows() ? 'acl.allow' : 'acl.deny';
    }

    private function subjectIdentifier(AdministrationManagingFieldAccessPolicyDescriptor $descriptor): string
    {
        $identifier = trim($descriptor->subjectIdentifier);

        if (AdministrationManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE === $descriptor->subjectType) {
            return $identifier;
        }

        if (str_contains($identifier, ':')) {
            return $identifier;
        }

        return sprintf('%s:%s', $descriptor->subjectType, $identifier);
    }
}
