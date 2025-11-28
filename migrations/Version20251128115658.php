<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128115658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mortgage_rate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, mortgage_id INTEGER NOT NULL, code VARCHAR(36) NOT NULL, payment_day INTEGER NOT NULL, payment_year INTEGER NOT NULL, payment NUMERIC(10, 2) NOT NULL, CONSTRAINT FK_3301D9FE15375FCD FOREIGN KEY (mortgage_id) REFERENCES mortgage (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3301D9FE15375FCD ON mortgage_rate (mortgage_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mortgage_rate');
    }
}
