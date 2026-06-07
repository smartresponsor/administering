<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationReviewServiceInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\Value\Managing\ManagingFieldAccessMutationReviewInput;
use App\Administering\Value\Managing\ManagingFieldAccessMutationReviewResult;
use App\Administering\Value\Managing\ManagingFieldAccessPolicyDescriptor;
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

    public function review(ManagingFieldAccessMutationReviewInput $input): ManagingFieldAccessMutationReviewResult
    {
        $request = $this->toRollingMutationRequest($input);
        $review = $this->buildReview($input, $request);
        $record = $this->reviewRecorder->record($request, $review);

        return new ManagingFieldAccessMutationReviewResult(
            $input->descriptor,
            $review,
            $record->requestKey(),
        );
    }

    private function buildReview(
        ManagingFieldAccessMutationReviewInput $input,
        AdministrationRollingAclMutationRequest $request,
    ): \App\Administering\Value\Rolling\AdministrationRollingAclMutationReview {
        $descriptor = $input->descriptor;
        $violations = [];
        foreach ([
            'permission key' => $descriptor->permissionKey,
            'subject type' => $descriptor->subjectType,
            'subject identifier' => $descriptor->subjectIdentifier,
            'resource class' => $descriptor->target->resourceClass,
            'field name' => $descriptor->target->fieldName,
            'page name' => $descriptor->target->pageName,
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

    private function toRollingMutationRequest(ManagingFieldAccessMutationReviewInput $input): AdministrationRollingAclMutationRequest
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

    private function mutationType(ManagingFieldAccessPolicyDescriptor $descriptor): string
    {
        if (ManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE === $descriptor->subjectType) {
            return $descriptor->allows() ? 'permission.grant' : 'permission.revoke';
        }

        return $descriptor->allows() ? 'acl.allow' : 'acl.deny';
    }

    private function subjectIdentifier(ManagingFieldAccessPolicyDescriptor $descriptor): string
    {
        $identifier = trim($descriptor->subjectIdentifier);

        if (ManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE === $descriptor->subjectType) {
            return $identifier;
        }

        if (str_contains($identifier, ':')) {
            return $identifier;
        }

        return sprintf('%s:%s', $descriptor->subjectType, $identifier);
    }
}
