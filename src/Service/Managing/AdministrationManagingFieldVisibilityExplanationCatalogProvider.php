<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityExplanationCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityExplanationScenario;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityExplanationStep;

/**
 * Documents the read-only Administering view of Managing field visibility diagnostics.
 */
final readonly class AdministrationManagingFieldVisibilityExplanationCatalogProvider implements AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    public function explanationSteps(): array
    {
        return [
            new AdministrationFieldVisibilityExplanationStep(
                10,
                'Page availability',
                'Managing',
                AdministrationFieldVisibilityExplanationStep::AXIS_AVAILABILITY,
                'deny/pass',
                'terminal on unavailable',
                'field_not_available_on_page',
                'Availability removes fields unavailable for index/detail/new/edit before access or profile checks.',
            ),
            new AdministrationFieldVisibilityExplanationStep(
                20,
                'Backend access deny config',
                'Managing',
                AdministrationFieldVisibilityExplanationStep::AXIS_ACCESS,
                'deny/pass',
                'terminal on denied',
                'backend_configured_denied',
                'A configured deny is an access-axis decision and cannot be overridden by user profiles.',
            ),
            new AdministrationFieldVisibilityExplanationStep(
                25,
                'Backend presentation config',
                'Managing',
                AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal',
                'backend_configured_hidden',
                'Configured visible/hidden rules shape presentation inside an already allowed access corridor.',
            ),
            new AdministrationFieldVisibilityExplanationStep(
                30,
                'External field-value access decision',
                'Rolling',
                AdministrationFieldVisibilityExplanationStep::AXIS_ACCESS,
                'allow/deny/abstain',
                'terminal on deny',
                'rolling_field_value_access_denied',
                'Rolling deny blocks field values; allow only opens access and never forces presentation visibility.',
            ),
            new AdministrationFieldVisibilityExplanationStep(
                40,
                'Field definition default',
                'Managing',
                AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden',
                'non-terminal',
                'field_default_hidden',
                'Metadata defaults provide presentation when no stronger backend presentation rule decided.',
            ),
            new AdministrationFieldVisibilityExplanationStep(
                50,
                'User personal profile',
                'Managing',
                AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal unless rejected',
                'user_profile_hidden',
                'User preference may only affect already allowed and hideable presentation fields.',
            ),
            new AdministrationFieldVisibilityExplanationStep(
                60,
                'Final EasyAdmin emission',
                'Managing',
                AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION,
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
            new AdministrationFieldVisibilityExplanationScenario(
                'rolling-deny',
                'Rolling denies field-value access',
                AdministrationFieldVisibilityExplanationStep::AXIS_ACCESS,
                'denied / not emitted',
                ['page_available', 'backend_visibility_rule_not_configured', 'rolling_field_value_access_denied'],
                'No profile or UI preference can show the field after an access-axis deny.',
            ),
            new AdministrationFieldVisibilityExplanationScenario(
                'user-hidden',
                'User hides an allowed field',
                AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'hidden / not emitted',
                ['page_available', 'rolling_field_value_access_allowed', 'field_default_visible', 'user_profile_hidden'],
                'The user already had field-value access but chose not to render the field.',
            ),
            new AdministrationFieldVisibilityExplanationScenario(
                'required-form-field',
                'User tries to hide a required form field',
                AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible / emitted',
                ['page_available', 'field_default_visible', 'user_profile_hide_not_allowed'],
                'Required or non-hideable fields stay visible on new/edit when a profile asks to hide them.',
            ),
            new AdministrationFieldVisibilityExplanationScenario(
                'backend-hidden-user-visible',
                'User shows a backend-hidden presentation default',
                AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible / emitted when access is allowed',
                ['page_available', 'backend_configured_hidden', 'rolling_field_value_access_allowed', 'user_profile_visible'],
                'Hidden is a presentation default, not a deny; the user profile still needs an allowed access corridor.',
            ),
            new AdministrationFieldVisibilityExplanationScenario(
                'page-unavailable',
                'Field is unavailable on the requested page',
                AdministrationFieldVisibilityExplanationStep::AXIS_AVAILABILITY,
                'denied / not emitted',
                ['field_not_available_on_page'],
                'Availability-axis denial happens before access and presentation layers are evaluated.',
            ),
        ];
    }
}
