<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107154704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ship AS SELECT id, code, name, type, class, price FROM ship');
        $this->addSql('DROP TABLE ship');
        $this->addSql('CREATE TABLE ship (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code CHAR(36) NOT NULL --(DC2Type:guid)
        , name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, price NUMERIC(11, 2) NOT NULL)');
        $this->addSql('INSERT INTO ship (id, code, name, type, class, price) SELECT id, code, name, type, class, price FROM __temp__ship');
        $this->addSql('DROP TABLE __temp__ship');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ship AS SELECT id, code, name, type, class, price FROM ship');
        $this->addSql('DROP TABLE ship');
        $this->addSql('CREATE TABLE ship (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code CHAR(36) NOT NULL --(DC2Type:guid)
        , name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL)');
        $this->addSql('INSERT INTO ship (id, code, name, type, class, price) SELECT id, code, name, type, class, price FROM __temp__ship');
        $this->addSql('DROP TABLE __temp__ship');
    }
}
