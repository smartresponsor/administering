# Administering entity-first migration retirement

## Scope

This patch moves Administering away from migration-first schema ownership.
The current component already contained Doctrine entities for every table created by `Administering/migrations/**`; this pass makes those entities the canonical schema source and retires the migration files.

## Retired schema-first sources

- `Administering/migrations/**`

## Entity-first coverage

The following migration-owned tables are covered by Doctrine entities:

- `administration_audit_event`
- `administration_config_snapshot`
- `administration_change_request`
- `administration_credential_definition`
- `administration_credential_state`
- `administration_operation_run`
- `administration_operation_event`
- `administration_operation_artifact`
- `administration_acl_mutation_review_record`
- `administration_acl_mutation_apply_record`
- `administration_account_action_request_record`
- `administration_accessing_account_record`
- `administration_connected_component_record`
- `administration_environment_runtime_record`
- `administration_managing_field_control_record`
- `administration_symfony_route_record`
- `administration_service_section_record`
- `administration_config_application`
- `administration_config_tool`
- `administration_config_value`
- `administration_config_apply_log`
- `administration_service_tool_record`

## Objecting decision

No new duplicate generic system-field traits were introduced. Administering records are operational/system-storage read models and audit records; existing lifecycle columns such as `createdAt`, `checkedAt`, `synchronizedAt`, `appliedAt`, `status`, and checksums are retained as business/runtime evidence fields. Objecting remains the canonical source for reusable generic identity/audit/state packs when a model is migrated to shared system-field embeddables.

## Repository canon

Repository interfaces and concrete repositories were added for all Administering entity-first models covered by the retired migrations. Entity metadata now points to those repositories directly with `repositoryClass`.

## Legacy monolith review

`Entity-src(6).zip` was checked for an old Administration/Administering monolith. No legacy Administering aggregate was present, so no legacy relationships were available to port.

## Validation

PHP syntax lint was run for patched PHP files. Full Doctrine metadata validation still requires the Symfony runtime host and installed vendor dependencies.
