<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the Administering system-storage tables for the host SQLite Entity Manager.
 */
final class Version20260517170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Administering administration_* system tables for SQLite/system storage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS administration_audit_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, action VARCHAR(190) NOT NULL, subject_identifier VARCHAR(190) NOT NULL, context CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_audit_event_action ON administration_audit_event (action)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_audit_event_subject ON administration_audit_event (subject_identifier)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_config_snapshot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, source_type VARCHAR(64) NOT NULL, source_path VARCHAR(255) NOT NULL, component_name VARCHAR(190) DEFAULT NULL, checksum VARCHAR(64) NOT NULL, normalized_entries CLOB NOT NULL --(DC2Type:json)
, scanned_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_config_snapshot_source ON administration_config_snapshot (source_type, source_path)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_change_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, request_key VARCHAR(160) NOT NULL, change_type VARCHAR(80) NOT NULL, target_reference VARCHAR(240) NOT NULL, status VARCHAR(40) NOT NULL, payload CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_change_request_key ON administration_change_request (request_key)');

        $this->addSql('CREATE TABLE IF NOT EXISTS administration_credential_definition (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, component_name VARCHAR(120) NOT NULL, credential_key VARCHAR(180) NOT NULL, environment_name VARCHAR(40) NOT NULL, source_type VARCHAR(40) NOT NULL, required BOOLEAN NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_credential_definition_key_env ON administration_credential_definition (credential_key, environment_name)');

        $this->addSql('CREATE TABLE IF NOT EXISTS administration_credential_state (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, credential_key VARCHAR(180) NOT NULL, environment_name VARCHAR(40) NOT NULL, present BOOLEAN NOT NULL, source_type VARCHAR(40) NOT NULL, safe_fingerprint VARCHAR(128) DEFAULT NULL, status VARCHAR(40) NOT NULL, checked_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable))');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_credential_state_key_env ON administration_credential_state (credential_key, environment_name)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_operation_run (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, operation_key VARCHAR(180) NOT NULL, operation_type VARCHAR(80) NOT NULL, status VARCHAR(40) NOT NULL, subject_identifier VARCHAR(190) NOT NULL, target_reference VARCHAR(240) DEFAULT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
, started_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
, finished_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_operation_run_key ON administration_operation_run (operation_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_operation_run_type_status ON administration_operation_run (operation_type, status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_operation_run_subject ON administration_operation_run (subject_identifier)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_operation_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, operation_key VARCHAR(180) NOT NULL, status VARCHAR(40) NOT NULL, safe_message VARCHAR(500) NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_operation_event_run ON administration_operation_event (operation_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_operation_event_status ON administration_operation_event (status)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_operation_artifact (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, operation_key VARCHAR(180) NOT NULL, artifact_type VARCHAR(80) NOT NULL, safe_label VARCHAR(180) NOT NULL, relative_path VARCHAR(500) NOT NULL, checksum VARCHAR(128) NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_operation_artifact_run ON administration_operation_artifact (operation_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_operation_artifact_type ON administration_operation_artifact (artifact_type)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_acl_mutation_review_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, request_key VARCHAR(180) NOT NULL, mutation_type VARCHAR(80) NOT NULL, subject_identifier VARCHAR(180) NOT NULL, permission_or_role_key VARCHAR(180) NOT NULL, scope_key VARCHAR(180) NOT NULL, requested_by_subject VARCHAR(180) NOT NULL, valid BOOLEAN NOT NULL, safe_review_payload CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_acl_review_request_key ON administration_acl_mutation_review_record (request_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_acl_review_subject ON administration_acl_mutation_review_record (subject_identifier)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_acl_review_permission ON administration_acl_mutation_review_record (permission_or_role_key)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_acl_mutation_apply_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, request_key VARCHAR(180) NOT NULL, mutation_type VARCHAR(80) NOT NULL, subject_identifier VARCHAR(180) NOT NULL, permission_or_role_key VARCHAR(180) NOT NULL, scope_key VARCHAR(180) NOT NULL, requested_by_subject VARCHAR(180) NOT NULL, status VARCHAR(40) NOT NULL, succeeded BOOLEAN NOT NULL, safe_message VARCHAR(500) NOT NULL, safe_result_payload CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_acl_apply_request_key ON administration_acl_mutation_apply_record (request_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_acl_apply_status ON administration_acl_mutation_apply_record (status)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_account_action_request_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, request_key VARCHAR(180) NOT NULL, action VARCHAR(120) NOT NULL, account_reference VARCHAR(180) NOT NULL, requested_by_subject VARCHAR(180) NOT NULL, status VARCHAR(40) NOT NULL, safe_reason CLOB NOT NULL, safe_result_message CLOB NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_account_action_request_key ON administration_account_action_request_record (request_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_account_action_account ON administration_account_action_request_record (account_reference)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_account_action_status ON administration_account_action_request_record (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS administration_account_action_request_record');
        $this->addSql('DROP TABLE IF EXISTS administration_acl_mutation_apply_record');
        $this->addSql('DROP TABLE IF EXISTS administration_acl_mutation_review_record');
        $this->addSql('DROP TABLE IF EXISTS administration_operation_artifact');
        $this->addSql('DROP TABLE IF EXISTS administration_operation_event');
        $this->addSql('DROP TABLE IF EXISTS administration_operation_run');
        $this->addSql('DROP TABLE IF EXISTS administration_credential_state');
        $this->addSql('DROP TABLE IF EXISTS administration_credential_definition');
        $this->addSql('DROP TABLE IF EXISTS administration_change_request');
        $this->addSql('DROP TABLE IF EXISTS administration_config_snapshot');
        $this->addSql('DROP TABLE IF EXISTS administration_audit_event');
    }
}
