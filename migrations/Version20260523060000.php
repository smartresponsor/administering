<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates Administering primary CRUD anchor tables for service-section menu roots.
 */
final class Version20260523060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Administering service-section primary CRUD anchor tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS administration_accessing_account_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, account_reference VARCHAR(180) NOT NULL, display_label VARCHAR(190) NOT NULL, status VARCHAR(40) NOT NULL, provider VARCHAR(80) NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, synchronized_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_accessing_account_status ON administration_accessing_account_record (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_accessing_account_reference ON administration_accessing_account_record (account_reference)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_connected_component_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, component_name VARCHAR(120) NOT NULL, status VARCHAR(40) NOT NULL, readiness_status VARCHAR(40) NOT NULL, safe_summary CLOB NOT NULL --(DC2Type:json)
, synchronized_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_connected_component_name ON administration_connected_component_record (component_name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_connected_component_status ON administration_connected_component_record (status)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_environment_runtime_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, environment_key VARCHAR(160) NOT NULL, category VARCHAR(80) NOT NULL, status VARCHAR(40) NOT NULL, source_type VARCHAR(80) NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, checked_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_environment_category ON administration_environment_runtime_record (category)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_environment_key ON administration_environment_runtime_record (environment_key)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_managing_field_control_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, resource_class VARCHAR(255) NOT NULL, field_name VARCHAR(120) NOT NULL, page_name VARCHAR(40) NOT NULL, subject_scope VARCHAR(120) NOT NULL, access_status VARCHAR(40) NOT NULL, visibility_status VARCHAR(40) NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, checked_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_managing_field_resource ON administration_managing_field_control_record (resource_class)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_managing_field_status ON administration_managing_field_control_record (access_status, visibility_status)');

        $this->addSql("CREATE TABLE IF NOT EXISTS administration_symfony_route_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, route_name VARCHAR(190) NOT NULL, path VARCHAR(500) NOT NULL, methods CLOB NOT NULL --(DC2Type:json)
, controller VARCHAR(255) DEFAULT NULL, status_code INTEGER DEFAULT NULL, status_class VARCHAR(40) NOT NULL, checked_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_symfony_route_name ON administration_symfony_route_record (route_name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_symfony_route_status ON administration_symfony_route_record (status_class)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS administration_symfony_route_record');
        $this->addSql('DROP TABLE IF EXISTS administration_managing_field_control_record');
        $this->addSql('DROP TABLE IF EXISTS administration_environment_runtime_record');
        $this->addSql('DROP TABLE IF EXISTS administration_connected_component_record');
        $this->addSql('DROP TABLE IF EXISTS administration_accessing_account_record');
    }
}
