<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119220026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transaction_archive (id SERIAL NOT NULL, asset_id INT NOT NULL, amount NUMERIC(15, 2) NOT NULL, description VARCHAR(255) NOT NULL, session_day INT NOT NULL, session_year INT NOT NULL, related_entity_type VARCHAR(255) DEFAULT NULL, related_entity_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, archived_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, original_transaction_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_archive_asset ON transaction_archive (asset_id)');
        $this->addSql('CREATE INDEX idx_archive_year ON transaction_archive (asset_id, session_year)');
        $this->addSql('COMMENT ON COLUMN transaction_archive.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN transaction_archive.archived_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE transaction_archive');
    }
}
