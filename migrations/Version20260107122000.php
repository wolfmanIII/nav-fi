<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107122000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea le tabelle income_trade_details e income_salvage_details (FK 1:1 con income)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE income_trade_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    income_id INTEGER NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    transfer_point VARCHAR(255) DEFAULT NULL,
    transfer_condition VARCHAR(255) DEFAULT NULL,
    goods_description CLOB DEFAULT NULL,
    qty INTEGER DEFAULT NULL,
    grade VARCHAR(100) DEFAULT NULL,
    batch_ids CLOB DEFAULT NULL,
    unit_price NUMERIC(11, 2) DEFAULT NULL,
    payment_terms NUMERIC(11, 2) DEFAULT NULL,
    delivery_method CLOB DEFAULT NULL,
    delivery_day INTEGER DEFAULT NULL,
    delivery_year INTEGER DEFAULT NULL,
    as_is_or_warranty VARCHAR(255) DEFAULT NULL,
    warranty_text CLOB DEFAULT NULL,
    claim_window CLOB DEFAULT NULL,
    return_policy CLOB DEFAULT NULL,
    CONSTRAINT FK_TRADE_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE
);
SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_TRADE_DETAILS_INCOME ON income_trade_details (income_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE income_salvage_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    income_id INTEGER NOT NULL,
    claim_id VARCHAR(100) DEFAULT NULL,
    case_ref VARCHAR(100) DEFAULT NULL,
    source VARCHAR(100) DEFAULT NULL,
    site_location VARCHAR(255) DEFAULT NULL,
    recovered_items_summary CLOB DEFAULT NULL,
    qty_value NUMERIC(11, 2) DEFAULT NULL,
    hazards CLOB DEFAULT NULL,
    salvage_award NUMERIC(11, 2) DEFAULT NULL,
    payment_terms CLOB DEFAULT NULL,
    split_terms CLOB DEFAULT NULL,
    rights_basis CLOB DEFAULT NULL,
    award_trigger CLOB DEFAULT NULL,
    dispute_process CLOB DEFAULT NULL,
    CONSTRAINT FK_SALVAGE_INCOME FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE
);
SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INCOME_SALVAGE_DETAILS_INCOME ON income_salvage_details (income_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS income_trade_details');
        $this->addSql('DROP TABLE IF EXISTS income_salvage_details');
    }
}
