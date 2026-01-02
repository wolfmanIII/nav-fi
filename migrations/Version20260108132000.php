<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rimuove policy_number da income_insurance_details (si usa Income.code).
 */
final class Version20260108132000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop policy_number da income_insurance_details';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__income_insurance_details AS SELECT id, income_id, incident_ref, incident_day, incident_year, incident_location, incident_cause, loss_type, verified_loss, deductible, payment_terms, acceptance_effect, subrogation_terms, coverage_notes FROM income_insurance_details');
        $this->addSql('DROP TABLE income_insurance_details');
        $this->addSql('CREATE TABLE income_insurance_details (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, income_id INTEGER NOT NULL, incident_ref VARCHAR(100) DEFAULT NULL, incident_day INTEGER DEFAULT NULL, incident_year INTEGER DEFAULT NULL, incident_location VARCHAR(255) DEFAULT NULL, incident_cause VARCHAR(255) DEFAULT NULL, loss_type VARCHAR(255) DEFAULT NULL, verified_loss NUMERIC(11, 2) DEFAULT NULL, deductible NUMERIC(11, 2) DEFAULT NULL, payment_terms CLOB DEFAULT NULL, acceptance_effect CLOB DEFAULT NULL, subrogation_terms CLOB DEFAULT NULL, coverage_notes CLOB DEFAULT NULL, CONSTRAINT FK_INSURANCE_INCOME FOREIGN KEY (income_id) REFERENCES income (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO income_insurance_details (id, income_id, incident_ref, incident_day, incident_year, incident_location, incident_cause, loss_type, verified_loss, deductible, payment_terms, acceptance_effect, subrogation_terms, coverage_notes) SELECT id, income_id, incident_ref, incident_day, incident_year, incident_location, incident_cause, loss_type, verified_loss, deductible, payment_terms, acceptance_effect, subrogation_terms, coverage_notes FROM __temp__income_insurance_details');
        $this->addSql('DROP TABLE __temp__income_insurance_details');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INSURANCE_INCOME ON income_insurance_details (income_id)');
    }

    public function down(Schema $schema): void
    {
        // Revert non supportato su SQLite senza ricreare la colonna; ripristinare manualmente se necessario.
    }
}
