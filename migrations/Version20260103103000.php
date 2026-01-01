<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260103103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge sessionDay/sessionYear su Ship per il calendario per nave';
    }

    public function up(Schema $schema): void
    {
        // SQLite non supporta ADD COLUMN multiplo; due istruzioni separate restano compatibili anche con altri RDBMS.
        $this->addSql('ALTER TABLE ship ADD session_day INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ship ADD session_year INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ship DROP session_day');
        $this->addSql('ALTER TABLE ship DROP session_year');
    }
}
