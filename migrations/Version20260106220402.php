<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106220402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge signingDay e signingYear a mortgage';
    }

    public function up(Schema $schema): void
    {
        // SQLite supporta ADD COLUMN con default NULL
        $this->addSql('ALTER TABLE mortgage ADD COLUMN signing_day INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE mortgage ADD COLUMN signing_year INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Ricrea la tabella senza le colonne aggiuntive
        $this->addSql('CREATE TEMPORARY TABLE __temp__mortgage AS SELECT id, ship_id, interest_rate_id, insurance_id, user_id, code, name, start_day, start_year, ship_shares, advance_payment, discount, signed, company_id, local_law_id FROM mortgage');
        $this->addSql('DROP TABLE mortgage');
        $this->addSql('CREATE TABLE mortgage (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ship_id INTEGER NOT NULL, interest_rate_id INTEGER NOT NULL, insurance_id INTEGER DEFAULT NULL, user_id INTEGER DEFAULT NULL, company_id INTEGER DEFAULT NULL, local_law_id INTEGER DEFAULT NULL, code VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, start_day INTEGER NOT NULL, start_year INTEGER NOT NULL, ship_shares INTEGER DEFAULT NULL, advance_payment NUMERIC(11, 2) DEFAULT NULL, discount NUMERIC(11, 2) DEFAULT NULL, signed BOOLEAN DEFAULT 0 NOT NULL, CONSTRAINT FK_E10ABAD0C256317D FOREIGN KEY (ship_id) REFERENCES ship (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0B3E3E851 FOREIGN KEY (interest_rate_id) REFERENCES interest_rate (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0D1E63CD1 FOREIGN KEY (insurance_id) REFERENCES insurance (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD023C96FD FOREIGN KEY (local_law_id) REFERENCES local_law (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mortgage (id, ship_id, interest_rate_id, insurance_id, user_id, code, name, start_day, start_year, ship_shares, advance_payment, discount, signed, company_id, local_law_id) SELECT id, ship_id, interest_rate_id, insurance_id, user_id, code, name, start_day, start_year, ship_shares, advance_payment, discount, signed, company_id, local_law_id FROM __temp__mortgage');
        $this->addSql('DROP TABLE __temp__mortgage');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E10ABAD0C256317D ON mortgage (ship_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0B3E3E851 ON mortgage (interest_rate_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0D1E63CD1 ON mortgage (insurance_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0A76ED395 ON mortgage (user_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0979B1AD6 ON mortgage (company_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD023C96FD ON mortgage (local_law_id)');
    }
}
