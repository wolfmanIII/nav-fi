<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121214258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE broker_opportunity (id SERIAL NOT NULL, session_id INT NOT NULL, summary VARCHAR(255) NOT NULL, amount NUMERIC(15, 2) NOT NULL, data JSON NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6ADAA447613FECDF ON broker_opportunity (session_id)');
        $this->addSql('COMMENT ON COLUMN broker_opportunity.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE broker_session (id SERIAL NOT NULL, campaign_id INT NOT NULL, sector VARCHAR(255) NOT NULL, origin_hex VARCHAR(4) NOT NULL, jump_range INT NOT NULL, seed VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6726D62EF639F774 ON broker_session (campaign_id)');
        $this->addSql('COMMENT ON COLUMN broker_session.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN broker_session.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE broker_opportunity ADD CONSTRAINT FK_6ADAA447613FECDF FOREIGN KEY (session_id) REFERENCES broker_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE broker_session ADD CONSTRAINT FK_6726D62EF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE broker_opportunity DROP CONSTRAINT FK_6ADAA447613FECDF');
        $this->addSql('ALTER TABLE broker_session DROP CONSTRAINT FK_6726D62EF639F774');
        $this->addSql('DROP TABLE broker_opportunity');
        $this->addSql('DROP TABLE broker_session');
    }
}
