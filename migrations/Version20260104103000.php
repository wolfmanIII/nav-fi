<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260104103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ship_details JSON field to ship';
    }

    public function up(Schema $schema): void
    {
        // SQLite supports ADD COLUMN directly
        $this->addSql('ALTER TABLE ship ADD COLUMN ship_details CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Cannot easily drop column in SQLite without table recreation; leave no-op
        $this->addSql('-- no down migration for ship_details (SQLite)');
    }
}
