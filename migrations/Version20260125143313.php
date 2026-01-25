<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125143313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE income_trade_details ADD purchase_cost_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE income_trade_details ADD CONSTRAINT FK_214925046654F06 FOREIGN KEY (purchase_cost_id) REFERENCES cost (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_214925046654F06 ON income_trade_details (purchase_cost_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE income_trade_details DROP CONSTRAINT FK_214925046654F06');
        $this->addSql('DROP INDEX IDX_214925046654F06');
        $this->addSql('ALTER TABLE income_trade_details DROP purchase_cost_id');
    }
}
