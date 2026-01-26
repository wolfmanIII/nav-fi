<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126183514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annual_budget (id SERIAL NOT NULL, financial_account_id INT NOT NULL, user_id INT DEFAULT NULL, code VARCHAR(36) NOT NULL, start_day INT NOT NULL, start_year INT NOT NULL, end_day INT NOT NULL, end_year INT NOT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_budget_user ON annual_budget (user_id)');
        $this->addSql('CREATE INDEX idx_budget_fin_acc ON annual_budget (financial_account_id)');
        $this->addSql('CREATE TABLE asset (id SERIAL NOT NULL, user_id INT DEFAULT NULL, campaign_id INT DEFAULT NULL, category VARCHAR(10) DEFAULT \'ship\' NOT NULL, code UUID NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, class VARCHAR(255) DEFAULT NULL, price NUMERIC(11, 2) DEFAULT NULL, asset_details JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_asset_user ON asset (user_id)');
        $this->addSql('CREATE INDEX idx_asset_campaign ON asset (campaign_id)');
        $this->addSql('CREATE TABLE asset_amendment (id SERIAL NOT NULL, asset_id INT NOT NULL, cost_id INT DEFAULT NULL, user_id INT DEFAULT NULL, code VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, effective_day INT DEFAULT NULL, effective_year INT DEFAULT NULL, patch_details JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_amendment_user ON asset_amendment (user_id)');
        $this->addSql('CREATE INDEX idx_amendment_asset ON asset_amendment (asset_id)');
        $this->addSql('CREATE INDEX idx_amendment_cost ON asset_amendment (cost_id)');
        $this->addSql('CREATE TABLE asset_role (id SERIAL NOT NULL, code VARCHAR(4) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(1000) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE broker_opportunity (id SERIAL NOT NULL, session_id INT NOT NULL, summary VARCHAR(255) NOT NULL, amount NUMERIC(15, 2) NOT NULL, data JSON NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6ADAA447613FECDF ON broker_opportunity (session_id)');
        $this->addSql('COMMENT ON COLUMN broker_opportunity.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE broker_session (id SERIAL NOT NULL, campaign_id INT NOT NULL, sector VARCHAR(255) NOT NULL, origin_hex VARCHAR(4) NOT NULL, jump_range INT NOT NULL, seed VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6726D62EF639F774 ON broker_session (campaign_id)');
        $this->addSql('COMMENT ON COLUMN broker_session.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN broker_session.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE campaign (id SERIAL NOT NULL, user_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, code UUID NOT NULL, description TEXT DEFAULT NULL, starting_year INT DEFAULT NULL, session_day INT DEFAULT NULL, session_year INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1F1512DD77153098 ON campaign (code)');
        $this->addSql('CREATE INDEX idx_campaign_user ON campaign (user_id)');
        $this->addSql('COMMENT ON COLUMN campaign.code IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE campaign_session_log (id SERIAL NOT NULL, campaign_id INT NOT NULL, user_id INT DEFAULT NULL, session_day INT NOT NULL, session_year INT NOT NULL, payload JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_campaign_session_campaign ON campaign_session_log (campaign_id)');
        $this->addSql('CREATE INDEX idx_campaign_session_user ON campaign_session_log (user_id)');
        $this->addSql('CREATE INDEX idx_campaign_session_created_at ON campaign_session_log (created_at)');
        $this->addSql('COMMENT ON COLUMN campaign_session_log.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE company (id SERIAL NOT NULL, user_id INT DEFAULT NULL, company_role_id INT NOT NULL, code VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, contact VARCHAR(255) DEFAULT NULL, sign_label VARCHAR(255) DEFAULT NULL, notes TEXT DEFAULT NULL, sector VARCHAR(255) DEFAULT NULL, subsector VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_company_user ON company (user_id)');
        $this->addSql('CREATE INDEX idx_company_role ON company (company_role_id)');
        $this->addSql('CREATE TABLE company_role (id SERIAL NOT NULL, code VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, short_description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE cost (id SERIAL NOT NULL, cost_category_id INT NOT NULL, user_id INT DEFAULT NULL, company_id INT DEFAULT NULL, financial_account_id INT NOT NULL, local_law_id INT DEFAULT NULL, code VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, amount NUMERIC(11, 2) NOT NULL, payment_day INT DEFAULT NULL, payment_year INT DEFAULT NULL, note TEXT DEFAULT NULL, detail_items JSON DEFAULT NULL, target_destination VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_182694FC979B1AD6 ON cost (company_id)');
        $this->addSql('CREATE INDEX IDX_182694FC23C96FD ON cost (local_law_id)');
        $this->addSql('CREATE INDEX idx_cost_user ON cost (user_id)');
        $this->addSql('CREATE INDEX idx_cost_fin_acc ON cost (financial_account_id)');
        $this->addSql('CREATE INDEX idx_cost_category ON cost (cost_category_id)');
        $this->addSql('CREATE INDEX idx_cost_payment_date ON cost (payment_day, payment_year)');
        $this->addSql('CREATE TABLE cost_category (id SERIAL NOT NULL, code VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE crew (id SERIAL NOT NULL, asset_id INT DEFAULT NULL, user_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, surname VARCHAR(100) NOT NULL, nickname VARCHAR(100) DEFAULT NULL, birth_year INT DEFAULT NULL, birth_day INT DEFAULT NULL, birth_world VARCHAR(100) DEFAULT NULL, code VARCHAR(36) NOT NULL, background TEXT DEFAULT NULL, status VARCHAR(30) DEFAULT NULL, active_day INT DEFAULT NULL, active_year INT DEFAULT NULL, on_leave_day INT DEFAULT NULL, on_leave_year INT DEFAULT NULL, retired_day INT DEFAULT NULL, retired_year INT DEFAULT NULL, mia_day INT DEFAULT NULL, mia_year INT DEFAULT NULL, deceased_day INT DEFAULT NULL, deceased_year INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_crew_user ON crew (user_id)');
        $this->addSql('CREATE INDEX idx_crew_asset ON crew (asset_id)');
        $this->addSql('CREATE TABLE crew_asset_role (crew_id INT NOT NULL, asset_role_id INT NOT NULL, PRIMARY KEY(crew_id, asset_role_id))');
        $this->addSql('CREATE INDEX IDX_775E54955FE259F6 ON crew_asset_role (crew_id)');
        $this->addSql('CREATE INDEX IDX_775E5495B5ED63FA ON crew_asset_role (asset_role_id)');
        $this->addSql('CREATE TABLE financial_account (id SERIAL NOT NULL, asset_id INT NOT NULL, user_id INT NOT NULL, campaign_id INT DEFAULT NULL, code UUID NOT NULL, credits NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FF514CE5DA1941 ON financial_account (asset_id)');
        $this->addSql('CREATE INDEX idx_fin_acc_user ON financial_account (user_id)');
        $this->addSql('CREATE INDEX idx_fin_acc_campaign ON financial_account (campaign_id)');
        $this->addSql('CREATE TABLE income (id SERIAL NOT NULL, company_id INT DEFAULT NULL, local_law_id INT DEFAULT NULL, purchase_cost_id INT DEFAULT NULL, income_category_id INT NOT NULL, financial_account_id INT NOT NULL, user_id INT DEFAULT NULL, code VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, patron_alias VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, signing_day INT DEFAULT NULL, signing_year INT DEFAULT NULL, payment_day INT DEFAULT NULL, payment_year INT DEFAULT NULL, cancel_day INT DEFAULT NULL, cancel_year INT DEFAULT NULL, expiration_day INT DEFAULT NULL, expiration_year INT DEFAULT NULL, amount NUMERIC(11, 2) NOT NULL, note TEXT DEFAULT NULL, details JSON DEFAULT NULL, signing_location VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3FA862D0979B1AD6 ON income (company_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D023C96FD ON income (local_law_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D06654F06 ON income (purchase_cost_id)');
        $this->addSql('CREATE INDEX idx_income_user ON income (user_id)');
        $this->addSql('CREATE INDEX idx_income_fin_acc ON income (financial_account_id)');
        $this->addSql('CREATE INDEX idx_income_category ON income (income_category_id)');
        $this->addSql('CREATE TABLE income_category (id SERIAL NOT NULL, code VARCHAR(10) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE insurance (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, annual_cost NUMERIC(11, 2) NOT NULL, loss_refund NUMERIC(5, 2) DEFAULT NULL, coverage JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE interest_rate (id SERIAL NOT NULL, duration INT NOT NULL, price_multiplier NUMERIC(11, 2) NOT NULL, price_divider INT NOT NULL, annual_interest_rate NUMERIC(11, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE ledger_transaction (id SERIAL NOT NULL, financial_account_id INT NOT NULL, amount NUMERIC(15, 2) NOT NULL, description VARCHAR(255) NOT NULL, session_day INT NOT NULL, session_year INT NOT NULL, related_entity_type VARCHAR(255) DEFAULT NULL, related_entity_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) DEFAULT \'Pending\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_transaction_fin_acc ON ledger_transaction (financial_account_id)');
        $this->addSql('CREATE INDEX idx_transaction_sync ON ledger_transaction (financial_account_id, status, session_year, session_day)');
        $this->addSql('CREATE INDEX idx_transaction_chronology ON ledger_transaction (financial_account_id, session_year, session_day, created_at)');
        $this->addSql('COMMENT ON COLUMN ledger_transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE local_law (id SERIAL NOT NULL, code VARCHAR(50) NOT NULL, short_description TEXT DEFAULT NULL, description TEXT NOT NULL, disclaimer TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mortgage (id SERIAL NOT NULL, asset_id INT NOT NULL, financial_account_id INT NOT NULL, interest_rate_id INT NOT NULL, insurance_id INT DEFAULT NULL, user_id INT DEFAULT NULL, company_id INT DEFAULT NULL, local_law_id INT DEFAULT NULL, code VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, start_day INT DEFAULT NULL, start_year INT DEFAULT NULL, asset_shares INT DEFAULT NULL, advance_payment NUMERIC(11, 2) DEFAULT NULL, discount NUMERIC(11, 2) DEFAULT NULL, signing_day INT DEFAULT NULL, signing_year INT DEFAULT NULL, signing_location VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E10ABAD05DA1941 ON mortgage (asset_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD01E09AF93 ON mortgage (financial_account_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0B3E3E851 ON mortgage (interest_rate_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0D1E63CD1 ON mortgage (insurance_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD0979B1AD6 ON mortgage (company_id)');
        $this->addSql('CREATE INDEX IDX_E10ABAD023C96FD ON mortgage (local_law_id)');
        $this->addSql('CREATE INDEX idx_mortgage_user ON mortgage (user_id)');
        $this->addSql('CREATE INDEX idx_mortgage_asset ON mortgage (asset_id)');
        $this->addSql('CREATE TABLE mortgage_installment (id SERIAL NOT NULL, mortgage_id INT NOT NULL, user_id INT DEFAULT NULL, code VARCHAR(36) NOT NULL, payment_day INT NOT NULL, payment_year INT NOT NULL, payment NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CE3D2EB915375FCD ON mortgage_installment (mortgage_id)');
        $this->addSql('CREATE INDEX IDX_CE3D2EB9A76ED395 ON mortgage_installment (user_id)');
        $this->addSql('CREATE TABLE route (id SERIAL NOT NULL, campaign_id INT DEFAULT NULL, asset_id INT NOT NULL, code UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, planned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, notes TEXT DEFAULT NULL, start_hex VARCHAR(4) DEFAULT NULL, dest_hex VARCHAR(4) DEFAULT NULL, start_day INT DEFAULT NULL, start_year INT DEFAULT NULL, dest_day INT DEFAULT NULL, dest_year INT DEFAULT NULL, jump_rating INT DEFAULT NULL, fuel_estimate NUMERIC(10, 2) DEFAULT NULL, payload JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_route_asset ON route (asset_id)');
        $this->addSql('CREATE INDEX idx_route_campaign ON route (campaign_id)');
        $this->addSql('COMMENT ON COLUMN route.planned_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE route_waypoint (id SERIAL NOT NULL, route_id INT NOT NULL, position INT NOT NULL, hex VARCHAR(4) NOT NULL, sector VARCHAR(64) DEFAULT NULL, world VARCHAR(255) DEFAULT NULL, uwp VARCHAR(255) DEFAULT NULL, jump_distance INT DEFAULT NULL, notes TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_route_waypoint_route ON route_waypoint (route_id)');
        $this->addSql('CREATE TABLE salary (id SERIAL NOT NULL, crew_id INT NOT NULL, amount NUMERIC(15, 2) NOT NULL, first_payment_day INT NOT NULL, first_payment_year INT NOT NULL, status VARCHAR(20) DEFAULT \'Active\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9413BB715FE259F6 ON salary (crew_id)');
        $this->addSql('CREATE TABLE salary_payment (id SERIAL NOT NULL, salary_id INT NOT NULL, payment_day INT NOT NULL, payment_year INT NOT NULL, amount NUMERIC(15, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FA2C1EC1B0FDF16E ON salary_payment (salary_id)');
        $this->addSql('CREATE TABLE transaction_archive (id SERIAL NOT NULL, asset_id INT NOT NULL, amount NUMERIC(15, 2) NOT NULL, description VARCHAR(255) NOT NULL, session_day INT NOT NULL, session_year INT NOT NULL, related_entity_type VARCHAR(255) DEFAULT NULL, related_entity_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, archived_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, original_transaction_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_archive_asset ON transaction_archive (asset_id)');
        $this->addSql('CREATE INDEX idx_archive_year ON transaction_archive (asset_id, session_year)');
        $this->addSql('COMMENT ON COLUMN transaction_archive.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN transaction_archive.archived_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, google_authenticator_secret VARCHAR(255) DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE annual_budget ADD CONSTRAINT FK_D441E6381E09AF93 FOREIGN KEY (financial_account_id) REFERENCES financial_account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE annual_budget ADD CONSTRAINT FK_D441E638A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5CF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE asset_amendment ADD CONSTRAINT FK_A741D5E55DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE asset_amendment ADD CONSTRAINT FK_A741D5E51DBF857F FOREIGN KEY (cost_id) REFERENCES cost (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE asset_amendment ADD CONSTRAINT FK_A741D5E5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE broker_opportunity ADD CONSTRAINT FK_6ADAA447613FECDF FOREIGN KEY (session_id) REFERENCES broker_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE broker_session ADD CONSTRAINT FK_6726D62EF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DDA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_session_log ADD CONSTRAINT FK_2A2CCC05F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_session_log ADD CONSTRAINT FK_2A2CCC05A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094FF651387B FOREIGN KEY (company_role_id) REFERENCES company_role (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_182694FC31E99DA1 FOREIGN KEY (cost_category_id) REFERENCES cost_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_182694FCA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_182694FC979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_182694FC1E09AF93 FOREIGN KEY (financial_account_id) REFERENCES financial_account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_182694FC23C96FD FOREIGN KEY (local_law_id) REFERENCES local_law (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crew ADD CONSTRAINT FK_894940B25DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crew ADD CONSTRAINT FK_894940B2A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crew_asset_role ADD CONSTRAINT FK_775E54955FE259F6 FOREIGN KEY (crew_id) REFERENCES crew (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crew_asset_role ADD CONSTRAINT FK_775E5495B5ED63FA FOREIGN KEY (asset_role_id) REFERENCES asset_role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE financial_account ADD CONSTRAINT FK_2FF514CE5DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE financial_account ADD CONSTRAINT FK_2FF514CEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE financial_account ADD CONSTRAINT FK_2FF514CEF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D0979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D023C96FD FOREIGN KEY (local_law_id) REFERENCES local_law (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D06654F06 FOREIGN KEY (purchase_cost_id) REFERENCES cost (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D053F8702F FOREIGN KEY (income_category_id) REFERENCES income_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D01E09AF93 FOREIGN KEY (financial_account_id) REFERENCES financial_account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D0A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ledger_transaction ADD CONSTRAINT FK_AAA417A71E09AF93 FOREIGN KEY (financial_account_id) REFERENCES financial_account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_E10ABAD05DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_E10ABAD01E09AF93 FOREIGN KEY (financial_account_id) REFERENCES financial_account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_E10ABAD0B3E3E851 FOREIGN KEY (interest_rate_id) REFERENCES interest_rate (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_E10ABAD0D1E63CD1 FOREIGN KEY (insurance_id) REFERENCES insurance (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_E10ABAD0A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_E10ABAD0979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage ADD CONSTRAINT FK_E10ABAD023C96FD FOREIGN KEY (local_law_id) REFERENCES local_law (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage_installment ADD CONSTRAINT FK_CE3D2EB915375FCD FOREIGN KEY (mortgage_id) REFERENCES mortgage (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mortgage_installment ADD CONSTRAINT FK_CE3D2EB9A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE route ADD CONSTRAINT FK_2C42079F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE route ADD CONSTRAINT FK_2C420795DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE route_waypoint ADD CONSTRAINT FK_4DF010AA34ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB715FE259F6 FOREIGN KEY (crew_id) REFERENCES crew (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE salary_payment ADD CONSTRAINT FK_FA2C1EC1B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE annual_budget DROP CONSTRAINT FK_D441E6381E09AF93');
        $this->addSql('ALTER TABLE annual_budget DROP CONSTRAINT FK_D441E638A76ED395');
        $this->addSql('ALTER TABLE asset DROP CONSTRAINT FK_2AF5A5CA76ED395');
        $this->addSql('ALTER TABLE asset DROP CONSTRAINT FK_2AF5A5CF639F774');
        $this->addSql('ALTER TABLE asset_amendment DROP CONSTRAINT FK_A741D5E55DA1941');
        $this->addSql('ALTER TABLE asset_amendment DROP CONSTRAINT FK_A741D5E51DBF857F');
        $this->addSql('ALTER TABLE asset_amendment DROP CONSTRAINT FK_A741D5E5A76ED395');
        $this->addSql('ALTER TABLE broker_opportunity DROP CONSTRAINT FK_6ADAA447613FECDF');
        $this->addSql('ALTER TABLE broker_session DROP CONSTRAINT FK_6726D62EF639F774');
        $this->addSql('ALTER TABLE campaign DROP CONSTRAINT FK_1F1512DDA76ED395');
        $this->addSql('ALTER TABLE campaign_session_log DROP CONSTRAINT FK_2A2CCC05F639F774');
        $this->addSql('ALTER TABLE campaign_session_log DROP CONSTRAINT FK_2A2CCC05A76ED395');
        $this->addSql('ALTER TABLE company DROP CONSTRAINT FK_4FBF094FA76ED395');
        $this->addSql('ALTER TABLE company DROP CONSTRAINT FK_4FBF094FF651387B');
        $this->addSql('ALTER TABLE cost DROP CONSTRAINT FK_182694FC31E99DA1');
        $this->addSql('ALTER TABLE cost DROP CONSTRAINT FK_182694FCA76ED395');
        $this->addSql('ALTER TABLE cost DROP CONSTRAINT FK_182694FC979B1AD6');
        $this->addSql('ALTER TABLE cost DROP CONSTRAINT FK_182694FC1E09AF93');
        $this->addSql('ALTER TABLE cost DROP CONSTRAINT FK_182694FC23C96FD');
        $this->addSql('ALTER TABLE crew DROP CONSTRAINT FK_894940B25DA1941');
        $this->addSql('ALTER TABLE crew DROP CONSTRAINT FK_894940B2A76ED395');
        $this->addSql('ALTER TABLE crew_asset_role DROP CONSTRAINT FK_775E54955FE259F6');
        $this->addSql('ALTER TABLE crew_asset_role DROP CONSTRAINT FK_775E5495B5ED63FA');
        $this->addSql('ALTER TABLE financial_account DROP CONSTRAINT FK_2FF514CE5DA1941');
        $this->addSql('ALTER TABLE financial_account DROP CONSTRAINT FK_2FF514CEA76ED395');
        $this->addSql('ALTER TABLE financial_account DROP CONSTRAINT FK_2FF514CEF639F774');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D0979B1AD6');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D023C96FD');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D06654F06');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D053F8702F');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D01E09AF93');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D0A76ED395');
        $this->addSql('ALTER TABLE ledger_transaction DROP CONSTRAINT FK_AAA417A71E09AF93');
        $this->addSql('ALTER TABLE mortgage DROP CONSTRAINT FK_E10ABAD05DA1941');
        $this->addSql('ALTER TABLE mortgage DROP CONSTRAINT FK_E10ABAD01E09AF93');
        $this->addSql('ALTER TABLE mortgage DROP CONSTRAINT FK_E10ABAD0B3E3E851');
        $this->addSql('ALTER TABLE mortgage DROP CONSTRAINT FK_E10ABAD0D1E63CD1');
        $this->addSql('ALTER TABLE mortgage DROP CONSTRAINT FK_E10ABAD0A76ED395');
        $this->addSql('ALTER TABLE mortgage DROP CONSTRAINT FK_E10ABAD0979B1AD6');
        $this->addSql('ALTER TABLE mortgage DROP CONSTRAINT FK_E10ABAD023C96FD');
        $this->addSql('ALTER TABLE mortgage_installment DROP CONSTRAINT FK_CE3D2EB915375FCD');
        $this->addSql('ALTER TABLE mortgage_installment DROP CONSTRAINT FK_CE3D2EB9A76ED395');
        $this->addSql('ALTER TABLE route DROP CONSTRAINT FK_2C42079F639F774');
        $this->addSql('ALTER TABLE route DROP CONSTRAINT FK_2C420795DA1941');
        $this->addSql('ALTER TABLE route_waypoint DROP CONSTRAINT FK_4DF010AA34ECB4E6');
        $this->addSql('ALTER TABLE salary DROP CONSTRAINT FK_9413BB715FE259F6');
        $this->addSql('ALTER TABLE salary_payment DROP CONSTRAINT FK_FA2C1EC1B0FDF16E');
        $this->addSql('DROP TABLE annual_budget');
        $this->addSql('DROP TABLE asset');
        $this->addSql('DROP TABLE asset_amendment');
        $this->addSql('DROP TABLE asset_role');
        $this->addSql('DROP TABLE broker_opportunity');
        $this->addSql('DROP TABLE broker_session');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE campaign_session_log');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE company_role');
        $this->addSql('DROP TABLE cost');
        $this->addSql('DROP TABLE cost_category');
        $this->addSql('DROP TABLE crew');
        $this->addSql('DROP TABLE crew_asset_role');
        $this->addSql('DROP TABLE financial_account');
        $this->addSql('DROP TABLE income');
        $this->addSql('DROP TABLE income_category');
        $this->addSql('DROP TABLE insurance');
        $this->addSql('DROP TABLE interest_rate');
        $this->addSql('DROP TABLE ledger_transaction');
        $this->addSql('DROP TABLE local_law');
        $this->addSql('DROP TABLE mortgage');
        $this->addSql('DROP TABLE mortgage_installment');
        $this->addSql('DROP TABLE route');
        $this->addSql('DROP TABLE route_waypoint');
        $this->addSql('DROP TABLE salary');
        $this->addSql('DROP TABLE salary_payment');
        $this->addSql('DROP TABLE transaction_archive');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
