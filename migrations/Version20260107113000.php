<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea le tabelle income_charter_details e income_subsidy_details (FK 1:1 con income)';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        // SQLite e altre: creiamo le tabelle esplicitamente
        $this->addSql(<<<'SQL'
CREATE TABLE income_charter_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    income_id INTEGER NOT NULL,
    area_or_route VARCHAR(255) DEFAULT NULL,
    purpose VARCHAR(255) DEFAULT NULL,
    manifest_summary CLOB DEFAULT NULL,
    payment_terms CLOB DEFAULT NULL,
    deposit NUMERIC(11, 2) DEFAULT NULL,
    extras CLOB DEFAULT NULL,
    damage_terms CLOB DEFAULT NULL,
    cancellation_terms CLOB DEFAULT NULL,
    CONSTRAINT FK_CHAR_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE
);
SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_CHARTER_DETAILS_INCOME ON income_charter_details (income_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE income_subsidy_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    income_id INTEGER NOT NULL,
    program_ref VARCHAR(100) DEFAULT NULL,
    origin VARCHAR(255) DEFAULT NULL,
    destination VARCHAR(255) DEFAULT NULL,
    service_level VARCHAR(255) DEFAULT NULL,
    subsidy_amount NUMERIC(11, 2) DEFAULT NULL,
    payment_terms CLOB DEFAULT NULL,
    milestones CLOB DEFAULT NULL,
    reporting_requirements CLOB DEFAULT NULL,
    non_compliance_terms CLOB DEFAULT NULL,
    proof_requirements CLOB DEFAULT NULL,
    cancellation_terms CLOB DEFAULT NULL,
    CONSTRAINT FK_SUBSIDY_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE
);
SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_SUBSIDY_DETAILS_INCOME ON income_subsidy_details (income_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE income_freight_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    income_id INTEGER NOT NULL,
    origin VARCHAR(255) DEFAULT NULL,
    destination VARCHAR(255) DEFAULT NULL,
    pickup_day INTEGER DEFAULT NULL,
    pickup_year INTEGER DEFAULT NULL,
    delivery_day INTEGER DEFAULT NULL,
    delivery_year INTEGER DEFAULT NULL,
    cargo_description CLOB DEFAULT NULL,
    cargo_qty VARCHAR(100) DEFAULT NULL,
    declared_value NUMERIC(11, 2) DEFAULT NULL,
    payment_terms CLOB DEFAULT NULL,
    liability_limit NUMERIC(11, 2) DEFAULT NULL,
    cancellation_terms CLOB DEFAULT NULL,
    CONSTRAINT FK_FREIGHT_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE
);
SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_FREIGHT_DETAILS_INCOME ON income_freight_details (income_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE income_passengers_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    income_id INTEGER NOT NULL,
    origin VARCHAR(255) DEFAULT NULL,
    destination VARCHAR(255) DEFAULT NULL,
    departure_day INTEGER DEFAULT NULL,
    departure_year INTEGER DEFAULT NULL,
    arrival_day INTEGER DEFAULT NULL,
    arrival_year INTEGER DEFAULT NULL,
    class_or_berth VARCHAR(100) DEFAULT NULL,
    qty INTEGER DEFAULT NULL,
    passenger_names CLOB DEFAULT NULL,
    passenger_contact VARCHAR(255) DEFAULT NULL,
    baggage_allowance VARCHAR(255) DEFAULT NULL,
    extra_baggage VARCHAR(255) DEFAULT NULL,
    fare_total NUMERIC(11, 2) DEFAULT NULL,
    payment_terms CLOB DEFAULT NULL,
    refund_change_policy CLOB DEFAULT NULL,
    CONSTRAINT FK_PASSENGERS_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE
);
SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_PASSENGERS_DETAILS_INCOME ON income_passengers_details (income_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS income_charter_details');
        $this->addSql('DROP TABLE IF EXISTS income_subsidy_details');
        $this->addSql('DROP TABLE IF EXISTS income_freight_details');
        $this->addSql('DROP TABLE IF EXISTS income_passengers_details');
    }
}
