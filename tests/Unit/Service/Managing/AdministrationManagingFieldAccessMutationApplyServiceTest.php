<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Service\Managing;

use App\Administering\Service\Managing\AdministrationManagingFieldAccessMutationApplyService;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\AdministrationCurrentUserContext;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessMutationApplyServiceTest extends TestCase
{
    public function testHandleFailsWhenReviewRequestKeyIsMissing(): void
    {
        $provider = $this->createMock(AdministrationCurrentUserContextProviderInterface::class);
        $provider->expects(self::never())->method('current');

        $result = (new AdministrationManagingFieldAccessMutationApplyService($provider))
            ->handleAdministrationServiceTool($this->invocation([]));

        self::assertFalse($result->successful());
        self::assertSame('failed', $result->status());
        self::assertSame(
            'Review request key is required to apply a Managing field-access mutation.',
            $result->safeMessage(),
        );
        self::assertSame([
            'tool_key' => 'managing.field-access.apply',
            'reason' => 'missing_request_key',
        ], $result->safeContext());
    }

    public function testHandleProducesDryRunFailureWithAuthenticatedSubject(): void
    {
        $provider = $this->createMock(AdministrationCurrentUserContextProviderInterface::class);
        $provider->expects(self::once())->method('current')->willReturn(
            new AdministrationCurrentUserContext('subject:42', 'user@example.test', ['ROLE_ADMIN']),
        );

        $result = (new AdministrationManagingFieldAccessMutationApplyService($provider))
            ->handleAdministrationServiceTool($this->invocation(['requestKey' => ' review-123 ']));

        self::assertFalse($result->successful());
        self::assertSame('failed', $result->status());
        self::assertSame(
            'Managing field-access apply is dry-run only inside Administering standalone runtime.',
            $result->safeMessage(),
        );

        $context = $result->safeContext();
        self::assertSame('managing.field-access.apply', $context['tool_key']);
        self::assertSame('managing', $context['section_key']);
        self::assertSame('field-access-apply', $context['tool_slug']);
        self::assertSame('review-123', $context['request_key']);
        self::assertSame('skipped', $context['result_status']);
        self::assertFalse($context['result_succeeded']);
        self::assertSame('subject:42', $context['result_context']['requested_by_subject']);
        self::assertSame('owner_managing_runtime_not_connected', $context['result_context']['reason']);
        self::assertSame('administering_self_contained_dry_runtime', $context['result_context']['mode']);
    }

    public function testHandleUsesStandaloneFallbackSubjectWhenCurrentUserIsUnavailable(): void
    {
        $provider = $this->createMock(AdministrationCurrentUserContextProviderInterface::class);
        $provider->expects(self::once())->method('current')->willReturn(null);

        $result = (new AdministrationManagingFieldAccessMutationApplyService($provider))
            ->handleAdministrationServiceTool($this->invocation(['requestKey' => 'review-standalone']));

        self::assertSame(
            'administering:service-tool',
            $result->safeContext()['result_context']['requested_by_subject'],
        );
    }

    public function testDirectApplyReturnsExplicitSkippedResult(): void
    {
        $provider = $this->createMock(AdministrationCurrentUserContextProviderInterface::class);

        $result = (new AdministrationManagingFieldAccessMutationApplyService($provider))
            ->applyReviewedFieldAccessMutation('review-1', 'subject:test');

        self::assertFalse($result->succeeded());
        self::assertSame('skipped', $result->status());
        self::assertSame('review-1', $result->requestKey());
        self::assertSame(
            'Managing field-access apply is dry-run only inside Administering standalone runtime.',
            $result->safeMessage(),
        );
        self::assertSame([
            'requested_by_subject' => 'subject:test',
            'reason' => 'owner_managing_runtime_not_connected',
            'mode' => 'administering_self_contained_dry_runtime',
        ], $result->safeContext());
    }

    /** @param array<string, mixed> $formData */
    private function invocation(array $formData): AdministrationServiceToolInvocation
    {
        return new AdministrationServiceToolInvocation(
            operationKey: 'operation-1',
            toolKey: 'managing.field-access.apply',
            sectionKey: 'managing',
            toolSlug: 'field-access-apply',
            serviceClass: AdministrationManagingFieldAccessMutationApplyService::class,
            serviceFile: null,
            formTypeClass: null,
            formDataClass: null,
            executable: true,
            sourceOwnership: 'administering_internal',
            ownerComponentKey: null,
            ownerComponentToken: null,
            ownerProviderClass: null,
            ownerServiceClass: null,
            ownerSourceLabel: null,
            formData: $formData,
            safeContext: [],
        );
    }
}
