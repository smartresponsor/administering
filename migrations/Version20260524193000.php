<?php

declare(strict_types=1);

namespace App\Administering\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Administering SQLite projection table for service tool records.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS administration_service_tool_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, section_key VARCHAR(120) NOT NULL, direction_token VARCHAR(120) NOT NULL, tool_slug VARCHAR(180) NOT NULL, tool_key VARCHAR(220) NOT NULL, label VARCHAR(180) NOT NULL, label_override VARCHAR(180) DEFAULT NULL, service_class VARCHAR(255) NOT NULL, service_short_name VARCHAR(180) NOT NULL, service_file VARCHAR(255) NOT NULL, form_type_class VARCHAR(255) DEFAULT NULL, form_data_class VARCHAR(255) DEFAULT NULL, operation_type VARCHAR(120) NOT NULL, executable BOOLEAN NOT NULL, primary_route_name VARCHAR(255) DEFAULT NULL, primary_route_label VARCHAR(180) DEFAULT NULL, source_ownership VARCHAR(40) NOT NULL, owner_component_key VARCHAR(120) DEFAULT NULL, owner_component_token VARCHAR(120) DEFAULT NULL, owner_provider_class VARCHAR(255) DEFAULT NULL, owner_service_class VARCHAR(255) DEFAULT NULL, owner_source_label VARCHAR(180) DEFAULT NULL, status VARCHAR(40) NOT NULL, enabled BOOLEAN NOT NULL, visible BOOLEAN NOT NULL, position INTEGER NOT NULL, checksum VARCHAR(64) NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)\n, synchronized_at DATETIME NOT NULL --(DC2Type:datetime_immutable)\n)");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_administration_service_tool_tool_key ON administration_service_tool_record (tool_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_service_tool_section ON administration_service_tool_record (section_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_service_tool_class ON administration_service_tool_record (service_class)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_service_tool_status ON administration_service_tool_record (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_service_tool_source_ownership ON administration_service_tool_record (source_ownership)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_service_tool_owner_component ON administration_service_tool_record (owner_component_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS administration_service_tool_record');
    }
}
