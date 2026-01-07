<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260111113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge short_description a company_role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company_role ADD COLUMN short_description VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // SQLite non supporta DROP COLUMN; lasciamo vuoto per evitare perdita dati.
    }
}
