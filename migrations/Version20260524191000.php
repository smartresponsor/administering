<?php

declare(strict_types=1);

namespace App\Administering\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Administering configuration registry, state, and audit tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS administration_config_application (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, application_code VARCHAR(120) NOT NULL, label VARCHAR(180) NOT NULL, root_path VARCHAR(255) NOT NULL, manifest_path VARCHAR(255) NOT NULL, status VARCHAR(40) NOT NULL, enabled BOOLEAN NOT NULL, checksum VARCHAR(64) NOT NULL, discovered_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_config_application_code ON administration_config_application (application_code)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_config_tool (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, application_code VARCHAR(120) NOT NULL, tool_code VARCHAR(160) NOT NULL, label VARCHAR(180) NOT NULL, description VARCHAR(255) DEFAULT NULL, form_class VARCHAR(255) NOT NULL, service_class VARCHAR(255) NOT NULL, required_permission VARCHAR(180) NOT NULL, apply_strategy VARCHAR(64) NOT NULL, status VARCHAR(40) NOT NULL, editable_fields CLOB NOT NULL --(DC2Type:json)
, sensitive_fields CLOB NOT NULL --(DC2Type:json)
, readable_files CLOB NOT NULL --(DC2Type:json)
, writable_files CLOB NOT NULL --(DC2Type:json)
, metadata CLOB NOT NULL --(DC2Type:json)
, secret_names CLOB NOT NULL --(DC2Type:json)
, discovered_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_config_tool_application ON administration_config_tool (application_code)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_config_tool_code ON administration_config_tool (tool_code)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_config_tool_application_tool ON administration_config_tool (application_code, tool_code)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_config_value (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, application_code VARCHAR(120) NOT NULL, tool_code VARCHAR(160) NOT NULL, field_key VARCHAR(180) NOT NULL, field_type VARCHAR(60) NOT NULL, secret BOOLEAN NOT NULL, current_value CLOB DEFAULT NULL, pending_value CLOB DEFAULT NULL, masked_value VARCHAR(255) DEFAULT NULL, status VARCHAR(40) NOT NULL, updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_config_value_tool ON administration_config_value (application_code, tool_code)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_config_value_field ON administration_config_value (application_code, tool_code, field_key)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_config_apply_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, application_code VARCHAR(120) NOT NULL, tool_code VARCHAR(160) NOT NULL, actor_identifier VARCHAR(180) NOT NULL, status VARCHAR(40) NOT NULL, changed_fields CLOB NOT NULL --(DC2Type:json)
, masked_secrets CLOB NOT NULL --(DC2Type:json)
, error_message CLOB DEFAULT NULL, applied_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_config_apply_log_tool ON administration_config_apply_log (application_code, tool_code)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_config_apply_log_status ON administration_config_apply_log (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS administration_config_apply_log');
        $this->addSql('DROP TABLE IF EXISTS administration_config_value');
        $this->addSql('DROP TABLE IF EXISTS administration_config_tool');
        $this->addSql('DROP TABLE IF EXISTS administration_config_application');
    }
}
