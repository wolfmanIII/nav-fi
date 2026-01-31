<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131171318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE financial_account DROP CONSTRAINT fk_2ff514cef639f774');
        $this->addSql('DROP INDEX idx_fin_acc_campaign');
        $this->addSql('ALTER TABLE financial_account DROP campaign_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE financial_account ADD campaign_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE financial_account ADD CONSTRAINT fk_2ff514cef639f774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fin_acc_campaign ON financial_account (campaign_id)');
    }
}
