<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125203533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE income_charter_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_contract_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_freight_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_insurance_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_interest_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_mail_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_passengers_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_prize_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_salvage_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_services_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_subsidy_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE income_trade_details_id_seq CASCADE');
        $this->addSql('ALTER TABLE income_services_details DROP CONSTRAINT fk_88efd8ac640ed2c0');
        $this->addSql('ALTER TABLE income_salvage_details DROP CONSTRAINT fk_747ba40640ed2c0');
        $this->addSql('ALTER TABLE income_charter_details DROP CONSTRAINT fk_baac1642640ed2c0');
        $this->addSql('ALTER TABLE income_mail_details DROP CONSTRAINT fk_4d0f8a05640ed2c0');
        $this->addSql('ALTER TABLE income_contract_details DROP CONSTRAINT fk_69456e93640ed2c0');
        $this->addSql('ALTER TABLE income_prize_details DROP CONSTRAINT fk_62541f8c640ed2c0');
        $this->addSql('ALTER TABLE income_freight_details DROP CONSTRAINT fk_316d3daa640ed2c0');
        $this->addSql('ALTER TABLE income_passengers_details DROP CONSTRAINT fk_e90c1830640ed2c0');
        $this->addSql('ALTER TABLE income_interest_details DROP CONSTRAINT fk_6e6e6495640ed2c0');
        $this->addSql('ALTER TABLE income_trade_details DROP CONSTRAINT fk_21492504640ed2c0');
        $this->addSql('ALTER TABLE income_trade_details DROP CONSTRAINT fk_214925046654f06');
        $this->addSql('ALTER TABLE income_subsidy_details DROP CONSTRAINT fk_11c99b4640ed2c0');
        $this->addSql('ALTER TABLE income_insurance_details DROP CONSTRAINT fk_6ec0fe75640ed2c0');
        $this->addSql('DROP TABLE income_services_details');
        $this->addSql('DROP TABLE income_salvage_details');
        $this->addSql('DROP TABLE income_charter_details');
        $this->addSql('DROP TABLE income_mail_details');
        $this->addSql('DROP TABLE income_contract_details');
        $this->addSql('DROP TABLE income_prize_details');
        $this->addSql('DROP TABLE income_freight_details');
        $this->addSql('DROP TABLE income_passengers_details');
        $this->addSql('DROP TABLE income_interest_details');
        $this->addSql('DROP TABLE income_trade_details');
        $this->addSql('DROP TABLE income_subsidy_details');
        $this->addSql('DROP TABLE income_insurance_details');
        $this->addSql('ALTER TABLE income ADD purchase_cost_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD details JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D06654F06 FOREIGN KEY (purchase_cost_id) REFERENCES cost (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3FA862D06654F06 ON income (purchase_cost_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE income_charter_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_contract_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_freight_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_insurance_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_interest_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_mail_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_passengers_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_prize_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_salvage_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_services_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_subsidy_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE income_trade_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE income_services_details (id SERIAL NOT NULL, income_id INT NOT NULL, location VARCHAR(255) DEFAULT NULL, service_type VARCHAR(255) DEFAULT NULL, requested_by VARCHAR(255) DEFAULT NULL, start_day INT DEFAULT NULL, start_year INT DEFAULT NULL, end_day INT DEFAULT NULL, end_year INT DEFAULT NULL, delivery_proof_ref VARCHAR(255) DEFAULT NULL, delivery_proof_day INT DEFAULT NULL, delivery_proof_year INT DEFAULT NULL, delivery_proof_received_by VARCHAR(255) DEFAULT NULL, work_summary TEXT DEFAULT NULL, parts_materials TEXT DEFAULT NULL, risks TEXT DEFAULT NULL, payment_terms TEXT DEFAULT NULL, extras TEXT DEFAULT NULL, liability_limit NUMERIC(11, 2) DEFAULT NULL, cancellation_terms TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_88efd8ac640ed2c0 ON income_services_details (income_id)');
        $this->addSql('CREATE TABLE income_salvage_details (id SERIAL NOT NULL, income_id INT NOT NULL, case_ref VARCHAR(100) DEFAULT NULL, source VARCHAR(100) DEFAULT NULL, site_location VARCHAR(255) DEFAULT NULL, recovered_items_summary TEXT DEFAULT NULL, qty_value NUMERIC(11, 2) DEFAULT NULL, hazards TEXT DEFAULT NULL, payment_terms TEXT DEFAULT NULL, split_terms TEXT DEFAULT NULL, rights_basis TEXT DEFAULT NULL, award_trigger TEXT DEFAULT NULL, dispute_process TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_747ba40640ed2c0 ON income_salvage_details (income_id)');
        $this->addSql('CREATE TABLE income_charter_details (id SERIAL NOT NULL, income_id INT NOT NULL, area_or_route VARCHAR(255) DEFAULT NULL, purpose VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, manifest_summary TEXT DEFAULT NULL, start_day INT DEFAULT NULL, start_year INT DEFAULT NULL, end_day INT DEFAULT NULL, end_year INT DEFAULT NULL, delivery_proof_ref VARCHAR(255) DEFAULT NULL, delivery_proof_day INT DEFAULT NULL, delivery_proof_year INT DEFAULT NULL, delivery_proof_received_by VARCHAR(255) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, deposit NUMERIC(11, 2) DEFAULT NULL, extras TEXT DEFAULT NULL, damage_terms TEXT DEFAULT NULL, cancellation_terms TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_baac1642640ed2c0 ON income_charter_details (income_id)');
        $this->addSql('CREATE TABLE income_mail_details (id SERIAL NOT NULL, income_id INT NOT NULL, origin VARCHAR(255) DEFAULT NULL, destination VARCHAR(255) DEFAULT NULL, dispatch_day INT DEFAULT NULL, dispatch_year INT DEFAULT NULL, delivery_day INT DEFAULT NULL, delivery_year INT DEFAULT NULL, delivery_proof_ref VARCHAR(255) DEFAULT NULL, delivery_proof_day INT DEFAULT NULL, delivery_proof_year INT DEFAULT NULL, delivery_proof_received_by VARCHAR(255) DEFAULT NULL, mail_type VARCHAR(255) DEFAULT NULL, package_count INT DEFAULT NULL, total_mass NUMERIC(11, 2) DEFAULT NULL, security_level VARCHAR(255) DEFAULT NULL, seal_codes VARCHAR(255) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, proof_of_delivery TEXT DEFAULT NULL, liability_limit NUMERIC(11, 2) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_4d0f8a05640ed2c0 ON income_mail_details (income_id)');
        $this->addSql('CREATE TABLE income_contract_details (id SERIAL NOT NULL, income_id INT NOT NULL, job_type VARCHAR(255) DEFAULT NULL, location TEXT DEFAULT NULL, objective TEXT DEFAULT NULL, success_condition TEXT DEFAULT NULL, start_day INT DEFAULT NULL, start_year INT DEFAULT NULL, deadline_day INT DEFAULT NULL, deadline_year INT DEFAULT NULL, bonus NUMERIC(11, 2) DEFAULT NULL, expenses_policy TEXT DEFAULT NULL, deposit NUMERIC(11, 2) DEFAULT NULL, restrictions TEXT DEFAULT NULL, confidentiality_level TEXT DEFAULT NULL, failure_terms TEXT DEFAULT NULL, cancellation_terms TEXT DEFAULT NULL, payment_terms TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_69456e93640ed2c0 ON income_contract_details (income_id)');
        $this->addSql('CREATE TABLE income_prize_details (id SERIAL NOT NULL, income_id INT NOT NULL, case_ref VARCHAR(100) DEFAULT NULL, legal_basis TEXT DEFAULT NULL, prize_description TEXT DEFAULT NULL, estimated_value NUMERIC(11, 2) DEFAULT NULL, disposition VARCHAR(255) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, share_split TEXT DEFAULT NULL, award_trigger TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_62541f8c640ed2c0 ON income_prize_details (income_id)');
        $this->addSql('CREATE TABLE income_freight_details (id SERIAL NOT NULL, income_id INT NOT NULL, origin VARCHAR(255) DEFAULT NULL, destination VARCHAR(255) DEFAULT NULL, pickup_day INT DEFAULT NULL, pickup_year INT DEFAULT NULL, delivery_day INT DEFAULT NULL, delivery_year INT DEFAULT NULL, delivery_proof_ref VARCHAR(255) DEFAULT NULL, delivery_proof_day INT DEFAULT NULL, delivery_proof_year INT DEFAULT NULL, delivery_proof_received_by VARCHAR(255) DEFAULT NULL, cargo_description TEXT DEFAULT NULL, cargo_qty VARCHAR(100) DEFAULT NULL, declared_value NUMERIC(11, 2) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, liability_limit NUMERIC(11, 2) DEFAULT NULL, cancellation_terms TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_316d3daa640ed2c0 ON income_freight_details (income_id)');
        $this->addSql('CREATE TABLE income_passengers_details (id SERIAL NOT NULL, income_id INT NOT NULL, origin VARCHAR(255) DEFAULT NULL, destination VARCHAR(255) DEFAULT NULL, departure_day INT DEFAULT NULL, departure_year INT DEFAULT NULL, arrival_day INT DEFAULT NULL, arrival_year INT DEFAULT NULL, delivery_proof_ref VARCHAR(255) DEFAULT NULL, delivery_proof_day INT DEFAULT NULL, delivery_proof_year INT DEFAULT NULL, delivery_proof_received_by VARCHAR(255) DEFAULT NULL, class_or_berth VARCHAR(100) DEFAULT NULL, qty INT DEFAULT NULL, passenger_names TEXT DEFAULT NULL, passenger_contact VARCHAR(255) DEFAULT NULL, baggage_allowance VARCHAR(255) DEFAULT NULL, extra_baggage VARCHAR(255) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, refund_change_policy TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_e90c1830640ed2c0 ON income_passengers_details (income_id)');
        $this->addSql('CREATE TABLE income_interest_details (id SERIAL NOT NULL, income_id INT NOT NULL, account_ref VARCHAR(100) DEFAULT NULL, instrument VARCHAR(255) DEFAULT NULL, principal NUMERIC(11, 2) DEFAULT NULL, interest_rate NUMERIC(11, 2) DEFAULT NULL, start_day INT DEFAULT NULL, start_year INT DEFAULT NULL, end_day INT DEFAULT NULL, end_year INT DEFAULT NULL, calc_method VARCHAR(100) DEFAULT NULL, interest_earned NUMERIC(11, 2) DEFAULT NULL, net_paid NUMERIC(11, 2) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, dispute_window TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_6e6e6495640ed2c0 ON income_interest_details (income_id)');
        $this->addSql('CREATE TABLE income_trade_details (id SERIAL NOT NULL, income_id INT NOT NULL, purchase_cost_id INT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, transfer_point VARCHAR(255) DEFAULT NULL, transfer_condition VARCHAR(255) DEFAULT NULL, goods_description TEXT DEFAULT NULL, qty INT DEFAULT NULL, grade VARCHAR(100) DEFAULT NULL, batch_ids TEXT DEFAULT NULL, unit_price NUMERIC(11, 2) DEFAULT NULL, payment_terms NUMERIC(11, 2) DEFAULT NULL, delivery_method TEXT DEFAULT NULL, delivery_day INT DEFAULT NULL, delivery_year INT DEFAULT NULL, delivery_proof_ref VARCHAR(255) DEFAULT NULL, delivery_proof_day INT DEFAULT NULL, delivery_proof_year INT DEFAULT NULL, delivery_proof_received_by VARCHAR(255) DEFAULT NULL, as_is_or_warranty VARCHAR(255) DEFAULT NULL, warranty_text TEXT DEFAULT NULL, claim_window TEXT DEFAULT NULL, return_policy TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_214925046654f06 ON income_trade_details (purchase_cost_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_21492504640ed2c0 ON income_trade_details (income_id)');
        $this->addSql('CREATE TABLE income_subsidy_details (id SERIAL NOT NULL, income_id INT NOT NULL, program_ref VARCHAR(100) DEFAULT NULL, origin VARCHAR(255) DEFAULT NULL, destination VARCHAR(255) DEFAULT NULL, start_day INT DEFAULT NULL, start_year INT DEFAULT NULL, end_day INT DEFAULT NULL, end_year INT DEFAULT NULL, delivery_proof_ref VARCHAR(255) DEFAULT NULL, delivery_proof_day INT DEFAULT NULL, delivery_proof_year INT DEFAULT NULL, delivery_proof_received_by VARCHAR(255) DEFAULT NULL, service_level VARCHAR(255) DEFAULT NULL, subsidy_amount NUMERIC(11, 2) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, milestones TEXT DEFAULT NULL, reporting_requirements TEXT DEFAULT NULL, non_compliance_terms TEXT DEFAULT NULL, proof_requirements TEXT DEFAULT NULL, cancellation_terms TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_11c99b4640ed2c0 ON income_subsidy_details (income_id)');
        $this->addSql('CREATE TABLE income_insurance_details (id SERIAL NOT NULL, income_id INT NOT NULL, incident_ref VARCHAR(100) DEFAULT NULL, incident_day INT DEFAULT NULL, incident_year INT DEFAULT NULL, incident_location VARCHAR(255) DEFAULT NULL, incident_cause VARCHAR(255) DEFAULT NULL, loss_type VARCHAR(255) DEFAULT NULL, verified_loss NUMERIC(11, 2) DEFAULT NULL, deductible NUMERIC(11, 2) DEFAULT NULL, payment_terms TEXT DEFAULT NULL, acceptance_effect TEXT DEFAULT NULL, subrogation_terms TEXT DEFAULT NULL, coverage_notes TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_6ec0fe75640ed2c0 ON income_insurance_details (income_id)');
        $this->addSql('ALTER TABLE income_services_details ADD CONSTRAINT fk_88efd8ac640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_salvage_details ADD CONSTRAINT fk_747ba40640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_charter_details ADD CONSTRAINT fk_baac1642640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_mail_details ADD CONSTRAINT fk_4d0f8a05640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_contract_details ADD CONSTRAINT fk_69456e93640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_prize_details ADD CONSTRAINT fk_62541f8c640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_freight_details ADD CONSTRAINT fk_316d3daa640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_passengers_details ADD CONSTRAINT fk_e90c1830640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_interest_details ADD CONSTRAINT fk_6e6e6495640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_trade_details ADD CONSTRAINT fk_21492504640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_trade_details ADD CONSTRAINT fk_214925046654f06 FOREIGN KEY (purchase_cost_id) REFERENCES cost (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_subsidy_details ADD CONSTRAINT fk_11c99b4640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income_insurance_details ADD CONSTRAINT fk_6ec0fe75640ed2c0 FOREIGN KEY (income_id) REFERENCES income (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D06654F06');
        $this->addSql('DROP INDEX IDX_3FA862D06654F06');
        $this->addSql('ALTER TABLE income DROP purchase_cost_id');
        $this->addSql('ALTER TABLE income DROP details');
    }
}
