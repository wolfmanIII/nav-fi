<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123140438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mortgage ADD COLUMN signed BOOLEAN DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mortgage AS SELECT id, ship_id, interest_rate_id, insurance_id, code, name, start_day, start_year, ship_shares, advance_payment, discount FROM mortgage');
        $this->addSql('DROP TABLE mortgage');
        $this->addSql('CREATE TABLE mortgage (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ship_id INTEGER NOT NULL, interest_rate_id INTEGER NOT NULL, insurance_id INTEGER DEFAULT NULL, code VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, start_day INTEGER NOT NULL, start_year INTEGER NOT NULL, ship_shares INTEGER DEFAULT NULL, advance_payment NUMERIC(11, 2) DEFAULT NULL, discount NUMERIC(11, 2) DEFAULT NULL, CONSTRAINT FK_E10ABAD0C256317D FOREIGN KEY (ship_id) REFERENCES ship (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0B3E3E851 FOREIGN KEY (interest_rate_id) REFERENCES interest_rate (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0D1E63CD1 FOREIGN KEY (insurance_id) REFERENCES insurance (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mortgage (id, ship_id, interest_rate_id, insurance_id, code, name, start_day, start_year, ship_shares, advance_payment, discount) SELECT id, ship_id, interest_rate_id, insurance_id, code, name, start_day, start_year, ship_shares, advance_payment, discount FROM __temp__mortgage');
        $this->addSql('DROP TABLE __temp__mortgage');
        $this->addSql('CREATE INDEX IDX_E10ABAD0C256317D ON mortgage (ship_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0B3E3E851 ON mortgage (interest_rate_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0D1E63CD1 ON mortgage (insurance_id)');
    }
}
