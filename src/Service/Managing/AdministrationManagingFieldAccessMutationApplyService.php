<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationApplyServiceInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Managing\ManagingAclMutationApplyResult;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Self-contained dry-runtime apply surface for reviewed Managing field-access mutations.
 */
final readonly class AdministrationManagingFieldAccessMutationApplyService implements AdministrationFieldAccessMutationApplyServiceInterface, AdministrationServiceToolHandlerInterface
{
    public function __construct(
        private AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    public function handleAdministrationServiceTool(AdministrationServiceToolInvocation $invocation): AdministrationOperationExecutionResult
    {
        $requestKey = $invocation->stringFormValue('requestKey');
        if ('' === $requestKey) {
            return AdministrationOperationExecutionResult::failed('Review request key is required to apply a Managing field-access mutation.', [
                'tool_key' => $invocation->toolKey,
                'reason' => 'missing_request_key',
            ]);
        }

        $result = $this->applyReviewedFieldAccessMutation($requestKey, $this->requestedBySubject());

        return $result->succeeded()
            ? AdministrationOperationExecutionResult::succeeded($result->safeMessage(), $this->executionSafeContext($invocation, $result))
            : AdministrationOperationExecutionResult::failed($result->safeMessage(), $this->executionSafeContext($invocation, $result));
    }

    public function applyReviewedFieldAccessMutation(string $requestKey, string $requestedBySubject): ManagingAclMutationApplyResult
    {
        return ManagingAclMutationApplyResult::skipped($requestKey, 'Managing field-access apply is dry-run only inside Administering standalone runtime.', [
            'requested_by_subject' => $requestedBySubject,
            'reason' => 'owner_managing_runtime_not_connected',
            'mode' => 'administering_self_contained_dry_runtime',
        ]);
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
}
