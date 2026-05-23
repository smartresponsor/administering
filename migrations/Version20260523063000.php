<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates a generic service-section anchor table for planned or empty sections.
 */
final class Version20260523063000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Administering generic service-section anchor table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS administration_service_section_record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, section_key VARCHAR(120) NOT NULL, label VARCHAR(160) NOT NULL, service_directory VARCHAR(255) NOT NULL, status VARCHAR(40) NOT NULL, tool_count INTEGER NOT NULL, safe_context CLOB NOT NULL --(DC2Type:json)
, synchronized_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_service_section_key ON administration_service_section_record (section_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_administration_service_section_status ON administration_service_section_record (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS administration_service_section_record');
    }
}
