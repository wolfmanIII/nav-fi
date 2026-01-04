<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260111100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge tabella campaign e relazione con ship';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE campaign (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code BLOB NOT NULL --(DC2Type:uuid), title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, starting_year INTEGER DEFAULT NULL, session_day INTEGER DEFAULT NULL, session_year INTEGER DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F639F77477153098 ON campaign (code)');
        $this->addSql('ALTER TABLE ship ADD COLUMN campaign_id INTEGER DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_EF4E8F97F639F774 ON ship (campaign_id)');
        $this->addSql('PRAGMA foreign_keys = ON');
        $this->addSql('ALTER TABLE ship ADD CONSTRAINT FK_EF4E8F97F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ship DROP FOREIGN KEY FK_EF4E8F97F639F774');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP INDEX IDX_EF4E8F97F639F774 ON ship');
        $this->addSql('ALTER TABLE ship DROP campaign_id');
    }
}
