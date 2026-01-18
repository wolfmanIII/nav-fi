<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260118213715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salary_payment DROP CONSTRAINT fk_fa2c1ec12fc0cb0f');
        $this->addSql('DROP INDEX uniq_fa2c1ec12fc0cb0f');
        $this->addSql('ALTER TABLE salary_payment DROP transaction_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE salary_payment ADD transaction_id INT NOT NULL');
        $this->addSql('ALTER TABLE salary_payment ADD CONSTRAINT fk_fa2c1ec12fc0cb0f FOREIGN KEY (transaction_id) REFERENCES ledger_transaction (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_fa2c1ec12fc0cb0f ON salary_payment (transaction_id)');
    }
}
