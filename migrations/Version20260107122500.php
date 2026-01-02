<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107122500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rimuove salvage_award da income_salvage_details (ridondante con income.amount)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE income_salvage_details_tmp (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, income_id INTEGER NOT NULL, claim_id VARCHAR(100) DEFAULT NULL, case_ref VARCHAR(100) DEFAULT NULL, source VARCHAR(100) DEFAULT NULL, site_location VARCHAR(255) DEFAULT NULL, recovered_items_summary CLOB DEFAULT NULL, qty_value NUMERIC(11, 2) DEFAULT NULL, hazards CLOB DEFAULT NULL, payment_terms CLOB DEFAULT NULL, split_terms CLOB DEFAULT NULL, rights_basis CLOB DEFAULT NULL, award_trigger CLOB DEFAULT NULL, dispute_process CLOB DEFAULT NULL, CONSTRAINT FK_SALVAGE_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO income_salvage_details_tmp (id, income_id, claim_id, case_ref, source, site_location, recovered_items_summary, qty_value, hazards, payment_terms, split_terms, rights_basis, award_trigger, dispute_process) SELECT id, income_id, claim_id, case_ref, source, site_location, recovered_items_summary, qty_value, hazards, payment_terms, split_terms, rights_basis, award_trigger, dispute_process FROM income_salvage_details');
        $this->addSql('DROP TABLE income_salvage_details');
        $this->addSql('ALTER TABLE income_salvage_details_tmp RENAME TO income_salvage_details');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_SALVAGE_DETAILS_INCOME ON income_salvage_details (income_id)');
    }

    public function down(Schema $schema): void
    {
        // Non si ripristina salvage_award per evitare perdita dati incoerenti.
    }
}
