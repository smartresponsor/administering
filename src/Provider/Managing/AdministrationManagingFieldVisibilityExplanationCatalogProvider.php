<?php

declare(strict_types=1);

namespace App\Administering\Provider\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityExplanationCatalogProviderInterface;
use App\Managing\Value\Administration\ManagingFieldVisibilityExplanationScenario;
use App\Managing\Value\Administration\ManagingFieldVisibilityExplanationStep;

/**
 * Documents the read-only Administering view of Managing field visibility diagnostics.
 */
final readonly class AdministrationManagingFieldVisibilityExplanationCatalogProvider implements AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    public function explanationSteps(): array
    {
        return [
            new ManagingFieldVisibilityExplanationStep(
                10,
                'Page availability',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_AVAILABILITY,
                'deny/pass',
                'terminal on unavailable',
                'field_not_available_on_page',
                'Availability removes fields unavailable for index/detail/new/edit before access or profile checks.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                20,
                'Backend access deny config',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_ACCESS,
                'deny/pass',
                'terminal on denied',
                'backend_configured_denied',
                'A configured deny is an access-axis decision and cannot be overridden by user profiles.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                25,
                'Backend presentation config',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal',
                'backend_configured_hidden',
                'Configured visible/hidden rules shape presentation inside an already allowed access corridor.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                30,
                'External field-value access decision',
                'Rolling',
                ManagingFieldVisibilityExplanationStep::AXIS_ACCESS,
                'allow/deny/abstain',
                'terminal on deny',
                'rolling_field_value_access_denied',
                'Rolling deny blocks field values; allow only opens access and never forces presentation visibility.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                40,
                'Field definition default',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden',
                'non-terminal',
                'field_default_hidden',
                'Metadata defaults provide presentation when no stronger backend presentation rule decided.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                50,
                'User personal profile',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal unless rejected',
                'user_profile_hidden',
                'User preference may only affect already allowed and hideable presentation fields.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                60,
                'Final EasyAdmin emission',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'render/not-render',
                'final',
                'final_renderable',
                'EasyAdmin receives only fields that remain access-allowed and presentation-visible.',
            ),
        ];
    }

    public function diagnosticScenarios(): array
    {
        return [
            new ManagingFieldVisibilityExplanationScenario(
                'rolling-deny',
                'Rolling denies field-value access',
                ManagingFieldVisibilityExplanationStep::AXIS_ACCESS,
                'denied / not emitted',
                ['page_available', 'backend_visibility_rule_not_configured', 'rolling_field_value_access_denied'],
                'No profile or UI preference can show the field after an access-axis deny.',
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'user-hidden',
                'User hides an allowed field',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'hidden / not emitted',
                ['page_available', 'rolling_field_value_access_allowed', 'field_default_visible', 'user_profile_hidden'],
                'The user already had field-value access but chose not to render the field.',
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'required-form-field',
                'User tries to hide a required form field',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible / emitted',
                ['page_available', 'field_default_visible', 'user_profile_hide_not_allowed'],
                'Required or non-hideable fields stay visible on new/edit when a profile asks to hide them.',
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'backend-hidden-user-visible',
                'User shows a backend-hidden presentation default',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible / emitted when access is allowed',
                ['page_available', 'backend_configured_hidden', 'rolling_field_value_access_allowed', 'user_profile_visible'],
                'Hidden is a presentation default, not a deny; the user profile still needs an allowed access corridor.',
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'page-unavailable',
                'Field is unavailable on the requested page',
                ManagingFieldVisibilityExplanationStep::AXIS_AVAILABILITY,
                'denied / not emitted',
                ['field_not_available_on_page'],
                'Availability-axis denial happens before access and presentation layers are evaluated.',
            ),
        ];
    }
}
