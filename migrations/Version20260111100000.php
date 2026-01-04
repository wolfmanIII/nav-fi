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
        $this->addSql('CREATE TABLE campaign (id INT AUTO_INCREMENT NOT NULL, code UUID NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, starting_year INT DEFAULT NULL, session_day INT DEFAULT NULL, session_year INT DEFAULT NULL, UNIQUE INDEX UNIQ_F639F77477153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ship ADD campaign_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ship ADD CONSTRAINT FK_EF4E8F97F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
        $this->addSql('CREATE INDEX IDX_EF4E8F97F639F774 ON ship (campaign_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ship DROP FOREIGN KEY FK_EF4E8F97F639F774');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP INDEX IDX_EF4E8F97F639F774 ON ship');
        $this->addSql('ALTER TABLE ship DROP campaign_id');
    }
}
