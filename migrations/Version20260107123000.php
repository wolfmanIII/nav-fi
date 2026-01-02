<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reintroduce le date start/end nei dettagli Charter/Subsidy/Services';
    }

    public function up(Schema $schema): void
    {
        // Charter
        $this->addSql('CREATE TABLE income_charter_details_tmp (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, income_id INTEGER NOT NULL, area_or_route VARCHAR(255) DEFAULT NULL, purpose VARCHAR(255) DEFAULT NULL, manifest_summary CLOB DEFAULT NULL, start_day INTEGER DEFAULT NULL, start_year INTEGER DEFAULT NULL, end_day INTEGER DEFAULT NULL, end_year INTEGER DEFAULT NULL, payment_terms CLOB DEFAULT NULL, deposit NUMERIC(11, 2) DEFAULT NULL, extras CLOB DEFAULT NULL, damage_terms CLOB DEFAULT NULL, cancellation_terms CLOB DEFAULT NULL, CONSTRAINT FK_CHAR_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql("INSERT INTO income_charter_details_tmp (id, income_id, area_or_route, purpose, manifest_summary, start_day, start_year, end_day, end_year, payment_terms, deposit, extras, damage_terms, cancellation_terms) SELECT id, income_id, area_or_route, purpose, manifest_summary, NULL, NULL, NULL, NULL, payment_terms, deposit, extras, damage_terms, cancellation_terms FROM income_charter_details");
        $this->addSql('DROP TABLE income_charter_details');
        $this->addSql('ALTER TABLE income_charter_details_tmp RENAME TO income_charter_details');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_CHARTER_DETAILS_INCOME ON income_charter_details (income_id)');

        // Subsidy
        $this->addSql('CREATE TABLE income_subsidy_details_tmp (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, income_id INTEGER NOT NULL, program_ref VARCHAR(100) DEFAULT NULL, origin VARCHAR(255) DEFAULT NULL, destination VARCHAR(255) DEFAULT NULL, start_day INTEGER DEFAULT NULL, start_year INTEGER DEFAULT NULL, end_day INTEGER DEFAULT NULL, end_year INTEGER DEFAULT NULL, service_level VARCHAR(255) DEFAULT NULL, subsidy_amount NUMERIC(11, 2) DEFAULT NULL, payment_terms CLOB DEFAULT NULL, milestones CLOB DEFAULT NULL, reporting_requirements CLOB DEFAULT NULL, non_compliance_terms CLOB DEFAULT NULL, proof_requirements CLOB DEFAULT NULL, cancellation_terms CLOB DEFAULT NULL, CONSTRAINT FK_SUBSIDY_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql("INSERT INTO income_subsidy_details_tmp (id, income_id, program_ref, origin, destination, start_day, start_year, end_day, end_year, service_level, subsidy_amount, payment_terms, milestones, reporting_requirements, non_compliance_terms, proof_requirements, cancellation_terms) SELECT id, income_id, program_ref, origin, destination, NULL, NULL, NULL, NULL, service_level, subsidy_amount, payment_terms, milestones, reporting_requirements, non_compliance_terms, proof_requirements, cancellation_terms FROM income_subsidy_details");
        $this->addSql('DROP TABLE income_subsidy_details');
        $this->addSql('ALTER TABLE income_subsidy_details_tmp RENAME TO income_subsidy_details');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_SUBSIDY_DETAILS_INCOME ON income_subsidy_details (income_id)');

        // Services
        $this->addSql('CREATE TABLE income_services_details_tmp (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, income_id INTEGER NOT NULL, location VARCHAR(255) DEFAULT NULL, vessel_id VARCHAR(255) DEFAULT NULL, service_type VARCHAR(255) DEFAULT NULL, requested_by VARCHAR(255) DEFAULT NULL, start_day INTEGER DEFAULT NULL, start_year INTEGER DEFAULT NULL, end_day INTEGER DEFAULT NULL, end_year INTEGER DEFAULT NULL, work_summary CLOB DEFAULT NULL, parts_materials CLOB DEFAULT NULL, risks CLOB DEFAULT NULL, payment_terms CLOB DEFAULT NULL, extras CLOB DEFAULT NULL, liability_limit NUMERIC(11, 2) DEFAULT NULL, cancellation_terms CLOB DEFAULT NULL, CONSTRAINT FK_SERVICES_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql("INSERT INTO income_services_details_tmp (id, income_id, location, vessel_id, service_type, requested_by, start_day, start_year, end_day, end_year, work_summary, parts_materials, risks, payment_terms, extras, liability_limit, cancellation_terms) SELECT id, income_id, location, vessel_id, service_type, requested_by, NULL, NULL, NULL, NULL, work_summary, parts_materials, risks, payment_terms, extras, liability_limit, cancellation_terms FROM income_services_details");
        $this->addSql('DROP TABLE income_services_details');
        $this->addSql('ALTER TABLE income_services_details_tmp RENAME TO income_services_details');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_SERVICES_DETAILS_INCOME ON income_services_details (income_id)');
    }

    public function down(Schema $schema): void
    {
        // Non si rimuovono nuovamente le colonne per evitare perdita dati.
    }
}
