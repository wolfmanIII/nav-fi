<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260118204627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE salary (id SERIAL NOT NULL, crew_id INT NOT NULL, amount NUMERIC(15, 2) NOT NULL, first_payment_day INT NOT NULL, first_payment_year INT NOT NULL, status VARCHAR(20) DEFAULT \'Active\' NOT NULL, payday_cycle INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9413BB715FE259F6 ON salary (crew_id)');
        $this->addSql('CREATE TABLE salary_payment (id SERIAL NOT NULL, salary_id INT NOT NULL, transaction_id INT NOT NULL, payment_day INT NOT NULL, payment_year INT NOT NULL, amount NUMERIC(15, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FA2C1EC1B0FDF16E ON salary_payment (salary_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FA2C1EC12FC0CB0F ON salary_payment (transaction_id)');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB715FE259F6 FOREIGN KEY (crew_id) REFERENCES crew (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE salary_payment ADD CONSTRAINT FK_FA2C1EC1B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE salary_payment ADD CONSTRAINT FK_FA2C1EC12FC0CB0F FOREIGN KEY (transaction_id) REFERENCES ledger_transaction (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE salary DROP CONSTRAINT FK_9413BB715FE259F6');
        $this->addSql('ALTER TABLE salary_payment DROP CONSTRAINT FK_FA2C1EC1B0FDF16E');
        $this->addSql('ALTER TABLE salary_payment DROP CONSTRAINT FK_FA2C1EC12FC0CB0F');
        $this->addSql('DROP TABLE salary');
        $this->addSql('DROP TABLE salary_payment');
    }
}
