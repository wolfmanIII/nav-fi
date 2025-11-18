<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251118163157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE insurance (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, annual_cost NUMERIC(11, 2) NOT NULL, coverage CLOB NOT NULL)');
        $this->addSql('CREATE TABLE interest_rate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, duration INTEGER NOT NULL, price_multiplier NUMERIC(11, 2) NOT NULL, price_divider INTEGER NOT NULL, annual_interest_rate NUMERIC(11, 2) NOT NULL)');
        $this->addSql('CREATE TABLE mortgage (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ship_id INTEGER NOT NULL, interest_rate_id INTEGER NOT NULL, insurance_id INTEGER DEFAULT NULL, code VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, start_day INTEGER NOT NULL, start_year INTEGER NOT NULL, ship_shares INTEGER DEFAULT NULL, advance_payment NUMERIC(11, 2) DEFAULT NULL, discount NUMERIC(11, 2) DEFAULT NULL, CONSTRAINT FK_E10ABAD0C256317D FOREIGN KEY (ship_id) REFERENCES ship (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0B3E3E851 FOREIGN KEY (interest_rate_id) REFERENCES interest_rate (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0D1E63CD1 FOREIGN KEY (insurance_id) REFERENCES insurance (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0C256317D ON mortgage (ship_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0B3E3E851 ON mortgage (interest_rate_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0D1E63CD1 ON mortgage (insurance_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE insurance');
        $this->addSql('DROP TABLE interest_rate');
        $this->addSql('DROP TABLE mortgage');
    }
}
