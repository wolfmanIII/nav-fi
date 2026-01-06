<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge campo background (TEXT, nullable) su crew';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crew ADD COLUMN background CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // SQLite non supporta DROP COLUMN; nessuna azione di rollback.
    }
}
