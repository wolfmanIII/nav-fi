<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aggiunge colonna detail_items (JSON) a cost';
    }

    public function up(Schema $schema): void
    {
        // Colonna JSON per elementi di dettaglio dei costi
        $this->addSql("ALTER TABLE cost ADD COLUMN detail_items CLOB DEFAULT NULL --(DC2Type:json)");
    }

    public function down(Schema $schema): void
    {
        // SQLite non supporta DROP COLUMN; lasciamo vuoto per evitare perdita dati.
    }
}
