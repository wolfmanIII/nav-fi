<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251231093130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE crew (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ship_id INTEGER DEFAULT NULL, user_id INTEGER DEFAULT NULL, name VARCHAR(100) NOT NULL, surname VARCHAR(100) NOT NULL, nickname VARCHAR(100) DEFAULT NULL, birth_year INTEGER DEFAULT NULL, birth_day INTEGER DEFAULT NULL, birth_world VARCHAR(100) DEFAULT NULL, code VARCHAR(36) NOT NULL, CONSTRAINT FK_894940B2C256317D FOREIGN KEY (ship_id) REFERENCES ship (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_894940B2A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_894940B2C256317D ON crew (ship_id)');
        $this->addSql('CREATE INDEX IDX_894940B2A76ED395 ON crew (user_id)');
        $this->addSql('CREATE TABLE crew_ship_role (crew_id INTEGER NOT NULL, ship_role_id INTEGER NOT NULL, PRIMARY KEY(crew_id, ship_role_id), CONSTRAINT FK_C71AD6D25FE259F6 FOREIGN KEY (crew_id) REFERENCES crew (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C71AD6D2D82E12C1 FOREIGN KEY (ship_role_id) REFERENCES ship_role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C71AD6D25FE259F6 ON crew_ship_role (crew_id)');
        $this->addSql('CREATE INDEX IDX_C71AD6D2D82E12C1 ON crew_ship_role (ship_role_id)');
        $this->addSql('CREATE TABLE insurance (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, annual_cost NUMERIC(11, 2) NOT NULL, coverage CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('CREATE TABLE interest_rate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, duration INTEGER NOT NULL, price_multiplier NUMERIC(11, 2) NOT NULL, price_divider INTEGER NOT NULL, annual_interest_rate NUMERIC(11, 2) NOT NULL)');
        $this->addSql('CREATE TABLE mortgage (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ship_id INTEGER NOT NULL, interest_rate_id INTEGER NOT NULL, insurance_id INTEGER DEFAULT NULL, user_id INTEGER DEFAULT NULL, code VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, start_day INTEGER NOT NULL, start_year INTEGER NOT NULL, ship_shares INTEGER DEFAULT NULL, advance_payment NUMERIC(11, 2) DEFAULT NULL, discount NUMERIC(11, 2) DEFAULT NULL, signed BOOLEAN DEFAULT 0 NOT NULL, CONSTRAINT FK_E10ABAD0C256317D FOREIGN KEY (ship_id) REFERENCES ship (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0B3E3E851 FOREIGN KEY (interest_rate_id) REFERENCES interest_rate (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0D1E63CD1 FOREIGN KEY (insurance_id) REFERENCES insurance (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E10ABAD0A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0C256317D ON mortgage (ship_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0B3E3E851 ON mortgage (interest_rate_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0D1E63CD1 ON mortgage (insurance_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0A76ED395 ON mortgage (user_id)');
        $this->addSql('CREATE TABLE mortgage_installment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, mortgage_id INTEGER NOT NULL, user_id INTEGER DEFAULT NULL, code VARCHAR(36) NOT NULL, payment_day INTEGER NOT NULL, payment_year INTEGER NOT NULL, payment NUMERIC(10, 2) NOT NULL, CONSTRAINT FK_CE3D2EB915375FCD FOREIGN KEY (mortgage_id) REFERENCES mortgage (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE3D2EB9A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CE3D2EB915375FCD ON mortgage_installment (mortgage_id)');
        $this->addSql('CREATE INDEX IDX_CE3D2EB9A76ED395 ON mortgage_installment (user_id)');
        $this->addSql('CREATE TABLE ship (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, code CHAR(36) NOT NULL --(DC2Type:guid)
        , name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, price NUMERIC(11, 2) NOT NULL, CONSTRAINT FK_FA30EB24A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_FA30EB24A76ED395 ON ship (user_id)');
        $this->addSql('CREATE TABLE ship_role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(4) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(1000) NOT NULL)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE crew');
        $this->addSql('DROP TABLE crew_ship_role');
        $this->addSql('DROP TABLE insurance');
        $this->addSql('DROP TABLE interest_rate');
        $this->addSql('DROP TABLE mortgage');
        $this->addSql('DROP TABLE mortgage_installment');
        $this->addSql('DROP TABLE ship');
        $this->addSql('DROP TABLE ship_role');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
