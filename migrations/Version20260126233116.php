<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126233116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE financial_account ADD bank_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE financial_account DROP account_number');
        $this->addSql('ALTER TABLE financial_account ADD CONSTRAINT FK_2FF514CE11C8FB41 FOREIGN KEY (bank_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fin_acc_bank ON financial_account (bank_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE financial_account DROP CONSTRAINT FK_2FF514CE11C8FB41');
        $this->addSql('DROP INDEX idx_fin_acc_bank');
        $this->addSql('ALTER TABLE financial_account ADD account_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE financial_account DROP bank_id');
    }
}
