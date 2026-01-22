<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122142531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ship_details (id SERIAL NOT NULL, asset_id INT NOT NULL, jump_drive_rating INT DEFAULT NULL, hull_tons DOUBLE PRECISION DEFAULT NULL, extra_data JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7C0B3BE75DA1941 ON ship_details (asset_id)');
        $this->addSql('CREATE TABLE structure_details (id SERIAL NOT NULL, asset_id INT NOT NULL, extra_data JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A9847BBB5DA1941 ON structure_details (asset_id)');
        $this->addSql('CREATE TABLE team_details (id SERIAL NOT NULL, asset_id INT NOT NULL, extra_data JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D5F7ABD5DA1941 ON team_details (asset_id)');
        $this->addSql('ALTER TABLE ship_details ADD CONSTRAINT FK_7C0B3BE75DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE structure_details ADD CONSTRAINT FK_A9847BBB5DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_details ADD CONSTRAINT FK_8D5F7ABD5DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE ship_details DROP CONSTRAINT FK_7C0B3BE75DA1941');
        $this->addSql('ALTER TABLE structure_details DROP CONSTRAINT FK_A9847BBB5DA1941');
        $this->addSql('ALTER TABLE team_details DROP CONSTRAINT FK_8D5F7ABD5DA1941');
        $this->addSql('DROP TABLE ship_details');
        $this->addSql('DROP TABLE structure_details');
        $this->addSql('DROP TABLE team_details');
    }
}
