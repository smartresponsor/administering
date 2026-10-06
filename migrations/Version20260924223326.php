<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924223326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_accessing_account_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              account_reference VARCHAR(180) NOT NULL,
              display_label VARCHAR(190) NOT NULL,
              status VARCHAR(40) NOT NULL,
              provider VARCHAR(80) NOT NULL,
              safe_context CLOB NOT NULL,
              synchronized_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_accessing_account_status ON administration_accessing_account_record (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_accessing_account_reference ON administration_accessing_account_record (account_reference)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_account_action_request_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              request_key VARCHAR(180) NOT NULL,
              "action" VARCHAR(120) NOT NULL,
              account_reference VARCHAR(180) NOT NULL,
              requested_by_subject VARCHAR(180) NOT NULL,
              status VARCHAR(40) NOT NULL,
              safe_reason CLOB NOT NULL,
              safe_result_message CLOB NOT NULL,
              safe_context CLOB NOT NULL,
              created_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_account_action_request_key ON administration_account_action_request_record (request_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_account_action_account ON administration_account_action_request_record (account_reference)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_account_action_status ON administration_account_action_request_record (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_acl_mutation_apply_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              request_key VARCHAR(180) NOT NULL,
              mutation_type VARCHAR(80) NOT NULL,
              subject_identifier VARCHAR(180) NOT NULL,
              permission_or_role_key VARCHAR(180) NOT NULL,
              scope_key VARCHAR(180) NOT NULL,
              requested_by_subject VARCHAR(180) NOT NULL,
              status VARCHAR(40) NOT NULL,
              succeeded BOOLEAN NOT NULL,
              safe_message VARCHAR(500) NOT NULL,
              safe_result_payload CLOB NOT NULL,
              created_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_acl_apply_request_key ON administration_acl_mutation_apply_record (request_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_acl_apply_status ON administration_acl_mutation_apply_record (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_acl_mutation_review_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              request_key VARCHAR(180) NOT NULL,
              mutation_type VARCHAR(80) NOT NULL,
              subject_identifier VARCHAR(180) NOT NULL,
              permission_or_role_key VARCHAR(180) NOT NULL,
              scope_key VARCHAR(180) NOT NULL,
              requested_by_subject VARCHAR(180) NOT NULL,
              valid BOOLEAN NOT NULL,
              safe_review_payload CLOB NOT NULL,
              created_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_acl_review_request_key ON administration_acl_mutation_review_record (request_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_acl_review_subject ON administration_acl_mutation_review_record (subject_identifier)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_acl_review_permission ON administration_acl_mutation_review_record (permission_or_role_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_audit_event (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              "action" VARCHAR(190) NOT NULL,
              subject_identifier VARCHAR(190) NOT NULL,
              context CLOB NOT NULL,
              created_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql('CREATE INDEX idx_administration_audit_event_action ON administration_audit_event ("action")');
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_audit_event_subject ON administration_audit_event (subject_identifier)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_change_request (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              request_key VARCHAR(160) NOT NULL,
              change_type VARCHAR(80) NOT NULL,
              target_reference VARCHAR(240) NOT NULL,
              status VARCHAR(40) NOT NULL,
              payload CLOB NOT NULL,
              created_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_change_request_request_key ON administration_change_request (request_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_config_application (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              application_code VARCHAR(120) NOT NULL,
              label VARCHAR(180) NOT NULL,
              root_path VARCHAR(255) NOT NULL,
              manifest_path VARCHAR(255) NOT NULL,
              status VARCHAR(40) NOT NULL,
              enabled BOOLEAN NOT NULL,
              checksum VARCHAR(64) NOT NULL,
              discovered_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_config_application_code ON administration_config_application (application_code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_config_apply_log (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              application_code VARCHAR(120) NOT NULL,
              tool_code VARCHAR(160) NOT NULL,
              actor_identifier VARCHAR(180) NOT NULL,
              status VARCHAR(40) NOT NULL,
              changed_fields CLOB NOT NULL,
              masked_secrets CLOB NOT NULL,
              error_message CLOB DEFAULT NULL,
              applied_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_config_apply_log_tool ON administration_config_apply_log (application_code, tool_code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_config_apply_log_status ON administration_config_apply_log (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_config_snapshot (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              source_type VARCHAR(64) NOT NULL,
              source_path VARCHAR(255) NOT NULL,
              component_name VARCHAR(190) DEFAULT NULL,
              checksum VARCHAR(64) NOT NULL,
              normalized_entries CLOB NOT NULL,
              scanned_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_config_snapshot_source ON administration_config_snapshot (source_type, source_path)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_config_tool (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              application_code VARCHAR(120) NOT NULL,
              tool_code VARCHAR(160) NOT NULL,
              label VARCHAR(180) NOT NULL,
              description VARCHAR(255) DEFAULT NULL,
              form_class VARCHAR(255) NOT NULL,
              service_class VARCHAR(255) NOT NULL,
              required_permission VARCHAR(180) NOT NULL,
              apply_strategy VARCHAR(64) NOT NULL,
              status VARCHAR(40) NOT NULL,
              editable_fields CLOB NOT NULL,
              sensitive_fields CLOB NOT NULL,
              readable_files CLOB NOT NULL,
              writable_files CLOB NOT NULL,
              metadata CLOB NOT NULL,
              secret_names CLOB NOT NULL,
              discovered_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_config_tool_application ON administration_config_tool (application_code)
        SQL);
        $this->addSql('CREATE INDEX idx_administration_config_tool_code ON administration_config_tool (tool_code)');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_config_tool_application_tool ON administration_config_tool (application_code, tool_code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_config_value (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              application_code VARCHAR(120) NOT NULL,
              tool_code VARCHAR(160) NOT NULL,
              field_key VARCHAR(180) NOT NULL,
              field_type VARCHAR(60) NOT NULL,
              secret BOOLEAN NOT NULL,
              current_value CLOB DEFAULT NULL,
              pending_value CLOB DEFAULT NULL,
              masked_value VARCHAR(255) DEFAULT NULL,
              status VARCHAR(40) NOT NULL,
              updated_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_config_value_tool ON administration_config_value (application_code, tool_code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_config_value_field ON administration_config_value (
              application_code, tool_code, field_key
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_connected_component_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              component_name VARCHAR(120) NOT NULL,
              status VARCHAR(40) NOT NULL,
              readiness_status VARCHAR(40) NOT NULL,
              safe_summary CLOB NOT NULL,
              synchronized_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_connected_component_name ON administration_connected_component_record (component_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_connected_component_status ON administration_connected_component_record (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_credential_definition (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              component_name VARCHAR(120) NOT NULL,
              credential_key VARCHAR(180) NOT NULL,
              environment_name VARCHAR(40) NOT NULL,
              source_type VARCHAR(40) NOT NULL,
              required BOOLEAN NOT NULL,
              description CLOB DEFAULT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_credential_definition_key_env ON administration_credential_definition (
              credential_key, environment_name
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_credential_state (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              credential_key VARCHAR(180) NOT NULL,
              environment_name VARCHAR(40) NOT NULL,
              present BOOLEAN NOT NULL,
              source_type VARCHAR(40) NOT NULL,
              safe_fingerprint VARCHAR(128) DEFAULT NULL,
              status VARCHAR(40) NOT NULL,
              checked_at DATETIME DEFAULT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_credential_state_key_env ON administration_credential_state (
              credential_key, environment_name
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_environment_runtime_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              environment_key VARCHAR(160) NOT NULL,
              category VARCHAR(80) NOT NULL,
              status VARCHAR(40) NOT NULL,
              source_type VARCHAR(80) NOT NULL,
              safe_context CLOB NOT NULL,
              checked_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_environment_category ON administration_environment_runtime_record (category)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_environment_key ON administration_environment_runtime_record (environment_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_managing_field_control_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              resource_class VARCHAR(255) NOT NULL,
              field_name VARCHAR(120) NOT NULL,
              page_name VARCHAR(40) NOT NULL,
              subject_scope VARCHAR(120) NOT NULL,
              access_status VARCHAR(40) NOT NULL,
              visibility_status VARCHAR(40) NOT NULL,
              safe_context CLOB NOT NULL,
              checked_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_managing_field_resource ON administration_managing_field_control_record (resource_class)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_managing_field_status ON administration_managing_field_control_record (
              access_status, visibility_status
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_operation_artifact (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              operation_key VARCHAR(180) NOT NULL,
              artifact_type VARCHAR(80) NOT NULL,
              safe_label VARCHAR(180) NOT NULL,
              relative_path VARCHAR(500) NOT NULL,
              checksum VARCHAR(128) NOT NULL,
              safe_context CLOB NOT NULL,
              created_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_operation_artifact_run ON administration_operation_artifact (operation_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_operation_artifact_type ON administration_operation_artifact (artifact_type)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_operation_event (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              operation_key VARCHAR(180) NOT NULL,
              status VARCHAR(40) NOT NULL,
              safe_message VARCHAR(500) NOT NULL,
              safe_context CLOB NOT NULL,
              created_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_operation_event_run ON administration_operation_event (operation_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_operation_event_status ON administration_operation_event (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_operation_run (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              operation_key VARCHAR(180) NOT NULL,
              operation_type VARCHAR(80) NOT NULL,
              status VARCHAR(40) NOT NULL,
              subject_identifier VARCHAR(190) NOT NULL,
              target_reference VARCHAR(240) DEFAULT NULL,
              safe_context CLOB NOT NULL,
              created_at DATETIME NOT NULL,
              started_at DATETIME DEFAULT NULL,
              finished_at DATETIME DEFAULT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_operation_run_type_status ON administration_operation_run (operation_type, status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_operation_run_subject ON administration_operation_run (subject_identifier)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_operation_run_operation_key ON administration_operation_run (operation_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_service_section_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              section_key VARCHAR(120) NOT NULL,
              label VARCHAR(160) NOT NULL,
              service_directory VARCHAR(255) NOT NULL,
              status VARCHAR(40) NOT NULL,
              tool_count INTEGER NOT NULL,
              safe_context CLOB NOT NULL,
              synchronized_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_service_section_key ON administration_service_section_record (section_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_service_section_status ON administration_service_section_record (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_service_tool_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              section_key VARCHAR(120) NOT NULL,
              direction_token VARCHAR(120) NOT NULL,
              tool_slug VARCHAR(180) NOT NULL,
              tool_key VARCHAR(220) NOT NULL,
              label VARCHAR(180) NOT NULL,
              label_override VARCHAR(180) DEFAULT NULL,
              service_class VARCHAR(255) NOT NULL,
              service_short_name VARCHAR(180) NOT NULL,
              service_file VARCHAR(255) NOT NULL,
              form_type_class VARCHAR(255) DEFAULT NULL,
              form_data_class VARCHAR(255) DEFAULT NULL,
              operation_type VARCHAR(120) NOT NULL,
              executable BOOLEAN NOT NULL,
              primary_route_name VARCHAR(255) DEFAULT NULL,
              primary_route_label VARCHAR(180) DEFAULT NULL,
              source_ownership VARCHAR(40) NOT NULL,
              owner_component_key VARCHAR(120) DEFAULT NULL,
              owner_component_token VARCHAR(120) DEFAULT NULL,
              owner_provider_class VARCHAR(255) DEFAULT NULL,
              owner_service_class VARCHAR(255) DEFAULT NULL,
              owner_source_label VARCHAR(180) DEFAULT NULL,
              status VARCHAR(40) NOT NULL,
              enabled BOOLEAN NOT NULL,
              visible BOOLEAN NOT NULL,
              position INTEGER NOT NULL,
              checksum VARCHAR(64) NOT NULL,
              safe_context CLOB NOT NULL,
              synchronized_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_service_tool_section ON administration_service_tool_record (section_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_service_tool_class ON administration_service_tool_record (service_class)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_service_tool_status ON administration_service_tool_record (status)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_service_tool_source_ownership ON administration_service_tool_record (source_ownership)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_service_tool_owner_component ON administration_service_tool_record (owner_component_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_administration_service_tool_tool_key ON administration_service_tool_record (tool_key)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administration_symfony_route_record (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              route_name VARCHAR(190) NOT NULL,
              path VARCHAR(500) NOT NULL,
              methods CLOB NOT NULL,
              controller VARCHAR(255) DEFAULT NULL,
              status_code INTEGER DEFAULT NULL,
              status_class VARCHAR(40) NOT NULL,
              checked_at DATETIME NOT NULL
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_symfony_route_name ON administration_symfony_route_record (route_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_administration_symfony_route_status ON administration_symfony_route_record (status_class)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE administration_accessing_account_record');
        $this->addSql('DROP TABLE administration_account_action_request_record');
        $this->addSql('DROP TABLE administration_acl_mutation_apply_record');
        $this->addSql('DROP TABLE administration_acl_mutation_review_record');
        $this->addSql('DROP TABLE administration_audit_event');
        $this->addSql('DROP TABLE administration_change_request');
        $this->addSql('DROP TABLE administration_config_application');
        $this->addSql('DROP TABLE administration_config_apply_log');
        $this->addSql('DROP TABLE administration_config_snapshot');
        $this->addSql('DROP TABLE administration_config_tool');
        $this->addSql('DROP TABLE administration_config_value');
        $this->addSql('DROP TABLE administration_connected_component_record');
        $this->addSql('DROP TABLE administration_credential_definition');
        $this->addSql('DROP TABLE administration_credential_state');
        $this->addSql('DROP TABLE administration_environment_runtime_record');
        $this->addSql('DROP TABLE administration_managing_field_control_record');
        $this->addSql('DROP TABLE administration_operation_artifact');
        $this->addSql('DROP TABLE administration_operation_event');
        $this->addSql('DROP TABLE administration_operation_run');
        $this->addSql('DROP TABLE administration_service_section_record');
        $this->addSql('DROP TABLE administration_service_tool_record');
        $this->addSql('DROP TABLE administration_symfony_route_record');
    }
}
