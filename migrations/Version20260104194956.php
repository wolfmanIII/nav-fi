<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make income.ship nullable (SQLite).
 */
final class Version20260104194956 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        // SQLite needs schema changes outside a transaction when toggling PRAGMA foreign_keys.
        return false;
    }

    public function getDescription(): string
    {
        return 'Set income.ship nullable';
    }

    public function up(Schema $schema): void
    {
        // SQLite requires table recreation; disable FKs during the operation.
        $this->addSql('PRAGMA foreign_keys = OFF');

        $this->addSql('CREATE TEMPORARY TABLE __temp__income AS SELECT id, income_category_id, ship_id, user_id, code, title, signing_day, signing_year, payment_day, payment_year, amount, note, cancel_day, cancel_year, expiration_day, expiration_year, company_id, local_law_id, signing_location FROM income');
        $this->addSql('DROP TABLE income');
        $this->addSql('CREATE TABLE income (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, income_category_id INTEGER NOT NULL, ship_id INTEGER DEFAULT NULL, user_id INTEGER DEFAULT NULL, company_id INTEGER DEFAULT NULL, local_law_id INTEGER DEFAULT NULL, code VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, signing_day INTEGER DEFAULT NULL, signing_year INTEGER DEFAULT NULL, payment_day INTEGER DEFAULT NULL, payment_year INTEGER DEFAULT NULL, amount NUMERIC(11, 2) NOT NULL, note CLOB DEFAULT NULL, cancel_day INTEGER DEFAULT NULL, cancel_year INTEGER DEFAULT NULL, expiration_day INTEGER DEFAULT NULL, expiration_year INTEGER DEFAULT NULL, signing_location VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_3FA862D053F8702F FOREIGN KEY (income_category_id) REFERENCES income_category (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D0C256317D FOREIGN KEY (ship_id) REFERENCES ship (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D0979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D023C96FD FOREIGN KEY (local_law_id) REFERENCES local_law (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO income (id, income_category_id, ship_id, user_id, code, title, signing_day, signing_year, payment_day, payment_year, amount, note, cancel_day, cancel_year, expiration_day, expiration_year, company_id, local_law_id, signing_location) SELECT id, income_category_id, ship_id, user_id, code, title, signing_day, signing_year, payment_day, payment_year, amount, note, cancel_day, cancel_year, expiration_day, expiration_year, company_id, local_law_id, signing_location FROM __temp__income');
        $this->addSql('DROP TABLE __temp__income');
        $this->addSql('CREATE INDEX IDX_3FA862D0A76ED395 ON income (user_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D0C256317D ON income (ship_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D053F8702F ON income (income_category_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D0979B1AD6 ON income (company_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D023C96FD ON income (local_law_id)');

        $this->addSql('PRAGMA foreign_keys = ON');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('PRAGMA foreign_keys = OFF');

        $this->addSql('CREATE TEMPORARY TABLE __temp__income AS SELECT id, income_category_id, ship_id, user_id, code, title, signing_day, signing_year, payment_day, payment_year, amount, note, cancel_day, cancel_year, expiration_day, expiration_year, company_id, local_law_id, signing_location FROM income');
        $this->addSql('DROP TABLE income');
        $this->addSql('CREATE TABLE income (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, income_category_id INTEGER NOT NULL, ship_id INTEGER NOT NULL, user_id INTEGER DEFAULT NULL, company_id INTEGER DEFAULT NULL, local_law_id INTEGER DEFAULT NULL, code VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, signing_day INTEGER DEFAULT NULL, signing_year INTEGER DEFAULT NULL, payment_day INTEGER DEFAULT NULL, payment_year INTEGER DEFAULT NULL, amount NUMERIC(11, 2) NOT NULL, note CLOB DEFAULT NULL, cancel_day INTEGER DEFAULT NULL, cancel_year INTEGER DEFAULT NULL, expiration_day INTEGER DEFAULT NULL, expiration_year INTEGER DEFAULT NULL, signing_location VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_3FA862D053F8702F FOREIGN KEY (income_category_id) REFERENCES income_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D0C256317D FOREIGN KEY (ship_id) REFERENCES ship (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D0979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D023C96FD FOREIGN KEY (local_law_id) REFERENCES local_law (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO income (id, income_category_id, ship_id, user_id, code, title, signing_day, signing_year, payment_day, payment_year, amount, note, cancel_day, cancel_year, expiration_day, expiration_year, company_id, local_law_id, signing_location) SELECT id, income_category_id, ship_id, user_id, code, title, signing_day, signing_year, payment_day, payment_year, amount, note, cancel_day, cancel_year, expiration_day, expiration_year, company_id, local_law_id, signing_location FROM __temp__income');
        $this->addSql('DROP TABLE __temp__income');
        $this->addSql('CREATE INDEX IDX_3FA862D0A76ED395 ON income (user_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D0C256317D ON income (ship_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D053F8702F ON income (income_category_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D0979B1AD6 ON income (company_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D023C96FD ON income (local_law_id)');

        $this->addSql('PRAGMA foreign_keys = ON');
    }
}
